@php $sleep = $a->age < 14 ? '9–11' : ($a->age < 18 ? '8–10' : '7–9'); @endphp
<div>
    @include('livewire.panel.strip')
    <div class="flex flex-col gap-4">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="card">
                <span class="label">{{ t('ov.height') }}</span>
                <div class="mt-2 flex items-center justify-between gap-3"><span class="big">{{ $a->height }} {{ t('u.cm') }}</span><x-pill :tone="$a->heightTone">{{ t('hs.'.$a->heightStatus) }}</x-pill></div>
                <dl class="kv">
                    <dt><x-term name="median">{{ t('ov.median', number_format($a->age, 1)) }}</x-term></dt><dd>{{ r1($a->median) }} {{ t('u.cm') }}</dd>
                    <dt><x-term name="sd">{{ t('ov.range') }}</x-term></dt><dd>{{ round($a->median - 2 * $a->sd) }} – {{ round($a->median + 2 * $a->sd) }} {{ t('u.cm') }}</dd>
                    <dt><x-term name="pct">{{ t('ov.pct') }}</x-term></dt><dd>P{{ $a->percentile }}</dd>
                </dl>
                @if ($a->heightTone === 'bad')<p class="note mt-3">{{ t('ov.doctor') }}</p>@endif
            </div>

            <div class="card">
                <span class="label">{{ t('ov.weight') }}</span>
                <div class="mt-2 flex items-center justify-between gap-3"><span class="big">{{ number_format($a->bmi, 1) }}</span><x-pill :tone="$a->bmiTone">{{ t('bs.'.$a->bmiStatus) }}</x-pill></div>
                @php $diff = $a->weight < $a->kgLow ? t('ov.plus', r1($a->kgLow - $a->weight)) : ($a->weight > $a->kgHigh ? t('ov.minus', r1($a->weight - $a->kgHigh)) : t('ov.inrange')); @endphp
                <dl class="kv">
                    <dt><x-term name="bmi">{{ t('ov.bmirange') }}</x-term></dt><dd>{{ r1($a->bmiLow) }} – {{ r1($a->bmiHigh) }}</dd>
                    <dt>{{ t('ov.hw', $a->height) }}</dt><dd>{{ r1($a->kgLow) }} – {{ r1($a->kgHigh) }} {{ t('u.kg') }}</dd>
                    <dt>{{ t('ov.yourw') }}</dt><dd>{{ $a->weight }} {{ t('u.kg') }}, {{ $diff }}</dd>
                </dl>
                <p class="mt-3 text-sm text-muted">{{ t($a->youth ? 'ov.bminote.y' : 'ov.bminote.a') }}</p>
            </div>

            <div class="card">
                <span class="label">{{ t('gr.title') }}</span>
                @if ($a->growable())
                    <div class="mt-2 flex items-center justify-between gap-3"><span class="big">{{ round($a->adultHeight) }} {{ t('u.cm') }}</span><x-pill tone="good">{{ t('gr.still') }}</x-pill></div>
                    <p class="mt-1 text-sm text-muted">{{ t('gr.proj') }}</p>
                    <dl class="kv">
                        <dt>{{ t('gr.rem') }}</dt><dd>{{ t('gr.remv', r1($a->remaining)) }}</dd>
                        <dt>{{ t('gr.vel') }}</dt><dd>{{ t('gr.velv', r1($a->velocity)) }}</dd>
                        <dt>{{ t('gr.end') }}</dt><dd>{{ t('gr.endv', $a->endAge) }}</dd>
                    </dl>
                    <p class="mt-3 text-sm text-muted">{{ t('gr.genes') }}</p>
                @else
                    <div class="mt-2 flex items-center justify-between gap-3"><span class="big">{{ t('gr.complete') }}</span><x-pill>{{ t($a->youth ? 'gr.near' : 'gr.adult') }}</x-pill></div>
                    <p class="mt-2.5">{{ t('gr.closed') }}</p>
                    <p class="mt-2 text-sm text-muted">{{ t('gr.posture') }}</p>
                @endif
            </div>
        </div>

        @if ($a->youth)
            <div class="card">
                <div class="flex flex-wrap items-baseline justify-between gap-2"><h3>{{ t('ch.title') }}</h3><span class="text-sm text-muted">{{ t('ch.sub') }}</span></div>
                <div class="mt-2">{!! \App\Support\HeightChart::svg($a->sex, $points) !!}</div>
                <p class="text-sm text-muted">{{ t('gl.band.d') }}</p>
            </div>
        @endif

        <div class="card">
            <h3>{{ t('dt.title') }}</h3>
            <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ([['dt.sleep', $sleep.' '.t('u.h')], ['dt.act', t($a->youth ? 'dt.actv.y' : 'dt.actv.a')], ['dt.ca', $plan->calcium.' '.t('u.mg')], ['dt.water', $plan->water.' '.t('u.l')]] as [$k, $v])
                    <div><span class="label">@if ($k === 'dt.ca')<x-term name="mg">{{ t($k) }}</x-term>@else{{ t($k) }}@endif</span><p class="font-mono text-lg">{{ $v }}</p></div>
                @endforeach
            </div>
        </div>
        <x-glossary />
        <p class="note">{{ t('ov.note') }}</p>
    </div>
</div>
