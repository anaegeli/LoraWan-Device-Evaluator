<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class CalibrationRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return array<string, mixed> */
    public function deviceType(int $deviceTypeId): array
    {
        $statement = $this->database->prepare(
            'SELECT id, manufacturer, model, minimum_calibration_pairs
             FROM device_types WHERE id = :id AND active = 1'
        );
        $statement->execute(['id' => $deviceTypeId]);
        $deviceType = $statement->fetch();

        if (!$deviceType) {
            throw new \RuntimeException('Gerätetyp wurde nicht gefunden.');
        }

        return $deviceType;
    }

    /** @return array<int, array<string, mixed>> */
    public function completePairs(int $deviceTypeId): array
    {
        $statement = $this->database->prepare(
            "SELECT d.pair_identifier,
                    d.rssi_dbm AS device_rssi, d.snr_db AS device_snr, d.tx_power_dbm AS device_tx,
                    f.rssi_dbm AS tester_rssi, f.snr_db AS tester_snr, f.tx_power_dbm AS tester_tx
             FROM measurements d
             JOIN measurements f
               ON f.pair_identifier = d.pair_identifier
              AND f.location_id = d.location_id
              AND f.source = 'field_tester'
              AND f.id = (
                  SELECT MAX(f2.id) FROM measurements f2
                  WHERE f2.pair_identifier = d.pair_identifier
                    AND f2.location_id = d.location_id
                    AND f2.source = 'field_tester'
              )
             WHERE d.source = 'device'
               AND d.device_type_id = :device_type_id
               AND d.pair_identifier IS NOT NULL
               AND d.pair_identifier <> ''
               AND d.id = (
                  SELECT MAX(d2.id) FROM measurements d2
                  WHERE d2.pair_identifier = d.pair_identifier
                    AND d2.location_id = d.location_id
                    AND d2.source = 'device'
                    AND d2.device_type_id = d.device_type_id
               )
             ORDER BY d.pair_identifier"
        );
        $statement->execute(['device_type_id' => $deviceTypeId]);

        return $statement->fetchAll();
    }

    /** @param array<string, float|int> $result */
    public function store(int $deviceTypeId, array $result): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO device_calibrations
             (device_type_id, sample_count, median_rssi_delta_db, median_snr_delta_db,
              rssi_spread_db, snr_spread_db, calculated_at)
             VALUES (:device_type_id, :sample_count, :rssi_delta, :snr_delta, :rssi_spread, :snr_spread, NOW())'
        );
        $statement->execute([
            'device_type_id' => $deviceTypeId,
            'sample_count' => $result['sample_count'],
            'rssi_delta' => $result['median_rssi_delta_db'],
            'snr_delta' => $result['median_snr_delta_db'],
            'rssi_spread' => $result['rssi_spread_db'],
            'snr_spread' => $result['snr_spread_db'],
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function latestForAllDevices(): array
    {
        return $this->database->query(
            'SELECT c.*, d.manufacturer, d.model, d.minimum_calibration_pairs
             FROM device_calibrations c
             JOIN device_types d ON d.id = c.device_type_id
             JOIN (
                 SELECT device_type_id, MAX(id) AS latest_id
                 FROM device_calibrations GROUP BY device_type_id
             ) latest ON latest.latest_id = c.id
             ORDER BY d.manufacturer, d.model'
        )->fetchAll();
    }
}
