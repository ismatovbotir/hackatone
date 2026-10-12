<?php

namespace App\Services\Telegram;

use App\Models\Client;
use App\Services\Clients\ClientHealth;
use App\Services\Health\EnergyPlanner;
use App\Services\Health\TrainingPlanner;
use App\Support\Tr;
use Illuminate\Support\Carbon;

class ReminderComposer
{
    public function __construct(
        private ClientHealth $health,
        private EnergyPlanner $energy,
        private TrainingPlanner $training,
    ) {}

    /**
     * Build today's message. Returns the text (null when nothing is enabled) and whether a measure reminder was included.
     *
     * @return array{text:?string, measure:bool}
     */
    public function compose(Client $client, Carbon $localNow): array
    {
        $a = $this->health->analyze($client);
        if (! $a) {
            return ['text' => null, 'measure' => false];
        }

        $l = $client->lang;
        $level = $this->health->level($client, $a);
        $plan = $this->energy->plan($a, $level);
        $lines = [];

        if ($client->reminderEnabled('workout')) {
            [$type, $min] = $this->training->day($level, $localNow->dayOfWeekIso - 1);
            $lines[] = $min > 0
                ? Tr::get('rem.workout', [Tr::get('ty.'.$type, [], $l), $min], $l)
                : Tr::get('rem.rest', [], $l);
        }
        if ($client->reminderEnabled('meals')) {
            $lines[] = Tr::get('rem.meals', [$plan->kcal], $l);
        }
        if ($client->reminderEnabled('water')) {
            $lines[] = Tr::get('rem.water', [$plan->water], $l);
        }

        $measure = $client->reminderEnabled('measure') && $this->measureDue($client, $localNow);
        if ($measure) {
            $lines[] = Tr::get('rem.measure', [], $l);
        }

        if ($lines === []) {
            return ['text' => null, 'measure' => false];
        }

        return [
            'text' => Tr::get('rem.title', [], $l)."\n\n".implode("\n", array_map(fn ($s) => '• '.$s, $lines)),
            'measure' => $measure,
        ];
    }

    private function measureDue(Client $client, Carbon $now): bool
    {
        $days = config('hh.measure_every_days');
        $last = $client->latestMeasurement?->measured_on;

        return ($last === null || $last->diffInDays($now, true) >= $days)
            && ($client->measure_reminded_at === null || $client->measure_reminded_at->diffInDays($now, true) >= $days);
    }
}
