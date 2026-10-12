<?php

use App\Models\TelegramLinkCode;
use Illuminate\Support\Facades\Schedule;

/*
 * One cron entry that runs every minute (shared hosting):
 *   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
 * It only sends reminders. Bot messages arrive through the webhook, not cron.
 */
Schedule::command('reminders:send')->everyMinute()->withoutOverlapping(10);

Schedule::command('model:prune', ['--model' => [TelegramLinkCode::class]])->daily();
