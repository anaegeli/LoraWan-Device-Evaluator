<?php

declare(strict_types=1);

namespace App\Auth;

use App\Config;

final class Auth
{
    public static function enforce(Config $config): void
    {
        $driver = strtolower((string) $config->get('AUTH_DRIVER', 'none'));
        if ($driver === 'none') {
            if ($config->get('APP_ENV', 'production') === 'production'
                && $config->get('AUTH_ALLOW_UNAUTHENTICATED', 'false') !== 'true') {
                throw new \RuntimeException('Authentifizierung ist für Produktion noch nicht konfiguriert.');
            }
            $_SESSION['user'] ??= ['display_name' => 'Lokaler Administrator', 'role' => 'admin', 'email' => null];
            return;
        }

        if (!in_array($driver, ['oidc', 'saml'], true)) {
            throw new \RuntimeException('Unbekannter AUTH_DRIVER. Erlaubt sind none, oidc oder saml.');
        }
        if (!isset($_SESSION['user'])) {
            header('Location: auth.php?action=login');
            exit;
        }
    }

    public static function requireRole(string $minimum): void
    {
        $levels = ['viewer' => 10, 'editor' => 20, 'admin' => 30];
        $role = $_SESSION['user']['role'] ?? 'viewer';
        if (($levels[$role] ?? 0) < ($levels[$minimum] ?? 999)) {
            throw new \RuntimeException('Für diese Aktion fehlen die erforderlichen Rechte.');
        }
    }
}
