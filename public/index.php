<?php

declare(strict_types=1);

use App\Config;
use App\Database;
use App\Http\Csrf;
use App\Repository\DeviceTypeRepository;
use App\Repository\LocationRepository;
use App\Repository\MeasurementRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']),
    'samesite' => 'Lax',
]);
session_start();

$config = Config::fromEnvironment(dirname(__DIR__));
$database = Database::connect($config);
$devices = new DeviceTypeRepository($database);
$locations = new LocationRepository($database);
$measurements = new MeasurementRepository($database);
$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard', 'devices', 'locations', 'measurements'];
$page = in_array($page, $allowedPages, true) ? $page : 'dashboard';
$message = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::verify($_POST['_token'] ?? null);
        $action = $_POST['action'] ?? '';

        if ($action === 'create_device') {
            $devices->create($_POST);
            $page = 'devices';
        } elseif ($action === 'create_location') {
            $locations->create($_POST);
            $page = 'locations';
        } elseif ($action === 'create_measurement') {
            $measurements->create($_POST);
            $page = 'measurements';
        } else {
            throw new RuntimeException('Unbekannte Aktion.');
        }

        $_SESSION['flash'] = 'Eintrag wurde gespeichert.';
        header('Location: ?page=' . urlencode($page));
        exit;
    } catch (Throwable $exception) {
        $error = $config->get('APP_DEBUG', 'false') === 'true'
            ? $exception->getMessage()
            : 'Der Eintrag konnte nicht gespeichert werden. Bitte Eingaben prüfen.';
    }
}

$deviceRows = $devices->all();
$locationRows = $locations->all();
$measurementRows = $measurements->latest();
$h = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>LoRaWAN Device Evaluator</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<header>
    <h1>LoRaWAN Device Evaluator</h1>
    <nav>
        <?php foreach (['dashboard' => 'Übersicht', 'devices' => 'Gerätetypen', 'locations' => 'Messorte', 'measurements' => 'Messungen'] as $key => $label): ?>
            <a class="<?= $page === $key ? 'active' : '' ?>" href="?page=<?= $key ?>"><?= $label ?></a>
        <?php endforeach ?>
    </nav>
</header>
<main>
    <?php if ($message): ?><p class="notice"><?= $h($message) ?></p><?php endif ?>
    <?php if ($error): ?><p class="notice error"><?= $h($error) ?></p><?php endif ?>

    <?php if ($page === 'dashboard'): ?>
        <section class="grid">
            <article class="panel"><h2><?= count($deviceRows) ?></h2><p>Gerätetypen</p></article>
            <article class="panel"><h2><?= count($locationRows) ?></h2><p>Messorte</p></article>
            <article class="panel"><h2><?= count($measurementRows) ?></h2><p>Letzte Messungen</p></article>
        </section>
        <section class="panel"><h2>Nächster Schritt</h2><p>Gerätetypen und Messorte anlegen, danach gekoppelte Messungen mit derselben Paar-ID erfassen.</p></section>
    <?php elseif ($page === 'devices'): ?>
        <section class="panel">
            <h2>Gerätetyp erfassen</h2>
            <form method="post">
                <input type="hidden" name="_token" value="<?= Csrf::token() ?>">
                <input type="hidden" name="action" value="create_device">
                <div class="grid">
                    <label>Hersteller<input required name="manufacturer" maxlength="191"></label>
                    <label>Modell<input required name="model" maxlength="191"></label>
                    <label>TX Power (dBm)<input required type="number" step="0.1" name="tx_power_dbm"></label>
                    <label>Antennengewinn (dBi)<input type="number" step="0.1" name="antenna_gain_dbi"></label>
                    <label>Min. Messpaare<input required type="number" min="2" max="10" value="3" name="minimum_calibration_pairs"></label>
                </div>
                <label>Beschreibung<textarea name="description"></textarea></label>
                <div class="actions"><button>Speichern</button></div>
            </form>
        </section>
        <section class="panel"><h2>Gerätetypen</h2><table><thead><tr><th>Hersteller</th><th>Modell</th><th>TX</th><th>Antenne</th><th>Messpaare</th></tr></thead><tbody>
        <?php foreach ($deviceRows as $row): ?><tr><td><?= $h($row['manufacturer']) ?></td><td><?= $h($row['model']) ?></td><td><?= $h($row['tx_power_dbm']) ?> dBm</td><td><?= $h($row['antenna_gain_dbi'] ?? '–') ?></td><td><?= $h($row['minimum_calibration_pairs']) ?></td></tr><?php endforeach ?>
        </tbody></table></section>
    <?php elseif ($page === 'locations'): ?>
        <section class="panel"><h2>Messort erfassen</h2><form method="post">
            <input type="hidden" name="_token" value="<?= Csrf::token() ?>"><input type="hidden" name="action" value="create_location">
            <div class="grid"><label>Name<input required name="name" maxlength="191"></label><label>Umgebung<select name="environment"><option value="unknown">Unbekannt</option><option value="indoor">Innen</option><option value="outdoor">Aussen</option><option value="underground">Unterirdisch</option><option value="mixed">Gemischt</option></select></label><label>Breitengrad<input type="number" step="0.0000001" name="latitude"></label><label>Längengrad<input type="number" step="0.0000001" name="longitude"></label></div>
            <label>Notizen<textarea name="notes"></textarea></label><div class="actions"><button>Speichern</button></div>
        </form></section>
        <section class="panel"><h2>Messorte</h2><table><thead><tr><th>Name</th><th>Umgebung</th><th>Koordinaten</th></tr></thead><tbody><?php foreach ($locationRows as $row): ?><tr><td><?= $h($row['name']) ?></td><td><?= $h($row['environment']) ?></td><td><?= $h($row['latitude'] ?? '–') ?> / <?= $h($row['longitude'] ?? '–') ?></td></tr><?php endforeach ?></tbody></table></section>
    <?php elseif ($page === 'measurements'): ?>
        <section class="panel"><h2>Messung erfassen</h2><form method="post">
            <input type="hidden" name="_token" value="<?= Csrf::token() ?>"><input type="hidden" name="action" value="create_measurement">
            <div class="grid">
                <label>Messort<select required name="location_id"><option value="">Bitte wählen</option><?php foreach ($locationRows as $row): ?><option value="<?= $h($row['id']) ?>"><?= $h($row['name']) ?></option><?php endforeach ?></select></label>
                <label>Quelle<select name="source"><option value="field_tester">Fieldtester</option><option value="device">Gerät</option></select></label>
                <label>Gerätetyp<select name="device_type_id"><option value="">Nur bei Gerät</option><?php foreach ($deviceRows as $row): ?><option value="<?= $h($row['id']) ?>"><?= $h($row['manufacturer'] . ' ' . $row['model']) ?></option><?php endforeach ?></select></label>
                <label>Paar-ID<input name="pair_identifier" maxlength="36" placeholder="Gleiche ID für beide Messungen"></label>
                <label>Zeitpunkt<input required type="datetime-local" name="measured_at" value="<?= date('Y-m-d\TH:i') ?>"></label>
                <label>RSSI (dBm)<input required type="number" step="0.1" name="rssi_dbm"></label>
                <label>SNR (dB)<input required type="number" step="0.1" name="snr_db"></label>
                <label>SF<input required type="number" min="7" max="12" name="spreading_factor" value="7"></label>
                <label>TX Power (dBm)<input required type="number" step="0.1" name="tx_power_dbm"></label>
                <label>Gateway-ID<input name="gateway_identifier" maxlength="191"></label>
                <label>Frequenz (Hz)<input type="number" name="frequency_hz"></label>
                <label>Datenrate<input name="data_rate" maxlength="32"></label>
            </div><label>Notizen<textarea name="notes"></textarea></label><div class="actions"><button>Speichern</button></div>
        </form></section>
        <section class="panel"><h2>Letzte Messungen</h2><table><thead><tr><th>Zeit</th><th>Ort</th><th>Quelle</th><th>Gerät</th><th>RSSI</th><th>SNR</th><th>SF</th><th>Paar-ID</th></tr></thead><tbody><?php foreach ($measurementRows as $row): ?><tr><td><?= $h($row['measured_at']) ?></td><td><?= $h($row['location_name']) ?></td><td><?= $h($row['source']) ?></td><td><?= $h(trim(($row['manufacturer'] ?? '') . ' ' . ($row['model'] ?? '')) ?: '–') ?></td><td><?= $h($row['rssi_dbm']) ?></td><td><?= $h($row['snr_db']) ?></td><td><?= $h($row['spreading_factor']) ?></td><td><?= $h($row['pair_identifier'] ?? '–') ?></td></tr><?php endforeach ?></tbody></table></section>
    <?php endif ?>
</main>
</body>
</html>
