<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class EvaluationRepository
{
    public function __construct(private PDO $database) {}

    /** @return array<int, array<string, mixed>> */
    public function fieldMeasurements(): array
    {
        return $this->database->query(
            "SELECT m.id, m.measured_at, m.rssi_dbm, m.snr_db, m.spreading_factor,
                    m.tx_power_dbm, m.gateway_identifier, l.name AS location_name
             FROM measurements m JOIN measurement_locations l ON l.id = m.location_id
             WHERE m.source = 'field_tester'
             ORDER BY m.measured_at DESC, m.id DESC LIMIT 200"
        )->fetchAll();
    }

    /** @return array<string, mixed> */
    public function fieldMeasurement(int $id): array
    {
        $statement = $this->database->prepare(
            "SELECT m.*, l.name AS location_name FROM measurements m
             JOIN measurement_locations l ON l.id = m.location_id
             WHERE m.id = :id AND m.source = 'field_tester'"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!$row) {
            throw new \RuntimeException('Fieldtester-Messung wurde nicht gefunden.');
        }
        return $row;
    }

    /** @return array<int, array<string, mixed>> */
    public function calibratedDevices(): array
    {
        return $this->database->query(
            'SELECT d.id AS device_type_id, d.manufacturer, d.model, d.tx_power_dbm,
                    c.id AS calibration_id, c.sample_count, c.median_rssi_delta_db,
                    c.rssi_spread_db, c.calculated_at
             FROM device_types d JOIN device_calibrations c ON c.device_type_id = d.id
             JOIN (SELECT device_type_id, MAX(id) AS latest_id FROM device_calibrations GROUP BY device_type_id) latest
               ON latest.latest_id = c.id
             WHERE d.active = 1 ORDER BY d.manufacturer, d.model'
        )->fetchAll();
    }

    /** @param array<string, mixed> $result */
    public function store(int $fieldMeasurementId, array $device, array $result): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO evaluations
             (field_measurement_id, device_type_id, calibration_id, estimated_rssi_dbm,
              estimated_snr_db, link_margin_db, verdict, explanation, created_at)
             VALUES (:measurement, :device, :calibration, :rssi, :snr, :margin, :verdict, :explanation, NOW())'
        );
        $statement->execute([
            'measurement' => $fieldMeasurementId,
            'device' => $device['device_type_id'],
            'calibration' => $device['calibration_id'],
            'rssi' => $result['estimated_rssi'],
            'snr' => $result['estimated_snr'],
            'margin' => $result['margin_db'],
            'verdict' => $result['verdict'],
            'explanation' => $result['explanation'],
        ]);
    }
}
