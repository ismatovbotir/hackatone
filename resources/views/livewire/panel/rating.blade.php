{{-- The client's own place in the points rating. Never shows the list or other people's names.
     $compact: the Calendar side column version (no streak, which that column already shows). --}}
@php
    $compact ??= false;
    $r = $this->rating;
    $perDay = \App\Services\Clients\Points::TRAINING_DONE + \App\Services\Clients\Points::FULL_MINUTES_BONUS + \App\Services\Clients\Points::PER_MEAL * \App\Services\Clients\Points::MAX_MEALS + \App\Services\Clients\Points::WATER_GOAL;
@endphp
<section class="card flex flex-col gap-3" aria-labelledby="rating-title-{{ $compact ? 'c' : 'f' }}">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 id="rating-title-{{ $compact ? 'c' : 'f' }}" class="flex items-center gap-2"><x-icon name="trophy" class="size-5 text-accent" />{{ t('lb.mine') }}</h3>
        <div class="flex gap-1 rounded-lg border border-line bg-surface p-0.5" role="group" aria-label="{{ t('lb.period') }}">
            @foreach (\App\Services\Clients\Leaderboard::PERIODS as $p)
                <button type="button" wire:click="setRatingPeriod('{{ $p }}')" aria-pressed="{{ $this->ratingPeriod === $p ? 'true' : 'false' }}"
                        class="min-h-8 rounded-md px-2.5 text-xs font-semibold {{ $this->ratingPeriod === $p ? 'bg-ink text-bg' : 'text-muted' }}">{{ t('lb.p.'.$p) }}</button>
            @endforeach
        </div>
    </div>

    <div wire:loading.class="opacity-60" wire:target="setRatingPeriod" class="flex flex-col gap-3">
        @if ($r['rank'] === null)
            <p class="note">{{ t('lb.join') }}</p>
            @unless ($compact)
                <a class="btn-ghost btn-sm self-start" href="{{ route('panel.calendar') }}" wire:navigate><x-icon name="calendar" class="size-4" />{{ t('tab.cal') }}</a>
            @endunless
        @else
            <div @class(['grid gap-3', 'grid-cols-3' => ! $compact, 'grid-cols-2' => $compact])>
                <div class="min-w-0">
                    <span class="label">{{ t('lb.rank') }}</span>
                    <p class="flex items-baseline gap-1.5">
                        <span class="big" aria-hidden="true">#{{ $r['rank'] }}</span>
                        <span class="text-sm text-muted" aria-hidden="true">{{ t('lb.of', $r['of']) }}</span>
                        <span class="sr-only">{{ t('lb.rankof', $r['rank'], $r['of']) }}</span>
                    </p>
                </div>
                <x-stat :label="t('lb.points')" :value="$r['points']" />
                @unless ($compact)
                    <div class="min-w-0">
                        <span class="label">{{ t('cal.streak') }}</span>
                        <p class="flex items-center gap-1.5"><x-icon name="flame" class="size-6 {{ $r['streak'] ? 'text-accent' : 'text-muted' }}" /><span class="big">{{ $r['streak'] }}</span></p>
                    </div>
                @endunless
            </div>

            @if ($r['to_next'] !== null)
                <p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="chev-r" class="size-4 -rotate-90 text-accent" />{{ tn('lb.to_next', $r['to_next']) }}</p>
            @elseif ($r['rank'] === 1)
                <p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="star" class="size-4 text-accent" />{{ t('lb.first') }}</p>
            @endif
        @endif
    </div>

    @if ($compact)
        <p class="text-xs text-muted">{{ t('lb.how_short', $perDay) }}</p>
    @else
        <p class="text-sm text-muted">{{ t('lb.how', \App\Services\Clients\Points::TRAINING_DONE, \App\Services\Clients\Points::FULL_MINUTES_BONUS, \App\Services\Clients\Points::PER_MEAL, \App\Services\Clients\Points::MAX_MEALS, \App\Services\Clients\Points::WATER_GOAL, $perDay) }} {{ t('lb.streak_note') }} {{ t('lb.private') }}</p>
    @endif
</section>
