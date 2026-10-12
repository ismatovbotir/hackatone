<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\Telegram\ReminderComposer;
use App\Services\Telegram\TelegramApi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Sends each linked client one Telegram reminder a day, at or after their chosen time.
 * Safe to run every minute from cron: a client is marked once today's message is delivered,
 * so a missed cron run is caught up on the next one.
 */
#[Signature('reminders:send {--dry-run : Show who would get a reminder without sending}')]
#[Description('Send daily Telegram reminders to clients whose reminder time has come')]
class SendReminders extends Command
{
    public function handle(TelegramApi $api, ReminderComposer $composer): int
    {
        if (! $api->configured() && ! $this->option('dry-run')) {
            $this->warn('TELEGRAM_BOT_TOKEN is not set, nothing sent.');

            return self::SUCCESS;
        }

        $now = Carbon::now(config('hh.timezone'));
        $today = $now->toDateString();
        $sent = $skipped = $failed = 0;

        Client::query()
            ->whereNotNull('telegram_chat_id')
            ->where('reminder_time', '<=', $now->format('H:i:s'))
            ->where(fn ($q) => $q->whereNull('last_reminded_on')->orWhere('last_reminded_on', '<', $today))
            ->with('latestMeasurement')
            ->chunkById(100, function ($clients) use ($api, $composer, $now, $today, &$sent, &$skipped, &$failed) {
                foreach ($clients as $client) {
                    ['text' => $text, 'measure' => $measure] = $composer->compose($client, $now);

                    if ($text === null) {
                        $client->forceFill(['last_reminded_on' => $today])->save();
                        $skipped++;

                        continue;
                    }

                    if ($this->option('dry-run')) {
                        $this->line("client #{$client->id}");
                        $sent++;

                        continue;
                    }

                    try {
                        $response = $api->sendMessage($client->telegram_chat_id, $text);
                    } catch (Throwable) {
                        $failed++;

                        continue; // Retried on the next run.
                    }

                    if ($response->status() === 403) {
                        // The user blocked the bot: unlink so we stop trying.
                        $client->forceFill(['telegram_chat_id' => null, 'telegram_linked_at' => null])->save();
                        $failed++;

                        continue;
                    }

                    if (! $response->successful()) {
                        $failed++;

                        continue;
                    }

                    $client->forceFill(array_filter([
                        'last_reminded_on' => $today,
                        'measure_reminded_at' => $measure ? now() : null,
                    ]))->save();
                    $sent++;
                }
            });

        $this->info("Sent {$sent}, skipped {$skipped}, failed {$failed}.");

        return self::SUCCESS;
    }
}
