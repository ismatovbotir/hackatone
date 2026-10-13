<x-layouts::app :title="t('tgapp.title')">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <div class="mx-auto max-w-md py-10" x-data="{
            state: 'loading',
            async init() {
                const tg = window.Telegram?.WebApp;
                if (! tg || ! tg.initData) { this.state = 'outside'; return; }
                tg.ready(); tg.expand();
                try {
                    const r = await fetch(@js(route('tg.auth')), {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ init_data: tg.initData, open: new URLSearchParams(location.search).get('open') }),
                    });
                    const data = await r.json();
                    if (data.status === 'ok') { window.location.replace(data.redirect); return; }
                    this.state = data.status === 'not_linked' ? 'not_linked' : 'error';
                } catch (e) { this.state = 'error'; }
            },
        }">
        <div class="card flex flex-col items-center gap-4 text-center">
            <template x-if="state === 'loading'">
                <div class="flex flex-col items-center gap-3" role="status">
                    <span class="size-10 animate-spin rounded-full border-4 border-line border-t-accent motion-reduce:animate-none"></span>
                    <p>{{ t('tgapp.loading') }}</p>
                </div>
            </template>
            <template x-if="state === 'not_linked'">
                <div class="flex flex-col items-center gap-3">
                    <h1 class="text-xl">{{ t('tgapp.not_linked') }}</h1>
                    <p class="text-muted">{{ t('tgapp.not_linked_d') }}</p>
                    <a class="btn" href="{{ route('register') }}">{{ t('nav.start') }}</a>
                    <a class="text-sm font-semibold text-accent underline" href="{{ route('login') }}">{{ t('nav.login') }}</a>
                </div>
            </template>
            <template x-if="state === 'outside'">
                <div class="flex flex-col items-center gap-3">
                    <h1 class="text-xl">{{ t('tgapp.outside') }}</h1>
                    <a class="btn" href="{{ route('login') }}">{{ t('nav.login') }}</a>
                </div>
            </template>
            <template x-if="state === 'error'">
                <p class="err">{{ t('tgapp.error') }}</p>
            </template>
        </div>
    </div>
</x-layouts::app>
