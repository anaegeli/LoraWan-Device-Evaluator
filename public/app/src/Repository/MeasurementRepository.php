<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class MeasurementRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function latest(int $limit = 50): array
    {
        $statement = $this->database->prepare(
            'SELECT m.id, m.source, m.measured_at, m.rssi_dbm, m.snr_db, m.spreading_factor,
                    m.tx_power_dbm, m.pair_identifier, l.name AS location_name,
                    d.manufacturer, d.model
             FROM measurements m
             JOIN measurement_locations l ON l.id = m.location_id
             LEFT JOIN device_types d ON d.id = m.device_type_id
             ORDER BY m.measured_at DESC, m.id DESC LIMIT :result_limit'
        );
        $statement->bindValue('result_limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): void
    {
        $source = ($data['source'] ?? '') === 'device' ? 'device' : 'field_tester';
        $deviceTypeId = $source === 'device' ? (int) $data['device_type_id'] : null;

        $statement = $this->database->prepare(
            'INSERT INTO measurements
             (location_id, device_type_id, source, pair_identifier, gateway_identifier, measured_at,
              rssi_dbm, snr_db, spreading_factor, tx_power_dbm, frequency_hz, data_rate, notes, created_at)
             VALUES
             (:location_id, :device_type_id, :source, :pair_identifier, :gateway_identifier, :measured_at,
              :rssi, :snr, :sf, :tx_power, :frequency, :data_rate, :notes, NOW())'
        );
        $statement->execute([
            'location_id' => (int) $data['location_id'],
            'device_type_id' => $deviceTypeId,
            'source' => $source,
            'pair_identifier' => trim((string) ($data['pair_identifier'] ?? '')) ?: null,
            'gateway_identifier' => trim((string) ($data['gateway_identifier'] ?? '')) ?: null,
            'measured_at' => str_replace('T', ' ', (string) $data['measured_at']) . ':00',
            'rssi' => (float) $data['rssi_dbm'],
            'snr' => (float) $data['snr_db'],
            'sf' => max(7, min(12, (int) $data['spreading_factor'])),
            'tx_power' => (float) $data['tx_power_dbm'],
            'frequency' => $data['frequency_hz'] !== '' ? (int) $data['frequency_hz'] : null,
            'data_rate' => trim((string) ($data['data_rate'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ]);
    }
}
