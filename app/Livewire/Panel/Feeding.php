<?php

namespace App\Livewire\Panel;

use App\Enums\Level;
use App\Livewire\Panel\Concerns\InteractsWithPlan;
use App\Services\Health\EnergyPlanner;
use App\Services\Health\MenuPlanner;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::panel')]
class Feeding extends Component
{
    use InteractsWithPlan;

    public function render(EnergyPlanner $energy, MenuPlanner $menu)
    {
        $plan = $energy->plan($this->analysis, $this->level);

        return view('livewire.panel.feeding', [
            'a' => $this->analysis,
            'plan' => $plan,
            'menu' => $menu->day($this->level, $plan->kcal),
            'compare' => collect(Level::cases())->mapWithKeys(fn ($l) => [$l->value => $energy->plan($this->analysis, $l)]),
        ])->title(t('tab.feeding'));
    }
}
