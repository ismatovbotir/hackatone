<?php

namespace App\Services\Telegram;

/**
 * Telegram Web App (Mini App) helpers.
 * initData is checked as Telegram documents it: secret = HMAC_SHA256(bot_token, key "WebAppData"),
 * hash = HMAC_SHA256(data_check_string, key secret), where data_check_string is every other field as
 * "key=value", sorted by key and joined with "\n".
 */
final class WebApp
{
    /** initData older than this is refused, so a leaked copy cannot be replayed later. */
    public const MAX_AGE_SECONDS = 86400;

    /** The Mini App address. Telegram only opens HTTPS web apps, so null otherwise. */
    public static function url(): ?string
    {
        $url = route('tg.app');

        return str_starts_with($url, 'https://') ? $url : null;
    }

    /**
     * The Telegram user from valid, fresh initData, or null.
     *
     * @return array{id:int, first_name?:string, username?:string, language_code?:string}|null
     */
    public static function user(string $initData, ?string $botToken): ?array
    {
        if (blank($botToken) || $initData === '') {
            return null;
        }

        parse_str($initData, $fields);
        $hash = $fields['hash'] ?? null;
        if (! is_string($hash)) {
            return null;
        }
        unset($fields['hash']);
        ksort($fields);

        $check = implode("\n", array_map(fn ($k, $v) => $k.'='.$v, array_keys($fields), $fields));
        $secret = hash_hmac('sha256', $botToken, 'WebAppData', true);

        if (! hash_equals(hash_hmac('sha256', $check, $secret), $hash)) {
            return null;
        }
        if ((int) ($fields['auth_date'] ?? 0) < time() - self::MAX_AGE_SECONDS) {
            return null;
        }

        $user = json_decode($fields['user'] ?? '', true);

        return is_array($user) && isset($user['id']) ? $user : null;
    }
}
