{{-- A phone-sized frame for mock screens (view only). --}}
@props(['title'])
<figure class="flex flex-col items-center gap-2">
    <div class="w-[260px] shrink-0 rounded-[2.4rem] border-[6px] border-ink bg-ink p-1 shadow-xl">
        <div class="relative flex h-[520px] flex-col overflow-hidden rounded-[2rem] bg-bg text-[13px] text-ink">
            <div class="flex items-center justify-between px-5 pt-2.5 pb-1 font-mono text-[10px] font-semibold">
                <span>9:41</span>
                <span class="absolute left-1/2 top-1.5 h-5 w-20 -translate-x-1/2 rounded-full bg-ink" aria-hidden="true"></span>
                <span>5G ▮▮▮</span>
            </div>
            <div class="flex min-h-0 flex-1 flex-col">{{ $slot }}</div>
        </div>
    </div>
    <figcaption class="text-sm font-semibold text-muted">{{ $title }}</figcaption>
</figure>
