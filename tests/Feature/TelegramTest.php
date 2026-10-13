<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Services\Telegram\AdminNotifier;
use App\Services\Telegram\LinkCodeService;
use App\Services\Telegram\UpdateHandler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => '123:test', 'services.telegram.webhook_secret' => 'secret-secret-secret']);
    }

    private function fakeTelegram(int $status = 200): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => $status === 200, 'result' => []], $status)]);
    }

    private function update(int $chat, string $text): array
    {
        return ['update_id' => 1, 'message' => ['chat' => ['id' => $chat, 'type' => 'private'], 'from' => ['id' => $chat, 'language_code' => 'ru'], 'text' => $text]];
    }

    private function shareContact(int $chat, string $phone, ?int $owner = null): array
    {
        return ['update_id' => 3, 'message' => [
            'chat' => ['id' => $chat, 'type' => 'private'], 'from' => ['id' => $chat],
            'contact' => ['phone_number' => $phone, 'user_id' => $owner ?? $chat],
        ]];
    }

    /** The bot conversation: the code (from the QR link, or typed), then the person's own phone. */
    private function converse(int $chat, string $phone, string $code, bool $typed = false): void
    {
        $bot = app(UpdateHandler::class);
        if ($typed) {
            $bot->handle($this->update($chat, '/start'));
            $bot->handle($this->update($chat, $code));
        } else {
            $bot->handle($this->update($chat, "/start $code"));
        }
        $bot->handle($this->shareContact($chat, $phone));
    }

    public function test_code_from_the_qr_then_own_phone_links_the_client_once(): void
    {
        $this->fakeTelegram();
        $client = Client::factory()->withMeasurement()->create(['phone' => '998901234567', 'lang' => 'uz']);
        $code = app(LinkCodeService::class)->issue($client)['code'];

        $this->converse(555, '+998 90 123 45 67', $code);

        $client->refresh();
        $this->assertSame(555, (int) $client->telegram_chat_id);
        $this->assertTrue($client->user->isConfirmed());
        Http::assertSent(fn ($r) => str_contains($r['text'] ?? '', $client->first_name));

        // Never accept a code twice.
        $other = Client::factory()->withMeasurement()->create(['phone' => '998907777777']);
        $this->converse(777, '998907777777', $code);
        $this->assertNull($other->fresh()->telegram_chat_id);
    }

    public function test_typed_code_works_too(): void
    {
        $this->fakeTelegram();
        $client = Client::factory()->withMeasurement()->create(['phone' => '998901234567']);
        $code = app(LinkCodeService::class)->issue($client)['code'];

        $this->converse(42, '998901234567', substr($code, 0, 3).' '.substr($code, 3), typed: true);
        $this->assertSame(42, (int) $client->fresh()->telegram_chat_id);
    }

    public function test_plain_start_begins_registration_and_a_code_switches_to_confirmation(): void
    {
        $this->fakeTelegram();
        $bot = app(UpdateHandler::class);

        $bot->handle($this->update(8, '/start'));
        Http::assertSent(fn ($r) => isset($r['reply_markup']['inline_keyboard'][0][0]['callback_data'])); // language buttons

        $bot->handle($this->update(8, '123456'));
        Http::assertSent(fn ($r) => ($r['reply_markup']['keyboard'][0][0]['request_contact'] ?? false) === true);
    }

    public function test_someone_elses_contact_is_refused(): void
    {
        $this->fakeTelegram();
        $client = Client::factory()->withMeasurement()->create(['phone' => '998901234567']);
        $code = app(LinkCodeService::class)->issue($client)['code'];
        $bot = app(UpdateHandler::class);

        $bot->handle($this->update(42, "/start $code"));
        $bot->handle($this->shareContact(42, '998901234567', owner: 999)); // a forwarded contact card

        $this->assertNull($client->fresh()->telegram_chat_id);
    }

    public function test_phone_must_match_the_registration_and_code_survives(): void
    {
        $this->fakeTelegram();
        $client = Client::factory()->withMeasurement()->create(['phone' => '998901234567']);
        $code = app(LinkCodeService::class)->issue($client)['code'];

        $this->converse(42, '998909999999', $code);
        $this->assertNull($client->fresh()->telegram_chat_id);
        // The reply names which number the code was registered with (last two digits only).
        Http::assertSent(fn ($r) => str_contains($r['text'] ?? '', '•• 67') && str_contains($r['text'] ?? '', '•• 99'));

        // The owner can still use the same code with the right number.
        $this->converse(43, '998901234567', $code);
        $this->assertSame(43, (int) $client->fresh()->telegram_chat_id);
    }

    public function test_expired_code_is_refused(): void
    {
        $this->fakeTelegram();
        $client = Client::factory()->withMeasurement()->create(['phone' => '998901234567']);
        $code = app(LinkCodeService::class)->issue($client)['code'];
        $this->travel(16)->minutes();

        $this->converse(42, '998901234567', $code);
        $this->assertNull($client->fresh()->telegram_chat_id);
    }

    public function test_new_code_invalidates_the_old_one(): void
    {
        $this->fakeTelegram();
        $client = Client::factory()->withMeasurement()->create(['phone' => '998901234567']);
        $old = app(LinkCodeService::class)->issue($client)['code'];
        app(LinkCodeService::class)->issue($client);

        $this->converse(42, '998901234567', $old);
        $this->assertNull($client->fresh()->telegram_chat_id);
    }

    public function test_attempts_are_rate_limited_per_chat(): void
    {
        $this->fakeTelegram();
        $client = Client::factory()->withMeasurement()->create(['phone' => '998901234567']);
        $bot = app(UpdateHandler::class);
        $bot->handle($this->shareContact(9, '998901234567'));
        for ($i = 0; $i < 5; $i++) {
            $bot->handle($this->update(9, '000000'));
        }
        $code = app(LinkCodeService::class)->issue($client)['code'];
        $bot->handle($this->update(9, $code));

        $this->assertNull($client->fresh()->telegram_chat_id);
    }

    public function test_webhook_requires_the_secret(): void
    {
        $this->fakeTelegram();
        $this->postJson('/telegram/webhook', $this->update(1, '/start'))->assertForbidden();
        $this->postJson('/telegram/webhook', $this->update(1, '/start'), ['X-Telegram-Bot-Api-Secret-Token' => 'secret-secret-secret'])->assertOk();
    }

    public function test_reminders_are_sent_once_a_day_after_the_chosen_time(): void
    {
        $this->fakeTelegram();
        $this->travelTo(now(config('hh.timezone'))->setTime(18, 5));
        $client = Client::factory()->withMeasurement()->create(['reminder_time' => '18:00']);
        $client->forceFill(['telegram_chat_id' => 99])->save();
        $later = Client::factory()->withMeasurement()->create(['reminder_time' => '19:00']);
        $later->forceFill(['telegram_chat_id' => 100])->save();

        $this->artisan('reminders:send')->assertSuccessful();
        $this->artisan('reminders:send')->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => (int) ($r['chat_id'] ?? 0) === 99);
        $this->assertNotNull($client->fresh()->last_reminded_on);
    }

    public function test_reminder_text_has_no_phone_number(): void
    {
        $this->fakeTelegram();
        $this->travelTo(now(config('hh.timezone'))->setTime(20, 0));
        $client = Client::factory()->withMeasurement()->create(['phone' => '998901112233']);
        $client->forceFill(['telegram_chat_id' => 5])->save();

        $this->artisan('reminders:send');
        Http::assertSent(fn ($r) => ! str_contains($r['text'] ?? '', '901112233'));
    }

    public function test_blocked_bot_unlinks_the_client(): void
    {
        $this->fakeTelegram(403);
        $this->travelTo(now(config('hh.timezone'))->setTime(20, 0));
        $client = Client::factory()->withMeasurement()->create();
        $client->forceFill(['telegram_chat_id' => 5])->save();

        $this->artisan('reminders:send');
        $this->assertNull($client->fresh()->telegram_chat_id);
    }

    public function test_admins_get_a_registration_alert_without_phone(): void
    {
        $this->fakeTelegram();
        config(['services.telegram.admin_chat_ids' => '11, 22']);
        $client = Client::factory()->withMeasurement()->create(['phone' => '998901112233']);

        app(AdminNotifier::class)->newRegistration($client);

        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => ! str_contains($r['text'] ?? '', '901112233'));
    }

    public function test_cron_schedule_only_sends_reminders(): void
    {
        $commands = collect(app(Schedule::class)->events())->pluck('command')->implode(' ');

        $this->assertStringContainsString('reminders:send', $commands);
        $this->assertStringNotContainsString('telegram:poll', $commands);
    }

    public function test_qr_link_carries_the_secret_code(): void
    {
        $this->fakeTelegram();
        config(['services.telegram.bot_username' => '@hh_test_bot']);
        $user = Client::factory()->withMeasurement()->create(['user_id' => User::factory()->unverified()])->user;

        $html = $this->actingAs($user)->get(route('verification.notice'))->getContent();

        $this->assertMatchesRegularExpression('~https://t\.me/hh_test_bot\?start=\d{6}~', $html);
    }

    public function test_fix_phones_command_repairs_local_formats(): void
    {
        $old = Client::factory()->withMeasurement()->create(['phone' => '0901234567']);

        $this->artisan('hh:fix-phones')->assertSuccessful();
        $this->assertSame('0901234567', $old->fresh()->phone); // dry run

        $this->artisan('hh:fix-phones --apply')->assertSuccessful();
        $this->assertSame('998901234567', $old->fresh()->phone);
    }
}
