<div class="flex flex-col gap-4">
    @if (! $sent)
        <form wire:submit="sendCode" class="flex flex-col gap-4" novalidate>
            <p class="text-sm text-muted">{{ t('plogin.sub') }}</p>
            <label class="field">{{ t('fl.phone') }}
                <input class="input" type="tel" wire:model="phone" inputmode="tel" autocomplete="tel" placeholder="+998 90 123 45 67" autofocus>
                @error('phone')<span class="err" role="alert">{{ $message }}</span>@enderror
            </label>
            <button class="btn w-full" wire:loading.attr="disabled"><x-icon name="send" class="size-4" />{{ t('plogin.send') }}</button>
        </form>
    @else
        <form wire:submit="verify" class="flex flex-col gap-4" novalidate>
            <p class="rounded-lg bg-good-bg px-3 py-2 text-sm text-good" role="status">{{ t('plogin.sent', \App\Support\Phone::format($phone)) }}</p>
            <label class="field">{{ t('plogin.code') }}
                <input class="input text-center font-mono text-2xl tracking-[.3em]" type="text" wire:model="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="000000" autofocus>
                @error('code')<span class="err" role="alert">{{ $message }}</span>@enderror
            </label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="remember" class="size-4 accent-[var(--accent)]"> {{ t('login.remember') }}</label>
            <button class="btn w-full" wire:loading.attr="disabled">{{ t('nav.login') }}</button>
            <div class="flex flex-wrap justify-between gap-2 text-sm">
                <button type="button" class="font-semibold text-muted underline" wire:click="back">{{ t('plogin.change') }}</button>
                <button type="button" class="font-semibold text-accent underline" wire:click="sendCode">{{ t('plogin.resend') }}</button>
            </div>
        </form>
    @endif
    <p class="text-xs text-muted">{{ t('plogin.note') }}</p>
</div>
