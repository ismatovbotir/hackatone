<?php

namespace App\Livewire\Panel;

use App\Livewire\Panel\Concerns\InteractsWithPlan;
use App\Services\Presenters\TrainingPresenter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::panel')]
class Training extends Component
{
    use InteractsWithPlan;

    public function render(TrainingPresenter $presenter)
    {
        return view('livewire.panel.training', [
            'a' => $this->analysis,
            't' => $presenter->present($this->analysis, $this->level),
        ])->title(t('tab.training'));
    }
}
