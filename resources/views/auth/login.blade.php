<x-layouts::app :title="t('login.title')">
    <div class="mx-auto max-w-md py-6">
        <form method="POST" action="{{ route('login.store') }}" class="card flex flex-col gap-4">
            @csrf
            <div>
                <h1 class="text-2xl">{{ t('login.title') }}</h1>
                <p class="mt-1 text-muted">{{ t('login.sub') }}</p>
            </div>
            <label class="field">{{ t('fl.email') }}
                <input class="input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            </label>
            <label class="field">{{ t('fl.password') }}
                <input class="input" type="password" name="password" autocomplete="current-password" required>
            </label>
            @error('email')<p class="err" role="alert">{{ $message }}</p>@enderror
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="size-4 accent-[var(--accent)]"> {{ t('login.remember') }}</label>
            <button class="btn w-full">{{ t('nav.login') }}</button>
            <p class="text-center text-sm text-muted">{{ t('login.noacc') }} <a class="font-semibold text-accent underline" href="{{ route('register') }}" wire:navigate>{{ t('nav.start') }}</a></p>
        </form>
    </div>
</x-layouts::app>
