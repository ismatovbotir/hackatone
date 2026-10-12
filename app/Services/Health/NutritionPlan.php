<?php

namespace App\Services\Health;

final readonly class NutritionPlan
{
    public function __construct(
        public int $kcal,
        public int $protein,
        public int $carbs,
        public int $fat,
        public int $calcium,
        public float $water,
    ) {}
}
