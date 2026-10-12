@php $lv = $this->level->value; $g = app()->getLocale() === 'ru' ? 'г' : 'g'; @endphp
<div>
    @include('livewire.panel.strip')
    <div class="flex flex-col gap-4">
        <div class="card">
            <span class="label">{{ t("lvtag.$lv") }}</span>
            <h2>{{ t('fd.title', t("lv.$lv")) }}</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div class="min-w-0"><span class="label"><x-term name="kcal">{{ t('fd.energy') }}</x-term></span><p class="big">{{ $plan->kcal }}</p><p class="text-sm text-muted">{{ t('u.kcalday') }}</p></div>
                <x-stat :label="t('fd.protein')" :value="$plan->protein" :unit="t('u.gday')" />
                <x-stat :label="t('fd.carbs')" :value="$plan->carbs" :unit="t('u.gday')" />
                <x-stat :label="t('fd.fat')" :value="$plan->fat" :unit="t('u.gday')" />
                <x-stat :label="t('fd.calcium')" :value="$plan->calcium" :unit="t('u.mgday')" />
                <x-stat :label="t('fd.water')" :value="$plan->water" :unit="t('u.lday')" />
            </div>
            <p class="mt-3 text-sm text-muted">{{ $a->youth ? t($a->bmiStatus === 'under' ? 'fd.adj.y.under' : 'fd.adj.y') : t('fd.adj.'.$a->bmiStatus) }}</p>
        </div>

        <div class="flex flex-col gap-2">
            @foreach ($menu as $meal)
                <div class="card !p-4">
                    <div class="flex items-center justify-between gap-2"><b>{{ t('m.'.$meal['meal']) }}</b><span class="pill pill-neutral font-mono">{{ $meal['kcal'] }} {{ t('u.kcal') }}</span></div>
                    <ul class="mt-2 flex flex-col gap-1 text-sm">
                        @foreach ($meal['items'] as $it)
                            <li class="flex justify-between gap-3 border-b border-line pb-1 last:border-0">
                                <span>{{ t('f.'.$it['food']) }}</span>
                                <span class="font-mono text-muted">{{ $it['grams'] !== null ? $it['grams'].' '.$g : $it['pieces'].' ×' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="card">
            <h3>{{ t('fd.cmp') }}</h3>
            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                @foreach ($compare as $l => $q)
                    <div class="grid grid-cols-[14px_1fr] gap-3 rounded-lg border border-line p-3 {{ $l === $lv ? ['easy' => 'bg-easy-bg', 'mid' => 'bg-mid-bg', 'strong' => 'bg-strong-bg'][$l] : '' }}">
                        <span class="dot dot-{{ $l }} mt-1.5"></span>
                        <div><b>{{ t("lv.$l") }}</b><p class="font-mono text-sm">{{ t('fd.cmpline', $q->kcal, $q->protein) }}</p></div>
                    </div>
                @endforeach
            </div>
        </div>
        <x-glossary :terms="['kcal', 'mg', 'bmi', 'who']" />
        <p class="note">{{ t('fd.note', 1.45, 1.65, 1.85) }}</p>
    </div>
</div>
