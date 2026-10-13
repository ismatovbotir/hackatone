<?php

namespace App\Services\Telegram;

use App\Models\Client;
use App\Models\DailyLog;
use App\Services\Clients\ClientHealth;
use App\Services\Clients\Leaderboard;
use App\Services\Clients\TrainingCalendar;
use App\Services\Health\EnergyPlanner;
use App\Services\Health\MenuPlanner;
use App\Support\Tr;
use Illuminate\Support\Carbon;

/** The end-of-day achievements text for a client who filled in today. Only effort, never height, weight or BMI. */
class EveningReport
{
    public function __construct(
        private ClientHealth $health,
        private EnergyPlanner $energy,
        private MenuPlanner $menu,
        private TrainingCalendar $calendar,
        private Leaderboard $leaderboard,
    ) {}

    public function compose(Client $client, DailyLog $log, Carbon $today): string
    {
        $l = $client->lang;
        $t = fn (string $key, array $args = []) => Tr::get($key, $args, $l);

        $a = $this->health->analyze($client);
        $level = $this->health->level($client, $a);
        $plan = $this->energy->plan($a, $level);
        [$session, $minutes] = $this->calendar->planFor($level, $today->copy()->startOfDay(), $log);
        $position = $this->leaderboard->positionOf($client, 'month');

        $lines = [$t('rep.title', [$client->first_name]), ''];

        $lines[] = $log->reading_goal
            ? $t('rep.pages_goal', [$log->pages_read, $log->reading_goal]).($log->pages_read >= $log->reading_goal ? ' ✅' : '')
            : $t('rep.pages', [$log->pages_read]);

        $lines[] = match (true) {
            $minutes === 0 => $t('rep.rest'),
            $log->workout_done => $t('rep.training_done', [Tr::get('ty.'.$session, [], $l), $log->workout_minutes ?? $minutes]).' ✅',
            default => $t('rep.training_not', [Tr::get('ty.'.$session, [], $l)]),
        };

        $lines[] = $t('rep.meals', [$log->mealsEaten(), count($this->menu->day($level, $plan->kcal))]);
        $lines[] = $t('rep.water', [number_format($log->water_ml / 1000, 1), number_format($plan->water, 1)]);
        $lines[] = '';
        $lines[] = $t('rep.points', [$log->points]);
        $lines[] = $t('rep.streak', [$position['streak']]);
        $lines[] = $position['rank'] ? $t('rep.rank', [$position['rank'], $position['of']]) : $t('rep.unranked');

        return implode("\n", $lines);
    }
}
