<?php

namespace App\Services\Health;

use App\Enums\Level;
use App\Enums\Sex;

final readonly class BodyAnalysis
{
    /**
     * @param  string  $heightStatus  low2|low1|typ|high1|high2
     * @param  string  $heightTone  good|warn|bad
     * @param  string  $bmiStatus  under|ok|over|obese
     * @param  string  $bmiTone  good|warn|bad
     */
    public function __construct(
        public Sex $sex,
        public float $age,
        public bool $youth,
        public float $height,
        public float $weight,
        public float $median,
        public float $sd,
        public float $z,
        public int $percentile,
        public string $heightStatus,
        public string $heightTone,
        public float $bmi,
        public float $bmiLow,
        public float $bmiHigh,
        public string $bmiStatus,
        public string $bmiTone,
        public float $kgLow,
        public float $kgHigh,
        public float $adultHeight,
        public float $remaining,
        public int $endAge,
        public float $velocity,
    ) {}

    /** Still has at least 1 cm of projected growth left. */
    public function growable(): bool
    {
        return $this->remaining >= 1;
    }

    public function recommendedLevel(): Level
    {
        return $this->bmiStatus === 'ok' ? Level::Mid : Level::Easy;
    }
}
