<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Admin-editable settings. Each key maps to a config path; a saved value overrides .env.
 */
final class Settings
{
    public const MAP = [
        'telegram.bot_token' => 'services.telegram.bot_token',
        'telegram.bot_username' => 'services.telegram.bot_username',
        'telegram.webhook_secret' => 'services.telegram.webhook_secret',
        'ai.hf_token' => 'services.huggingface.token',
        'ai.video_model' => 'services.huggingface.video_model',
        // Comma-separated chat IDs that get a message on every new registration.
        'telegram.admin_chat_ids' => 'services.telegram.admin_chat_ids',
    ];

    private const CACHE_KEY = 'hh-settings';

    /** Config values as they came from .env, before saved settings replaced them. */
    private static array $fromEnv = [];

    /** Called on boot: copy saved settings into config. */
    public static function apply(): void
    {
        foreach (self::MAP as $key => $path) {
            self::$fromEnv[$key] = config($path);
        }

        try {
            $values = Cache::rememberForever(self::CACHE_KEY, fn () => Schema::hasTable('settings')
                ? Setting::all()->pluck('value', 'key')->all()
                : []);
        } catch (Throwable) {
            return; // Before the first migration there is no table or cache.
        }

        foreach ($values as $key => $value) {
            if (isset(self::MAP[$key]) && $value !== null && $value !== '') {
                config([self::MAP[$key] => $value]);
            }
        }
    }

    /** @param  array<string, ?string>  $values */
    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! isset(self::MAP[$key])) {
                continue;
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            config([self::MAP[$key] => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /** The value .env (or config) gives for a setting, ignoring what is saved in the database. */
    public static function fromEnv(string $key): ?string
    {
        $value = array_key_exists($key, self::$fromEnv) ? self::$fromEnv[$key] : config(self::MAP[$key] ?? '');

        return filled($value) ? (string) $value : null;
    }

    public static function get(string $key): ?string
    {
        return config(self::MAP[$key]);
    }
}
