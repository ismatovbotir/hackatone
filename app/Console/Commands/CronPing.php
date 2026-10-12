<?php

namespace App\Console\Commands;

use App\Services\Telegram\AdminNotifier;
use App\Services\Telegram\TelegramApi;
use App\Support\Tr;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/** Tells the admin Telegram chats that cron runs, with the server time. Scheduled by HH_CRON_PING; can also be run by hand. */
#[Signature('cron:ping')]
#[Description('Send the server time to the admin Telegram chats (checks that cron and the bot work)')]
class CronPing extends Command
{
    public function handle(TelegramApi $api, AdminNotifier $notifier): int
    {
        $ids = $notifier->chatIds();
        if (! $api->configured() || $ids === []) {
            $this->warn('Set the bot token and admin chat IDs first (TELEGRAM_BOT_TOKEN, TELEGRAM_ADMIN_CHAT_IDS or Admin > Settings).');

            return self::FAILURE;
        }

        $locale = config('hh.default_locale');
        $text = Tr::get('cron.ping', [Carbon::now(config('hh.timezone'))->format('d.m.Y H:i:s'), config('hh.timezone')], $locale);

        $sent = 0;
        foreach ($ids as $id) {
            try {
                $sent += $api->sendMessage($id, $text)->successful() ? 1 : 0;
            } catch (Throwable) {
                // Counted as not sent.
            }
        }

        $this->info("Sent to {$sent} of ".count($ids).' admin chat(s).');

        return $sent > 0 ? self::SUCCESS : self::FAILURE;
    }
}
