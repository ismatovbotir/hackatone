<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Models\Client;
use App\Models\DailyLog;
use App\Models\Measurement;
use App\Models\User;
use App\Services\Clients\ClientHealth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::panel')]
class Dashboard extends Component
{
    public function render(ClientHealth $health)
    {
        $clients = Client::with('latestMeasurement')->get();
        $levels = ['easy' => 0, 'mid' => 0, 'strong' => 0];
        $bmi = ['under' => 0, 'ok' => 0, 'over' => 0, 'obese' => 0];
        $minors = 0;

        foreach ($clients as $c) {
            if (! $a = $health->analyze($c)) {
                continue;
            }
            $levels[$health->level($c, $a)->value]++;
            $bmi[$a->bmiStatus]++;
            $minors += $a->age < 18 ? 1 : 0;
        }

        return view('livewire.admin.dashboard', [
            'stats' => [
                'admin.st.clients' => $clients->count(),
                'admin.st.linked' => $clients->whereNotNull('telegram_chat_id')->count(),
                'admin.st.minors' => $minors,
                'admin.st.week' => $clients->where('created_at', '>=', now()->subDays(7))->count(),
                'admin.st.meas' => Measurement::count(),
                'admin.st.unverified' => User::where('role', Role::User)->whereNull('email_verified_at')->count(),
            ],
            'levels' => $levels,
            'bmi' => $bmi,
            'sessions' => DailyLog::where('workout_done', true)->where('log_date', '>=', now()->subDays(7)->toDateString())->count(),
            'recent' => Client::with('user')->latest()->take(8)->get(),
        ])->title(t('admin.dashboard'));
    }
}
