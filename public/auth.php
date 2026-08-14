<?php

declare(strict_types=1);

use App\Auth\IdentityService;
use App\Config;
use App\Database;
use App\Repository\UserRepository;
use Jumbojett\OpenIDConnectClient;
use OneLogin\Saml2\Auth as SamlAuth;

require __DIR__ . '/vendor/autoload.php';
session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']), 'samesite' => 'Lax']);
ini_set('session.use_strict_mode', '1');
session_start();

$config = Config::fromEnvironment(__DIR__);
set_exception_handler(static function (Throwable $exception) use ($config): void {
    error_log((string) $exception);
    http_response_code(500);
    echo '<!doctype html><html lang="de"><meta charset="utf-8"><title>Anmeldefehler</title><body><h1>Anmeldung fehlgeschlagen</h1><p>Authentifizierungskonfiguration und Serverprotokoll prüfen.</p></body></html>';
});
$identity = new IdentityService($config, new UserRepository(Database::connect($config)));
$action = $_GET['action'] ?? 'login';

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

$driver = strtolower($config->require('AUTH_DRIVER'));
if ($driver === 'oidc') {
    $oidc = new OpenIDConnectClient(
        $config->require('OIDC_ISSUER'),
        $config->require('OIDC_CLIENT_ID'),
        $config->require('OIDC_CLIENT_SECRET')
    );
    $oidc->setRedirectURL($config->require('OIDC_REDIRECT_URI'));
    $oidc->addScope(['openid', 'profile', 'email']);
    $oidc->authenticate();
    $subject = (string) $oidc->requestUserInfo('sub');
    $email = $oidc->requestUserInfo('email');
    $name = $oidc->requestUserInfo('name') ?: $email ?: $subject;
    $identity->login('oidc', $subject, is_string($email) ? $email : null, (string) $name);
} elseif ($driver === 'saml') {
    $acsUrl = $config->get('SAML_ACS_URL') ?: rtrim($config->require('APP_URL'), '/') . '/auth.php?action=acs';
    $settings = [
        'strict' => true,
        'debug' => $config->get('APP_DEBUG', 'false') === 'true',
        'sp' => ['entityId' => $config->require('SAML_SP_ENTITY_ID'), 'assertionConsumerService' => ['url' => $acsUrl]],
        'idp' => [
            'entityId' => $config->require('SAML_IDP_ENTITY_ID'),
            'singleSignOnService' => ['url' => $config->require('SAML_IDP_SSO_URL')],
            'x509cert' => str_replace('\\n', "\n", $config->require('SAML_IDP_X509_CERT')),
        ],
        'security' => ['wantAssertionsSigned' => true, 'wantMessagesSigned' => false, 'wantNameId' => true],
    ];
    $saml = new SamlAuth($settings);
    if ($action !== 'acs') {
        $saml->login();
        exit;
    }
    $saml->processResponse();
    if ($saml->getErrors() !== [] || !$saml->isAuthenticated()) {
        throw new RuntimeException('SAML-Anmeldung fehlgeschlagen: ' . implode(', ', $saml->getErrors()));
    }
    $attributes = $saml->getAttributes();
    $emailKey = $config->get('SAML_EMAIL_ATTRIBUTE', 'mail');
    $nameKey = $config->get('SAML_NAME_ATTRIBUTE', 'displayName');
    $email = $attributes[$emailKey][0] ?? null;
    $name = $attributes[$nameKey][0] ?? $email ?? $saml->getNameId();
    $identity->login('saml', (string) $saml->getNameId(), is_string($email) ? $email : null, (string) $name);
} else {
    throw new RuntimeException('AUTH_DRIVER muss oidc oder saml sein.');
}

header('Location: index.php');
