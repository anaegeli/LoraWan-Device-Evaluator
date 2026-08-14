<?php

declare(strict_types=1);

namespace App\Evaluation;

final class LinkEvaluator
{
    private const REQUIRED_SNR_BY_SF = [
        7 => -7.5,
        8 => -10.0,
        9 => -12.5,
        10 => -15.0,
        11 => -17.5,
        12 => -20.0,
    ];

    /** @return array{estimated_rssi: float, estimated_snr: float, margin_db: float, uncertainty_db: float, verdict: string, explanation: string} */
    public function evaluate(
        float $fieldRssi,
        float $fieldSnr,
        float $fieldTxPower,
        int $spreadingFactor,
        float $deviceTxPower,
        float $medianRssiResidual,
        float $rssiSpread,
        int $sampleCount
    ): array {
        if (!isset(self::REQUIRED_SNR_BY_SF[$spreadingFactor])) {
            throw new \InvalidArgumentException('Spreading Factor muss zwischen 7 und 12 liegen.');
        }

        $estimatedRssi = $fieldRssi + ($deviceTxPower - $fieldTxPower) + $medianRssiResidual;
        $noiseFloor = $fieldRssi - $fieldSnr;
        $estimatedSnr = $estimatedRssi - $noiseFloor;
        $uncertainty = max(1.5, 1.4826 * $rssiSpread);
        if ($sampleCount < 5) {
            $uncertainty += 1.5;
        }

        $rawMargin = $estimatedSnr - self::REQUIRED_SNR_BY_SF[$spreadingFactor];
        $conservativeMargin = $rawMargin - $uncertainty;
        $verdict = match (true) {
            $conservativeMargin >= 6.0 => 'suitable',
            $conservativeMargin >= 0.0 => 'marginal',
            default => 'unsuitable',
        };
        $labels = [
            'suitable' => 'Geeignet: auch nach Sicherheitsabzug bleibt mindestens 6 dB Reserve.',
            'marginal' => 'Grenzwertig: rechnerisch möglich, aber ohne belastbare Reserve.',
            'unsuitable' => 'Ungeeignet: die konservativ geschätzte SNR-Reserve ist negativ.',
        ];

        return [
            'estimated_rssi' => round($estimatedRssi, 2),
            'estimated_snr' => round($estimatedSnr, 2),
            'margin_db' => round($conservativeMargin, 2),
            'uncertainty_db' => round($uncertainty, 2),
            'verdict' => $verdict,
            'explanation' => $labels[$verdict],
        ];
    }
}
