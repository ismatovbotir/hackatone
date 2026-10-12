<?php

namespace Tests\Unit\Health;

use App\Enums\Level;
use App\Enums\Sex;
use App\Services\Health\EnergyPlanner;
use App\Services\Health\HealthAnalyzer;
use App\Services\Health\TrainingPlanner;
use PHPUnit\Framework\TestCase;

class DomainRulesTest extends TestCase
{
    public function test_activity_factors_and_paces(): void
    {
        $this->assertSame([1.45, 1.65, 1.85], array_map(fn ($l) => $l->activityFactor(), Level::cases()));
        $this->assertSame([0.9, 1.0, 1.05], array_map(fn ($l) => $l->growthPace(), Level::cases()));
    }

    public function test_weekly_training_minutes(): void
    {
        $t = new TrainingPlanner;
        $this->assertSame([150, 285, 375], array_map(fn ($l) => $t->weeklyMinutes($l), Level::cases()));
        $this->assertSame(39, $t->youthDailyTopUp(Level::Easy));
    }

    public function test_under_19_calories_are_never_cut(): void
    {
        $a = (new HealthAnalyzer)->analyzeAge(Sex::Female, 16, 160, 90);
        $this->assertSame('obese', $a->bmiStatus);

        $kcal = (new EnergyPlanner)->plan($a, Level::Mid)->kcal;
        $raw = EnergyPlanner::bmr(Sex::Female, 16, 90) * 1.65;
        $this->assertSame((int) (round($raw / 50) * 50), $kcal);
    }

    public function test_adult_obesity_cuts_fifteen_percent(): void
    {
        $a = (new HealthAnalyzer)->analyzeAge(Sex::Male, 40, 170, 95);
        $this->assertSame('obese', $a->bmiStatus);

        $raw = EnergyPlanner::bmr(Sex::Male, 40, 95) * 1.45 * 0.85;
        $this->assertSame((int) (round($raw / 50) * 50), (new EnergyPlanner)->plan($a, Level::Easy)->kcal);
    }

    public function test_adult_bmi_thresholds(): void
    {
        $an = new HealthAnalyzer;
        $this->assertSame('under', $an->analyzeAge(Sex::Male, 30, 180, 59)->bmiStatus);
        $this->assertSame('ok', $an->analyzeAge(Sex::Male, 30, 180, 70)->bmiStatus);
        $this->assertSame('over', $an->analyzeAge(Sex::Male, 30, 180, 90)->bmiStatus);
        $this->assertSame('obese', $an->analyzeAge(Sex::Male, 30, 180, 98)->bmiStatus);
    }

    public function test_growth_window_closes_for_girls_at_17(): void
    {
        $a = (new HealthAnalyzer)->analyzeAge(Sex::Female, 17.5, 150, 45);
        $this->assertSame(0.0, $a->remaining);
        $this->assertFalse($a->growable());
    }
}
