<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Services\Telegram\WebApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramWebAppTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456:TESTTOKENtesttokenTESTTOKENtesttoken';

    /** initData signed the way Telegram signs it. */
    private function initData(int $userId, ?int $authDate = null, string $token = self::TOKEN): string
    {
        $fields = [
            'auth_date' => (string) ($authDate ?? time()),
            'query_id' => 'AAHdF6IQAAAAAN0XohDhrOrc',
            'user' => json_encode(['id' => $userId, 'first_name' => 'Aziz', 'language_code' => 'uz']),
        ];
        ksort($fields);
        $check = implode("\n", array_map(fn ($k, $v) => "$k=$v", array_keys($fields), $fields));
        $fields['hash'] = hash_hmac('sha256', $check, hash_hmac('sha256', $token, 'WebAppData', true));

        return http_build_query($fields);
    }

    public function test_valid_init_data_is_accepted_and_tampering_is_refused(): void
    {
        $this->assertSame(55, WebApp::user($this->initData(55), self::TOKEN)['id']);
        $this->assertNull(WebApp::user($this->initData(55, token: '999:other'), self::TOKEN)); // signed with another bot
        $this->assertNull(WebApp::user(str_replace('%22id%22%3A55', '%22id%22%3A56', $this->initData(55)), self::TOKEN)); // user id changed
        $this->assertNull(WebApp::user($this->initData(55, time() - 2 * 86400), self::TOKEN)); // too old
        $this->assertNull(WebApp::user($this->initData(55), null));
    }

    public function test_linked_client_is_signed_in_from_telegram(): void
    {
        config(['services.telegram.bot_token' => self::TOKEN]);
        $client = Client::factory()->withMeasurement()->create(['lang' => 'ru']);
        $client->forceFill(['telegram_chat_id' => 7001])->save();

        $this->postJson(route('tg.auth'), ['init_data' => $this->initData(7001)])
            ->assertOk()->assertJson(['status' => 'ok', 'redirect' => route('panel.overview')]);
        $this->assertAuthenticatedAs($client->user);
    }

    public function test_unlinked_or_forged_requests_do_not_sign_in(): void
    {
        config(['services.telegram.bot_token' => self::TOKEN]);

        $this->postJson(route('tg.auth'), ['init_data' => $this->initData(8888)])->assertNotFound()->assertJson(['status' => 'not_linked']);
        $this->postJson(route('tg.auth'), ['init_data' => 'user=%7B%22id%22%3A1%7D&hash=abc'])->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_app_page_loads_the_telegram_script(): void
    {
        $this->get(route('tg.app'))->assertOk()->assertSee('telegram-web-app.js', false);
    }
}
