<?php

namespace Tests\Feature;

use App\Livewire\Admin\Settings;
use App\Models\User;
use App\Support\CronHeartbeat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CronCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_sends_server_time_to_every_admin_chat(): void
    {
        config(['services.telegram.bot_token' => '123:test', 'services.telegram.admin_chat_ids' => '11,22']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);

        $this->artisan('cron:ping')->assertSuccessful();

        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => str_contains($r['text'], now(config('hh.timezone'))->format('d.m.Y')));
    }

    public function test_ping_fails_clearly_without_bot_settings(): void
    {
        $this->artisan('cron:ping')->assertFailed();
    }

    public function test_ping_schedule_follows_the_setting(): void
    {
        $pings = fn () => collect(app(Schedule::class)->events())->filter(fn ($e) => str_contains((string) $e->command, 'cron:ping'));

        $this->assertCount(0, $pings()); // off by default: not scheduled at all

        config(['hh.cron_ping' => 'hourly']);
        require base_path('routes/console.php');
        $this->assertSame('0 * * * *', $pings()->first()->expression);
    }

    public function test_heartbeat_is_shown_in_admin_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(Settings::class)->assertSee(t('cron.never'));

        $this->artisan('schedule:run')->assertSuccessful();
        $this->assertTrue(CronHeartbeat::healthy());

        $this->travel(10)->minutes();
        $this->assertFalse(CronHeartbeat::healthy());
    }
}
