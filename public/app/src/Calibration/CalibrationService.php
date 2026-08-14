<?php

declare(strict_types=1);

namespace App\Calibration;

use App\Repository\CalibrationRepository;
use App\Support\Statistics;

final class CalibrationService
{
    public function __construct(private CalibrationRepository $repository)
    {
    }

    /** @return array<string, float|int> */
    public function calculate(int $deviceTypeId): array
    {
        $deviceType = $this->repository->deviceType($deviceTypeId);
        $pairs = $this->repository->completePairs($deviceTypeId);
        $minimum = (int) $deviceType['minimum_calibration_pairs'];

        if (count($pairs) < $minimum) {
            throw new \RuntimeException(sprintf(
                'Für %s %s sind mindestens %d vollständige Messpaare erforderlich; vorhanden: %d.',
                $deviceType['manufacturer'],
                $deviceType['model'],
                $minimum,
                count($pairs)
            ));
        }

        $rssiResiduals = [];
        $snrDeltas = [];

        foreach ($pairs as $pair) {
            $txDifference = (float) $pair['device_tx'] - (float) $pair['tester_tx'];
            $rssiResiduals[] = (float) $pair['device_rssi']
                - (float) $pair['tester_rssi']
                - $txDifference;
            $snrDeltas[] = (float) $pair['device_snr'] - (float) $pair['tester_snr'];
        }

        $result = [
            'sample_count' => count($pairs),
            'median_rssi_delta_db' => round(Statistics::median($rssiResiduals), 2),
            'median_snr_delta_db' => round(Statistics::median($snrDeltas), 2),
            'rssi_spread_db' => round(Statistics::medianAbsoluteDeviation($rssiResiduals), 2),
            'snr_spread_db' => round(Statistics::medianAbsoluteDeviation($snrDeltas), 2),
        ];

        $this->repository->store($deviceTypeId, $result);

        return $result;
    }
}
