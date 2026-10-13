<?php

namespace App\Livewire\Forms;

use App\Models\Client;
use Illuminate\Validation\Rule;
use Livewire\Form;

/** The client's own plan for a day (today or later): which session, how long, and a reading goal. */
class DayPlanForm extends Form
{
    public const SESSIONS = ['cardio', 'strength', 'stretch', 'sport', 'recover', 'rest'];

    public string $session = 'rest';

    public ?int $minutes = null;

    public ?int $reading_goal = null;

    /** @param  array  $day  from TrainingCalendar::day() */
    public function load(array $day): void
    {
        $this->session = $day['minutes'] > 0 ? $day['session'] : 'rest';
        $this->minutes = $day['minutes'] ?: null;
        $this->reading_goal = $day['reading_goal'];
        $this->resetValidation();
    }

    public function save(Client $client, array $day, string $level): void
    {
        $rest = $this->session === 'rest';

        $this->validate([
            'session' => ['required', Rule::in(self::SESSIONS)],
            // A real session: at least 10 minutes, so a 1-minute "session" cannot earn training points.
            'minutes' => $rest ? ['nullable'] : ['required', 'integer', 'between:10,180'],
            'reading_goal' => ['nullable', 'integer', 'between:1,500'],
        ], [
            'minutes.*' => t('e.plan_minutes'),
            'reading_goal.*' => t('e.reading_goal'),
        ]);

        $client->dailyLogs()->updateOrCreate(['log_date' => $day['date']->toDateString()], [
            'level' => $level,
            'custom_plan' => true,
            'session' => $this->session,
            'planned_minutes' => $rest ? 0 : (int) $this->minutes,
            'reading_goal' => $this->reading_goal ?: null,
        ]);
    }
}
