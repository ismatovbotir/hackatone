<?php

namespace App\Livewire\Panel;

use App\Livewire\Forms\DailyLogForm;
use App\Livewire\Panel\Concerns\InteractsWithPlan;
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
    use InteractsWithPlan;

    /** Month shown, Y-m. */
    #[Url(as: 'm')]
    public string $month = '';

    /** Day open in the modal, Y-m-d, or '' when closed. */
    #[Locked]
    public string $open = '';

    public DailyLogForm $day;

    public function mount(TrainingCalendar $calendar): void
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = $calendar->today()->format('Y-m');
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

        $this->day->save($this->client, $info, $this->level->value, TrainingCalendar::GLASS_ML);
        $this->open = '';
        session()->flash('status', t('cal.saved'));
    }

    private function info(string $date, TrainingCalendar $calendar): array
    {
        $d = Carbon::createFromFormat('Y-m-d', $date, config('hh.timezone'))->startOfDay();

        return $calendar->day($this->client, $this->level, $d, $this->client->dailyLogs()->whereDate('log_date', $date)->first());
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
