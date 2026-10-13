<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Services\Telegram\SendThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class TelegramLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        config(['services.telegram.bot_token' => '123:test']);
        $this->travelTo(Carbon::parse('2026-10-14 20:00', config('hh.timezone')));
    }

    private function linkedClients(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $c = Client::factory()->withMeasurement()->create(['reminder_time' => '18:00']);
            $c->forceFill(['telegram_chat_id' => 1000 + $i])->save();
        }
    }

    public function test_a_run_stops_at_the_cap_and_the_rest_is_sent_next_run(): void
    {
        config(['hh.telegram.per_run' => 3]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
        $this->linkedClients(5);

        $this->artisan('reminders:send');
        Http::assertSentCount(3);

        $this->artisan('reminders:send');
        Http::assertSentCount(5); // the remaining two, nobody twice
    }

    public function test_sending_is_paced_under_the_per_second_limit(): void
    {
        config(['hh.telegram.per_second' => 25]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
        $this->linkedClients(4);

        $this->artisan('reminders:send');

        Sleep::assertSleptTimes(3); // a short wait between consecutive messages
    }

    public function test_429_pauses_all_sending_for_retry_after(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'parameters' => ['retry_after' => 20]], 429)]);
        $this->linkedClients(3);

        $this->artisan('reminders:send');
        Http::assertSentCount(1); // stopped at the first 429
        $this->assertTrue(SendThrottle::paused());

        $this->artisan('reminders:evening');
        Http::assertSentCount(1); // the evening run respects the pause too

        $this->travel(21)->seconds();
        $this->assertFalse(SendThrottle::paused());
    }
}
