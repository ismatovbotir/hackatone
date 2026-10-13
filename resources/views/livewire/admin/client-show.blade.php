<div class="flex flex-col gap-4">
    <a class="text-sm font-semibold text-accent underline" href="{{ route('admin.clients.index') }}" wire:navigate>← {{ t('admin.back') }}</a>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1>{{ $client->fullName() }}</h1>
        <x-pill :tone="$client->isLinked() ? 'good' : 'neutral'">{{ $client->isLinked() ? t('admin.tg.linked') : t('admin.tg.not') }}</x-pill>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="card">
            <h3>{{ t('admin.profile') }}</h3>
            <dl class="kv">
                <dt>{{ t('fl.email') }}</dt><dd class="break-all">{{ $client->user->email }}</dd>
                <dt>{{ t('admin.verified') }}</dt><dd>{{ $client->user->hasVerifiedEmail() ? t('admin.yes') : t('admin.no') }}</dd>
                <dt>{{ t('fl.phone') }}</dt><dd>{{ $phone }}</dd>
                <dt>{{ t('prof.age') }}</dt><dd>{{ number_format($a->age, 1) }}</dd>
                <dt>{{ t('fl.sex') }}</dt><dd>{{ t($client->sex->value === 'F' ? 'fl.female' : 'fl.male') }}</dd>
                <dt>{{ t('prof.level') }}</dt><dd>{{ t('lv.'.$level->value) }}</dd>
                <dt>Lang</dt><dd>{{ strtoupper($client->lang) }}</dd>
                <dt>{{ t('admin.consent') }}</dt><dd>{{ $client->consent_at->toDateString() }}</dd>
                <dt>{{ t('admin.registered') }}</dt><dd>{{ $client->created_at->toDateString() }}</dd>
            </dl>
        </div>
        <div class="card">
            <h3>{{ t('ov.height') }} · {{ t('ov.weight') }}</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                <x-pill :tone="$a->heightTone">{{ t('hs.'.$a->heightStatus) }}</x-pill>
                <x-pill :tone="$a->bmiTone">BMI {{ number_format($a->bmi, 1) }} · {{ t('bs.'.$a->bmiStatus) }}</x-pill>
            </div>
            <dl class="kv">
                <dt>{{ t('ov.pct') }}</dt><dd>P{{ $a->percentile }}</dd>
                <dt>{{ t('fd.energy') }}</dt><dd>{{ $plan->kcal }} {{ t('u.kcal') }}</dd>
                <dt>{{ t('fd.protein') }}</dt><dd>{{ $plan->protein }} g</dd>
            </dl>
            <h3 class="mt-5">{{ t('admin.cal') }}</h3>
            <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <x-stat :label="t('cal.st.done')" :value="$month['done']" class="[&_.big]:text-good" />
                <x-stat :label="t('cal.st.missed')" :value="$month['missed']" class="[&_.big]:text-bad" />
                <x-stat :label="t('cal.st.planned')" :value="$month['planned']" />
                <x-stat :label="t('mg.pages_month')" :value="$month['pages']" />
            </div>
        </div>
    </div>

    <div class="card">
        <h3>{{ t('admin.reminders') }}</h3>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach (\App\Models\Client::REMINDER_KEYS as $k)
                <x-pill :tone="$client->reminderEnabled($k) ? 'good' : 'neutral'">{{ t('tg.r.'.$k) }}</x-pill>
            @endforeach
            <x-pill class="font-mono">{{ substr($client->reminder_time, 0, 5) }}</x-pill>
        </div>
    </div>

    <div class="flex flex-col gap-2">
        <h3>{{ t('admin.history') }}</h3>
        <div class="tablewrap">
            <table class="table !min-w-[360px]">
                <thead><tr><th>{{ t('lg.date') }}</th><th class="num">{{ t('lg.h') }}</th><th class="num">{{ t('lg.w') }}</th></tr></thead>
                <tbody>
                    @foreach ($client->measurements->reverse() as $m)
                        <tr><td class="font-mono">{{ $m->measured_on->toDateString() }}</td><td class="num">{{ $m->height_cm }}</td><td class="num">{{ $m->weight_kg }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <button type="button" class="btn-danger w-full sm:w-auto" wire:click="delete" wire:confirm="{{ t('admin.delete_confirm') }}">{{ t('admin.delete') }}</button>
    </div>
</div>
