<div class="flex flex-col gap-4">
    <h1>{{ t('admin.users') }}</h1>

    <div class="grid grid-cols-3 gap-1 self-start rounded-lg border border-line bg-surface p-1 sm:flex">
        @foreach (['' => 'admin.all', 'admin' => 'admin.role.admin', 'user' => 'admin.role.user'] as $v => $label)
            <button type="button" wire:click="$set('role', '{{ $v }}')" aria-pressed="{{ $role === $v ? 'true' : 'false' }}"
                    class="rounded-md px-3 py-1.5 text-sm font-semibold {{ $role === $v ? 'bg-ink text-bg' : 'text-muted' }}">{{ t($label) }}</button>
        @endforeach
    </div>

    <ul class="flex flex-col gap-2">
        @foreach ($users as $u)
            <li class="card flex flex-col gap-3 !p-4 sm:flex-row sm:items-center sm:justify-between" wire:key="u-{{ $u->id }}">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <b class="truncate">{{ $u->name }}</b>
                        <x-pill :tone="$u->isAdmin() ? 'warn' : 'neutral'">{{ t('admin.role.'.$u->role->value) }}</x-pill>
                        @if ($u->id === auth()->id())<x-pill>{{ t('admin.you') }}</x-pill>@endif
                        @unless ($u->isConfirmed())<x-pill tone="warn">{{ t('admin.not_confirmed') }}</x-pill>@endunless
                    </div>
                    <p class="truncate text-sm text-muted">{{ $u->email }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    @unless ($u->isConfirmed())
                        <button type="button" class="btn btn-sm" wire:click="activate({{ $u->id }})" wire:confirm="{{ t('admin.activate_confirm') }}">{{ t('admin.activate') }}</button>
                    @endunless
                    @if ($u->client)
                        <a class="btn-ghost btn-sm" href="{{ route('admin.clients.show', $u->client) }}" wire:navigate>{{ t('admin.view') }}</a>
                    @endif
                    @if ($u->id !== auth()->id())
                        @if ($u->isAdmin())
                            <button type="button" class="btn-ghost btn-sm" wire:click="setRole({{ $u->id }}, 'user')" wire:confirm="{{ t('admin.make_user') }}?">{{ t('admin.make_user') }}</button>
                        @else
                            <button type="button" class="btn btn-sm" wire:click="setRole({{ $u->id }}, 'admin')" wire:confirm="{{ t('admin.make_admin') }}?">{{ t('admin.make_admin') }}</button>
                        @endif
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
    <div>{{ $users->links() }}</div>
</div>
