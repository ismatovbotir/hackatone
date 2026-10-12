{{-- Profile strip with the level switch, shown on every tab of the client panel. --}}
@php
    $c = $this->client; $a = $this->analysis; $m = $c->measurements->last();
    $rec = $a->recommendedLevel();
@endphp
<div class="mb-5 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div class="flex min-w-0 flex-col gap-2">
        <h1 class="truncate">{{ $c->fullName() }}</h1>
        <div class="grid grid-cols-4 gap-3 sm:flex sm:gap-6">
            @foreach ([['prof.age', number_format($a->age, 1)], ['prof.height', $m->height_cm.' '.t('u.cm')], ['prof.weight', $m->weight_kg.' '.t('u.kg')]] as [$k, $v])
                <div class="min-w-0"><span class="label block truncate">{{ t($k) }}</span><b class="block font-mono text-base font-medium sm:text-xl">{{ $v }}</b></div>
            @endforeach
            <div class="min-w-0"><span class="label"><x-term name="bmi">{{ t('prof.bmi') }}</x-term></span><b class="block font-mono text-base font-medium sm:text-xl">{{ number_format($a->bmi, 1) }}</b></div>
        </div>
    </div>
    <div class="flex flex-col gap-2">
        <span class="label">{{ t('prof.level') }}</span>
        <div class="grid grid-cols-3 gap-2 sm:flex" role="group">
            @foreach (\App\Enums\Level::cases() as $l)
                <button type="button" wire:click="setLevel('{{ $l->value }}')" aria-pressed="{{ $this->level === $l ? 'true' : 'false' }}"
                    class="flex min-h-11 items-center justify-center gap-2 rounded-lg border bg-surface px-3 font-semibold {{ $this->level === $l ? 'border-ink shadow-[inset_0_0_0_1px_var(--ink)]' : 'border-line' }}">
                    <span class="dot dot-{{ $l->value }}"></span>{{ t('lv.'.$l->value) }}
                </button>
            @endforeach
        </div>
        <p class="text-sm text-muted">{{ t('prof.suggest', t('lv.'.$rec->value)) }}</p>
    </div>
</div>
