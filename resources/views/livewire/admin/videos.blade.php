<div class="flex flex-col gap-4" @if ($anyRunning) wire:poll.5s="checkJobs" @endif>
    <div>
        <h1>{{ t('admin.videos') }}</h1>
        <p class="mt-1.5 max-w-prose text-muted">{{ t('vid.sub') }}</p>
    </div>
    <p class="note">{{ t('vid.check') }}</p>
    @unless ($aiReady)
        <p class="rounded-lg border border-warn bg-warn-bg px-4 py-3 text-sm text-warn">
            {{ t('vid.ai_off') }} <a class="font-semibold underline" href="{{ route('admin.settings') }}" wire:navigate>{{ t('admin.settings') }}</a>
        </p>
    @endunless

    @foreach (\App\Services\Health\ActivityVideos::TYPES as $type)
        <section class="flex flex-col gap-2">
            <h2>{{ t('ty.'.$type) }}</h2>
            <div class="grid gap-3 2xl:grid-cols-2">
                @foreach (\App\Services\Health\ActivityVideos::AUDIENCES as $aud)
                    @php $key = "$type-$aud"; $item = $items[$key]; $row = $item['row']; @endphp
                    <div wire:key="v-{{ $key }}" class="card flex flex-col gap-3" x-data="{ copied: false }">
                        <div class="flex items-center justify-between gap-2">
                            <h3>{{ t('vid.'.$aud) }}</h3>
                            <x-pill :tone="$item['media'] ? 'good' : 'neutral'">{{ $item['media'] ? t('vid.live').' · '.strtoupper($item['media']['type']) : t('vid.none') }}</x-pill>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            {{-- Published --}}
                            <div class="flex flex-col gap-2">
                                <span class="label">{{ t('vid.live') }}</span>
                                @if ($item['media'])
                                    <x-activity-media :media="$item['media']" :title="t('ty.'.$type)" />
                                    <button type="button" class="btn-ghost btn-sm self-start text-bad" wire:click="removeMedia('{{ $key }}')" wire:confirm="{{ t('vid.remove') }}?">{{ t('vid.remove') }}</button>
                                @else
                                    <div class="grid aspect-video place-items-center rounded-xl border border-dashed border-line text-sm text-muted">{{ t('vid.none') }}</div>
                                @endif
                            </div>

                            {{-- AI draft / progress --}}
                            <div class="flex flex-col gap-2">
                                <span class="label">{{ t('vid.ai') }}</span>
                                @if ($row?->generating())
                                    <div class="grid aspect-video place-items-center rounded-xl border border-accent/40 bg-surface2 p-3 text-center text-sm" role="status">
                                        <span class="flex flex-col items-center gap-2">
                                            <span class="size-8 animate-spin rounded-full border-4 border-line border-t-accent motion-reduce:animate-none"></span>
                                            {{ t('vid.generating') }}
                                        </span>
                                    </div>
                                @elseif ($item['draft'])
                                    <x-activity-media :media="$item['draft']" :title="t('ty.'.$type)" class="ring-2 ring-warn" />
                                    <p class="text-xs text-warn">{{ t('vid.review') }}</p>
                                    @if ($item['draft']['type'] === 'gif')
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-sm" wire:click="publishDraft('{{ $key }}')">{{ t('vid.publish') }}</button>
                                            <button type="button" class="btn-ghost btn-sm" wire:click="discardDraft('{{ $key }}')">{{ t('vid.discard') }}</button>
                                        </div>
                                    @else
                                        {{-- MP4 draft: the browser converts it to a small GIF, uploads it, then publishes. --}}
                                        <div class="flex flex-col gap-2" x-data="{ busy: false, pct: 0, err: '' }">
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-sm" :disabled="busy"
                                                    x-on:click="busy = true; err = ''; pct = 0;
                                                        try {
                                                            const blob = await window.hhMp4ToGif(@js($item['draft']['src']), { maxBytes: {{ config('hh.media_max_kb') * 1024 }}, onProgress: (p) => pct = Math.round(p * 100) });
                                                            $wire.upload('gif', new File([blob], 'activity.gif', { type: 'image/gif' }),
                                                                () => { $wire.publishDraftAsGif('{{ $key }}').then(() => busy = false) },
                                                                () => { err = @js(t('vid.convert_fail')); busy = false });
                                                        } catch (e) { err = e.message; busy = false }">
                                                    <span x-show="! busy">{{ t('vid.publish_gif') }}</span>
                                                    <span x-show="busy" x-cloak>{{ t('vid.converting') }} <span x-text="pct + '%'"></span></span>
                                                </button>
                                                <button type="button" class="btn-ghost btn-sm" wire:click="discardDraft('{{ $key }}')" :disabled="busy">{{ t('vid.discard') }}</button>
                                            </div>
                                            <div x-show="busy" x-cloak class="h-1.5 overflow-hidden rounded-full bg-surface2"><div class="h-full bg-accent transition-all" :style="`width: ${pct}%`"></div></div>
                                            <p x-show="err" x-cloak class="err" x-text="err" role="alert"></p>
                                            @error('gif')<p class="err">{{ $message }}</p>@enderror
                                        </div>
                                    @endif
                                @else
                                    <div class="grid aspect-video place-items-center rounded-xl border border-dashed border-line p-3 text-center text-sm text-muted">{{ t('vid.ai_empty') }}</div>
                                @endif
                                @if ($row?->job_error)
                                    <p class="err" role="alert">{{ t('vid.error') }}: {{ $row->job_error }}</p>
                                @endif
                                <button type="button" class="btn btn-sm self-start" wire:click="generate('{{ $key }}')" wire:loading.attr="disabled" wire:target="generate('{{ $key }}')"
                                        @disabled(! $aiReady || $row?->generating())>
                                    <x-icon name="bot" class="size-4" />{{ t('vid.generate') }}
                                </button>
                            </div>
                        </div>

                        <label class="field">{{ t('vid.upload', (int) (config('hh.media_max_kb') / 1024)) }}
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <input class="input file:mr-3 file:rounded-md file:border-0 file:bg-surface2 file:px-3 file:py-1.5 file:font-semibold" type="file" accept="image/gif" wire:model="files.{{ $key }}">
                                <button type="button" class="btn-ghost shrink-0" wire:click="upload('{{ $key }}')" wire:loading.attr="disabled" wire:target="files.{{ $key }}">{{ t('vid.upload_btn') }}</button>
                            </div>
                            <span wire:loading wire:target="files.{{ $key }}" class="text-xs font-normal text-muted">{{ t('vid.uploading') }}</span>
                            @error("files.$key")<span class="err">{{ $message }}</span>@enderror
                        </label>

                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-semibold">
                                <x-icon name="chev-r" class="size-4 transition group-open:rotate-90" />{{ t('vid.prompt') }}
                                @if ($item['custom'])<x-pill tone="warn">{{ t('vid.custom') }}</x-pill>@endif
                            </summary>
                            <textarea class="input mt-2 min-h-32 font-mono text-xs leading-relaxed" wire:model="prompts.{{ $key }}" x-ref="prompt"></textarea>
                            @error("prompts.$key")<span class="err">{{ $message }}</span>@enderror
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button type="button" class="btn-ghost btn-sm" wire:click="savePrompt('{{ $key }}')">{{ t('vid.save') }}</button>
                                <button type="button" class="btn-ghost btn-sm" x-on:click="navigator.clipboard.writeText($refs.prompt.value); copied = true; setTimeout(() => copied = false, 2000)">
                                    <span x-show="!copied">{{ t('vid.copy') }}</span><span x-show="copied" x-cloak>{{ t('tg.copied') }}</span>
                                </button>
                                <button type="button" class="btn-ghost btn-sm" wire:click="resetPrompt('{{ $key }}')">{{ t('vid.reset') }}</button>
                            </div>
                        </details>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
