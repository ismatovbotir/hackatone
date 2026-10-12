<?php

namespace App\Livewire\Panel;

use App\Livewire\Forms\MeasurementForm;
use App\Livewire\Panel\Concerns\InteractsWithPlan;
use App\Services\Presenters\AchievementsPresenter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::panel')]
class Achievements extends Component
{
    use InteractsWithPlan;

    public MeasurementForm $log;

    public function mount(): void
    {
        $this->log->measured_on = now(config('hh.timezone'))->toDateString();
    }

    public function addMeasurement(): void
    {
        $this->log->store($this->client);
        $this->refreshPlan();
        session()->flash('status', t('lg.saved'));
    }

    public function removeMeasurement(int $id): void
    {
        $client = $this->client;
        // The first measurement anchors progress and cannot be removed.
        if ($client->measurements->first()?->id !== $id) {
            $client->measurements()->whereKey($id)->delete();
        }
        $this->refreshPlan();
    }

    public function render(AchievementsPresenter $presenter)
    {
        $meas = $this->client->measurements;

        return view('livewire.panel.achievements', [
            'a' => $this->analysis,
            'ach' => $presenter->present($this->analysis, $meas),
            'history' => $meas->reverse()->take(6),
            'firstId' => $meas->first()->id,
        ])->title(t('tab.ach'));
    }
}
