<?php

namespace App\Livewire\Panel;

use App\Livewire\Panel\Concerns\InteractsWithPlan;
use App\Services\Health\EnergyPlanner;
use App\Services\Health\HealthAnalyzer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::panel')]
class Overview extends Component
{
    use InteractsWithPlan;

    public function render(EnergyPlanner $energy)
    {
        $client = $this->client;

        return view('livewire.panel.overview', [
            'a' => $this->analysis,
            'plan' => $energy->plan($this->analysis, $this->level),
            'points' => $client->measurements
                ->map(fn ($m) => [HealthAnalyzer::age($client->birth_date, $m->measured_on), $m->height_cm])
                ->filter(fn ($p) => $p[0] >= 5 && $p[0] <= 19)->values()->all(),
        ])->title(t('tab.overview'));
    }
}
