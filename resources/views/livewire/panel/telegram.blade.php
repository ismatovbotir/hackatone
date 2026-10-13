<div @unless ($linked) wire:poll.5s="checkLinked" @endunless>
    @if ($welcome && ! $linked)
        <div class="mb-4 rounded-lg border border-good bg-good-bg px-4 py-3 text-sm font-semibold text-good">{{ t('tg.welcome') }}</div>
    @endif

    <div class="flex flex-col gap-4">
        <div class="card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><h2>{{ t('tg.title') }}</h2><p class="mt-1.5 max-w-prose text-muted">{{ $linked ? t('tg.linked') : t('tg.sub') }}</p></div>
                <x-pill :tone="$linked ? 'good' : 'warn'">{{ $linked ? t('tg.status.ok') : t('tg.status') }}</x-pill>
            </div>

            @unless ($linked)
                <div class="mt-5 grid gap-6 md:grid-cols-[1fr_auto]">
                    <div class="flex flex-col gap-4">
                        <div x-data="{ copied: false }">
                            <span class="label">{{ t('tg.code') }}</span>
                            <p class="mt-1 font-mono text-4xl font-medium tracking-[.12em] sm:text-5xl">{{ substr($code['code'], 0, 3) }} {{ substr($code['code'], 3) }}</p>
                            <p class="mt-1 text-sm text-muted">{{ t('tg.expires', $code['expires_at']->setTimezone(config('hh.timezone'))->format('H:i')) }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="btn-ghost btn-sm" x-on:click="navigator.clipboard.writeText('{{ $code['code'] }}'); copied = true; setTimeout(() => copied = false, 2000)">
                                    <span x-show="!copied">{{ t('tg.copy') }}</span><span x-show="copied" x-cloak>{{ t('tg.copied') }}</span>
                                </button>
                                <button type="button" class="btn-ghost btn-sm" wire:click="newCode">{{ t('tg.regen') }}</button>
                            </div>
                        </div>
                        <ol class="flex list-decimal flex-col gap-1.5 pl-5 text-sm text-muted">
                            <li>{{ t('tg.s1') }}</li><li>{{ t('tg.s2') }}</li><li>{{ t('tg.s3') }}</li>
                        </ol>
                    </div>
                    <div class="flex flex-col items-start">
                        @if ($link)
                            <x-telegram-qr :link="$link" class="w-[min(240px,100%)] p-3.5" />
                            <a class="btn mt-3 w-full" href="{{ $link }}" target="_blank" rel="noopener">{{ t('tg.open') }}</a>
                        @else
                            <p class="note max-w-xs">{{ t('tg.nobot') }}</p>
                        @endif
                    </div>
                </div>
            @endunless
        </div>

        <form wire:submit="saveReminders" class="card">
            <h3>{{ t('tg.rem') }}</h3>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                @foreach (\App\Models\Client::REMINDER_KEYS as $k)
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-line bg-bg px-3 font-medium">
                        <input type="checkbox" wire:model="remindersForm.reminders.{{ $k }}" class="size-5 accent-[var(--accent)]"> {{ t('tg.r.'.$k) }}
                    </label>
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap items-end gap-3">
                <label class="field w-40">{{ t('tg.time') }}
                    <input class="input" type="time" wire:model="remindersForm.reminder_time">
                </label>
                <button class="btn">{{ t('tg.save') }}</button>
            </div>
            @error('remindersForm.reminder_time')<p class="err mt-2">{{ $message }}</p>@enderror
        </form>

        @if ($welcome && ! $linked)
            <a class="btn-ghost" href="{{ route('panel.overview') }}" wire:navigate>{{ t('tg.skip') }}</a>
            <p class="text-center text-sm text-muted">{{ t('tg.later') }}</p>
        @endif
        <p class="note">{{ t('tg.note') }}</p>
    </div>
</div>
