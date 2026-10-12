<div class="grid items-start gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div class="flex flex-col gap-4 xl:col-span-2">
    <h1>{{ t('admin.settings') }}</h1>

    @if ($result)
        <div role="status" class="rounded-lg border px-4 py-3 text-sm font-semibold {{ ['good' => 'border-good bg-good-bg text-good', 'warn' => 'border-warn bg-warn-bg text-warn', 'bad' => 'border-bad bg-bad-bg text-bad'][$result[0]] }}">{{ $result[1] }}</div>
    @endif
    </div>

    <form wire:submit="save" class="card flex flex-col gap-4">
        <div>
            <h2>{{ t('set.tg') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ t('set.tg.d') }}</p>
        </div>

        <label class="field">
            <span class="flex flex-wrap items-center gap-2">{{ t('set.token') }} <x-pill :tone="$hasToken ? 'good' : 'warn'">{{ $hasToken ? t('set.token.saved') : t('set.token.none') }}</x-pill></span>
            <input class="input font-mono" type="password" wire:model="form.token" autocomplete="off" placeholder="{{ $hasToken ? t('set.token.keep') : '123456789:AA…' }}">
            @error('form.token')<span class="err">{{ $message }}</span>@enderror
        </label>

        <label class="field">{{ t('set.username') }}
            <input class="input font-mono" type="text" wire:model="form.username" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="your_bot">
            @error('form.username')<span class="err">{{ $message }}</span>@enderror
        </label>

        <label class="field">{{ t('set.secret') }}
            <div class="flex gap-2">
                <input class="input font-mono" type="text" wire:model="form.secret" autocomplete="off" placeholder="{{ t('set.secret.auto') }}">
                <button type="button" class="btn-ghost shrink-0" wire:click="generateSecret">{{ t('set.secret.gen') }}</button>
            </div>
            @error('form.secret')<span class="err">{{ $message }}</span>@enderror
        </label>
        <div class="field">{{ t('set.webhook.url') }}<code class="input break-all font-mono text-xs">{{ $webhookUrl }}</code></div>

        <label class="field">{{ t('set.admin_ids') }}
            <input class="input font-mono" type="text" wire:model="form.adminChatIds" inputmode="numeric" placeholder="123456789, 987654321">
            <span class="text-xs font-normal text-muted">{{ t('set.admin_ids.d') }}</span>
            @error('form.adminChatIds')<span class="err">{{ $message }}</span>@enderror
        </label>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <button class="btn" wire:loading.attr="disabled">{{ t('set.save') }}</button>
            <button type="button" class="btn-ghost" wire:click="testToken" wire:loading.attr="disabled">{{ t('set.test') }}</button>
            <button type="button" class="btn-ghost" wire:click="testAdminAlert" wire:loading.attr="disabled">{{ t('set.admin_ids.test') }}</button>
            <button type="button" class="btn-ghost" wire:click="setWebhook" wire:loading.attr="disabled">{{ t('set.hook.set') }}</button>
            <button type="button" class="btn-ghost" wire:click="deleteWebhook" wire:loading.attr="disabled">{{ t('set.hook.del') }}</button>
        </div>
    </form>

    <form wire:submit="saveAi" class="card flex flex-col gap-4">
        <div>
            <h2>{{ t('set.ai') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ t('set.ai.d') }}</p>
        </div>
        <label class="field">
            <span class="flex flex-wrap items-center gap-2">{{ t('set.ai.token') }} <x-pill :tone="$hasHfToken ? 'good' : 'warn'">{{ $hasHfToken ? t('set.token.saved') : t('set.token.none') }}</x-pill></span>
            <input class="input font-mono" type="password" wire:model="hfToken" autocomplete="off" placeholder="{{ $hasHfToken ? t('set.token.keep') : 'hf_…' }}">
            <span class="text-xs font-normal text-muted">{{ t('set.ai.token.d') }} <a class="underline" href="https://huggingface.co/settings/tokens/new?ownUserPermissions=inference.serverless.write&tokenType=fineGrained" target="_blank" rel="noopener noreferrer">huggingface.co/settings/tokens</a></span>
            @error('hfToken')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label class="field">{{ t('set.ai.model') }}
            <input class="input font-mono" type="text" wire:model="videoModel" autocomplete="off" spellcheck="false">
            <span class="text-xs font-normal text-muted">{{ t('set.ai.model.d') }}</span>
            @error('videoModel')<span class="err">{{ $message }}</span>@enderror
        </label>
        <div class="flex flex-col gap-2 sm:flex-row">
            <button class="btn" wire:loading.attr="disabled">{{ t('set.save') }}</button>
            <button type="button" class="btn-ghost" wire:click="testAi" wire:loading.attr="disabled">{{ t('set.test') }}</button>
        </div>
    </form>

    <div class="card">
        <h3>{{ t('set.cron') }}</h3>
        <p class="mt-1 text-sm text-muted">{{ t('set.cron.d') }}</p>
        <code class="mt-3 block overflow-x-auto rounded-lg border border-line bg-bg p-3 font-mono text-xs whitespace-pre">{{ $cron }}</code>
    </div>
</div>
