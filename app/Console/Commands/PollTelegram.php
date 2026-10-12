<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramApi;
use App\Services\Telegram\UpdateHandler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Local development only: the site has no public HTTPS address, so read bot messages by hand.
 * Production uses the webhook. Telegram refuses getUpdates while a webhook is set (remove it first).
 */
#[Signature('telegram:poll')]
#[Description('Read new bot messages once with getUpdates (local development; production uses the webhook)')]
class PollTelegram extends Command
{
    private const OFFSET_KEY = 'telegram:update-offset';

    public function handle(TelegramApi $api, UpdateHandler $handler): int
    {
        if (! $api->configured()) {
            $this->warn('TELEGRAM_BOT_TOKEN is not set.');

            return self::SUCCESS;
        }

        $offset = (int) Cache::get(self::OFFSET_KEY, 0);
        $updates = $api->getUpdates($offset);

        foreach ($updates as $update) {
            // Move the offset first so a failing update is not retried forever.
            Cache::forever(self::OFFSET_KEY, $update['update_id'] + 1);
            rescue(fn () => $handler->handle($update), report: true);
        }

        $this->info('Handled '.count($updates).' update(s).');

        return self::SUCCESS;
    }
}
