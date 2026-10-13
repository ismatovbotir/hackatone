@php
    $tone = ['done' => 'bg-good text-surface', 'missed' => 'bg-bad-bg text-bad', 'planned' => 'bg-surface2 text-ink', 'rest' => 'text-muted', 'none' => 'text-muted/50'];
    $pill = ['done' => 'good', 'missed' => 'bad', 'planned' => 'neutral', 'rest' => 'neutral', 'none' => 'neutral'];
    $dayNames = collect(range(0, 6))->map(fn ($i) => mb_substr(t('d.'.$i), 0, 2));
    $glassL = \App\Services\Clients\TrainingCalendar::GLASS_ML / 1000;
@endphp
<div>
    @include('livewire.panel.strip')
    <div class="grid items-start gap-4 lg:grid-cols-[1fr_300px]">
        <div class="card !p-3 sm:!p-5">
            <div class="flex items-center justify-between gap-2">
                <button type="button" class="btn-ghost btn-sm" wire:click="shift(-1)" aria-label="{{ t('cal.prev') }}"><x-icon name="chev-l" class="size-4" /></button>
                <h2 class="text-center capitalize">{{ $monthLabel }}</h2>
                <button type="button" class="btn-ghost btn-sm" wire:click="shift(1)" aria-label="{{ t('cal.next') }}"><x-icon name="chev-r" class="size-4" /></button>
            </div>

            <div class="mt-4 grid grid-cols-7 gap-1 text-center sm:gap-1.5">
                @foreach ($dayNames as $n)
                    <div class="label pb-1 !text-[11px]">{{ $n }}</div>
                @endforeach
                @foreach ($days as $d)
                    @php
                        $key = $d['date']->toDateString();
                        $log = $d['log'];
                        $ate = $log && $log->mealsEaten() >= max(1, count($menu) - 1);
                        $drank = $log && $log->water_ml >= $waterGoal * \App\Services\Clients\TrainingCalendar::GLASS_ML;
                    @endphp
                    <button type="button" wire:click="openDay('{{ $key }}')" wire:key="d-{{ $key }}"
                        aria-label="{{ $d['date']->locale(\App\Support\Tr::dateLocale())->translatedFormat('j F') }}: {{ t('cal.st.'.$d['status']) }}"
                        class="relative flex aspect-square flex-col items-center justify-center gap-0.5 rounded-lg text-sm font-semibold transition hover:ring-2 hover:ring-line
                               {{ $d['in_month'] ? '' : 'opacity-35' }} {{ $tone[$d['status']] }} {{ $key === $today ? 'outline outline-2 outline-ink' : '' }}">
                        <span class="font-mono leading-none">{{ $d['date']->day }}</span>
                        <span class="flex h-3.5 items-center gap-0.5">
                            @if ($d['status'] === 'done')
                                <x-icon name="check" class="size-3.5" />
                            @elseif ($d['status'] === 'missed')
                                <x-icon name="x" class="size-3.5" />
                            @elseif ($d['minutes'] > 0 && $d['status'] === 'planned')
                                <span class="size-1.5 rounded-full bg-accent"></span>
                            @endif
                            @if ($ate)<span class="size-1.5 rounded-full bg-mid" title="{{ t('cal.food') }}"></span>@endif
                            @if ($drank)<span class="size-1.5 rounded-full bg-easy" title="{{ t('cal.water') }}"></span>@endif
                            @if ($log && $log->pages_read > 0)<span class="size-1.5 rounded-full bg-strong" title="{{ t('cal.reading') }}"></span>@endif
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-muted">
                <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-good"></span>{{ t('cal.st.done') }}</span>
                <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-bad-bg ring-1 ring-bad"></span>{{ t('cal.st.missed') }}</span>
                <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-surface2 ring-1 ring-line"></span>{{ t('cal.st.planned') }}</span>
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-mid"></span>{{ t('cal.food') }}</span>
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-easy"></span>{{ t('cal.water') }}</span>
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-strong"></span>{{ t('cal.reading') }}</span>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="card grid grid-cols-3 gap-3 lg:grid-cols-1">
                <x-stat :label="t('cal.st.done')" :value="$summary['done']" class="[&_.big]:text-good" />
                <x-stat :label="t('cal.st.missed')" :value="$summary['missed']" class="[&_.big]:text-bad" />
                <x-stat :label="t('cal.streak')" :value="$streak" />
            </div>
            <div class="card flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-strong-bg text-strong"><x-icon name="book" class="size-5" /></span>
                <x-stat :label="t('mg.pages_month')" :value="$summary['pages']" :unit="t('mg.days', $summary['reading_days'])" />
            </div>
            @include('livewire.panel.rating', ['compact' => true])
            <p class="text-sm text-muted">{{ t('cal.sub') }} {{ t('cal.level') }}</p>
        </div>
    </div>

    {{-- Day modal: bottom sheet on phones, centred dialog from sm up. --}}
    @if ($selected)
        @php $s = $selected; $editable = $s['editable']; @endphp
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-ink/40 sm:items-center sm:p-4" wire:key="modal-{{ $open }}"
             x-data x-init="$nextTick(() => $el.querySelector('[data-autofocus]')?.focus())" x-on:keydown.escape.window="$wire.close()">
            <div class="absolute inset-0" wire:click="close" aria-hidden="true"></div>
            <form wire:submit="saveDay" role="dialog" aria-modal="true" aria-labelledby="day-title"
                  class="relative flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-2xl bg-surface shadow-2xl sm:max-w-lg sm:rounded-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-line px-5 py-4">
                    <div>
                        <span class="label">{{ $s['date']->toDateString() === $today ? t('cal.today') : '' }}</span>
                        <h2 id="day-title" class="capitalize">{{ $s['date']->locale(\App\Support\Tr::dateLocale())->translatedFormat('l, j F') }}</h2>
                    </div>
                    <button type="button" class="btn-ghost btn-sm" wire:click="close" aria-label="{{ t('del.cancel') }}"><x-icon name="x" class="size-4" /></button>
                </div>

                <div class="flex flex-col gap-5 overflow-y-auto px-5 py-4">
                    {{-- The client's own plan: today and future days. Open for future days, folded for today. --}}
                    @if ($s['plannable'])
                        <details class="group rounded-xl border border-line bg-bg p-3" @if (! $editable) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 font-semibold">
                                <span class="flex items-center gap-2"><x-icon name="calendar" class="size-5 text-accent" />{{ t('plan.title') }}</span>
                                <span class="flex items-center gap-2">
                                    @if ($s['custom'])<x-pill tone="warn">{{ t('plan.changed') }}</x-pill>@endif
                                    <x-icon name="chev-r" class="size-4 transition group-open:rotate-90" />
                                </span>
                            </summary>
                            <div class="mt-3 flex flex-col gap-3">
                                <div class="grid grid-cols-[1fr_7rem] gap-3">
                                    <label class="field">{{ t('tr.session') }}
                                        <select class="input" wire:model.live="plan.session">
                                            @foreach (\App\Livewire\Forms\DayPlanForm::SESSIONS as $type)
                                                <option value="{{ $type }}">{{ t('ty.'.$type) }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="field">{{ t('tr.mins') }}
                                        <input class="input" type="number" min="10" max="180" inputmode="numeric" wire:model="plan.minutes" @disabled($plan->session === 'rest')>
                                    </label>
                                </div>
                                @error('plan.minutes')<p class="err">{{ $message }}</p>@enderror
                                <label class="field">{{ t('plan.reading_goal') }}
                                    <input class="input" type="number" min="1" max="500" inputmode="numeric" placeholder="20" wire:model="plan.reading_goal">
                                </label>
                                @error('plan.reading_goal')<p class="err">{{ $message }}</p>@enderror
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm" wire:click="savePlan">{{ t('plan.save') }}</button>
                                    @if ($s['custom'])
                                        <button type="button" class="btn-ghost btn-sm" wire:click="resetPlan">{{ t('plan.reset') }}</button>
                                    @endif
                                </div>
                                <p class="text-xs text-muted">{{ t('plan.note') }}</p>
                            </div>
                        </details>
                    @endif

                    @if ($s['telegram_locked'] ?? false)
                        <p class="note">{{ t('cal.tg_today_only') }}</p>
                    @elseif (! $editable)
                        <p class="note">{{ t('cal.future') }}</p>
                    @endif

                    @if ($editable)
                    {{-- Mental growth: reading comes first --}}
                    <section class="flex flex-col gap-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="flex items-center gap-2"><x-icon name="book" class="size-5 text-strong" />{{ t('cal.reading') }}</h3>
                            @if ($s['reading_goal'])
                                <span class="font-mono text-sm {{ ($day->pages_read ?? 0) >= $s['reading_goal'] ? 'text-good' : 'text-muted' }}">{{ (int) $day->pages_read }} / {{ $s['reading_goal'] }}</span>
                            @endif
                        </div>
                        <label class="field">{{ t('cal.pages') }}
                            <input class="input text-lg" type="number" min="0" max="2000" inputmode="numeric" placeholder="0"
                                   wire:model="day.pages_read" @disabled(! $editable) data-autofocus>
                        </label>
                        @error('day.pages_read')<p class="err">{{ $message }}</p>@enderror
                        <p class="text-xs text-muted">{{ t('cal.pages_hint', \App\Services\Clients\Points::PAGES_PER_POINT, \App\Services\Clients\Points::MAX_READING) }}</p>
                    </section>

                    {{-- Training --}}
                    <section class="flex flex-col gap-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="flex items-center gap-2"><x-icon name="run" class="size-5 text-accent" />{{ $s['minutes'] ? t('ty.'.$s['session']) : t('ty.rest') }}</h3>
                            <x-pill :tone="$pill[$s['status']]">{{ t('cal.st.'.$s['status']) }}</x-pill>
                        </div>
                        @if ($s['minutes'])
                            <p class="text-sm text-muted">{{ t('cal.planned', $s['minutes']) }} · {{ t('lv.'.$this->level->value) }}</p>
                            @if ($videos[$s['session']] ?? null)
                                <x-activity-media :media="$videos[$s['session']]" :title="t('ty.'.$s['session'])" />
                            @endif
                            <div class="grid grid-cols-[1fr_auto] items-end gap-3">
                                <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-line bg-bg px-3 font-medium has-checked:border-good has-checked:bg-good-bg">
                                    <input type="checkbox" wire:model.live="day.workout_done" class="size-5 accent-[var(--good)]" @disabled(! $editable)> {{ t('cal.did') }}
                                </label>
                                <label class="field w-28">{{ t('tr.mins') }}
                                    <input class="input" type="number" min="0" max="600" inputmode="numeric" wire:model="day.workout_minutes" @disabled(! $editable || ! $day->workout_done)>
                                </label>
                            </div>
                            @error('day.workout_minutes')<p class="err">{{ $message }}</p>@enderror
                        @else
                            <p class="text-sm text-muted">{{ t('dx.rest') }}</p>
                        @endif
                    </section>

                    {{-- Food --}}
                    <section class="flex flex-col gap-2.5">
                        <h3 class="flex items-center gap-2"><x-icon name="food" class="size-5 text-mid" />{{ t('cal.food') }}</h3>
                        <div class="flex flex-col gap-1.5">
                            @foreach ($menu as $i => $meal)
                                <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-line bg-bg px-3 has-checked:border-mid has-checked:bg-mid-bg">
                                    <input type="checkbox" wire:model="day.meals.{{ $i }}" class="size-5 shrink-0 accent-[var(--mid)]" @disabled(! $editable)>
                                    <span class="min-w-0 flex-1">
                                        <b class="block text-sm">{{ t('m.'.$meal['meal']) }}</b>
                                        <span class="block truncate text-xs text-muted">{{ collect($meal['items'])->map(fn ($it) => t('f.'.$it['food']))->implode(', ') }}</span>
                                    </span>
                                    <span class="shrink-0 font-mono text-xs text-muted">{{ $meal['kcal'] }} {{ t('u.kcal') }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    {{-- Water --}}
                    <section class="flex flex-col gap-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="flex items-center gap-2"><svg class="size-5 text-easy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"/></svg>{{ t('cal.water') }}</h3>
                            <span class="font-mono text-sm">{{ $day->glasses * $glassL }} / {{ $waterGoal * $glassL }} {{ t('u.l') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" class="btn-ghost size-11 !p-0 text-xl" wire:click="$set('day.glasses', {{ max(0, $day->glasses - 1) }})" @disabled(! $editable || $day->glasses === 0) aria-label="−1">−</button>
                            <div class="flex flex-1 flex-wrap gap-1" aria-hidden="true">
                                @for ($g = 1; $g <= max($waterGoal, $day->glasses); $g++)
                                    <span class="h-6 w-4 rounded-b-md border-2 {{ $g <= $day->glasses ? 'border-easy bg-easy' : 'border-line' }}"></span>
                                @endfor
                            </div>
                            <button type="button" class="btn-ghost size-11 !p-0 text-xl" wire:click="$set('day.glasses', {{ min(40, $day->glasses + 1) }})" @disabled(! $editable) aria-label="+1">+</button>
                        </div>
                        <p class="text-xs text-muted">{{ t('cal.glass', \App\Services\Clients\TrainingCalendar::GLASS_ML) }}</p>
                    </section>
                    @endif
                </div>

                <div class="flex gap-2 border-t border-line px-5 py-3">
                    <button type="button" class="btn-ghost flex-1" wire:click="close">{{ t('del.cancel') }}</button>
                    @if ($editable)
                        <button class="btn flex-1" wire:loading.attr="disabled">{{ t('cal.save') }}</button>
                    @endif
                </div>
            </form>
        </div>
    @endif
</div>
