@php $lv = $this->level->value; @endphp
<div>
    @include('livewire.panel.strip')
    <div class="flex flex-col gap-4">
        <div class="card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><span class="label">{{ t("lvtag.$lv") }}</span><h2>{{ t('tr.title', t("lv.$lv")) }}</h2></div>
                <div class="flex flex-wrap gap-2"><x-pill class="font-mono">{{ t('tr.min', $t['total']) }}</x-pill><x-pill class="font-mono">{{ t('tr.sessions', $t['sessions']) }}</x-pill></div>
            </div>
            <p class="mt-3 text-sm text-muted"><x-pill :tone="$t['whoTone']" class="mr-1.5">{{ t('tr.who') }}</x-pill>{{ $t['who'] }}</p>
        </div>

        {{-- Phones: one card per day. Wider screens: a table. --}}
        <ul class="flex flex-col gap-2 md:hidden">
            @foreach ($t['days'] as $d)
                <li class="card !p-4">
                    <div class="flex items-center justify-between gap-2"><b>{{ $d['day'] }}</b><span class="font-mono text-sm">{{ $d['minutes'] ?: '-' }} {{ $d['minutes'] ? t('u.min') : '' }}</span></div>
                    <p class="mt-0.5 font-semibold text-accent">{{ $d['type'] }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $d['detail'] }}</p>
                    @if ($d['video'])
                        <details class="group mt-2">
                            <summary class="btn-ghost btn-sm cursor-pointer list-none"><x-icon name="video" class="size-4" />{{ t('vid.watch') }}</summary>
                            <x-activity-media class="mt-2" :media="$d['video']" :title="$d['type']" />
                        </details>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="tablewrap hidden md:block">
            <table class="table">
                <thead><tr><th>{{ t('tr.day') }}</th><th>{{ t('tr.session') }}</th><th class="num">{{ t('tr.mins') }}</th><th>{{ t('tr.todo') }}</th></tr></thead>
                <tbody>
                    @foreach ($t['days'] as $d)
                        <tr><td><b>{{ $d['day'] }}</b></td><td>{{ $d['type'] }}</td><td class="num">{{ $d['minutes'] ?: '-' }}</td><td>{{ $d['detail'] }}@if ($d['video'])
                            <details class="mt-1.5">
                                <summary class="inline-flex cursor-pointer list-none items-center gap-1 text-sm font-semibold text-accent underline"><x-icon name="video" class="size-4" />{{ t('vid.watch') }}</summary>
                                <x-activity-media class="mt-2 max-w-md" :media="$d['video']" :title="$d['type']" />
                            </details>
                        @endif</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($a->growable())
            <div class="card">
                <h3>{{ t('gb.title') }}</h3>
                <ul class="mt-2.5 flex list-disc flex-col gap-1.5 pl-5">
                    <li>{{ t('gb.1', $t['growthSleep']) }}</li><li>{{ t('gb.2') }}</li><li>{{ t('gb.3') }}</li><li>{{ t('gb.4') }}</li><li>{{ t('gb.5') }}</li>
                </ul>
            </div>
        @endif
        <p class="note">{{ t('tr.note') }}</p>
    </div>
</div>
