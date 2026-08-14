<?php

declare(strict_types=1);

require dirname(__DIR__) . '/public/vendor/autoload.php';

use App\Evaluation\LinkEvaluator;
use App\Support\Statistics;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$assert(Statistics::median([1.0, 9.0, 3.0]) === 3.0, 'Median für ungerade Anzahl ist falsch.');
$assert(Statistics::median([1.0, 3.0, 5.0, 7.0]) === 4.0, 'Median für gerade Anzahl ist falsch.');
$assert(Statistics::medianAbsoluteDeviation([1.0, 2.0, 3.0]) === 1.0, 'MAD ist falsch.');

$result = (new LinkEvaluator())->evaluate(-100.0, -5.0, 14.0, 7, 14.0, 0.0, 1.0, 5);
$assert($result['estimated_rssi'] === -100.0, 'RSSI-Prognose ohne Korrektur ist falsch.');
$assert($result['estimated_snr'] === -5.0, 'SNR-Prognose ohne Korrektur ist falsch.');
$assert(in_array($result['verdict'], ['suitable', 'marginal', 'unsuitable'], true), 'Bewertung ist ungültig.');

echo "Alle Tests erfolgreich.\n";
