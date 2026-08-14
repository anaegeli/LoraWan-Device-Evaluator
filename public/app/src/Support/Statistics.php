<?php

declare(strict_types=1);

namespace App\Support;

final class Statistics
{
    /** @param array<int, float> $values */
    public static function median(array $values): float
    {
        if ($values === []) {
            throw new \InvalidArgumentException('Für den Median sind Messwerte erforderlich.');
        }

        sort($values, SORT_NUMERIC);
        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? (float) $values[$middle]
            : ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }

    /** @param array<int, float> $values */
    public static function medianAbsoluteDeviation(array $values): float
    {
        $median = self::median($values);
        $deviations = array_map(
            static fn (float $value): float => abs($value - $median),
            $values
        );

        return self::median($deviations);
    }
}
