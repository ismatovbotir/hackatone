@php
    // Sample data only: a demo pupil, not a real user.
    $name = 'Aziz';
    $days = collect(range(1, 31));
    $status = fn ($d) => match (true) { in_array($d, [3, 10, 17]) => 'rest', in_array($d, [6, 12]) => 'missed', $d < 14 => 'done', $d === 14 => 'today', default => 'planned' };
    $tone = ['done' => 'bg-good text-surface', 'missed' => 'bg-bad-bg text-bad', 'rest' => 'text-muted', 'today' => 'bg-surface2 outline outline-2 outline-ink', 'planned' => 'bg-surface2'];
@endphp
<div class="flex flex-col gap-5" x-data="{ scheme: 'green' }">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1>{{ t('apps.title') }}</h1>
            <p class="mt-1.5 max-w-prose text-muted">{{ t('apps.sub') }}</p>
        </div>
        <div class="grid grid-cols-2 gap-1 rounded-lg border border-line bg-surface p-1" role="group" aria-label="{{ t('apps.scheme') }}">
            <button type="button" x-on:click="scheme = 'green'" :aria-pressed="(scheme === 'green').toString()" :class="scheme === 'green' ? 'bg-ink text-bg' : 'text-muted'" class="rounded-md px-3 py-1.5 text-sm font-semibold">{{ t('apps.boys') }}</button>
            <button type="button" x-on:click="scheme = 'pink'" :aria-pressed="(scheme === 'pink').toString()" :class="scheme === 'pink' ? 'bg-ink text-bg' : 'text-muted'" class="rounded-md px-3 py-1.5 text-sm font-semibold">{{ t('apps.girls') }}</button>
        </div>
    </div>
    <p class="note">{{ t('apps.sample') }}</p>

    {{-- The colour scheme applies to the phones only (data-theme on this wrapper). --}}
    <div :data-theme="scheme" class="-mx-4 flex snap-x gap-6 overflow-x-auto px-4 pb-4 lg:mx-0 lg:grid lg:grid-cols-[repeat(auto-fill,minmax(260px,1fr))] lg:overflow-visible lg:px-0 [&>*]:snap-center">

        {{-- 1. Welcome --}}
        <x-phone-frame :title="t('apps.s.welcome')">
            <div class="flex flex-1 flex-col items-center justify-center gap-4 px-6 text-center">
                <span class="grid size-16 place-items-center rounded-2xl bg-accent text-on-accent"><x-icon name="run" class="size-9" /></span>
                <div>
                    <p class="font-display text-xl font-bold">HackaTone Health</p>
                    <p class="mt-1 text-muted">{{ t('apps.welcome_d') }}</p>
                </div>
            </div>
            <div class="flex flex-col gap-2 px-5 pb-8">
                <span class="flex items-center justify-center gap-2 rounded-xl bg-[#2AABEE] py-3 font-semibold text-white"><x-icon name="send" class="size-4" />{{ t('apps.tg_signin') }}</span>
                <span class="rounded-xl border border-line py-3 text-center font-semibold">{{ t('plogin.tab') }} · {{ t('fl.phone') }}</span>
                <span class="text-center text-[11px] text-muted">{{ t('nav.start') }}</span>
            </div>
        </x-phone-frame>

        {{-- 2. Today --}}
        <x-phone-frame :title="t('apps.s.today')">
            <div class="flex flex-col gap-3 overflow-hidden px-4 pt-2">
                <div class="flex items-center justify-between">
                    <div><p class="text-[11px] text-muted">{{ t('cal.today') }}</p><p class="font-display text-lg font-bold">{{ t('apps.hello', $name) }}</p></div>
                    <span class="grid size-9 place-items-center rounded-full bg-surface2 font-bold">A</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="rounded-xl bg-accent p-2 text-on-accent"><p class="text-[10px] opacity-80">{{ t('apps.points_today') }}</p><p class="font-mono text-lg font-bold">23</p></div>
                    <div class="rounded-xl bg-surface p-2"><p class="text-[10px] text-muted">{{ t('cal.streak') }}</p><p class="font-mono text-lg font-bold">🔥5</p></div>
                    <div class="rounded-xl bg-surface p-2"><p class="text-[10px] text-muted">{{ t('apps.tab.rating') }}</p><p class="font-mono text-lg font-bold">#3</p></div>
                </div>
                <p class="label !text-[10px]">{{ t('apps.tasks') }}</p>
                @foreach ([['book', 'text-strong', t('cal.reading'), '12 / 20', 60], ['run', 'text-accent', t('ty.cardio').' · 40 '.t('u.min'), '✓', 100], ['food', 'text-mid', t('cal.food'), '3 / 5', 60], ['calendar', 'text-easy', t('cal.water'), '1.0 / 1.9 '.t('u.l'), 52]] as [$icon, $color, $label, $value, $pct])
                    <div class="rounded-xl bg-surface p-2.5">
                        <div class="flex items-center justify-between gap-2"><span class="flex items-center gap-2 font-semibold"><x-icon :name="$icon" class="size-4 {{ $color }}" />{{ $label }}</span><span class="font-mono text-[11px] text-muted">{{ $value }}</span></div>
                        <div class="mt-1.5 h-1.5 rounded-full bg-surface2"><div class="h-1.5 rounded-full bg-accent" style="width: {{ $pct }}%"></div></div>
                    </div>
                @endforeach
            </div>
            <span class="absolute right-4 bottom-20 grid size-12 place-items-center rounded-full bg-accent text-2xl text-on-accent shadow-lg" aria-hidden="true">+</span>
            <x-phone-tabs active="today" />
        </x-phone-frame>

        {{-- 3. Log the day --}}
        <x-phone-frame :title="t('apps.s.log')">
            <div class="flex-1 bg-ink/40"></div>
            <div class="-mt-48 flex flex-col gap-3 rounded-t-2xl bg-surface px-4 pt-3 pb-5 shadow-2xl">
                <span class="mx-auto h-1 w-10 rounded-full bg-line"></span>
                <p class="font-display text-base font-bold">{{ t('cal.today') }}</p>
                <div>
                    <p class="flex items-center gap-1.5 font-semibold"><x-icon name="book" class="size-4 text-strong" />{{ t('cal.pages') }}</p>
                    <div class="mt-1 rounded-lg border-2 border-accent bg-bg px-3 py-2 font-mono text-lg">12</div>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-good bg-good-bg px-3 py-2"><span class="font-semibold">{{ t('cal.did') }}</span><span class="text-good">✓</span></div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (['breakfast', 'snack', 'lunch', 'snack', 'dinner'] as $i => $meal)
                        <span class="rounded-full px-2 py-1 text-[11px] font-semibold {{ $i < 3 ? 'bg-mid-bg text-mid' : 'bg-surface2 text-muted' }}">{{ t('m.'.$meal) }}</span>
                    @endforeach
                </div>
                <div class="flex items-center gap-2"><span class="grid size-8 place-items-center rounded-lg border border-line">−</span><span class="flex flex-1 gap-1">@for ($g = 0; $g < 8; $g++)<span class="h-5 flex-1 rounded-b border-2 {{ $g < 4 ? 'border-easy bg-easy' : 'border-line' }}"></span>@endfor</span><span class="grid size-8 place-items-center rounded-lg border border-line">+</span></div>
                <span class="rounded-xl bg-accent py-2.5 text-center font-semibold text-on-accent">{{ t('cal.save') }}</span>
            </div>
        </x-phone-frame>

        {{-- 4. Calendar --}}
        <x-phone-frame :title="t('apps.s.calendar')">
            <div class="flex flex-col gap-3 px-4 pt-2">
                <p class="text-center font-display text-base font-bold">{{ now()->locale(\App\Support\Tr::dateLocale())->translatedFormat('F Y') }}</p>
                <div class="grid grid-cols-7 gap-1 text-center text-[11px]">
                    @foreach (range(0, 6) as $i)<span class="text-[9px] font-semibold text-muted">{{ mb_substr(t('d.'.$i), 0, 2) }}</span>@endforeach
                    @foreach ($days as $d)
                        <span class="flex aspect-square flex-col items-center justify-center rounded-md font-semibold {{ $tone[$status($d)] }}">
                            {{ $d }}
                            @if ($status($d) === 'done' && $d % 2 === 0)<span class="size-1 rounded-full bg-strong"></span>@endif
                        </span>
                    @endforeach
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-surface p-2"><p class="font-mono text-lg font-bold text-good">10</p><p class="text-[10px] text-muted">{{ t('cal.st.done') }}</p></div>
                    <div class="rounded-xl bg-surface p-2"><p class="font-mono text-lg font-bold text-bad">2</p><p class="text-[10px] text-muted">{{ t('cal.st.missed') }}</p></div>
                    <div class="rounded-xl bg-surface p-2"><p class="font-mono text-lg font-bold">186</p><p class="text-[10px] text-muted">{{ t('apps.pages') }}</p></div>
                </div>
            </div>
            <x-phone-tabs active="calendar" />
        </x-phone-frame>

        {{-- 5. Growth --}}
        <x-phone-frame :title="t('apps.s.growth')">
            <div class="flex flex-col gap-3 overflow-hidden px-4 pt-2">
                <p class="font-display text-base font-bold">{{ t('ch.title') }}</p>
                <div class="rounded-xl bg-surface p-2 [&_text]:!text-[9px]">{!! \App\Support\HeightChart::svg(\App\Enums\Sex::Male, [[13, 152], [13.5, 155], [14, 158.5]]) !!}</div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-surface p-2.5"><p class="text-[10px] text-muted">{{ t('ov.height') }}</p><p class="font-mono text-lg font-bold">158.5</p><span class="pill pill-good">{{ t('hs.typ') }}</span></div>
                    <div class="rounded-xl bg-surface p-2.5"><p class="text-[10px] text-muted">{{ t('prof.bmi') }}</p><p class="font-mono text-lg font-bold">19.1</p><span class="pill pill-good">{{ t('bs.ok') }}</span></div>
                </div>
            </div>
            <x-phone-tabs active="profile" />
        </x-phone-frame>

        {{-- 6. Rating --}}
        <x-phone-frame :title="t('apps.s.rating')">
            <div class="flex flex-col gap-3 px-4 pt-2">
                <div class="flex flex-col items-center gap-1 rounded-2xl bg-accent px-3 py-5 text-on-accent">
                    <x-icon name="star" class="size-8" />
                    <p class="font-mono text-4xl font-bold">#3</p>
                    <p class="text-[11px] opacity-90">{{ t('apps.of', 54) }}</p>
                </div>
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="rounded-xl bg-surface p-2"><p class="font-mono text-lg font-bold">412</p><p class="text-[10px] text-muted">{{ t('apps.points_month') }}</p></div>
                    <div class="rounded-xl bg-surface p-2"><p class="font-mono text-lg font-bold">🔥5</p><p class="text-[10px] text-muted">{{ t('cal.streak') }}</p></div>
                </div>
                <p class="rounded-xl bg-surface p-2.5 text-[11px]">{{ t('apps.to_next', 18) }}</p>
                <p class="text-[10px] text-muted">{{ t('apps.private') }}</p>
            </div>
            <x-phone-tabs active="rating" />
        </x-phone-frame>
    </div>

    {{-- Roadmap --}}
    <section class="card">
        <h2>{{ t('apps.roadmap') }}</h2>
        <ol class="mt-3 grid gap-3 md:grid-cols-3">
            @foreach ([['done', 'good', 'apps.r1'], ['next', 'warn', 'apps.r2'], ['later', 'neutral', 'apps.r3']] as $i => [$state, $toneName, $key])
                <li class="flex flex-col gap-1.5 rounded-xl border border-line p-3">
                    <div class="flex items-center justify-between gap-2"><b>{{ $i + 1 }}. {{ t($key) }}</b><x-pill :tone="$toneName">{{ t('apps.state.'.$state) }}</x-pill></div>
                    <p class="text-sm text-muted">{{ t($key.'.d') }}</p>
                </li>
            @endforeach
        </ol>
    </section>
</div>
