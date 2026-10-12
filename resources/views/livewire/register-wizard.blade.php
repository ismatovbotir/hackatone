@php
    $steps = \App\Livewire\Forms\RegistrationForm::STEPS;
    $index = array_search($step, $steps, true);
    $furthest = array_search(collect($steps)->first(fn ($s) => $s === 'review' || ! in_array($s, $done, true)), $steps, true);
    $p = $this->preview;
@endphp
{{-- Colours follow the chosen gender as soon as it is picked: green for male, pink for female. --}}
<div class="py-2" x-data x-effect="document.body.dataset.theme = ({ M: 'green', F: 'pink' })[$wire.form.sex] ?? 'claude'">
    <div class="mb-5 flex flex-col gap-2">
        <h1>{{ t('wz.title') }}</h1>
        <p class="max-w-prose text-muted">{{ t('wz.sub') }}</p>
    </div>

    <div class="grid items-start gap-5 lg:grid-cols-[1.1fr_.9fr]">
        <form wire:submit="next" class="card" novalidate>
            {{-- Stepper: scrolls sideways on phones --}}
            <ol class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1">
                @foreach ($steps as $i => $s)
                    @php $state = $i === $index ? 'cur' : ($i < $index || in_array($s, $done, true) ? 'done' : 'todo'); @endphp
                    <li class="shrink-0">
                        <button type="button" wire:click="goTo('{{ $s }}')" @disabled($i > $furthest) @if ($state === 'cur') aria-current="step" @endif
                            class="flex items-center gap-2 rounded-full border py-1 pr-3 pl-1 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50 {{ $state === 'cur' ? 'border-ink text-ink' : 'border-line text-muted' }}">
                            <span class="grid size-6 place-items-center rounded-full font-mono text-xs {{ $state === 'cur' ? 'bg-ink text-bg' : ($state === 'done' ? 'bg-accent text-on-accent' : 'bg-surface2') }}">{{ $state === 'done' && $i !== $index ? '✓' : $i + 1 }}</span>
                            <span class="{{ $state === 'cur' ? '' : 'hidden sm:inline' }}">{{ t("st.$s") }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>

            <div class="mt-5 flex flex-col gap-1.5">
                <span class="label">{{ t('wz.step', $index + 1, count($steps)) }}</span>
                <h2>{{ t("st.$step") }}</h2>
                <p class="text-muted">{{ t("st.$step.d") }}</p>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2" wire:key="step-{{ $step }}">
                @switch($step)
                    @case('phone')
                        <label class="field sm:col-span-2">{{ t('fl.phone') }}
                            <input class="input" type="tel" wire:model.live.debounce.300ms="form.phone" inputmode="tel" autocomplete="tel" placeholder="+998 90 123 45 67" autofocus>
                            @error('form.phone')<span class="err">{{ $message }}</span>@enderror
                        </label>
                        @break
                    @case('name')
                        <label class="field">{{ t('fl.first') }}
                            <input class="input" type="text" wire:model.live.debounce.300ms="form.first" autocomplete="given-name" autofocus>
                            @error('form.first')<span class="err">{{ $message }}</span>@enderror
                        </label>
                        <label class="field">{{ t('fl.last') }}
                            <input class="input" type="text" wire:model.live.debounce.300ms="form.last" autocomplete="family-name">
                            @error('form.last')<span class="err">{{ $message }}</span>@enderror
                        </label>
                        @break
                    @case('account')
                        <label class="field sm:col-span-2">{{ t('fl.email') }}
                            <input class="input" type="email" wire:model.live.debounce.300ms="form.email" autocomplete="email" autofocus>
                            @error('form.email')<span class="err">{{ $message }}</span>@enderror
                        </label>
                        <label class="field">{{ t('fl.password') }}
                            <input class="input" type="password" wire:model="form.password" autocomplete="new-password"
                                   @if ($form->hasPasswordHash()) placeholder="••••••••" @endif>
                            @error('form.password')<span class="err">{{ $message }}</span>@enderror
                        </label>
                        <label class="field">{{ t('fl.password2') }}
                            <input class="input" type="password" wire:model="form.password_confirmation" autocomplete="new-password">
                        </label>
                        @break
                    @case('about')
                        <label class="field">{{ t('fl.dob') }}
                            <input class="input" type="date" wire:model.live.debounce.400ms="form.dob" max="{{ now()->toDateString() }}">
                            @error('form.dob')<span class="err">{{ $message }}</span>@enderror
                        </label>
                        <fieldset class="field">
                            <legend class="mb-1.5">{{ t('fl.sex') }}</legend>
                            <div class="flex gap-2">
                                @foreach (['M' => 'fl.male', 'F' => 'fl.female'] as $v => $label)
                                    <label class="flex min-h-11 flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line bg-bg px-3 font-medium has-checked:border-accent">
                                        <input type="radio" wire:model.live="form.sex" value="{{ $v }}" class="accent-[var(--accent)]"> {{ t($label) }}
                                    </label>
                                @endforeach
                            </div>
                            @error('form.sex')<span class="err">{{ $message }}</span>@enderror
                        </fieldset>
                        @break
                    @case('body')
                        <label class="field">{{ t('fl.weight') }}
                            <input class="input" type="number" wire:model.live.debounce.400ms="form.w" inputmode="decimal" step="0.1" min="10" max="300" autofocus>
                            @error('form.w')<span class="err">{{ $message }}</span>@enderror
                        </label>
                        <label class="field">{{ t('fl.height') }}
                            <input class="input" type="number" wire:model.live.debounce.400ms="form.h" inputmode="decimal" step="0.1" min="80" max="250">
                            @error('form.h')<span class="err">{{ $message }}</span>@enderror
                        </label>
                        @break
                    @case('review')
                        <dl class="kv sm:col-span-2 !mt-0">
                            <dt>{{ t('fl.phone') }}</dt><dd>{{ \App\Support\Phone::format($form->phone) }}</dd>
                            <dt>{{ t('fl.first') }}</dt><dd>{{ $form->first }}</dd>
                            <dt>{{ t('fl.last') }}</dt><dd>{{ $form->last }}</dd>
                            <dt>{{ t('fl.email') }}</dt><dd class="break-all">{{ $form->email }}</dd>
                            <dt>{{ t('fl.dob') }}</dt><dd>{{ $form->dob }}</dd>
                            <dt>{{ t('fl.sex') }}</dt><dd>{{ t($form->sex === 'F' ? 'fl.female' : 'fl.male') }}</dd>
                            <dt>{{ t('fl.weight') }}</dt><dd>{{ $form->w }}</dd>
                            <dt>{{ t('fl.height') }}</dt><dd>{{ $form->h }}</dd>
                        </dl>
                        <label class="flex items-start gap-3 rounded-lg border border-line bg-bg p-3 text-sm sm:col-span-2">
                            <input type="checkbox" wire:model.live="form.consent" class="mt-0.5 size-5 shrink-0 accent-[var(--accent)]">
                            <span>{{ t('fl.consent') }}</span>
                        </label>
                        @error('form.consent')<p class="err sm:col-span-2">{{ $message }}</p>@enderror
                        @break
                @endswitch
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-2">
                @if ($index > 0)
                    <button type="button" class="btn-ghost" wire:click="back">{{ t('wz.back') }}</button>
                @endif
                <button type="submit" class="btn flex-1 sm:flex-none" wire:loading.attr="disabled">
                    {{ $step === 'review' ? t('wz.create') : t('wz.next') }}
                </button>
                @if (app()->isLocal())
                    <button type="button" class="btn-ghost btn-sm ml-auto border-dashed" wire:click="fillExample" title="Local development only">{{ t('wz.sample') }} <span class="pill pill-warn">dev</span></button>
                @endif
                <button type="button" class="btn-ghost btn-sm {{ app()->isLocal() ? '' : 'ml-auto' }}" wire:click="clear" wire:confirm="{{ t('wz.clear') }}?">{{ t('wz.clear') }}</button>
            </div>
        </form>

        <aside class="card lg:sticky lg:top-4" aria-live="polite">
            <h3>{{ t('pv.title') }}</h3>
            <div class="mt-3">
                @if (! $p)
                    <p class="text-muted">{{ t('pv.empty') }}</p>
                @else
                    <dl class="kv !mt-0">
                        <dt>{{ t('prof.age') }}</dt><dd>{{ number_format($p['age'], 1) }}</dd>
                        <dt>{{ t('pv.expect', number_format($p['age'], 1)) }}</dt><dd>{{ round($p['median'] - 2 * $p['sd']) }} – {{ round($p['median'] + 2 * $p['sd']) }} {{ t('u.cm') }}</dd>
                        <dt><x-term name="median">{{ t('ov.median', number_format($p['age'], 1)) }}</x-term></dt><dd>{{ r1($p['median']) }} {{ t('u.cm') }}</dd>
                    </dl>
                    <p class="mt-2.5 text-sm text-muted">{{ $p['age'] < $p['endAge'] ? t('pv.grow', $p['endAge']) : t('pv.done') }}</p>
                    @if (! $p['a'])
                        <p class="note mt-3.5">{{ t('pv.need') }}</p>
                    @else
                        @php $a = $p['a']; $rec = $a->recommendedLevel()->value; @endphp
                        <hr class="my-4 border-line">
                        <div class="flex items-center justify-between gap-3">
                            <div><span class="label">{{ t('pv.you') }}</span><p class="big">{{ $a->height }} {{ t('u.cm') }}</p></div>
                            <x-pill :tone="$a->heightTone">{{ t('hs.'.$a->heightStatus) }}</x-pill>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <div><span class="label"><x-term name="bmi">{{ t('pv.bmi') }}</x-term></span><p class="big">{{ number_format($a->bmi, 1) }}</p></div>
                            <x-pill :tone="$a->bmiTone">{{ t('bs.'.$a->bmiStatus) }}</x-pill>
                        </div>
                        <dl class="kv">
                            <dt><x-term name="pct">{{ t('ov.pct') }}</x-term></dt><dd>P{{ $a->percentile }}</dd>
                            <dt>{{ t('pv.healthy') }}</dt><dd>{{ r1($a->kgLow) }} – {{ r1($a->kgHigh) }} {{ t('u.kg') }}</dd>
                        </dl>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="label">{{ t('pv.suggest') }}</span>
                            <x-pill><span class="dot dot-{{ $rec }}"></span>{{ t("lv.$rec") }}</x-pill>
                        </div>
                        @if ($a->youth)
                            <div class="mt-3">{!! \App\Support\HeightChart::svg($p['sex'], [[min(19, $a->age), $a->height]]) !!}</div>
                        @endif
                    @endif
                @endif
            </div>
            <x-glossary class="mt-4 !p-3" :terms="['who', 'bmi', 'pct', 'median', 'sd']" />
        </aside>
    </div>
</div>
