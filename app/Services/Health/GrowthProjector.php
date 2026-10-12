<?php

namespace App\Services\Health;

/**
 * Height achievement periods. The level pace factor (90/100/105%) is our assumption, not WHO.
 * No programme can promise centimetres; keep the on-screen disclaimer.
 */
final class GrowthProjector
{
    private const HORIZON_MONTHS = 240;

    /**
     * Months until each height gain target is reached, or null if not within the horizon.
     *
     * @param  list<int|float>  $targets  cm
     * @return list<?int>
     */
    public function simulate(BodyAnalysis $a, float $paceFactor, array $targets): array
    {
        $raw = 0.0;
        for ($i = 0; $i < self::HORIZON_MONTHS; $i++) {
            $raw += GrowthReference::velocity($a->sex, $a->age + $i / 12) / 12;
        }

        $out = array_fill(0, count($targets), null);
        if ($raw <= 0) {
            return $out;
        }

        $scale = min(1.5, max(0.6, $a->remaining / $raw));
        $cum = 0.0;
        for ($i = 1; $i <= self::HORIZON_MONTHS; $i++) {
            $cum += GrowthReference::velocity($a->sex, $a->age + ($i - 1) / 12) * $scale * $paceFactor / 12;
            foreach ($targets as $k => $target) {
                if ($out[$k] === null && $cum >= $target) {
                    $out[$k] = $i;
                }
            }
        }

        return $out;
    }

    /** @return list<int> */
    public function gainTargets(BodyAnalysis $a): array
    {
        $limit = $a->remaining * 0.85;
        $targets = array_values(array_filter([1, 2, 3, 5, 8, 10, 15], fn ($x) => $x <= $limit));
        if ($targets === [] && $a->remaining >= 1) {
            $targets = [1];
        }

        return array_slice($targets, 0, 5);
    }
}
