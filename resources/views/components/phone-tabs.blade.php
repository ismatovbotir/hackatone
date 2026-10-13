@props(['active' => 'today'])
<nav class="mt-auto grid grid-cols-5 border-t border-line bg-surface px-1 pt-1.5 pb-3 text-[9px] font-semibold" aria-hidden="true">
    @foreach (['today' => ['home', 'apps.tab.today'], 'calendar' => ['calendar', 'tab.cal'], 'plan' => ['run', 'tab.training'], 'rating' => ['star', 'apps.tab.rating'], 'profile' => ['user', 'apps.tab.profile']] as $key => [$icon, $label])
        <span class="flex flex-col items-center gap-0.5 {{ $active === $key ? 'text-accent' : 'text-muted' }}"><x-icon :name="$icon" class="size-5" />{{ t($label) }}</span>
    @endforeach
</nav>
