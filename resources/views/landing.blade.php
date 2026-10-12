<x-layouts::app>
    <section class="grid items-center gap-8 py-6 md:grid-cols-[1.05fr_1fr] md:py-10">
        <div class="flex min-w-0 flex-col gap-5">
            <span class="label">{{ t('land.eyebrow') }}</span>
            <h1 class="text-4xl sm:text-5xl">{{ t('land.h1') }}</h1>
            <p class="max-w-prose text-lg text-muted">{{ t('land.sub') }}</p>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a class="btn" href="{{ route('register') }}" wire:navigate>{{ t('land.cta') }}</a>
                <a class="btn-ghost" href="#how">{{ t('land.cta2') }}</a>
            </div>
        </div>
        <div class="card">
            <h3>{{ t('ch.title') }}</h3>
            <div class="mt-2">{!! \App\Support\HeightChart::svg($chartSex, $chartPoints) !!}</div>
            <p class="text-sm text-muted">{{ t('land.chartcap') }}</p>
            <p class="mt-2 text-sm text-muted">{{ t('gl.band.d') }}</p>
        </div>
    </section>

    <section id="how" class="scroll-mt-4 py-6">
        <h2>{{ t('how.title') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([1, 2, 3, 4] as $i)
                <div class="card flex flex-col gap-2">
                    <span class="grid size-7 place-items-center rounded-full bg-ink font-mono text-sm text-bg">{{ $i }}</span>
                    <h3>{{ t("how.$i.t") }}</h3>
                    <p class="text-muted">{{ t("how.$i.d") }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="py-6">
        <h2>{{ t('lvl.title') }}</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            @foreach ($minutes as $lv => $min)
                <div class="card flex flex-col gap-2">
                    <div class="flex items-center gap-2"><span class="dot dot-{{ $lv }}"></span><span class="label">{{ t("lvtag.$lv") }}</span></div>
                    <h3>{{ t("lv.$lv") }}</h3>
                    <p class="big">{{ $min }}<span class="text-sm text-muted"> {{ t('u.min') }}/7</span></p>
                    <p class="text-sm text-muted">{{ t("lvb.$lv") }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="py-6">
        <h2>{{ t('feat.title') }}</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-3">
            @foreach ([1, 2, 3] as $i)
                <div><h3>{{ t("feat.$i.t") }}</h3><p class="mt-1.5 text-muted">{{ t("feat.$i.d") }}</p></div>
            @endforeach
        </div>
    </section>

    <x-glossary class="my-6" />

    <section class="my-6 flex flex-col items-start gap-4 rounded-xl border border-line bg-surface2 px-5 py-8">
        <h2>{{ t('land.final') }}</h2>
        <a class="btn" href="{{ route('register') }}" wire:navigate>{{ t('land.cta') }}</a>
    </section>
</x-layouts::app>
