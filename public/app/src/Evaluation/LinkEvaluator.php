<?php

declare(strict_types=1);

namespace App\Evaluation;

final class LinkEvaluator
{
    /**
     * Applies calibration deltas to a field-tester measurement.
     *
     * @return array{estimated_rssi: float, estimated_snr: float, margin_db: float, verdict: string}
     */
    public function evaluate(
        float $fieldRssi,
        float $fieldSnr,
        float $medianRssiDelta,
        float $medianSnrDelta,
        float $requiredSnr,
        float $safetyMarginDb = 6.0
    ): array {
        $estimatedRssi = $fieldRssi + $medianRssiDelta;
        $estimatedSnr = $fieldSnr + $medianSnrDelta;
        $margin = $estimatedSnr - $requiredSnr;

        $verdict = match (true) {
            $margin >= $safetyMarginDb => 'suitable',
            $margin >= 0.0 => 'marginal',
            default => 'unsuitable',
        };

        return [
            'estimated_rssi' => round($estimatedRssi, 1),
            'estimated_snr' => round($estimatedSnr, 1),
            'margin_db' => round($margin, 1),
            'verdict' => $verdict,
        ];
    }
}
