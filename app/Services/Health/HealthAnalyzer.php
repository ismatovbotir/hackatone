<?php

namespace App\Services\Health;

use App\Enums\Sex;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class HealthAnalyzer
{
    public const YOUTH_UNTIL = 19;

    /** Age in years, as the prototype computes it (365.25-day years). */
    public static function age(CarbonInterface $birthDate, ?CarbonInterface $at = null): float
    {
        $at ??= Carbon::now();

        return ($at->getTimestamp() - $birthDate->copy()->startOfDay()->getTimestamp()) / (365.25 * 86400);
    }

    public function analyze(Sex $sex, CarbonInterface $birthDate, float $height, float $weight, ?CarbonInterface $at = null): BodyAnalysis
    {
        return $this->analyzeAge($sex, self::age($birthDate, $at), $height, $weight);
    }

    public function analyzeAge(Sex $sex, float $age, float $height, float $weight): BodyAnalysis
    {
        $youth = $age < self::YOUTH_UNTIL;
        $median = GrowthReference::heightMedian($sex, $age);
        $sd = GrowthReference::heightSd($sex, $age);
        $z = ($height - $median) / $sd;
        $pct = (int) min(99, max(1, round(GrowthReference::cdf($z) * 100)));

        [$hs, $hc] = match (true) {
            $z < -2 => ['low2', 'bad'],
            $z < -1 => ['low1', 'warn'],
            $z <= 1 => ['typ', 'good'],
            $z <= 2 => ['high1', 'warn'],
            default => ['high2', 'bad'],
        };

        $bmi = $weight / (($height / 100) ** 2);
        if ($youth) {
            // Approximates -2 SD .. +1 SD of BMI-for-age.
            $bm = GrowthReference::bmiMedian($sex, $age);
            $q = (min($age, 19) - 5) / 14;
            $bLow = $bm * (0.85 - 0.075 * $q);
            $bHigh = $bm * (1.09 + 0.11 * $q);
            $bObese = $bm * (1.20 + 0.20 * $q);
        } else {
            $bLow = 18.5;
            $bHigh = 24.9;
            $bObese = 30.0;
        }

        [$bs, $bc] = match (true) {
            $bmi < $bLow => ['under', 'warn'],
            $bmi <= $bHigh => ['ok', 'good'],
            $bmi < $bObese => ['over', 'warn'],
            default => ['obese', 'bad'],
        };

        $hm = ($height / 100) ** 2;
        $adultHeight = GrowthReference::heightMedian($sex, 19) + $z * GrowthReference::heightSd($sex, 19);
        $endAge = $sex->growthEndAge();
        $remaining = $youth && $age < $endAge ? max(0, $adultHeight - $height) : 0.0;

        return new BodyAnalysis(
            sex: $sex,
            age: $age,
            youth: $youth,
            height: $height,
            weight: $weight,
            median: $median,
            sd: $sd,
            z: $z,
            percentile: $pct,
            heightStatus: $hs,
            heightTone: $hc,
            bmi: $bmi,
            bmiLow: $bLow,
            bmiHigh: $bHigh,
            bmiStatus: $bs,
            bmiTone: $bc,
            kgLow: $bLow * $hm,
            kgHigh: $bHigh * $hm,
            adultHeight: $adultHeight,
            remaining: $remaining,
            endAge: $endAge,
            velocity: $youth ? GrowthReference::velocity($sex, $age) : 0.0,
        );
    }
}
