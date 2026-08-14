<?php

declare(strict_types=1);

namespace App\Evaluation;

use App\Repository\EvaluationRepository;

final class EvaluationService
{
    public function __construct(private EvaluationRepository $repository, private LinkEvaluator $evaluator) {}

    /** @return array{measurement: array<string, mixed>, results: array<int, array<string, mixed>>} */
    public function evaluateAll(int $fieldMeasurementId): array
    {
        $measurement = $this->repository->fieldMeasurement($fieldMeasurementId);
        $devices = $this->repository->calibratedDevices();
        if ($devices === []) {
            throw new \RuntimeException('Es ist noch kein kalibrierter Gerätetyp vorhanden.');
        }

        $results = [];
        foreach ($devices as $device) {
            $result = $this->evaluator->evaluate(
                (float) $measurement['rssi_dbm'], (float) $measurement['snr_db'],
                (float) $measurement['tx_power_dbm'], (int) $measurement['spreading_factor'],
                (float) $device['tx_power_dbm'], (float) $device['median_rssi_delta_db'],
                (float) $device['rssi_spread_db'], (int) $device['sample_count']
            );
            $this->repository->store($fieldMeasurementId, $device, $result);
            $results[] = array_merge($device, $result);
        }
        usort($results, static fn (array $a, array $b): int => $b['margin_db'] <=> $a['margin_db']);
        return ['measurement' => $measurement, 'results' => $results];
    }
}
