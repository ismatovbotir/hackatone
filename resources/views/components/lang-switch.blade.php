<div class="flex overflow-hidden rounded-lg border border-line bg-surface" role="group" aria-label="Language">
    @foreach (['uz' => 'UZ', 'en' => 'EN', 'ru' => 'RU'] as $code => $label)
        <a href="{{ route('locale', $code) }}" aria-pressed="{{ app()->getLocale() === $code ? 'true' : 'false' }}"
           class="px-2.5 py-1.5 text-xs font-semibold tracking-wide {{ ! $loop->first ? 'border-l border-line' : '' }} {{ app()->getLocale() === $code ? 'bg-ink text-bg' : 'text-muted' }}">{{ $label }}</a>
    @endforeach
</div>
