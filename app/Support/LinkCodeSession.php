<?php

namespace App\Support;

use App\Models\Client;
use App\Services\Telegram\LinkCodeService;
use Illuminate\Support\Carbon;

/** The plain link code lives only in the session; the database keeps its hash. */
final class LinkCodeSession
{
    private const KEY = 'tg_code';

    /** @return array{code:string, expires_at:Carbon} */
    public static function current(Client $client): array
    {
        $saved = session(self::KEY);
        if ($saved && $saved['client_id'] === $client->id && $saved['expires_at'] > now()->getTimestamp()) {
            return ['code' => $saved['code'], 'expires_at' => Carbon::createFromTimestamp($saved['expires_at'])];
        }

        return self::renew($client);
    }

    /** @return array{code:string, expires_at:Carbon} */
    public static function renew(Client $client): array
    {
        $new = app(LinkCodeService::class)->issue($client);
        session()->put(self::KEY, ['code' => $new['code'], 'expires_at' => $new['expires_at']->getTimestamp(), 'client_id' => $client->id]);

        return $new;
    }

    /** t.me deep link, or null while the bot username is not set. */
    public static function deepLink(string $code): ?string
    {
        $username = ltrim((string) config('services.telegram.bot_username'), '@');

        return preg_match('/^[A-Za-z][A-Za-z0-9_]{3,31}$/', $username) ? "https://t.me/{$username}?start={$code}" : null;
    }
}
