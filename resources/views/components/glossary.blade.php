@props(['terms' => ['who', 'bmi', 'sd', 'pct', 'median', 'kcal', 'mg']])
<details {{ $attributes->class('card group') }}>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 font-display text-lg font-semibold">
        {{ t('gl.title') }}
        <x-icon name="chev-r" class="size-4 transition group-open:rotate-90" />
    </summary>
    <dl class="mt-3 flex flex-col gap-3 text-sm">
        @foreach ($terms as $term)
            <div><dt class="font-semibold">{{ t('gl.'.$term.'.t') }}</dt><dd class="mt-0.5 text-muted">{{ t('gl.'.$term.'.d') }}</dd></div>
        @endforeach
    </dl>
</details>
