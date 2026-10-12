@props(['tone' => 'neutral'])
<span {{ $attributes->class(['pill', 'pill-'.$tone]) }}>{{ $slot }}</span>
