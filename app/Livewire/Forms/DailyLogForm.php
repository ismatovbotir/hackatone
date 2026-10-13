<?php

namespace App\Livewire\Forms;

use App\Models\Client;
use App\Models\DailyLog;
use App\Services\Clients\Points;
use Livewire\Form;

class DailyLogForm extends Form
{
    /** Mental growth: pages read that day (first field in the day form). */
    public ?int $pages_read = null;

    public bool $workout_done = false;

    public ?int $workout_minutes = null;

    /** @var list<bool> */
    public array $meals = [];

    public int $glasses = 0;

    public function load(?DailyLog $log, int $plannedMinutes, int $mealCount, int $glassMl): void
    {
        $this->pages_read = $log?->pages_read ?: null;
        $this->workout_done = (bool) $log?->workout_done;
        $this->workout_minutes = $log?->workout_minutes ?? ($plannedMinutes ?: null);
        $this->meals = array_map(fn ($i) => (bool) ($log?->meals[$i] ?? false), range(0, $mealCount - 1));
        $this->glasses = (int) round(($log?->water_ml ?? 0) / $glassMl);
        $this->resetValidation();
    }

    public function save(Client $client, array $day, string $level, int $glassMl, int $waterGoalMl): void
    {
        $this->validate([
            'pages_read' => ['nullable', 'integer', 'between:0,2000'],
            'workout_done' => ['boolean'],
            'workout_minutes' => ['nullable', 'integer', 'between:0,600'],
            'meals' => ['array', 'max:8'],
            'meals.*' => ['boolean'],
            'glasses' => ['integer', 'between:0,40'],
        ], ['pages_read.*' => t('e.pages')]);

        $done = $day['minutes'] > 0 && $this->workout_done;
        $minutes = $done ? $this->workout_minutes : null;
        $meals = array_values(array_map('boolval', $this->meals));
        $water = $this->glasses * $glassMl;

        $client->dailyLogs()->updateOrCreate(['log_date' => $day['date']->toDateString()], [
            'level' => $level,
            'session' => $day['minutes'] > 0 ? $day['session'] : 'rest',
            'planned_minutes' => $day['minutes'],
            'workout_done' => $done,
            'workout_minutes' => $minutes,
            'meals' => $meals,
            'water_ml' => $water,
            'pages_read' => (int) $this->pages_read,
            'points' => Points::forDay($day['minutes'], $done, $minutes, count(array_filter($meals)), $water, $waterGoalMl, (int) $this->pages_read),
        ]);
    }
}
