<?php

namespace Tests\Feature;

use App\Enums\Level;
use App\Livewire\Panel\Calendar;
use App\Models\Client;
use App\Services\Clients\TrainingCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DayPlanAndEveningTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-14 12:00', config('hh.timezone'))); // Wednesday
        $this->client = Client::factory()->withMeasurement(150, 42)->create(['birth_date' => '2014-01-01', 'created_at' => '2026-10-01', 'level' => 'mid']);
        $this->actingAs($this->client->user);
    }

    private function day(string $date): array
    {
        return app(TrainingCalendar::class)->day($this->client, Level::Mid, Carbon::parse($date, config('hh.timezone')), $this->client->dailyLogs()->where('log_date', $date)->first());
    }

    public function test_a_future_day_can_be_planned_and_the_calendar_follows_it(): void
    {
        Livewire::test(Calendar::class)->call('openDay', '2026-10-20')
            ->assertSee(t('plan.title'))
            ->set('plan.session', 'sport')->set('plan.minutes', 5)->call('savePlan')->assertHasErrors('plan.minutes')
            ->set('plan.minutes', 45)->set('plan.reading_goal', 20)->call('savePlan')->assertHasNoErrors();

        $d = $this->day('2026-10-20');
        $this->assertSame(['sport', 45, 20, true], [$d['session'], $d['minutes'], $d['reading_goal'], $d['custom']]);

        // A planned rest day is not "missed".
        Livewire::test(Calendar::class)->call('openDay', '2026-10-14')->set('plan.session', 'rest')->call('savePlan')->assertHasNoErrors();
        $this->travelTo(Carbon::parse('2026-10-15 12:00', config('hh.timezone')));
        $this->assertSame('rest', $this->day('2026-10-14')['status']);

        Livewire::test(Calendar::class)->call('openDay', '2026-10-20')->call('resetPlan');
        $this->assertFalse($this->day('2026-10-20')['custom']);
        $this->assertSame(20, $this->day('2026-10-20')['reading_goal']); // goal kept
    }

    public function test_past_days_cannot_be_planned_and_future_days_have_no_results(): void
    {
        Livewire::test(Calendar::class)->call('openDay', '2026-10-10')->call('savePlan')->assertForbidden();
        Livewire::test(Calendar::class)->call('openDay', '2026-10-20')->assertDontSee(t('cal.pages'));
    }

    public function test_in_the_telegram_web_app_only_today_can_be_changed(): void
    {
        session(['via_telegram' => true]);

        Livewire::test(Calendar::class)->call('openDay', '2026-10-13')->assertSee(t('cal.tg_today_only'))->call('saveDay')->assertForbidden();
        Livewire::test(Calendar::class)->call('openDay', '2026-10-20')->call('savePlan')->assertForbidden();
        Livewire::test(Calendar::class)->call('openDay', '2026-10-14')->set('day.pages_read', 10)->call('saveDay')->assertHasNoErrors();

        $this->assertSame(10, $this->client->dailyLogs()->where('log_date', '2026-10-14')->first()->pages_read);
    }

    public function test_reminder_link_opens_todays_form(): void
    {
        Livewire::withQueryParams(['day' => 'today'])->test(Calendar::class)->assertSet('open', '2026-10-14');
    }

    public function test_evening_reminder_is_sent_once_after_the_time_and_only_if_nothing_is_logged(): void
    {
        config(['services.telegram.bot_token' => '123:test', 'hh.evening_time' => '20:30']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
        $this->client->forceFill(['telegram_chat_id' => 501])->save();
        $done = Client::factory()->withMeasurement()->create();
        $done->forceFill(['telegram_chat_id' => 502])->save();
        $done->dailyLogs()->create(['log_date' => '2026-10-14', 'level' => 'mid', 'session' => 'cardio', 'planned_minutes' => 40, 'pages_read' => 5]);
        $off = Client::factory()->withMeasurement()->create(['reminders' => ['evening' => false]]);
        $off->forceFill(['telegram_chat_id' => 503])->save();

        $this->artisan('reminders:evening'); // 12:00, too early
        Http::assertNothingSent();

        $this->travelTo(Carbon::parse('2026-10-14 20:31', config('hh.timezone')));
        $this->artisan('reminders:evening');
        $this->artisan('reminders:evening'); // only once a day

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => (int) $r['chat_id'] === 501
            && str_contains($r['reply_markup']['inline_keyboard'][0][0]['url'] ?? '', 'day=today'));
    }
}
