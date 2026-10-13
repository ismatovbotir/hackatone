<?php

namespace Tests\Feature;

use App\Livewire\Admin\ClientShow;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Panel\CompleteProfile;
use App\Models\Client;
use App\Models\User;
use App\Services\Telegram\UpdateHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TelegramRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => '123:test']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
    }

    private function text(int $chat, string $text): array
    {
        return ['message' => ['chat' => ['id' => $chat, 'type' => 'private'], 'from' => ['id' => $chat], 'text' => $text]];
    }

    private function lang(int $chat, string $lang): array
    {
        return ['callback_query' => ['id' => 'cb', 'data' => "lang:$lang", 'message' => ['chat' => ['id' => $chat]]]];
    }

    private function contact(int $chat, string $phone, ?int $owner = null): array
    {
        return ['message' => ['chat' => ['id' => $chat, 'type' => 'private'], 'from' => ['id' => $chat], 'contact' => ['phone_number' => $phone, 'user_id' => $owner ?? $chat]]];
    }

    private function register(int $chat, string $phone, string $name, string $lang = 'ru'): void
    {
        $bot = app(UpdateHandler::class);
        $bot->handle($this->text($chat, '/start'));
        $bot->handle($this->lang($chat, $lang));
        $bot->handle($this->contact($chat, $phone));
        $bot->handle($this->text($chat, $name));
    }

    public function test_full_registration_in_telegram_without_email(): void
    {
        $this->register(900, '+998 91 234 56 78', 'Malika Yusupova');

        $client = Client::firstWhere('phone', '998912345678');
        $this->assertNotNull($client);
        $this->assertSame(['Malika', 'Yusupova', 'ru', 'telegram', 900], [$client->first_name, $client->last_name, $client->lang, $client->source, (int) $client->telegram_chat_id]);
        $this->assertNull($client->user->email);
        $this->assertNull($client->user->password);
        $this->assertTrue($client->user->isConfirmed()); // Telegram proves the number
        $this->assertFalse($client->isComplete());
        Http::assertSent(fn ($r) => str_contains(json_encode($r['reply_markup'] ?? []), 'inline_keyboard') && str_contains($r['text'] ?? '', 'Последний шаг'));
    }

    public function test_incomplete_profile_must_be_filled_in_before_the_panel(): void
    {
        $this->register(901, '998912345679', 'Aziz Karimov');
        $user = Client::firstWhere('phone', '998912345679')->user;
        $this->actingAs($user);

        $this->get(route('panel.overview'))->assertRedirect(route('panel.profile'));

        Livewire::test(CompleteProfile::class)
            ->set('form.dob', now()->subYears(13)->toDateString())->set('form.sex', 'M')
            ->set('form.w', '45')->set('form.h', '152')
            ->call('save')->assertHasErrors('form.consent')
            ->set('form.consent', true)->call('save')->assertHasNoErrors()->assertRedirect(route('panel.overview'));

        $this->assertTrue($user->client->fresh()->isComplete());
        $this->get(route('panel.overview'))->assertOk();
    }

    public function test_optional_email_needs_a_password(): void
    {
        $this->register(902, '998912345670', 'Aziz Karimov');
        $this->actingAs(Client::firstWhere('phone', '998912345670')->user);

        Livewire::test(CompleteProfile::class)
            ->set('form.dob', '2012-05-05')->set('form.sex', 'M')->set('form.w', '45')->set('form.h', '152')->set('form.consent', true)
            ->set('form.email', 'aziz@school.uz')->call('save')->assertHasErrors('form.password')
            ->set('form.password', 'secret123')->set('form.password_confirmation', 'secret123')->call('save')->assertHasNoErrors();

        $this->post('/logout');
        $this->post('/login', ['email' => 'aziz@school.uz', 'password' => 'secret123'])->assertRedirect(route('panel.overview'));
    }

    public function test_number_of_an_unlinked_website_account_links_that_account(): void
    {
        $web = Client::factory()->withMeasurement()->create(['phone' => '998901112233']);
        $users = User::count();

        $bot = app(UpdateHandler::class);
        $bot->handle($this->text(903, '/start'));
        $bot->handle($this->lang(903, 'uz'));
        $bot->handle($this->contact(903, '998901112233'));

        $this->assertSame(903, (int) $web->fresh()->telegram_chat_id);
        $this->assertSame($users, User::count()); // no duplicate account
    }

    public function test_number_already_on_another_telegram_account_is_refused(): void
    {
        $taken = Client::factory()->withMeasurement()->create(['phone' => '998901112244']);
        $taken->forceFill(['telegram_chat_id' => 1])->save();

        $this->register(904, '998901112244', 'Someone Else');

        $this->assertSame(1, (int) $taken->fresh()->telegram_chat_id);
        $this->assertSame(1, Client::where('phone', '998901112244')->count());
    }

    public function test_bad_names_and_foreign_contacts_are_refused(): void
    {
        $bot = app(UpdateHandler::class);
        $bot->handle($this->text(905, '/start'));
        $bot->handle($this->lang(905, 'en'));
        $bot->handle($this->contact(905, '998901112255', owner: 42)); // someone else's card
        $this->assertSame(0, Client::where('phone', '998901112255')->count());

        $bot->handle($this->contact(905, '998901112255'));
        $bot->handle($this->text(905, '12345'));
        $this->assertSame(0, Client::where('phone', '998901112255')->count());

        $bot->handle($this->text(905, 'Oʻgʻiloy Sobirova'));
        $this->assertSame('Oʻgʻiloy', Client::firstWhere('phone', '998901112255')->first_name);
    }

    public function test_start_again_welcomes_back_without_a_new_account(): void
    {
        $this->register(906, '998901112266', 'Aziz Karimov');
        $count = Client::count();

        app(UpdateHandler::class)->handle($this->text(906, '/start'));

        $this->assertSame($count, Client::count());
    }

    public function test_incomplete_profiles_do_not_break_admin_pages_or_reminders(): void
    {
        $this->register(907, '998901112277', 'Aziz Karimov');
        $client = Client::firstWhere('phone', '998901112277');

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(Dashboard::class)->assertOk();
        Livewire::test(ClientShow::class, ['client' => $client])->assertSee(t('admin.incomplete'));

        $this->artisan('reminders:send')->assertSuccessful();
        $this->travelTo(now(config('hh.timezone'))->setTime(21, 0));
        $this->artisan('reminders:evening')->assertSuccessful();
        Http::assertSent(fn ($r) => (int) ($r['chat_id'] ?? 0) === 907 && str_contains($r['text'] ?? '', 'Aziz'));
    }
}
