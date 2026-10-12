<?php

namespace App\Services\Health;

use App\Enums\Sex;

/**
 * Approximation of the WHO 2007 growth reference, ages 5 to 19, and growth velocity (cm/yr) at ages 5 to 20.
 * Replace with the official WHO tables before any real use.
 */
final class GrowthReference
{
    private const TABLES = [
        'M' => [
            'h' => [109.2, 115.5, 121.7, 127.3, 132.6, 137.8, 143.1, 149.1, 156.0, 163.2, 169.0, 172.9, 175.2, 176.1, 176.5],
            'sd' => [4.7, 5.0, 5.3, 5.6, 5.9, 6.2, 6.7, 7.3, 7.9, 7.6, 7.0, 6.6, 6.4, 6.4, 6.4],
            'bmi' => [15.3, 15.3, 15.5, 15.8, 16.1, 16.5, 17.0, 17.5, 18.1, 18.8, 19.5, 20.0, 20.5, 20.9, 21.2],
            'vel' => [6.0, 5.8, 5.6, 5.5, 5.4, 5.5, 6.0, 7.0, 8.2, 7.2, 4.5, 2.5, 1.2, 0.5, 0.2, 0],
        ],
        'F' => [
            'h' => [109.4, 115.1, 121.0, 126.6, 132.5, 138.6, 144.8, 151.2, 156.7, 159.8, 161.2, 162.0, 162.5, 162.9, 163.1],
            'sd' => [4.6, 4.9, 5.2, 5.6, 6.0, 6.4, 6.6, 6.4, 6.0, 5.9, 5.8, 5.8, 5.8, 5.9, 5.9],
            'bmi' => [15.2, 15.2, 15.4, 15.8, 16.3, 16.9, 17.5, 18.2, 18.9, 19.5, 20.0, 20.4, 20.7, 20.9, 21.0],
            'vel' => [6.0, 5.8, 5.6, 5.7, 6.0, 6.8, 7.0, 5.2, 3.0, 1.8, 1.0, 0.5, 0.2, 0, 0, 0],
        ],
    ];

    public static function heightMedian(Sex $sex, float $age): float
    {
        return self::at(self::TABLES[$sex->value]['h'], $age);
    }

    public static function heightSd(Sex $sex, float $age): float
    {
        return self::at(self::TABLES[$sex->value]['sd'], $age);
    }

    public static function bmiMedian(Sex $sex, float $age): float
    {
        return self::at(self::TABLES[$sex->value]['bmi'], $age);
    }

    /** Typical growth velocity in cm per year; 0 from age 20. */
    public static function velocity(Sex $sex, float $age): float
    {
        if ($age >= 20) {
            return 0.0;
        }
        $arr = self::TABLES[$sex->value]['vel'];
        $a = max(5, $age);
        $i = min(14, (int) floor($a - 5));
        $t = $a - 5 - $i;

        return $arr[$i] + ($arr[$i + 1] - $arr[$i]) * $t;
    }

    /** Linear interpolation, clamped to ages 5..19. */
    private static function at(array $arr, float $age): float
    {
        $a = min(19, max(5, $age));
        $i = min(13, (int) floor($a - 5));
        $t = $a - 5 - $i;

        return $arr[$i] + ($arr[$i + 1] - $arr[$i]) * $t;
    }

    /** Standard normal CDF (Abramowitz and Stegun approximation, as in the prototype). */
    public static function cdf(float $z): float
    {
        $t = 1 / (1 + 0.2316419 * abs($z));
        $d = 0.3989423 * exp(-$z * $z / 2);
        $p = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));

        return $z > 0 ? 1 - $p : $p;
    }
}
