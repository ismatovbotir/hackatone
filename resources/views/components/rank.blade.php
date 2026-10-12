{{-- Leaderboard place. Places 1-3 get a filled badge, the rest an outline (shape, not colour alone). --}}
@props(['rank'])
<span {{ $attributes->class('inline-flex shrink-0') }}>
    <span aria-hidden="true" @class([
        'inline-flex size-8 items-center justify-center rounded-full font-mono text-sm',
        'bg-accent font-semibold text-on-accent' => $rank <= 3,
        'border border-line text-muted' => $rank > 3,
    ])>{{ $rank }}</span>
    <span class="sr-only">{{ t('lb.place', $rank) }}</span>
</span>
