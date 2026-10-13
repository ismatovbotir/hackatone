@props(['title' => null])
@php
    $user = auth()->user();
    $items = $user->isAdmin()
        ? [
            ['admin.dashboard', 'admin.dashboard', 'home'],
            ['admin.clients.*', 'admin.clients', 'users'],
            ['admin.users.*', 'admin.users', 'shield'],
            ['admin.videos', 'admin.videos', 'video'],
            ['admin.monetisation', 'mon.title', 'coins'],
            ['admin.apps', 'apps.title', 'phone'],
            ['admin.settings', 'admin.settings', 'cog'],
        ]
        : [
            ['panel.overview', 'tab.overview', 'home'],
            ['panel.training', 'tab.training', 'run'],
            ['panel.calendar', 'tab.cal', 'calendar'],
            ['panel.feeding', 'tab.feeding', 'food'],
            ['panel.achievements', 'tab.ach', 'star'],
            ['panel.telegram', 'tab.tg', 'send'],
            ['panel.account', 'panel.account', 'user'],
        ];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) && $title ? $title.' · ' : '' }}HackaTone Health</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js" defer></script>
</head>
<body class="min-h-dvh" data-theme="{{ \App\Support\Theme::current() }}">
    {{-- Mobile first: sticky top bar with a scrollable tab row; sidebar from lg up. --}}
    <header class="sticky top-0 z-20 border-b border-line bg-bg/95 backdrop-blur lg:hidden">
        <div class="flex items-center justify-between gap-2 px-4 py-3">
            <a href="{{ route('home') }}" wire:navigate><x-brand /></a>
            <div class="flex items-center gap-2">
                <x-lang-switch />
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="btn-ghost btn-sm" aria-label="{{ t('nav.logout') }}"><x-icon name="logout" /></button>
                </form>
            </div>
        </div>
        <nav class="flex gap-1 overflow-x-auto px-2 pb-2" aria-label="{{ t('admin.menu') }}">
            @foreach ($items as [$route, $label, $icon])
                @php $on = request()->routeIs($route); @endphp
                <a href="{{ route(str_replace('.*', '.index', $route)) }}" wire:navigate @if ($on) aria-current="page" @endif
                   class="flex shrink-0 items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold whitespace-nowrap {{ $on ? 'bg-ink text-bg' : 'text-muted' }}">
                    <x-icon :name="$icon" class="size-4" />{{ t($label) }}
                </a>
            @endforeach
        </nav>
    </header>

    {{-- Admin pages are fluid (full width); the client's plan keeps a readable width. --}}
    <div class="mx-auto {{ $user->isAdmin() ? 'max-w-none' : 'max-w-6xl' }} lg:grid lg:grid-cols-[230px_1fr] lg:gap-8 lg:px-6">
        <aside class="hidden lg:block">
            <div class="sticky top-0 flex h-dvh flex-col gap-6 py-6">
                <a href="{{ route('home') }}" wire:navigate><x-brand /></a>
                <div>
                    <p class="text-sm font-semibold">{{ $user->name }}</p>
                    <span class="pill {{ $user->isAdmin() ? 'pill-warn' : 'pill-neutral' }} mt-1">{{ t('admin.role.'.$user->role->value) }}</span>
                </div>
                <nav class="flex flex-col gap-1" aria-label="{{ t('admin.menu') }}">
                    @foreach ($items as [$route, $label, $icon])
                        @php $on = request()->routeIs($route); @endphp
                        <a href="{{ route(str_replace('.*', '.index', $route)) }}" wire:navigate @if ($on) aria-current="page" @endif
                           class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-semibold {{ $on ? 'bg-surface text-ink shadow-[inset_3px_0_0_var(--accent)]' : 'text-muted hover:bg-surface' }}">
                            <x-icon :name="$icon" class="size-5" />{{ t($label) }}
                        </a>
                    @endforeach
                </nav>
                <div class="mt-auto flex flex-col gap-3">
                    <x-lang-switch />
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="btn-ghost btn-sm w-full"><x-icon name="logout" class="size-4" />{{ t('nav.logout') }}</button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="min-w-0 px-4 py-5 lg:px-0 lg:py-8">
            <x-flash />
            {{ $slot }}
            @unless ($user->isAdmin())
                <p class="note mt-8">{{ t('disc') }}</p>
            @endunless
        </main>
    </div>
    @stack('scripts')
    <x-ai-agent />
    @livewireScripts
</body>
</html>
