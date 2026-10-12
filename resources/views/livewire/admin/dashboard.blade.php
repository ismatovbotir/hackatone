@php
    $bar = fn ($n, $total) => $total ? round($n / $total * 100) : 0;
    $lvTotal = array_sum($levels); $bmiTotal = array_sum($bmi);
    $bmiTone = ['under' => 'bg-warn', 'ok' => 'bg-good', 'over' => 'bg-warn', 'obese' => 'bg-bad'];
    $lvTone = ['easy' => 'bg-easy', 'mid' => 'bg-mid', 'strong' => 'bg-strong'];
@endphp
<div class="flex flex-col gap-5">
    <h1>{{ t('admin.dashboard') }}</h1>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ($stats as $label => $value)
            <div class="card"><x-stat :label="t($label)" :value="$value" /></div>
        @endforeach
    </div>

    {{-- Top 20 by calendar points. Phones: cards. From md up: a table. --}}
    @php
        $lbHow = t('lb.how', \App\Services\Clients\Points::TRAINING_DONE, \App\Services\Clients\Points::FULL_MINUTES_BONUS, \App\Services\Clients\Points::PER_MEAL, \App\Services\Clients\Points::MAX_MEALS, \App\Services\Clients\Points::WATER_GOAL, \App\Services\Clients\Points::TRAINING_DONE + \App\Services\Clients\Points::FULL_MINUTES_BONUS + \App\Services\Clients\Points::PER_MEAL * \App\Services\Clients\Points::MAX_MEALS + \App\Services\Clients\Points::WATER_GOAL);
    @endphp
    <section class="card flex flex-col gap-4" aria-labelledby="lb-title">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h2 id="lb-title" class="flex items-center gap-2"><x-icon name="trophy" class="size-6 text-accent" />{{ t('lb.title') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ t('lb.sub') }}</p>
            </div>
            <div class="grid shrink-0 grid-cols-2 gap-1 rounded-lg border border-line bg-surface p-1 sm:flex" role="group" aria-label="{{ t('lb.period') }}">
                @foreach (\App\Services\Clients\Leaderboard::PERIODS as $p)
                    <button type="button" wire:click="setPeriod('{{ $p }}')" aria-pressed="{{ $period === $p ? 'true' : 'false' }}"
                            class="rounded-md px-3 py-1.5 text-sm font-semibold {{ $period === $p ? 'bg-ink text-bg' : 'text-muted' }}">{{ t('lb.p.'.$p) }}</button>
                @endforeach
            </div>
        </div>

        @if ($top->isEmpty())
            <p class="note">{{ t('lb.empty') }}</p>
        @else
            {{-- Phones --}}
            <ol class="flex flex-col gap-2 md:hidden" wire:loading.class="opacity-60">
                @foreach ($top as $row)
                    @php $c = $row['client']; $lv = $row['level']; @endphp
                    <li wire:key="lbm-{{ $period }}-{{ $c->id }}">
                        <a href="{{ route('admin.clients.show', $c) }}" wire:navigate
                           class="flex items-center gap-3 rounded-lg border p-3 hover:bg-surface2 {{ $row['rank'] <= 3 ? 'border-accent/40 bg-band/40' : 'border-line' }}">
                            <x-rank :rank="$row['rank']" />
                            <span class="min-w-0 flex-1">
                                <b class="block truncate">{{ $c->fullName() }}</b>
                                <span class="mt-0.5 flex items-center gap-3 text-xs text-muted">
                                    @if ($lv)<span class="flex items-center gap-1.5"><span class="dot dot-{{ $lv->value }}"></span>{{ t('lv.'.$lv->value) }}</span>@else<span>-</span>@endif
                                    <span class="flex items-center gap-1" title="{{ t('cal.streakv', $row['streak']) }}"><x-icon name="flame" class="size-3.5 {{ $row['streak'] ? 'text-accent' : '' }}" /><span class="font-mono">{{ $row['streak'] }}</span><span class="sr-only">{{ t('cal.streak') }}</span></span>
                                </span>
                            </span>
                            <span class="shrink-0 text-right">
                                <b class="block font-mono text-xl font-medium tabular-nums">{{ $row['points'] }}</b>
                                <span class="label !text-[10px]">{{ t('lb.points') }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>

            {{-- md and up --}}
            <div class="tablewrap hidden md:block" wire:loading.class="opacity-60">
                <table class="table">
                    <thead><tr><th class="w-14">#</th><th>{{ t('admin.name') }}</th><th>{{ t('lb.level') }}</th><th>{{ t('cal.streak') }}</th><th class="text-right">{{ t('lb.points') }}</th></tr></thead>
                    <tbody>
                        @foreach ($top as $row)
                            @php $c = $row['client']; $lv = $row['level']; @endphp
                            <tr wire:key="lbt-{{ $period }}-{{ $c->id }}" class="{{ $row['rank'] <= 3 ? 'bg-band/30' : '' }}">
                                <td class="!align-middle"><x-rank :rank="$row['rank']" /></td>
                                <td class="!align-middle"><a class="font-semibold hover:underline" href="{{ route('admin.clients.show', $c) }}" wire:navigate>{{ $c->fullName() }}</a></td>
                                <td class="!align-middle">@if ($lv)<span class="dot dot-{{ $lv->value }} mr-1.5 align-middle"></span>{{ t('lv.'.$lv->value) }}@else<span class="text-muted">-</span>@endif</td>
                                <td class="!align-middle"><span class="inline-flex items-center gap-1.5" title="{{ t('cal.streakv', $row['streak']) }}"><x-icon name="flame" class="size-4 {{ $row['streak'] ? 'text-accent' : 'text-muted' }}" /><span class="font-mono">{{ $row['streak'] }}</span></span></td>
                                <td class="!align-middle text-right">
                                    <b class="font-mono text-lg font-medium tabular-nums">{{ $row['points'] }}</b>
                                    <span class="ml-auto mt-1 block h-1.5 w-24 rounded-full bg-surface2" aria-hidden="true"><span class="block h-1.5 rounded-full bg-accent" style="width: {{ $bar($row['points'], $topMax) }}%"></span></span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <p class="text-sm text-muted">{{ $lbHow }} {{ t('lb.streak_note') }}</p>
    </section>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="card">
            <h3>{{ t('admin.levels') }}</h3>
            <div class="mt-3 flex flex-col gap-3">
                @foreach ($levels as $l => $n)
                    <div>
                        <div class="flex justify-between text-sm"><span class="flex items-center gap-2"><span class="dot dot-{{ $l }}"></span>{{ t("lv.$l") }}</span><span class="font-mono">{{ $n }}</span></div>
                        <div class="mt-1 h-2 rounded-full bg-surface2"><div class="h-2 rounded-full {{ $lvTone[$l] }}" style="width: {{ $bar($n, $lvTotal) }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card">
            <h3>{{ t('admin.bmi') }}</h3>
            <div class="mt-3 flex flex-col gap-3">
                @foreach ($bmi as $k => $n)
                    <div>
                        <div class="flex justify-between text-sm"><span>{{ t("bs.$k") }}</span><span class="font-mono">{{ $n }}</span></div>
                        <div class="mt-1 h-2 rounded-full bg-surface2"><div class="h-2 rounded-full {{ $bmiTone[$k] }}" style="width: {{ $bar($n, $bmiTotal) }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="flex items-center justify-between gap-2">
            <h3>{{ t('admin.recent') }}</h3>
            <a class="text-sm font-semibold text-accent underline" href="{{ route('admin.clients.index') }}" wire:navigate>{{ t('admin.clients') }}</a>
        </div>
        <ul class="mt-3 divide-y divide-line">
            @forelse ($recent as $c)
                <li class="flex items-center justify-between gap-3 py-2.5">
                    <a class="min-w-0 truncate font-semibold hover:underline" href="{{ route('admin.clients.show', $c) }}" wire:navigate>{{ $c->fullName() }}</a>
                    <span class="flex shrink-0 items-center gap-2">
                        <x-pill :tone="$c->isLinked() ? 'good' : 'neutral'">{{ $c->isLinked() ? t('admin.tg.linked') : t('admin.tg.not') }}</x-pill>
                        <span class="hidden font-mono text-xs text-muted sm:inline">{{ $c->created_at->toDateString() }}</span>
                    </span>
                </li>
            @empty
                <li class="py-3 text-muted">{{ t('admin.empty') }}</li>
            @endforelse
        </ul>
    </div>
</div>
