<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Config;

$config = Config::fromEnvironment(dirname(__DIR__));

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'application' => 'LoRaWAN Device Evaluator',
    'status' => 'ok',
    'environment' => $config->get('APP_ENV', 'production'),
    'auth_driver' => $config->get('AUTH_DRIVER', 'none'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
