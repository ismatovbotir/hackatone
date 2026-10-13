@php
    $money = fn ($v) => $v === null ? '—' : number_format((float) $v, 0, '.', ' ').' '.t('mon.uzs');
    $num = fn ($v, $d = 1) => $v === null ? '—' : number_format((float) $v, $d, '.', ' ');
    $ratioTone = $r['ltv_cac'] === null ? 'neutral' : ($r['ltv_cac'] >= 3 ? 'good' : ($r['ltv_cac'] >= 1 ? 'warn' : 'bad'));
    $mixTotal = max(1, array_sum($r['mix']));
    $mixTone = ['subscription' => 'bg-accent', 'schools' => 'bg-easy', 'sponsors' => 'bg-mid', 'partners' => 'bg-strong'];
    $maxMrr = max(1, max(array_column($r['forecast'], 'mrr')));
    $field = function (string $key, string $label, string $unit = '') {
        return ['key' => $key, 'label' => $label, 'unit' => $unit];
    };
    $groups = [
        'mon.g.base' => [$field('active_users', t('mon.f.active_users')), $field('growth_pct', t('mon.f.growth_pct'), '%')],
        'mon.m.premium' => [$field('price_month', t('mon.f.price_month'), t('mon.uzs')), $field('conversion_pct', t('mon.f.conversion_pct'), '%'), $field('churn_pct', t('mon.f.churn_pct'), '%'), $field('payment_fee_pct', t('mon.f.payment_fee_pct'), '%'), $field('cac', t('mon.f.cac'), t('mon.uzs'))],
        'mon.m.schools' => [$field('schools', t('mon.f.schools')), $field('pupils_per_school', t('mon.f.pupils_per_school')), $field('price_per_pupil_year', t('mon.f.price_per_pupil_year'), t('mon.uzs'))],
        'mon.m.sponsors' => [$field('sponsorship_year', t('mon.f.sponsorship_year'), t('mon.uzs')), $field('partner_income_per_user', t('mon.f.partner_income_per_user'), t('mon.uzs'))],
        'mon.g.costs' => [$field('cost_per_active_user', t('mon.f.cost_per_active_user'), t('mon.uzs')), $field('fixed_costs_month', t('mon.f.fixed_costs_month'), t('mon.uzs'))],
    ];
@endphp
<div class="flex flex-col gap-5">
    <div>
        <h1>{{ t('mon.title') }}</h1>
        <p class="mt-1.5 max-w-prose text-muted">{{ t('mon.sub') }}</p>
    </div>

    {{-- Real base from the database --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
        @foreach (['clients' => 'mon.b.clients', 'complete' => 'mon.b.complete', 'linked' => 'mon.b.linked', 'active30' => 'mon.b.active30', 'minors' => 'mon.b.minors'] as $k => $label)
            <div class="card"><x-stat :label="t($label)" :value="$base[$k]" /></div>
        @endforeach
    </div>

    {{-- Models --}}
    <section class="flex flex-col gap-2">
        <h2>{{ t('mon.models') }}</h2>
        <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-4">
            @foreach (['premium' => 'accent', 'schools' => 'easy', 'sponsors' => 'mid', 'partners' => 'strong'] as $m => $tone)
                <div class="card flex flex-col gap-2">
                    <div class="flex items-center gap-2"><span class="size-3 rounded-full bg-{{ $tone }}"></span><h3>{{ t('mon.m.'.$m) }}</h3></div>
                    <p class="text-sm">{{ t('mon.m.'.$m.'.d') }}</p>
                    <p class="text-xs text-muted"><b>{{ t('mon.pros') }}:</b> {{ t('mon.m.'.$m.'.pro') }}</p>
                    <p class="text-xs text-muted"><b>{{ t('mon.cons') }}:</b> {{ t('mon.m.'.$m.'.con') }}</p>
                </div>
            @endforeach
        </div>
        <p class="note">{{ t('mon.ethics') }}</p>
    </section>

    <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
        {{-- Assumptions --}}
        <form wire:submit="save" class="card flex flex-col gap-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2>{{ t('mon.assumptions') }}</h2>
                <button type="button" class="btn-ghost btn-sm" wire:click="useRealUsers">{{ t('mon.use_real') }}</button>
            </div>
            <p class="text-sm text-muted">{{ t('mon.assumptions_d') }}</p>
            @foreach ($groups as $groupLabel => $fields)
                <fieldset class="flex flex-col gap-2">
                    <legend class="label mb-1">{{ t($groupLabel) }}</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($fields as $f)
                            <label class="field">{{ $f['label'] }}
                                <span class="flex items-center gap-2">
                                    <input class="input font-mono" type="number" min="0" step="any" inputmode="decimal" wire:model.live.debounce.400ms="in.{{ $f['key'] }}">
                                    @if ($f['unit'])<span class="shrink-0 text-xs font-normal text-muted">{{ $f['unit'] }}</span>@endif
                                </span>
                                @error('in.'.$f['key'])<span class="err">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
            <div class="flex flex-wrap gap-2">
                <button class="btn">{{ t('mon.save') }}</button>
                <button type="button" class="btn-ghost" wire:click="resetDefaults">{{ t('mon.reset') }}</button>
            </div>
        </form>

        {{-- Results --}}
        <div class="flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="card"><x-stat :label="t('mon.r.mrr')" :value="$money($r['mrr'])" /></div>
                <div class="card"><x-stat :label="t('mon.r.arr')" :value="$money($r['arr'])" /></div>
                <div class="card"><x-stat :label="t('mon.r.margin')" :value="$num($r['gross_margin_pct']).' %'" /></div>
                <div class="card"><x-stat :label="t('mon.r.profit')" :value="$money($r['profit_month'])" class="{{ $r['profit_month'] >= 0 ? '[&_.big]:text-good' : '[&_.big]:text-bad' }}" /></div>
                <div class="card"><x-stat :label="t('mon.r.payers')" :value="$r['payers']" /></div>
                <div class="card"><x-stat :label="t('mon.r.ltv')" :value="$money($r['ltv'])" /></div>
                <div class="card">
                    <span class="label">{{ t('mon.r.ltv_cac') }}</span>
                    <p class="big">{{ $num($r['ltv_cac']) }}</p>
                    <x-pill :tone="$ratioTone">{{ t('mon.r.ltv_cac.'.$ratioTone) }}</x-pill>
                </div>
                <div class="card"><x-stat :label="t('mon.r.payback')" :value="$num($r['cac_payback_months'])" :unit="t('mon.months')" /></div>
            </div>

            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3>{{ t('mon.r.mix') }}</h3>
                    <span class="text-sm text-muted">{{ t('mon.r.break_even', $r['break_even_users'] ?? '—') }}</span>
                </div>
                <div class="mt-3 flex h-3 overflow-hidden rounded-full bg-surface2" aria-hidden="true">
                    @foreach ($r['mix'] as $k => $v)
                        @if ($v > 0)<div class="{{ $mixTone[$k] }}" style="width: {{ $v / $mixTotal * 100 }}%"></div>@endif
                    @endforeach
                </div>
                <dl class="kv">
                    @foreach ($r['mix'] as $k => $v)
                        <dt class="flex items-center gap-2"><span class="size-2.5 rounded-full {{ $mixTone[$k] }}"></span>{{ t('mon.mix.'.$k) }}</dt><dd>{{ $money($v) }}</dd>
                    @endforeach
                </dl>
            </div>

            <div class="card">
                <h3>{{ t('mon.r.forecast') }}</h3>
                <div class="mt-3 flex h-40 items-end gap-1" role="img" aria-label="{{ t('mon.r.forecast') }}">
                    @foreach ($r['forecast'] as $row)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-t {{ $row['profit'] >= 0 ? 'bg-good' : 'bg-bad/70' }}" style="height: {{ max(2, $row['mrr'] / $maxMrr * 130) }}px" title="{{ $money($row['mrr']) }}"></div>
                            <span class="font-mono text-[10px] text-muted">{{ $row['month'] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 flex flex-wrap gap-x-4 text-xs text-muted">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded bg-good"></span>{{ t('mon.r.profitable') }}</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded bg-bad/70"></span>{{ t('mon.r.loss') }}</span>
                </p>
                <div class="tablewrap mt-3">
                    <table class="table !min-w-[420px]">
                        <thead><tr><th>{{ t('mon.month') }}</th><th class="num">{{ t('mon.f.active_users') }}</th><th class="num">{{ t('mon.r.mrr') }}</th><th class="num">{{ t('mon.r.profit') }}</th></tr></thead>
                        <tbody>
                            @foreach ($r['forecast'] as $row)
                                <tr><td>{{ $row['month'] }}</td><td class="num">{{ number_format($row['users'], 0, '.', ' ') }}</td><td class="num">{{ $money($row['mrr']) }}</td><td class="num {{ $row['profit'] >= 0 ? 'text-good' : 'text-bad' }}">{{ $money($row['profit']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="note">{{ t('mon.disclaimer') }}</p>
        </div>
    </div>
</div>
