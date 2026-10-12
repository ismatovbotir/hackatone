<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Copies the Telegram bot settings from .env (TELEGRAM_BOT_TOKEN, TELEGRAM_BOT_USERNAME,
 * TELEGRAM_WEBHOOK_SECRET, TELEGRAM_ADMIN_CHAT_IDS) into the encrypted settings that Admin > Settings uses.
 * Empty .env values are skipped, so nothing an admin entered in the panel is wiped. Invalid values are skipped with a warning.
 *
 *   php artisan db:seed --class=TelegramBotSeeder
 */
class TelegramBotSeeder extends Seeder
{
    /** Same checks as the settings form. */
    private const RULES = [
        'telegram.bot_token' => '/^\d+:[A-Za-z0-9_-]{30,}$/',
        'telegram.bot_username' => '/^[A-Za-z][A-Za-z0-9_]{3,31}$/',
        'telegram.webhook_secret' => '/^[A-Za-z0-9_-]{16,256}$/',
        'telegram.admin_chat_ids' => '/^-?\d+(,-?\d+)*$/',
    ];

    public function run(): void
    {
        $values = [];

        foreach (self::RULES as $key => $pattern) {
            $value = Settings::fromEnv($key);
            if ($value === null) {
                continue;
            }

            $value = match ($key) {
                'telegram.bot_username' => ltrim(trim($value), '@'),
                'telegram.admin_chat_ids' => implode(',', array_filter(array_map('trim', explode(',', $value)))),
                default => trim($value),
            };

            if (! preg_match($pattern, $value)) {
                $this->command?->warn("Skipped {$key}: the value in .env does not look right.");

                continue;
            }

            $values[$key] = $value;
        }

        // The webhook needs a secret; make one when there is a token but no secret anywhere.
        // Count only a valid token from .env or one already saved in the panel (config may hold an invalid .env value).
        $hasToken = isset($values['telegram.bot_token']) || filled(Setting::find('telegram.bot_token')?->value);
        if ($hasToken && ! isset($values['telegram.webhook_secret']) && blank(Setting::find('telegram.webhook_secret')?->value)) {
            $values['telegram.webhook_secret'] = Str::random(48);
        }

        if ($values === []) {
            $this->command?->warn('Telegram bot: nothing to seed. Fill TELEGRAM_* in .env first.');

            return;
        }

        Settings::save($values);

        $this->command?->info('Telegram bot settings saved: '.implode(', ', array_keys($values)).'.');
        if (isset($values['telegram.bot_token'])) {
            $this->command?->line('Next: Admin > Settings > Check token, then Set webhook (needs HTTPS).');
        }
    }
}
