<?php

namespace Tests\Feature;

use App\Livewire\Admin\Monetisation;
use App\Models\Client;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonetisationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_models_and_the_calculator_updates_and_saves(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Monetisation::class)
            ->assertSee(t('mon.m.schools'))->assertSee(t('mon.ethics'))
            ->set('in.active_users', 2000)->set('in.conversion_pct', 5)->set('in.price_month', 20000)->set('in.payment_fee_pct', 0)
            ->assertViewHas('r', fn ($r) => $r['payers'] === 100 && abs($r['mix']['subscription'] - 2000000) < 0.01)
            ->set('in.churn_pct', 150)->call('save')->assertHasErrors('in.churn_pct')
            ->set('in.churn_pct', 8)->call('save')->assertHasNoErrors();

        $this->assertEquals(2000, json_decode(Setting::find('monetisation.assumptions')->value, true)['active_users']);
        Livewire::test(Monetisation::class)->assertSet('in.active_users', 2000);
    }

    public function test_real_active_users_come_from_the_calendar(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $c = Client::factory()->withMeasurement()->create();
        $c->dailyLogs()->create(['log_date' => now(config('hh.timezone'))->toDateString(), 'level' => 'mid', 'session' => 'cardio', 'planned_minutes' => 40, 'pages_read' => 3]);

        Livewire::test(Monetisation::class)->call('useRealUsers')->assertSet('in.active_users', 1);
    }

    public function test_only_admins(): void
    {
        $this->actingAs(Client::factory()->withMeasurement()->create()->user)->get(route('admin.monetisation'))->assertForbidden();
    }
}
