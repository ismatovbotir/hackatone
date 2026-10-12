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
