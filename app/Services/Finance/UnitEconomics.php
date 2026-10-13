<?php

namespace App\Services\Finance;

/**
 * Unit economics for the monetisation models. Pure maths on the admin's assumptions (all money in UZS per month
 * unless named otherwise). Formulas:
 *   paying users       = active users x conversion
 *   net ARPU           = price x (1 - payment fee)
 *   MRR                = paying users x net ARPU + school licences / 12 + sponsorship / 12 + partner income
 *   gross margin       = (MRR - active users x cost per active user) / MRR
 *   LTV (per payer)    = net ARPU x margin / monthly churn
 *   LTV:CAC, CAC payback (months) = CAC / (net ARPU x margin)
 *   break-even         = fixed costs covered by contribution
 */
final class UnitEconomics
{
    /** @param  array<string, float|int>  $in  see defaults() */
    public static function calculate(array $in): array
    {
        $in = array_merge(self::defaults(), array_filter($in, fn ($v) => $v !== null && $v !== ''));
        $f = fn (string $k) => (float) $in[$k];

        $active = $f('active_users');
        $payers = $active * $f('conversion_pct') / 100;
        $arpu = $f('price_month') * (1 - $f('payment_fee_pct') / 100);

        $subscription = $payers * $arpu;
        $schools = $f('schools') * $f('pupils_per_school') * $f('price_per_pupil_year') / 12;
        $sponsors = $f('sponsorship_year') / 12;
        $partners = $active * $f('partner_income_per_user');

        $mrr = $subscription + $schools + $sponsors + $partners;
        $variable = $active * $f('cost_per_active_user');
        $contribution = $mrr - $variable;
        $margin = $mrr > 0 ? $contribution / $mrr : 0.0;
        $profit = $contribution - $f('fixed_costs_month');

        $churn = $f('churn_pct') / 100;
        $payerContribution = $arpu * max(0.0, $margin);
        $ltv = $churn > 0 ? $payerContribution / $churn : null;
        $cac = $f('cac');

        // Users needed for contribution to cover fixed costs, at the current revenue mix per active user.
        $perUser = $active > 0 ? $contribution / $active : 0.0;
        $breakEven = $perUser > 0 ? (int) ceil($f('fixed_costs_month') / $perUser) : null;

        return [
            'payers' => (int) round($payers),
            'arpu' => $arpu,
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'mix' => ['subscription' => $subscription, 'schools' => $schools, 'sponsors' => $sponsors, 'partners' => $partners],
            'variable_costs' => $variable,
            'gross_margin_pct' => $margin * 100,
            'profit_month' => $profit,
            'ltv' => $ltv,
            'ltv_cac' => $ltv !== null && $cac > 0 ? $ltv / $cac : null,
            'cac_payback_months' => $payerContribution > 0 && $cac > 0 ? $cac / $payerContribution : null,
            'break_even_users' => $breakEven,
            'forecast' => self::forecast($in),
        ];
    }

    /**
     * 12 months ahead: active users grow by growth_pct a month; paying users follow conversion; school and sponsor
     * income stay flat.
     *
     * @return list<array{month:int, users:int, mrr:float, profit:float}>
     */
    public static function forecast(array $in, int $months = 12): array
    {
        $out = [];
        $users = (float) $in['active_users'];

        for ($m = 1; $m <= $months; $m++) {
            $users *= 1 + (float) $in['growth_pct'] / 100;
            $r = self::monthAt(array_merge($in, ['active_users' => $users]));
            $out[] = ['month' => $m, 'users' => (int) round($users), 'mrr' => $r['mrr'], 'profit' => $r['profit']];
        }

        return $out;
    }

    private static function monthAt(array $in): array
    {
        $active = (float) $in['active_users'];
        $arpu = (float) $in['price_month'] * (1 - (float) $in['payment_fee_pct'] / 100);
        $mrr = $active * (float) $in['conversion_pct'] / 100 * $arpu
            + (float) $in['schools'] * (float) $in['pupils_per_school'] * (float) $in['price_per_pupil_year'] / 12
            + (float) $in['sponsorship_year'] / 12
            + $active * (float) $in['partner_income_per_user'];

        return ['mrr' => $mrr, 'profit' => $mrr - $active * (float) $in['cost_per_active_user'] - (float) $in['fixed_costs_month']];
    }

    /**
     * Placeholder assumptions for the admin to replace with real figures. Not market data.
     *
     * @return array<string, float|int>
     */
    public static function defaults(): array
    {
        return [
            'active_users' => 1000,
            'growth_pct' => 10,
            // Family Premium
            'price_month' => 29000,
            'conversion_pct' => 4,
            'churn_pct' => 8,
            'payment_fee_pct' => 2,
            'cac' => 25000,
            // School licence
            'schools' => 0,
            'pupils_per_school' => 300,
            'price_per_pupil_year' => 60000,
            // Sponsors and partners
            'sponsorship_year' => 0,
            'partner_income_per_user' => 0,
            // Costs
            'cost_per_active_user' => 300,
            'fixed_costs_month' => 3000000,
        ];
    }
}
