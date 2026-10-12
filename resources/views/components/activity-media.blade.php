{{-- Example animation for an activity. MP4 plays like a GIF: silent, looping, inline. --}}
@props(['media', 'title' => ''])
@if ($media)
    <div {{ $attributes->class('overflow-hidden rounded-xl border border-line bg-surface2') }}>
        @if ($media['type'] === 'mp4')
            <video src="{{ $media['src'] }}" class="mx-auto block max-h-80 w-full object-contain" autoplay loop muted playsinline preload="metadata" aria-label="{{ $title }}"></video>
        @else
            <img src="{{ $media['src'] }}" alt="{{ $title }}" loading="lazy" class="mx-auto block max-h-80 w-full object-contain">
        @endif
    </div>
@endif
