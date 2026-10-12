<?php

namespace Tests\Unit\Health;

use App\Enums\Sex;
use App\Services\Health\GrowthReference;
use App\Services\Health\HealthAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Checks against the WHO 2007 reference: values copied from WHO's expanded tables and the worked
 * examples in WHO's "Computation of centiles and z-scores" guide (computation.pdf).
 */
class WhoReferenceTest extends TestCase
{
    public function test_table_values_at_exact_months(): void
    {
        // hfa-boys-z-who-2007-exp.xlsx, months 61 and 228; bmi-boys, month 132.
        $this->assertSame([1.0, 110.2647, 0.04164], array_map('floatval', GrowthReference::lms('hfa', Sex::Male, 61 / 12)));
        $this->assertEqualsWithDelta(176.5432, GrowthReference::heightMedian(Sex::Male, 19), 1e-9);
        $this->assertEqualsWithDelta(163.1548, GrowthReference::heightMedian(Sex::Female, 19), 1e-9);
        $this->assertEqualsWithDelta(7.2983, GrowthReference::heightSd(Sex::Male, 19), 1e-3); // StDev column
        [$l, $m, $s] = GrowthReference::lms('bmi', Sex::Male, 11);
        $this->assertEqualsWithDelta(-1.7862, $l, 1e-9);
        $this->assertEqualsWithDelta(16.9392, $m, 1e-9);
        $this->assertEqualsWithDelta(0.11070, $s, 1e-9);
    }

    public function test_ages_between_months_are_interpolated_and_clamped(): void
    {
        $a = GrowthReference::heightMedian(Sex::Female, 120 / 12);
        $b = GrowthReference::heightMedian(Sex::Female, 121 / 12);
        $this->assertEqualsWithDelta(($a + $b) / 2, GrowthReference::heightMedian(Sex::Female, 120.5 / 12), 1e-9);

        $this->assertSame(GrowthReference::heightMedian(Sex::Male, 61 / 12), GrowthReference::heightMedian(Sex::Male, 5));
        $this->assertSame(GrowthReference::heightMedian(Sex::Male, 19), GrowthReference::heightMedian(Sex::Male, 25));
    }

    public function test_who_worked_examples_for_bmi_z_scores(): void
    {
        // Child 1: 11-year-old boy, BMI 30 -> z* = 3.35 (beyond +3, fixed SD).
        $this->assertEqualsWithDelta(3.35, GrowthReference::bmiZ(Sex::Male, 11, 30.0), 0.01);
        // Child 2: 16-year-old boy, BMI 14 -> z* = -3.80 (beyond -3).
        $this->assertEqualsWithDelta(-3.80, GrowthReference::bmiZ(Sex::Male, 16, 14.0), 0.01);
        // Child 3: 9-year-old boy, BMI 19 -> z = 1.47.
        $this->assertEqualsWithDelta(1.47, GrowthReference::bmiZ(Sex::Male, 9, 19.0), 0.01);
        // Cut-offs in the same example: +3 SD = 28.03, +2 SD = 22.45 at 11 years.
        $this->assertEqualsWithDelta(28.03, GrowthReference::bmiAtZ(Sex::Male, 11, 3), 0.01);
        $this->assertEqualsWithDelta(22.45, GrowthReference::bmiAtZ(Sex::Male, 11, 2), 0.01);
    }

    public function test_youth_bmi_categories_follow_who_cutoffs(): void
    {
        $an = new HealthAnalyzer;
        $h = 150.0;
        $bmiAt = fn (float $z) => GrowthReference::bmiAtZ(Sex::Female, 12, $z);
        $weightFor = fn (float $bmi) => $bmi * ($h / 100) ** 2;

        $this->assertSame('under', $an->analyzeAge(Sex::Female, 12, $h, $weightFor($bmiAt(-2.1)))->bmiStatus);
        $this->assertSame('ok', $an->analyzeAge(Sex::Female, 12, $h, $weightFor($bmiAt(0)))->bmiStatus);
        $this->assertSame('over', $an->analyzeAge(Sex::Female, 12, $h, $weightFor($bmiAt(1.5)))->bmiStatus);
        $this->assertSame('obese', $an->analyzeAge(Sex::Female, 12, $h, $weightFor($bmiAt(2.1)))->bmiStatus);
    }

    public function test_height_z_score_and_projection(): void
    {
        $a = (new HealthAnalyzer)->analyzeAge(Sex::Male, 174 / 12, 161, 52);
        [, $m, $s] = GrowthReference::lms('hfa', Sex::Male, 174 / 12);

        $this->assertEqualsWithDelta((161 - $m) / ($m * $s), $a->z, 1e-9);
        $this->assertSame('typ', $a->heightStatus);
        // Adult height keeps the z-score: median at 19 + z x SD at 19.
        $this->assertEqualsWithDelta(176.5432 + $a->z * 176.5432 * 0.04134, $a->adultHeight, 1e-6);
    }

    public function test_growth_speed_comes_from_the_who_median_curve(): void
    {
        $this->assertGreaterThan(5, GrowthReference::velocity(Sex::Male, 13.5)); // puberty spurt
        $this->assertLessThan(1, GrowthReference::velocity(Sex::Female, 17.5));
        $this->assertSame(0.0, GrowthReference::velocity(Sex::Male, 19));
    }
}
