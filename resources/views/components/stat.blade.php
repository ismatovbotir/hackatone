@props(['label', 'value', 'unit' => null])
<div {{ $attributes->class('min-w-0') }}>
    <span class="label">{{ $label }}</span>
    <p class="big">{{ $value }}</p>
    @if ($unit)<p class="text-sm text-muted">{{ $unit }}</p>@endif
</div>
