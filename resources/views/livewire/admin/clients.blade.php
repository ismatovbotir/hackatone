<div class="flex flex-col gap-4">
    <h1>{{ t('admin.clients') }}</h1>

    <div class="flex flex-col gap-2 sm:flex-row">
        <input class="input sm:max-w-sm" type="search" wire:model.live.debounce.400ms="q" placeholder="{{ t('admin.search') }}" aria-label="{{ t('admin.search') }}">
        <div class="grid grid-cols-3 gap-1 rounded-lg border border-line bg-surface p-1 sm:flex">
            @foreach (['' => 'admin.all', 'linked' => 'admin.tg.linked', 'not' => 'admin.tg.not'] as $v => $label)
                <button type="button" wire:click="$set('telegram', '{{ $v }}')" aria-pressed="{{ $telegram === $v ? 'true' : 'false' }}"
                        class="rounded-md px-3 py-1.5 text-sm font-semibold {{ $telegram === $v ? 'bg-ink text-bg' : 'text-muted' }}">{{ t($label) }}</button>
            @endforeach
        </div>
    </div>

    {{-- Phones: cards. Wider screens: a table. --}}
    <ul class="flex flex-col gap-2 md:hidden">
        @forelse ($clients as $c)
            <li wire:key="cm-{{ $c->id }}">
                <a href="{{ route('admin.clients.show', $c) }}" wire:navigate class="card flex items-center justify-between gap-3 !p-4">
                    <span class="min-w-0">
                        <b class="block truncate">{{ $c->fullName() }}</b>
                        <span class="block truncate text-sm text-muted">{{ $c->user->email }}</span>
                    </span>
                    <span class="flex shrink-0 flex-col items-end gap-1">
                        <x-pill :tone="$c->isLinked() ? 'good' : 'neutral'">{{ $c->isLinked() ? t('admin.tg.linked') : t('admin.tg.not') }}</x-pill>
                        <span class="font-mono text-xs text-muted">{{ $c->created_at->toDateString() }}</span>
                    </span>
                </a>
            </li>
        @empty
            <li class="note">{{ t('admin.empty') }}</li>
        @endforelse
    </ul>

    <div class="tablewrap hidden md:block">
        <table class="table">
            <thead><tr><th>{{ t('admin.name') }}</th><th>{{ t('fl.email') }}</th><th>{{ t('prof.level') }}</th><th>{{ t('tab.tg') }}</th><th>{{ t('admin.registered') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($clients as $c)
                    <tr wire:key="ct-{{ $c->id }}">
                        <td class="font-semibold">{{ $c->fullName() }}</td>
                        <td class="break-all">{{ $c->user->email }} @unless ($c->user->hasVerifiedEmail())<x-pill tone="warn">!</x-pill>@endunless</td>
                        <td>@if ($c->level)<span class="dot dot-{{ $c->level->value }} mr-1.5 align-middle"></span>{{ t('lv.'.$c->level->value) }}@else<span class="text-muted">-</span>@endif</td>
                        <td><x-pill :tone="$c->isLinked() ? 'good' : 'neutral'">{{ $c->isLinked() ? t('admin.tg.linked') : t('admin.tg.not') }}</x-pill></td>
                        <td class="font-mono text-xs">{{ $c->created_at->toDateString() }}</td>
                        <td class="text-right"><a class="btn-ghost btn-sm" href="{{ route('admin.clients.show', $c) }}" wire:navigate>{{ t('admin.view') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">{{ t('admin.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $clients->links() }}</div>
</div>
