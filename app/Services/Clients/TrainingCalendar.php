<?php

namespace App\Services\Clients;

use App\Enums\Level;
use App\Models\Client;
use App\Models\DailyLog;
use App\Services\Health\TrainingPlanner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Month view of the plan with a training status per day:
 * done (marked by the client), missed (past training day not done), planned (today or later), rest, or none (before registration).
 * Food and water from the day's log are shown too. Uses the client's current level; level history is not stored.
 */
class TrainingCalendar
{
    /** How many days back a client may still fill in a day. */
    public const EDIT_DAYS_BACK = 7;

    public const GLASS_ML = 250;

    public function __construct(private TrainingPlanner $planner) {}

    public function today(): Carbon
    {
        return Carbon::now(config('hh.timezone'))->startOfDay();
    }

    /** @return Collection<int, array> */
    public function month(Client $client, Level $level, Carbon $month): Collection
    {
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $logs = $client->dailyLogs()
            ->whereBetween('log_date', [$start->toDateString(), $end->toDateString()])
            ->get()->keyBy(fn ($l) => $l->log_date->toDateString());

        $days = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days->push($this->day($client, $level, $d->copy(), $logs->get($d->toDateString())) + ['in_month' => $d->month === $month->month]);
        }

        return $days;
    }

    public function day(Client $client, Level $level, Carbon $date, ?DailyLog $log): array
    {
        [$session, $minutes] = $this->planner->day($level, $date->dayOfWeekIso - 1);
        $today = $this->today();
        $registered = $client->created_at->copy()->setTimezone(config('hh.timezone'))->startOfDay();

        $status = match (true) {
            (bool) $log?->workout_done => 'done',
            $date->lt($registered) => 'none',
            $minutes === 0 => 'rest',
            $date->lt($today) => 'missed',
            default => 'planned',
        };

        return [
            'date' => $date,
            'session' => $session,
            'minutes' => $minutes,
            'status' => $status,
            'log' => $log,
            'editable' => $date->lte($today) && $date->gte($today->copy()->subDays(self::EDIT_DAYS_BACK)) && $date->gte($registered),
        ];
    }

    /** Done, missed and planned training days, and pages read, for the days of a month. */
    public function summary(Collection $days): array
    {
        $own = $days->where('in_month', true);

        return [
            'done' => $own->where('status', 'done')->count(),
            'missed' => $own->where('status', 'missed')->count(),
            'planned' => $own->where('status', 'planned')->count(),
            'pages' => (int) $own->sum(fn ($d) => $d['log']?->pages_read ?? 0),
            'reading_days' => $own->filter(fn ($d) => ($d['log']?->pages_read ?? 0) > 0)->count(),
        ];
    }

    public function streak(Client $client, Level $level): int
    {
        $done = $client->dailyLogs()->where('workout_done', true)
            ->where('log_date', '>=', $this->today()->subDays(120)->toDateString())
            ->pluck('log_date')->map(fn ($d) => $d->toDateString())->flip();

        // Training days in a row that were done, walking back from yesterday (today does not break it).
        $streak = 0;
        for ($d = $this->today()->subDay(); $d->gte($client->created_at->copy()->startOfDay()); $d->subDay()) {
            [, $min] = $this->planner->day($level, $d->dayOfWeekIso - 1);
            if ($min === 0) {
                continue;
            }
            if (! $done->has($d->toDateString())) {
                break;
            }
            $streak++;
        }

        return $streak + ($done->has($this->today()->toDateString()) ? 1 : 0);
    }
}
