<?php

namespace App\Livewire\Panel;

use App\Livewire\Panel\Concerns\InteractsWithPlan;
use App\Livewire\Panel\Concerns\ShowsRating;
use App\Services\Clients\TrainingCalendar;
use App\Services\Health\EnergyPlanner;
use App\Services\Health\HealthAnalyzer;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::panel')]
class Overview extends Component
{
    use InteractsWithPlan, ShowsRating;

    public function render(EnergyPlanner $energy, TrainingCalendar $calendar)
    {
        $client = $this->client;
        $month = $calendar->summary($calendar->month($client, $this->level, $calendar->today()));

        return view('livewire.panel.overview', [
            'a' => $this->analysis,
            'plan' => $energy->plan($this->analysis, $this->level),
            'reading' => ['pages' => $month['pages'], 'days' => $month['reading_days'], 'total' => (int) $client->dailyLogs()->sum('pages_read')],
            'points' => $client->measurements
                ->map(fn ($m) => [HealthAnalyzer::age($client->birth_date, $m->measured_on), $m->height_cm])
                ->filter(fn ($p) => $p[0] >= 5 && $p[0] <= 19)->values()->all(),
        ])->title(t('tab.overview'));
    }
}
