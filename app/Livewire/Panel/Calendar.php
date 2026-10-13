<?php

namespace App\Livewire\Panel;

use App\Livewire\Forms\DailyLogForm;
use App\Livewire\Forms\DayPlanForm;
use App\Livewire\Panel\Concerns\InteractsWithPlan;
use App\Livewire\Panel\Concerns\ShowsRating;
use App\Services\Clients\TrainingCalendar;
use App\Services\Health\ActivityVideos;
use App\Services\Health\EnergyPlanner;
use App\Services\Health\MenuPlanner;
use App\Support\Tr;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::panel')]
class Calendar extends Component
{
    use InteractsWithPlan, ShowsRating;

    /** Month shown, Y-m. */
    #[Url(as: 'm')]
    public string $month = '';

    /** Day open in the modal, Y-m-d, or '' when closed. */
    #[Locked]
    public string $open = '';

    public DailyLogForm $day;

    public DayPlanForm $plan;

    public function mount(TrainingCalendar $calendar): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = $calendar->today()->format('Y-m');
        }

        // The evening reminder links here with ?day=today to open today's form straight away.
        if (request()->query('day') === 'today') {
            $this->openDay($calendar->today()->toDateString(), $calendar, app(MenuPlanner::class), app(EnergyPlanner::class));
        }
    }

    public function shift(int $by): void
    {
        $this->month = $this->monthDate()->addMonthsNoOverflow($by)->format('Y-m');
    }

    public function openDay(string $date, TrainingCalendar $calendar, MenuPlanner $menu, EnergyPlanner $energy): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return;
        }
        $info = $this->info($date, $calendar);
        $meals = $menu->day($this->level, $energy->plan($this->analysis, $this->level)->kcal);

        $this->day->load($info['log'], $info['minutes'], count($meals), TrainingCalendar::GLASS_ML);
        $this->plan->load($info);
        $this->open = $date;
    }

    public function close(): void
    {
        $this->open = '';
    }

    public function saveDay(TrainingCalendar $calendar)
    {
        if ($this->open === '') {
            return;
        }
        $info = $this->info($this->open, $calendar);
        abort_unless($info['editable'], 403);

        $waterGoalMl = (int) round(app(EnergyPlanner::class)->plan($this->analysis, $this->level)->water * 1000);
        $this->day->save($this->client, $info, $this->level->value, TrainingCalendar::GLASS_ML, $waterGoalMl);
        $this->open = '';
        session()->flash('status', t('cal.saved'));
    }

    /** Change the plan for today or a future day. Results are still logged on the day itself. */
    public function savePlan(TrainingCalendar $calendar): void
    {
        if ($this->open === '') {
            return;
        }
        $info = $this->info($this->open, $calendar);
        abort_unless($info['plannable'], 403);

        $this->plan->save($this->client, $info, $this->level->value);
        session()->flash('status', t('plan.saved'));
        $this->openDay($this->open, $calendar, app(MenuPlanner::class), app(EnergyPlanner::class));
    }

    /** Back to the level's weekly pattern for that day (the reading goal is kept). */
    public function resetPlan(TrainingCalendar $calendar): void
    {
        if ($this->open === '') {
            return;
        }
        $info = $this->info($this->open, $calendar);
        abort_unless($info['plannable'], 403);

        $info['log']?->update(['custom_plan' => false]);
        $this->openDay($this->open, $calendar, app(MenuPlanner::class), app(EnergyPlanner::class));
    }

    private function info(string $date, TrainingCalendar $calendar): array
    {
        $d = Carbon::createFromFormat('Y-m-d', $date, config('hh.timezone'))->startOfDay();
        $info = $calendar->day($this->client, $this->level, $d, $this->client->dailyLogs()->where('log_date', $date)->first());

        // Opened from the Telegram web app: only today can be filled in or planned.
        if ($this->viaTelegram()) {
            $isToday = $d->equalTo($calendar->today());
            $info['editable'] = $info['editable'] && $isToday;
            $info['plannable'] = $info['plannable'] && $isToday;
            $info['telegram_locked'] = ! $isToday;
        }

        return $info;
    }

    private function viaTelegram(): bool
    {
        return (bool) session('via_telegram');
    }

    private function monthDate(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $this->month.'-01', config('hh.timezone'))->startOfDay();
    }

    public function render(TrainingCalendar $calendar, ActivityVideos $videos, MenuPlanner $menu, EnergyPlanner $energy)
    {
        $days = $calendar->month($this->client, $this->level, $this->monthDate());
        $plan = $energy->plan($this->analysis, $this->level);

        return view('livewire.panel.calendar', [
            'days' => $days,
            'summary' => $calendar->summary($days),
            'streak' => $calendar->streak($this->client, $this->level),
            'today' => $calendar->today()->toDateString(),
            'selected' => $this->open !== '' ? $this->info($this->open, $calendar) : null,
            'menu' => $menu->day($this->level, $plan->kcal),
            'waterGoal' => (int) ceil($plan->water * 1000 / TrainingCalendar::GLASS_ML),
            'videos' => $videos->mediaFor($this->analysis->youth),
            'monthLabel' => $this->monthDate()->locale(Tr::dateLocale())->translatedFormat('F Y'),
        ])->title(t('tab.cal'));
    }
}
