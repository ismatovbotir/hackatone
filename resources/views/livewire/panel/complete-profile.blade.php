<div class="mx-auto flex max-w-xl flex-col gap-4">
    <div>
        <h1>{{ t('prof.title') }}</h1>
        <p class="mt-1.5 text-muted">{{ t('prof.sub', $client->first_name) }}</p>
    </div>

    <form wire:submit="save" class="card flex flex-col gap-4" novalidate>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="field">{{ t('fl.dob') }}
                <input class="input" type="date" wire:model="form.dob" max="{{ now()->toDateString() }}">
                @error('form.dob')<span class="err">{{ $message }}</span>@enderror
            </label>
            <fieldset class="field">
                <legend class="mb-1.5">{{ t('fl.sex') }}</legend>
                <div class="flex gap-2">
                    @foreach (['M' => 'fl.male', 'F' => 'fl.female'] as $v => $label)
                        <label class="flex min-h-11 flex-1 cursor-pointer items-center gap-2 rounded-lg border border-line bg-bg px-3 font-medium has-checked:border-accent">
                            <input type="radio" wire:model="form.sex" value="{{ $v }}" class="accent-[var(--accent)]"> {{ t($label) }}
                        </label>
                    @endforeach
                </div>
                @error('form.sex')<span class="err">{{ $message }}</span>@enderror
            </fieldset>
            <label class="field">{{ t('fl.weight') }}
                <input class="input" type="number" wire:model="form.w" inputmode="decimal" step="0.1" min="10" max="300">
                @error('form.w')<span class="err">{{ $message }}</span>@enderror
            </label>
            <label class="field">{{ t('fl.height') }}
                <input class="input" type="number" wire:model="form.h" inputmode="decimal" step="0.1" min="80" max="250">
                @error('form.h')<span class="err">{{ $message }}</span>@enderror
            </label>
        </div>

        <details class="rounded-lg border border-line bg-bg p-3">
            <summary class="cursor-pointer font-semibold">{{ t('prof.email_optional') }}</summary>
            <p class="mt-2 text-sm text-muted">{{ t('prof.email_d') }}</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <label class="field sm:col-span-2">{{ t('fl.email') }}
                    <input class="input" type="email" wire:model="form.email" autocomplete="email">
                    @error('form.email')<span class="err">{{ $message }}</span>@enderror
                </label>
                <label class="field">{{ t('fl.password') }}
                    <input class="input" type="password" wire:model="form.password" autocomplete="new-password" @if ($hasPassword) placeholder="••••••••" @endif>
                    @error('form.password')<span class="err">{{ $message }}</span>@enderror
                </label>
                <label class="field">{{ t('fl.password2') }}
                    <input class="input" type="password" wire:model="form.password_confirmation" autocomplete="new-password">
                </label>
            </div>
        </details>

        <label class="flex items-start gap-3 rounded-lg border border-line bg-bg p-3 text-sm">
            <input type="checkbox" wire:model="form.consent" class="mt-0.5 size-5 shrink-0 accent-[var(--accent)]">
            <span>{{ t('fl.consent') }}</span>
        </label>
        @error('form.consent')<p class="err">{{ $message }}</p>@enderror

        <button class="btn w-full sm:w-auto" wire:loading.attr="disabled">{{ t('prof.save') }}</button>
    </form>
</div>
