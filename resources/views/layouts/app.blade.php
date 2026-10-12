@props(['title' => null])
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
    <div class="mx-auto flex min-h-dvh max-w-5xl flex-col px-4 pb-10">
        <header class="flex flex-wrap items-center justify-between gap-3 py-4">
            <a href="{{ route('home') }}" wire:navigate><x-brand /></a>
            <div class="flex items-center gap-2">
                <x-lang-switch />
                @auth
                    <a class="btn btn-sm" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('panel.overview') }}" wire:navigate>{{ t('nav.dash') }}</a>
                @else
                    @unless (request()->routeIs('login'))
                        <a class="btn-ghost btn-sm" href="{{ route('login') }}" wire:navigate>{{ t('nav.login') }}</a>
                    @endunless
                    @unless (request()->routeIs('register*'))
                        <a class="btn btn-sm" href="{{ route('register') }}" wire:navigate>{{ t('nav.start') }}</a>
                    @endunless
                @endauth
            </div>
        </header>

        <x-flash />

        <main class="flex-1">{{ $slot }}</main>

        <footer class="mt-10 space-y-3">
            <p class="note">{{ t('disc') }}</p>
            <p class="text-sm text-muted">{{ t('foot') }}</p>
        </footer>
    </div>
    <x-ai-agent />
    @livewireScripts
</body>
</html>
