<div class="py-2" wire:poll.4s="check">
    <div class="mb-5 flex flex-col gap-2">
        <h1>{{ t('confirm.title') }}</h1>
        <p class="max-w-prose text-muted">{{ t('confirm.sub') }}</p>
    </div>

    {{-- Live status: the page checks every few seconds and moves on by itself. --}}
    <div class="mb-5 flex items-center gap-4 overflow-hidden rounded-xl border border-accent/40 bg-good-bg px-4 py-3.5 text-good" role="status" aria-live="polite">
        <span class="relative grid size-10 shrink-0 place-items-center">
            <span class="absolute inset-0 animate-ping rounded-full bg-accent/30 motion-reduce:animate-none"></span>
            <span class="absolute inset-1.5 animate-ping rounded-full bg-accent/25 [animation-delay:.5s] motion-reduce:animate-none"></span>
            <span class="relative size-4 rounded-full bg-accent"></span>
        </span>
        <p class="font-semibold">{{ t('confirm.waiting_all') }}
            <span class="inline-flex gap-0.5 align-middle motion-reduce:hidden" aria-hidden="true">
                <span class="size-1.5 animate-bounce rounded-full bg-current"></span>
                <span class="size-1.5 animate-bounce rounded-full bg-current [animation-delay:.15s]"></span>
                <span class="size-1.5 animate-bounce rounded-full bg-current [animation-delay:.3s]"></span>
            </span>
        </p>
    </div>

    <div class="grid items-stretch gap-4 md:grid-cols-[1fr_auto_1fr]">
        {{-- Option 1: Telegram (fastest on a phone) --}}
        <section class="card flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-easy-bg text-easy"><x-icon name="send" /></span>
                <div><h2 class="text-lg sm:text-xl">{{ t('confirm.tg.t') }}</h2><p class="text-sm text-muted">{{ t('confirm.tg.d') }}</p></div>
            </div>
            @if ($code)
                <div x-data="{ copied: false }">
                    <span class="label">{{ t('tg.code') }}</span>
                    <p class="mt-1 font-mono text-4xl font-medium tracking-[.12em]">{{ substr($code['code'], 0, 3) }} {{ substr($code['code'], 3) }}</p>
                    <p class="mt-1 text-sm text-muted">{{ t('tg.expires', $code['expires_at']->setTimezone(config('hh.timezone'))->format('H:i')) }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" class="btn-ghost btn-sm" x-on:click="navigator.clipboard.writeText('{{ $code['code'] }}'); copied = true; setTimeout(() => copied = false, 2000)">
                            <span x-show="!copied">{{ t('tg.copy') }}</span><span x-show="copied" x-cloak>{{ t('tg.copied') }}</span>
                        </button>
                        <button type="button" class="btn-ghost btn-sm" wire:click="newCode">{{ t('tg.regen') }}</button>
                    </div>
                </div>
                @if ($link)
                    <x-telegram-qr :link="$link" class="w-[min(220px,100%)] p-3" />
                    <a class="btn w-full" href="{{ $link }}" target="_blank" rel="noopener">{{ t('tg.open') }}</a>
                @else
                    <p class="note">{{ t('tg.nobot') }}</p>
                @endif
                <ol class="flex list-decimal flex-col gap-1 pl-5 text-sm text-muted">
                    <li>{{ t('tg.s1') }}</li><li>{{ t('tg.s2') }}</li><li>{{ t('tg.s3') }}</li>
                </ol>
            @endif
        </section>

        <div class="flex items-center justify-center gap-3 text-sm font-semibold text-muted md:flex-col">
            <span class="h-px flex-1 bg-line md:h-auto md:w-px"></span>{{ t('confirm.or') }}<span class="h-px flex-1 bg-line md:h-auto md:w-px"></span>
        </div>

        {{-- Option 2: email link --}}
        <section class="card flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-good-bg text-good">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16v12H4z M4 7l8 6 8-6"/></svg>
                </span>
                <div><h2 class="text-lg sm:text-xl">{{ t('confirm.email.t') }}</h2><p class="text-sm text-muted">{{ t('verify.body', $email) }}</p></div>
            </div>
            @if ($sent)
                <p class="rounded-lg bg-good-bg px-3 py-2 text-sm font-semibold text-good" role="status">{{ t('verify.sent') }}</p>
            @endif
            <button type="button" class="btn-ghost mt-auto" wire:click="resend" wire:loading.attr="disabled">{{ t('verify.resend') }}</button>
        </section>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-6">@csrf
        <button class="text-sm font-semibold text-muted underline">{{ t('nav.logout') }}</button>
    </form>
</div>
