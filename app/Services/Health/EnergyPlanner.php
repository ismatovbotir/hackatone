<?php

namespace App\Services\Health;

use App\Enums\Level;
use App\Enums\Sex;

final class EnergyPlanner
{
    /** WHO/FAO Schofield basal metabolic rate, kcal/day. */
    public static function bmr(Sex $sex, float $age, float $weight): float
    {
        $m = $sex === Sex::Male;

        return match (true) {
            $age < 10 => $m ? 22.7 * $weight + 495 : 22.5 * $weight + 499,
            $age < 18 => $m ? 17.5 * $weight + 651 : 12.2 * $weight + 746,
            $age < 30 => $m ? 15.3 * $weight + 679 : 14.7 * $weight + 496,
            $age < 60 => $m ? 11.6 * $weight + 879 : 8.7 * $weight + 829,
            default => $m ? 13.5 * $weight + 487 : 10.5 * $weight + 596,
        };
    }

    public function plan(BodyAnalysis $a, Level $level): NutritionPlan
    {
        $w = $a->weight;
        $k = self::bmr($a->sex, $a->age, $w) * $level->activityFactor();

        if ($a->youth) {
            // Never cut calories for under-19s.
            if ($a->bmiStatus === 'under') {
                $k *= 1.05;
            }
        } else {
            $k *= match ($a->bmiStatus) {
                'under' => 1.10,
                'over' => 0.90,
                'obese' => 0.85,
                default => 1.0,
            };
        }

        $kcal = (int) (self::jsRound($k / 50) * 50);
        $protein = (int) self::jsRound($level->proteinPerKg($a->youth) * min($w, $a->kgHigh));
        $fat = (int) self::jsRound($kcal * 0.28 / 9);
        $carbs = (int) self::jsRound(($kcal - $protein * 4 - $fat * 9) / 4);

        $calcium = match (true) {
            $a->age < 7 => 600,
            $a->age < 10 => 700,
            $a->age < 19 => 1300,
            default => 1000,
        };
        $water = $a->youth
            ? ($a->age < 9 ? 1.6 : ($a->age < 14 ? 1.9 : 2.3))
            : self::jsRound($w * 0.033 * 10) / 10;

        return new NutritionPlan($kcal, $protein, $carbs, $fat, $calcium, $water);
    }

    /** Math.round from JavaScript: halves round up, also for negatives. */
    public static function jsRound(float $n): float
    {
        return floor($n + 0.5);
    }
}
