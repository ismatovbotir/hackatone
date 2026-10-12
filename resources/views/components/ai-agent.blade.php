{{-- Placeholder for the future AI assistant: greets and says it is still in training. No data is sent anywhere. --}}
<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="fixed right-4 bottom-4 z-30 flex flex-col items-end gap-3 print:hidden">
    <div x-show="open" x-cloak x-transition.origin.bottom.right role="dialog" aria-labelledby="ai-title"
         class="w-[min(20rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-line bg-surface shadow-2xl">
        <div class="flex items-center justify-between gap-2 bg-accent px-4 py-3 text-on-accent">
            <p id="ai-title" class="flex items-center gap-2 font-display font-semibold">
                <x-icon name="bot" class="size-5" />{{ t('ai.name') }}
            </p>
            <button type="button" x-on:click="open = false" class="rounded-md p-1 hover:bg-white/15" aria-label="{{ t('ai.close') }}"><x-icon name="x" class="size-4" /></button>
        </div>
        <div class="flex flex-col gap-2 p-4 text-sm">
            <p class="self-start rounded-2xl rounded-tl-sm bg-surface2 px-3.5 py-2.5">{{ t('ai.hello') }} 👋</p>
            <p class="self-start rounded-2xl rounded-tl-sm bg-surface2 px-3.5 py-2.5">{{ t('ai.soon') }}</p>
            <p class="mt-1 flex items-center gap-2 text-xs text-muted">
                <span class="inline-flex gap-0.5 motion-reduce:hidden" aria-hidden="true">
                    <span class="size-1.5 animate-bounce rounded-full bg-accent"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-accent [animation-delay:.15s]"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-accent [animation-delay:.3s]"></span>
                </span>
                {{ t('ai.training') }}
            </p>
        </div>
    </div>
    <button type="button" x-on:click="open = ! open" :aria-expanded="open.toString()" aria-label="{{ t('ai.name') }}"
            class="relative grid size-14 place-items-center rounded-full bg-accent text-on-accent shadow-lg transition hover:scale-105 focus-visible:scale-105">
        <span class="absolute inset-0 animate-ping rounded-full bg-accent/30 motion-reduce:animate-none" x-show="! open"></span>
        <x-icon name="bot" class="relative size-7" />
    </button>
</div>
