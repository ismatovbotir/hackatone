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
        $level = $health->level($client, $a);

        return view('livewire.admin.client-show', [
            'a' => $a,
            'level' => $level,
            'plan' => $energy->plan($a, $level),
            'phone' => Phone::format($client->phone),
            'month' => $calendar->summary($calendar->month($client, $level, $calendar->today())),
        ])->title($client->fullName());
    }
}
