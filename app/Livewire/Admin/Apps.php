<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Component;

/** Preview of the future mobile app: view-only mock screens with sample data, for planning next steps. */
#[Layout('layouts::panel')]
class Apps extends Component
{
    public function render()
    {
        return view('livewire.admin.apps')->title(t('apps.title'));
    }
}
