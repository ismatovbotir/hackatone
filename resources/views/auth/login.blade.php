<x-layouts::app :title="t('login.title')">
    <div class="mx-auto max-w-md py-6" x-data="{ tab: @js(old('email') ? 'email' : 'phone') }">
        <div class="card flex flex-col gap-4">
            <div>
                <h1 class="text-2xl">{{ t('login.title') }}</h1>
            </div>

            <div class="grid grid-cols-2 gap-1 rounded-lg border border-line bg-bg p-1" role="tablist">
                <button type="button" role="tab" x-on:click="tab = 'phone'" :aria-selected="(tab === 'phone').toString()"
                        :class="tab === 'phone' ? 'bg-surface text-ink shadow-sm' : 'text-muted'" class="flex items-center justify-center gap-1.5 rounded-md px-3 py-2 text-sm font-semibold">
                    <x-icon name="send" class="size-4" />{{ t('plogin.tab') }}
                </button>
                <button type="button" role="tab" x-on:click="tab = 'email'" :aria-selected="(tab === 'email').toString()"
                        :class="tab === 'email' ? 'bg-surface text-ink shadow-sm' : 'text-muted'" class="rounded-md px-3 py-2 text-sm font-semibold">
                    {{ t('fl.email') }}
                </button>
            </div>

            <div x-show="tab === 'phone'">
                <livewire:auth.phone-login />
            </div>

            <form x-show="tab === 'email'" x-cloak method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
                @csrf
                <p class="text-sm text-muted">{{ t('login.sub') }}</p>
                <label class="field">{{ t('fl.email') }}
                    <input class="input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                </label>
                <label class="field">{{ t('fl.password') }}
                    <input class="input" type="password" name="password" autocomplete="current-password" required>
                </label>
                @error('email')<p class="err" role="alert">{{ $message }}</p>@enderror
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="size-4 accent-[var(--accent)]"> {{ t('login.remember') }}</label>
                <button class="btn w-full">{{ t('nav.login') }}</button>
            </form>

            <p class="text-center text-sm text-muted">{{ t('login.noacc') }} <a class="font-semibold text-accent underline" href="{{ route('register') }}" wire:navigate>{{ t('nav.start') }}</a></p>
        </div>
    </div>
</x-layouts::app>
