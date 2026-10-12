<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/** When the scheduler last ran, so admins can see that cron works without Telegram. */
final class CronHeartbeat
{
    private const KEY = 'cron:last-run';

    public static function beat(): void
    {
        Cache::forever(self::KEY, now()->getTimestamp());
    }

    public static function lastRun(): ?Carbon
    {
        $ts = Cache::get(self::KEY);

        return $ts ? Carbon::createFromTimestamp($ts) : null;
    }

    /** Cron runs every minute, so anything older than 3 minutes means it is not running. */
    public static function healthy(): bool
    {
        return self::lastRun()?->gt(now()->subMinutes(3)) ?? false;
    }
}
