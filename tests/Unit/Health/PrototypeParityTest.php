<?php

namespace Tests\Unit\Health;

use App\Enums\Level;
use App\Enums\Sex;
use App\Services\Health\EnergyPlanner;
use App\Services\Health\GrowthProjector;
use App\Services\Health\HealthAnalyzer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Expected values come from running the prototype's core.js (.claude/hackatone-health.html)
 * on the same inputs. Regenerate tests/Fixtures/prototype-core.json if the prototype changes.
 */
class PrototypeParityTest extends TestCase
{
    public static function cases(): array
    {
        $rows = json_decode(file_get_contents(__DIR__.'/../../Fixtures/prototype-core.json'), true);

        return collect($rows)->mapWithKeys(fn ($r) => ["{$r['sex']} {$r['age']}y {$r['h']}cm {$r['w']}kg" => [$r]])->all();
    }

    #[DataProvider('cases')]
    public function test_analysis_matches_prototype(array $e): void
    {
        $a = (new HealthAnalyzer)->analyzeAge(Sex::from($e['sex']), $e['age'], $e['h'], $e['w']);

        $this->assertEqualsWithDelta($e['z'], $a->z, 1e-5);
        $this->assertSame($e['pct'], $a->percentile);
        $this->assertSame($e['hs'], $a->heightStatus);
        $this->assertEqualsWithDelta($e['bmi'], $a->bmi, 1e-5);
        $this->assertSame($e['bs'], $a->bmiStatus);
        $this->assertEqualsWithDelta($e['kgLow'], $a->kgLow, 1e-5);
        $this->assertEqualsWithDelta($e['kgHigh'], $a->kgHigh, 1e-5);
        $this->assertEqualsWithDelta($e['adultH'], $a->adultHeight, 1e-5);
        $this->assertEqualsWithDelta($e['remaining'], $a->remaining, 1e-5);
        $this->assertEqualsWithDelta($e['vel'], $a->velocity, 1e-5);
        $this->assertSame($e['rec'], $a->recommendedLevel()->value);
    }

    #[DataProvider('cases')]
    public function test_nutrition_plan_matches_prototype(array $e): void
    {
        $a = (new HealthAnalyzer)->analyzeAge(Sex::from($e['sex']), $e['age'], $e['h'], $e['w']);

        foreach (Level::cases() as $level) {
            $p = (new EnergyPlanner)->plan($a, $level);
            [$kcal, $protein, $carbs, $fat, $calcium, $water] = $e['plans'][$level->value];
            $this->assertSame([$kcal, $protein, $carbs, $fat, $calcium], [$p->kcal, $p->protein, $p->carbs, $p->fat, $p->calcium], "plan {$level->value}");
            $this->assertEqualsWithDelta($water, $p->water, 1e-9);
        }
    }

    #[DataProvider('cases')]
    public function test_growth_periods_match_prototype(array $e): void
    {
        $a = (new HealthAnalyzer)->analyzeAge(Sex::from($e['sex']), $e['age'], $e['h'], $e['w']);
        $g = new GrowthProjector;

        $targets = $g->gainTargets($a);
        $this->assertSame($e['targets'], $targets);

        foreach (Level::cases() as $level) {
            $this->assertSame($e['sims'][$level->value], $g->simulate($a, $level->growthPace(), $targets), "sim {$level->value}");
        }
    }
}
