<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Services\Clients\ClientHealth;
use App\Services\Clients\TrainingCalendar;
use App\Services\Health\EnergyPlanner;
use App\Support\Phone;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts::panel')]
class ClientShow extends Component
{
    #[Locked]
    public Client $client;

    public function activate(): void
    {
        $this->client->user->activate();
        session()->flash('status', t('admin.activated'));
    }

    public function delete()
    {
        // Deleting the user removes the profile, measurements, workout logs and link codes.
        $this->client->user->delete();
        session()->flash('status', t('admin.deleted'));

        return $this->redirectRoute('admin.clients.index', navigate: true);
    }

    public function render(ClientHealth $health, EnergyPlanner $energy, TrainingCalendar $calendar)
    {
        $client = $this->client->load(['user', 'measurements']);
        $a = $health->analyze($client, $client->measurements->last());
        $level = $a ? $health->level($client, $a) : null;

        return view('livewire.admin.client-show', [
            'a' => $a,
            'level' => $level,
            'plan' => $a ? $energy->plan($a, $level) : null,
            'phone' => Phone::format($client->phone),
            'complete' => $client->isComplete(),
            'month' => $level ? $calendar->summary($calendar->month($client, $level, $calendar->today())) : null,
        ])->title($client->fullName());
    }
}
