<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\Settings;
use Database\Seeders\TelegramBotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelegramBotSeederTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456789:AAHabcdefghijklmnopqrstuvwxyz012345';

    /** Pretend .env holds these values, as at boot. */
    private function env(array $values): void
    {
        config(collect($values)->mapWithKeys(fn ($v, $k) => [Settings::MAP[$k] => $v])->all());
        Settings::apply();
    }

    public function test_env_values_are_saved_encrypted_and_a_secret_is_made(): void
    {
        $this->env([
            'telegram.bot_token' => self::TOKEN,
            'telegram.bot_username' => '@hh_test_bot',
            'telegram.webhook_secret' => null,
            'telegram.admin_chat_ids' => ' 111, 222 ',
        ]);

        $this->seed(TelegramBotSeeder::class);

        $this->assertSame(self::TOKEN, Setting::find('telegram.bot_token')->value);
        $this->assertNotSame(self::TOKEN, DB::table('settings')->where('key', 'telegram.bot_token')->value('value')); // encrypted at rest
        $this->assertSame('hh_test_bot', Setting::find('telegram.bot_username')->value);
        $this->assertSame('111,222', Setting::find('telegram.admin_chat_ids')->value);
        $this->assertSame(48, strlen(Setting::find('telegram.webhook_secret')->value));
    }

    public function test_empty_env_never_wipes_panel_values(): void
    {
        Settings::save(['telegram.bot_token' => self::TOKEN, 'telegram.webhook_secret' => str_repeat('s', 20)]);
        $this->env(['telegram.bot_token' => null, 'telegram.bot_username' => null, 'telegram.webhook_secret' => null, 'telegram.admin_chat_ids' => null]);

        $this->seed(TelegramBotSeeder::class);

        $this->assertSame(self::TOKEN, Setting::find('telegram.bot_token')->value);
        $this->assertSame(str_repeat('s', 20), Setting::find('telegram.webhook_secret')->value);
    }

    public function test_invalid_values_are_skipped(): void
    {
        $this->env(['telegram.bot_token' => 'not-a-token', 'telegram.bot_username' => 'ok_bot_name', 'telegram.webhook_secret' => null, 'telegram.admin_chat_ids' => '12, abc']);

        $this->seed(TelegramBotSeeder::class);

        $this->assertNull(Setting::find('telegram.bot_token'));
        $this->assertNull(Setting::find('telegram.admin_chat_ids'));
        $this->assertSame('ok_bot_name', Setting::find('telegram.bot_username')->value);
        $this->assertNull(Setting::find('telegram.webhook_secret')); // no token, so no secret
    }
}
