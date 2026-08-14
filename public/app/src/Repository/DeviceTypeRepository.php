<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class DeviceTypeRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->database->query(
            'SELECT id, manufacturer, model, tx_power_dbm, antenna_gain_dbi, minimum_calibration_pairs, active
             FROM device_types ORDER BY manufacturer, model'
        )->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO device_types
             (manufacturer, model, description, tx_power_dbm, antenna_gain_dbi, minimum_calibration_pairs, active, created_at, updated_at)
             VALUES (:manufacturer, :model, :description, :tx_power, :antenna_gain, :minimum_pairs, 1, NOW(), NOW())'
        );
        $statement->execute([
            'manufacturer' => trim((string) $data['manufacturer']),
            'model' => trim((string) $data['model']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'tx_power' => (float) $data['tx_power_dbm'],
            'antenna_gain' => $data['antenna_gain_dbi'] !== '' ? (float) $data['antenna_gain_dbi'] : null,
            'minimum_pairs' => max(2, min(10, (int) $data['minimum_calibration_pairs'])),
        ]);
    }
}
