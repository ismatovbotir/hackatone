<?php

namespace Tests\Unit;

use App\Services\Finance\UnitEconomics;
use PHPUnit\Framework\TestCase;

class UnitEconomicsTest extends TestCase
{
    private function base(array $over = []): array
    {
        return array_merge([
            'active_users' => 1000, 'growth_pct' => 0,
            'price_month' => 10000, 'conversion_pct' => 10, 'churn_pct' => 10, 'payment_fee_pct' => 0, 'cac' => 20000,
            'schools' => 0, 'pupils_per_school' => 0, 'price_per_pupil_year' => 0,
            'sponsorship_year' => 0, 'partner_income_per_user' => 0,
            'cost_per_active_user' => 100, 'fixed_costs_month' => 500000,
        ], $over);
    }

    public function test_subscription_metrics(): void
    {
        $r = UnitEconomics::calculate($this->base());

        $this->assertSame(100, $r['payers']);                 // 1000 x 10%
        $this->assertEqualsWithDelta(1000000, $r['mrr'], 0.01); // 100 x 10 000
        $this->assertEqualsWithDelta(12000000, $r['arr'], 0.01);
        $this->assertEqualsWithDelta(90, $r['gross_margin_pct'], 0.001); // (1 000 000 - 100 000) / 1 000 000
        $this->assertEqualsWithDelta(400000, $r['profit_month'], 0.01); // 900 000 - 500 000
        $this->assertEqualsWithDelta(90000, $r['ltv'], 0.01);          // 10 000 x 0.9 / 0.10
        $this->assertEqualsWithDelta(4.5, $r['ltv_cac'], 0.001);
        $this->assertEqualsWithDelta(20000 / 9000, $r['cac_payback_months'], 0.001);
        $this->assertSame(556, $r['break_even_users']);                // 500 000 / 900 per user
    }

    public function test_payment_fee_school_and_sponsor_income(): void
    {
        $r = UnitEconomics::calculate($this->base(['payment_fee_pct' => 2, 'schools' => 2, 'pupils_per_school' => 300, 'price_per_pupil_year' => 60000, 'sponsorship_year' => 12000000]));

        $this->assertEqualsWithDelta(9800, $r['arpu'], 0.01);
        $this->assertEqualsWithDelta(3000000, $r['mix']['schools'], 0.01); // 600 pupils x 60 000 / 12
        $this->assertEqualsWithDelta(1000000, $r['mix']['sponsors'], 0.01);
        $this->assertEqualsWithDelta(980000 + 3000000 + 1000000, $r['mrr'], 0.01);
    }

    public function test_no_churn_or_no_revenue_does_not_divide_by_zero(): void
    {
        $this->assertNull(UnitEconomics::calculate($this->base(['churn_pct' => 0]))['ltv']);
        $r = UnitEconomics::calculate($this->base(['price_month' => 0]));
        $this->assertSame(0.0, $r['gross_margin_pct']);
        $this->assertNull($r['break_even_users']);
    }

    public function test_forecast_compounds_growth(): void
    {
        $f = UnitEconomics::forecast($this->base(['growth_pct' => 10]));

        $this->assertCount(12, $f);
        $this->assertSame(1100, $f[0]['users']);
        $this->assertSame(3138, $f[11]['users']); // 1000 x 1.1^12
        $this->assertGreaterThan($f[0]['mrr'], $f[11]['mrr']);
    }
}
