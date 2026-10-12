{{-- Label with a tap-to-open explanation of an abbreviation, for readers who are not doctors. Tooltips do not work on touch, so this is a button. --}}
@props(['name'])
<span x-data="{ open: false }" class="relative inline-flex items-center gap-1" x-on:keydown.escape="open = false" x-on:click.outside="open = false">
    {{ $slot }}
    <button type="button" x-on:click="open = ! open" :aria-expanded="open.toString()" aria-label="{{ t('gl.more') }}: {{ t('gl.'.$name.'.t') }}"
            class="inline-grid size-[18px] shrink-0 place-items-center rounded-full border border-line align-middle font-sans text-[11px] font-bold normal-case tracking-normal text-muted hover:border-accent hover:text-accent">i</button>
    <span x-show="open" x-cloak x-transition.opacity role="tooltip"
          class="absolute top-full left-0 z-30 mt-1.5 w-[min(18rem,80vw)] rounded-lg border border-line bg-surface p-3 text-left font-sans text-sm font-normal tracking-normal text-ink normal-case shadow-lg">
        <b class="block">{{ t('gl.'.$name.'.t') }}</b>
        <span class="mt-1 block text-muted">{{ t('gl.'.$name.'.d') }}</span>
    </span>
</span>
