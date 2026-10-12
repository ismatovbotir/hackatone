<?php

use App\Models\TelegramLinkCode;
use App\Support\CronHeartbeat;
use Illuminate\Support\Facades\Schedule;

/*
 * One cron entry that runs every minute (shared hosting):
 *   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
 * It sends reminders. Bot messages arrive through the webhook, not cron.
 */
Schedule::command('reminders:send')->everyMinute()->withoutOverlapping(10);

Schedule::command('model:prune', ['--model' => [TelegramLinkCode::class]])->daily();

// Every run records the time, shown in Admin > Settings ("Last cron run").
Schedule::call(fn () => CronHeartbeat::beat())->everyMinute()->name('cron-heartbeat');

// Optional Telegram check that cron works: HH_CRON_PING=minute (testing), hourly, daily or off (not scheduled).
match (config('hh.cron_ping')) {
    'minute' => Schedule::command('cron:ping')->everyMinute(),
    'hourly' => Schedule::command('cron:ping')->hourly(),
    'daily' => Schedule::command('cron:ping')->dailyAt('09:00')->timezone(config('hh.timezone')),
    default => null,
};
