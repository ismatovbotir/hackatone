<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramApi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('telegram:webhook {--delete : Remove the webhook and go back to polling}')]
#[Description('Register the bot webhook (needs HTTPS and TELEGRAM_WEBHOOK_SECRET)')]
class TelegramWebhook extends Command
{
    public function handle(TelegramApi $api): int
    {
        if ($this->option('delete')) {
            $ok = $api->deleteWebhook()->json('ok');
            $this->info($ok ? 'Webhook removed.' : 'Telegram refused the request.');

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        if (blank(config('services.telegram.webhook_secret'))) {
            $this->error('Set TELEGRAM_WEBHOOK_SECRET first.');

            return self::FAILURE;
        }

        $url = route('telegram.webhook');
        $response = $api->setWebhook($url, config('services.telegram.webhook_secret'));
        $this->line($response->json('description') ?? $response->body());

        return $response->json('ok') ? self::SUCCESS : self::FAILURE;
    }
}
