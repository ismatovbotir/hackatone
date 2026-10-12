<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramApi
{
    public function configured(): bool
    {
        return filled(config('services.telegram.bot_token'));
    }

    /** @return array<string, mixed>|null bot info, or null if the token is rejected */
    public function getMe(): ?array
    {
        $response = $this->http()->get('getMe');

        return $response->json('ok') ? $response->json('result') : null;
    }

    /** @param  array<string, mixed>|null  $markup  inline_keyboard, keyboard or remove_keyboard */
    public function sendMessage(int $chatId, string $text, ?array $markup = null): Response
    {
        return $this->http()->post('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => true,
            'reply_markup' => $markup,
        ], fn ($v) => $v !== null));
    }

    public function answerCallback(string $callbackId): Response
    {
        return $this->http()->post('answerCallbackQuery', ['callback_query_id' => $callbackId]);
    }

    /** @return list<array<string, mixed>> */
    public function getUpdates(int $offset): array
    {
        $response = $this->http()->get('getUpdates', [
            'offset' => $offset,
            'timeout' => 0,
            'allowed_updates' => json_encode(['message', 'callback_query']),
        ]);

        return $response->successful() ? ($response->json('result') ?? []) : [];
    }

    public function setWebhook(string $url, ?string $secret): Response
    {
        return $this->http()->post('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query'],
        ]));
    }

    public function deleteWebhook(): Response
    {
        return $this->http()->post('deleteWebhook');
    }

    private function http(): PendingRequest
    {
        if (! $this->configured()) {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not set.');
        }

        return Http::baseUrl('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/')
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(8);
    }
}
