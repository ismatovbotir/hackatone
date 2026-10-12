@php
    $sel = ['easy' => 'bg-easy-bg', 'mid' => 'bg-mid-bg', 'strong' => 'bg-strong-bg'];
    $cur = $this->level->value;
@endphp
<div>
    @include('livewire.panel.strip')
    <div class="flex flex-col gap-4">
        <div class="card">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div><span class="label">{{ t('ac.badges') }}</span><h2>{{ t('ac.unlocked', $ach['won'], count($ach['badges'])) }}</h2></div>
                @if ($a->growable())
                    <div><span class="label">{{ t('ac.gain') }}</span><b class="block font-mono text-xl font-medium">{{ $ach['gain'] >= 0 ? '+' : '' }}{{ $ach['gain'] }} {{ t('u.cm') }}</b></div>
                @endif
            </div>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($ach['badges'] as $b)
                    <div class="flex flex-col gap-1.5 rounded-xl border p-3.5 {{ $b['ok'] ? 'border-accent' : 'border-line' }}">
                        <span class="grid size-9 place-items-center rounded-full font-mono text-xs font-semibold {{ $b['ok'] ? 'bg-accent text-on-accent' : 'border-2 border-dashed border-line bg-surface2 text-muted' }}">{{ $b['m'] }}</span>
                        <b>{{ $b['n'] }}</b>
                        <span class="text-sm text-muted">{{ $b['d'] }}</span>
                        <x-pill :tone="$b['ok'] ? 'good' : 'neutral'" class="self-start">{{ t($b['ok'] ? 'bd.on' : 'bd.off') }}</x-pill>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card">
            <h3>{{ t('lg.title') }}</h3>
            <form wire:submit="addMeasurement" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end" novalidate>
                <label class="field col-span-2 sm:col-span-1">{{ t('lg.date') }}
                    <input class="input" type="date" wire:model="log.measured_on" max="{{ now(config('hh.timezone'))->toDateString() }}">
                </label>
                <label class="field">{{ t('lg.h') }}
                    <input class="input" type="number" wire:model="log.height_cm" step="0.1" min="80" max="250" inputmode="decimal">
                </label>
                <label class="field">{{ t('lg.w') }}
                    <input class="input" type="number" wire:model="log.weight_kg" step="0.1" min="10" max="300" inputmode="decimal" placeholder="{{ t('lg.opt') }}">
                </label>
                <button class="btn col-span-2 sm:col-span-1">{{ t('lg.add') }}</button>
            </form>
            @error('log.*')<p class="err mt-2" role="alert">{{ $message }}</p>@enderror
            <div class="mt-3 flex flex-col gap-1.5 text-sm">
                @foreach ($history as $m)
                    <div class="flex items-center justify-between gap-3 border-b border-line pb-1.5" wire:key="m-{{ $m->id }}">
                        <span class="flex flex-wrap gap-x-3"><span class="font-mono">{{ $m->measured_on->toDateString() }}</span><b class="font-mono">{{ $m->height_cm }} {{ t('u.cm') }}</b><span class="font-mono text-muted">{{ $m->weight_kg }} {{ t('u.kg') }}</span></span>
                        @if ($m->id !== $firstId)
                            <button type="button" class="btn-ghost btn-sm" wire:click="removeMeasurement({{ $m->id }})">{{ t('lg.rm') }}</button>
                        @else
                            <span class="text-xs text-muted">{{ t('lg.first') }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        @if ($ach['growth'])
            <div class="flex flex-col gap-2.5">
                <div><h2>{{ t('ac.ht') }}</h2><p class="mt-1 text-sm text-muted">{{ t('ac.hsub') }}</p></div>
                <div class="tablewrap">
                    <table class="table !min-w-[480px]">
                        <thead><tr><th>{{ t('ac.th') }}</th>@foreach (\App\Enums\Level::cases() as $l)<th><span class="dot dot-{{ $l->value }} mr-1.5 align-middle"></span>{{ t('lv.'.$l->value) }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach ($ach['growth'] as $row)
                                <tr>
                                    <td><b>+{{ $row['gain'] }} {{ t('u.cm') }}</b><br><span class="text-xs text-muted">{{ t('ac.hgain', $a->height) }}</span></td>
                                    @foreach ($row['cells'] as $l => $cell)
                                        <td class="{{ $l === $cur ? $sel[$l] : '' }}">
                                            @if ($cell)<b class="font-mono font-medium">{{ $cell['label'] }}</b><br><span class="text-xs text-muted">{{ $cell['by'] }}</span>@else<span class="text-muted">-</span>@endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="note">{{ t('ac.hnote', r1($a->velocity)) }}</p>
            </div>
        @else
            <div class="card"><h2>{{ t('ac.done.t') }}</h2><p class="mt-1.5 text-muted">{{ t('ac.done.d') }}</p></div>
        @endif

        <div class="flex flex-col gap-2.5">
            <div><h2>{{ t('ac.hab') }}</h2><p class="mt-1 text-sm text-muted">{{ t('ac.habsub') }}</p></div>
            <div class="tablewrap">
                <table class="table !min-w-[480px]">
                    <thead><tr><th>{{ t('ac.th') }}</th>@foreach (\App\Enums\Level::cases() as $l)<th><span class="dot dot-{{ $l->value }} mr-1.5 align-middle"></span>{{ t('lv.'.$l->value) }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($ach['habits'] as $h)
                            <tr>
                                <td><b>{{ $h['name'] }}</b><br><span class="text-xs text-muted">{{ $h['desc'] }}</span></td>
                                @foreach ($h['cells'] as $l => $cell)
                                    <td class="{{ $l === $cur ? $sel[$l] : '' }}"><b class="font-mono font-medium">{{ $cell['label'] }}</b>@if ($cell['sub'])<br><span class="text-xs text-muted">{{ $cell['sub'] }}</span>@endif</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
