<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\DailyLog;
use App\Models\Setting;
use App\Services\Finance\UnitEconomics;
use App\Services\Health\HealthAnalyzer;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Monetisation models and a unit-economics calculator on the admin's own assumptions (UZS). */
#[Layout('layouts::panel')]
class Monetisation extends Component
{
    private const KEY = 'monetisation.assumptions';

    /** @var array<string, float|int|string> */
    public array $in = [];

    public function mount(): void
    {
        $saved = json_decode((string) Setting::find(self::KEY)?->value, true);
        $this->in = array_merge(UnitEconomics::defaults(), is_array($saved) ? $saved : []);
    }

    /** Start from the real number of clients active in the last 30 days. */
    public function useRealUsers(): void
    {
        $this->in['active_users'] = max(1, $this->base()['active30']);
    }

    public function save(): void
    {
        $this->validate(collect(UnitEconomics::defaults())->mapWithKeys(fn ($v, $k) => ["in.$k" => ['required', 'numeric', 'min:0', str_ends_with($k, '_pct') ? 'max:100' : 'max:1000000000000']])->all());
        Setting::updateOrCreate(['key' => self::KEY], ['value' => json_encode(array_map('floatval', $this->in))]);
        session()->flash('status', t('mon.saved'));
    }

    public function resetDefaults(): void
    {
        $this->in = UnitEconomics::defaults();
    }

    private function base(): array
    {
        $clients = Client::with('latestMeasurement')->get();

        return [
            'clients' => $clients->count(),
            'complete' => $clients->filter->isComplete()->count(),
            'linked' => $clients->whereNotNull('telegram_chat_id')->count(),
            'active30' => DailyLog::where('log_date', '>=', now(config('hh.timezone'))->subDays(30)->toDateString())->distinct()->count('client_id'),
            'minors' => $clients->filter(fn ($c) => $c->birth_date && HealthAnalyzer::age($c->birth_date) < 18)->count(),
        ];
    }

    public function render()
    {
        $numbers = array_map(fn ($v) => is_numeric($v) ? (float) $v : 0.0, $this->in);

        return view('livewire.admin.monetisation', [
            'base' => $this->base(),
            'r' => UnitEconomics::calculate($numbers),
        ])->title(t('mon.title'));
    }
}
