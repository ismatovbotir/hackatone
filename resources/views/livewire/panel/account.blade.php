<div class="flex max-w-2xl flex-col gap-4">
    <h1>{{ t('panel.account') }}</h1>
    <div class="card">
        <dl class="kv !mt-0">
            <dt>{{ t('admin.name') }}</dt><dd class="!font-sans">{{ $this->client->fullName() }}</dd>
            <dt>{{ t('fl.email') }}</dt><dd class="break-all">{{ $user->email ?? '—' }}</dd>
            <dt>{{ t('fl.phone') }}</dt><dd>{{ $phone }}</dd>
            <dt>{{ t('fl.dob') }}</dt><dd>{{ $this->client->birth_date->toDateString() }}</dd>
            <dt>{{ t('tab.tg') }}</dt><dd class="!font-sans">{{ $this->client->isLinked() ? t('tg.status.ok') : t('tg.status') }}</dd>
        </dl>
    </div>
    <div class="card border-bad/40">
        <h3>{{ t('panel.privacy') }}</h3>
        <p class="mt-1.5 text-muted">{{ t('del.body') }}</p>
        <button type="button" class="btn-danger mt-4 w-full sm:w-auto" wire:click="deleteData" wire:confirm="{{ t('del.title') }} {{ t('del.body') }}">{{ t('btn.reset') }}</button>
    </div>
</div>
