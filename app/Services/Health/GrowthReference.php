<?php

namespace App\Services\Health;

use App\Enums\Sex;

/**
 * WHO 2007 growth reference for 5 to 19 years (height-for-age and BMI-for-age), with the LMS method
 * exactly as in WHO's "Computation of centiles and z-scores" guide. Data: Data/who2007.php.
 */
final class GrowthReference
{
    public const FIRST_MONTH = 61;

    public const LAST_MONTH = 228;

    private static ?array $tables = null;

    /** @return array{0: float, 1: float, 2: float} L, M, S at an age in years, interpolated between months */
    public static function lms(string $indicator, Sex $sex, float $age): array
    {
        $table = self::tables()[$indicator][$sex->value];
        $months = min(self::LAST_MONTH, max(self::FIRST_MONTH, $age * 12));
        $i = (int) floor($months);
        $j = min(self::LAST_MONTH, $i + 1);
        $t = $months - $i;

        return array_map(fn ($a, $b) => $a + ($b - $a) * $t, $table[$i], $table[$j]);
    }

    public static function heightMedian(Sex $sex, float $age): float
    {
        return self::lms('hfa', $sex, $age)[1];
    }

    /** Height-for-age has L = 1, so one SD is simply M x S. */
    public static function heightSd(Sex $sex, float $age): float
    {
        [, $m, $s] = self::lms('hfa', $sex, $age);

        return $m * $s;
    }

    public static function heightZ(Sex $sex, float $age, float $height): float
    {
        return ($height - self::heightMedian($sex, $age)) / self::heightSd($sex, $age);
    }

    public static function bmiMedian(Sex $sex, float $age): float
    {
        return self::lms('bmi', $sex, $age)[1];
    }

    /** BMI at a given z-score (the LMS centile formula). */
    public static function bmiAtZ(Sex $sex, float $age, float $z): float
    {
        [$l, $m, $s] = self::lms('bmi', $sex, $age);

        return $m * (1 + $l * $s * $z) ** (1 / $l);
    }

    /** BMI-for-age z-score, with WHO's fixed SD beyond +/-3. */
    public static function bmiZ(Sex $sex, float $age, float $bmi): float
    {
        [$l, $m, $s] = self::lms('bmi', $sex, $age);
        $z = (($bmi / $m) ** $l - 1) / ($s * $l);

        if ($z > 3) {
            $sd3 = self::bmiAtZ($sex, $age, 3);

            return 3 + ($bmi - $sd3) / ($sd3 - self::bmiAtZ($sex, $age, 2));
        }
        if ($z < -3) {
            $sd3 = self::bmiAtZ($sex, $age, -3);

            return -3 + ($bmi - $sd3) / (self::bmiAtZ($sex, $age, -2) - $sd3);
        }

        return $z;
    }

    /**
     * Typical growth speed in cm per year: the slope of the WHO median height curve around this age.
     * 0 from 19 years, where the reference ends.
     */
    public static function velocity(Sex $sex, float $age): float
    {
        if ($age >= 19) {
            return 0.0;
        }
        $from = max(self::FIRST_MONTH / 12, $age - 0.5);
        $to = min(self::LAST_MONTH / 12, $from + 1);

        return max(0.0, (self::heightMedian($sex, $to) - self::heightMedian($sex, $from)) / ($to - $from));
    }

    /** Standard normal CDF (Abramowitz and Stegun 26.2.17). */
    public static function cdf(float $z): float
    {
        $t = 1 / (1 + 0.2316419 * abs($z));
        $d = 0.3989423 * exp(-$z * $z / 2);
        $p = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));

        return $z > 0 ? 1 - $p : $p;
    }

    private static function tables(): array
    {
        return self::$tables ??= require __DIR__.'/Data/who2007.php';
    }
}
