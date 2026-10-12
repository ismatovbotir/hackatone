<?php

namespace App\Services\Clients;

/**
 * Points a client earns for one logged day (at most 30). Only effort counts:
 * never height, weight or BMI, so children are not ranked by their bodies.
 */
final class Points
{
    public const TRAINING_DONE = 10;

    public const FULL_MINUTES_BONUS = 5;

    public const PER_MEAL = 2;

    public const MAX_MEALS = 5;

    public const WATER_GOAL = 5;

    public static function forDay(
        int $plannedMinutes,
        bool $workoutDone,
        ?int $workoutMinutes,
        int $mealsEaten,
        int $waterMl,
        int $waterGoalMl,
    ): int {
        $points = 0;

        if ($plannedMinutes > 0 && $workoutDone) {
            $points += self::TRAINING_DONE;
            if (($workoutMinutes ?? 0) >= $plannedMinutes) {
                $points += self::FULL_MINUTES_BONUS;
            }
        }

        $points += min($mealsEaten, self::MAX_MEALS) * self::PER_MEAL;

        if ($waterGoalMl > 0 && $waterMl >= $waterGoalMl) {
            $points += self::WATER_GOAL;
        }

        return $points;
    }
}
