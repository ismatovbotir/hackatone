<?php

namespace App\Services\Telegram;

use App\Models\Client;
use App\Support\Phone;
use App\Support\Tr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Bot conversation that links a Telegram account to a registered client:
 *   1. the secret 6-digit code: "/start 123456" from the QR/link on the site, or typed as a message
 *   2. the mobile number, shared with Telegram's contact button
 *   3. checks: the shared contact is this Telegram account (contact.user_id = from.id), and the code belongs to the
 *      client registered with that mobile number. Then the Telegram id is saved; the site sees it and opens the panel.
 * Before the code identifies the client, messages use the Telegram app language (Uzbek if unknown); after, the client's.
 * Conversation state is kept in the cache per chat for 30 minutes.
 */
class UpdateHandler
{
    private const STATE_TTL = 1800;

    public function __construct(private TelegramApi $api, private LinkCodeService $codes) {}

    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;
        $chatId = $message['chat']['id'] ?? null;
        if (! $chatId || ($message['chat']['type'] ?? 'private') !== 'private') {
            return;
        }

        $state = $this->state($chatId);
        $state['lang'] = $this->guessLocale($message['from']['language_code'] ?? null);
        $text = trim((string) ($message['text'] ?? ''));

        if (isset($message['contact'])) {
            $this->onContact($chatId, $message, $state);

            return;
        }

        if (preg_match('/^\/start(?:\s+(\d{6}))?/', $text, $m)) {
            // A new start forgets an earlier half-finished attempt.
            $state = ['lang' => $state['lang'], 'code' => $m[1] ?? null, 'phone' => null];
        } elseif (preg_match('/^\d{3}\s?\d{3}$/', $text)) {
            $state['code'] = preg_replace('/\D/', '', $text);
        }

        $this->save($chatId, $state);
        $this->next($chatId, $state);
    }

    private function onContact(int $chatId, array $message, array $state): void
    {
        // Match 1: the shared contact must be this Telegram account, not someone else's card.
        if (($message['contact']['user_id'] ?? null) !== ($message['from']['id'] ?? false)) {
            $this->api->sendMessage($chatId, Tr::get('bot.own_number', [], $state['lang']), $this->contactKeyboard($state['lang']));

            return;
        }

        $state['phone'] = Phone::normalize($message['contact']['phone_number'] ?? '');
        $this->save($chatId, $state);
        $this->next($chatId, $state);
    }

    /** Ask for what is missing (code first, then phone), or finish. */
    private function next(int $chatId, array $state): void
    {
        $lang = $state['lang'];

        if (empty($state['code'])) {
            $this->api->sendMessage($chatId, Tr::get('bot.ask_code', [], $lang), ['remove_keyboard' => true]);

            return;
        }

        if (empty($state['phone'])) {
            $this->api->sendMessage($chatId, Tr::get('bot.share', [], $lang), $this->contactKeyboard($lang));

            return;
        }

        $this->complete($chatId, $state);
    }

    private function complete(int $chatId, array $state): void
    {
        $lang = $state['lang'];
        $key = 'tg-link:'.$chatId;

        if (RateLimiter::tooManyAttempts($key, config('hh.link_attempts_per_chat'))) {
            $this->api->sendMessage($chatId, Tr::get('bot.limited', [], $lang), ['remove_keyboard' => true]);

            return;
        }
        RateLimiter::hit($key, config('hh.link_attempts_decay_seconds'));

        $row = $this->codes->find($state['code']);
        $client = $row?->client;

        if (! $client) {
            $this->forget($chatId, $state, 'code');
            $this->api->sendMessage($chatId, Tr::get('bot.invalid', [], $lang), ['remove_keyboard' => true]);

            return;
        }

        // Match 2: the code must belong to the client registered with this mobile number.
        if ($client->phone !== $state['phone']) {
            // Keep the code unused: the owner may share the right number next.
            $this->forget($chatId, $state, 'phone');
            $this->api->sendMessage($chatId, Tr::get('bot.phone_mismatch', [], $lang), $this->contactKeyboard($lang));

            return;
        }

        if (! $this->codes->use($row)) {
            $this->forget($chatId, $state, 'code');
            $this->api->sendMessage($chatId, Tr::get('bot.invalid', [], $lang), ['remove_keyboard' => true]);

            return;
        }

        $this->link($client, $chatId);
        RateLimiter::clear($key);
        Cache::forget($this->stateKey($chatId));

        $done = Tr::get('bot.linked', [$client->first_name, substr($client->reminder_time, 0, 5)], $client->lang);
        $this->api->sendMessage($chatId, $done, ['remove_keyboard' => true]);
        if ($app = WebApp::url()) {
            $this->api->sendMessage($chatId, Tr::get('bot.app_hint', [], $client->lang), [
                'inline_keyboard' => [[['text' => Tr::get('bot.open_app', [], $client->lang), 'web_app' => ['url' => $app]]]],
            ]);
        }
    }

    private function link(Client $client, int $chatId): void
    {
        DB::transaction(function () use ($client, $chatId) {
            // One Telegram account belongs to one client.
            Client::where('telegram_chat_id', $chatId)->whereKeyNot($client->id)
                ->update(['telegram_chat_id' => null, 'telegram_linked_at' => null]);

            $local = Carbon::now(config('hh.timezone'));
            $client->forceFill([
                'telegram_chat_id' => $chatId,
                'telegram_linked_at' => now(),
                // Don't send today's reminder straight away if its time has already passed.
                'last_reminded_on' => $local->format('H:i') >= substr($client->reminder_time, 0, 5) ? $local->toDateString() : null,
            ])->save();
        });
    }

    private function contactKeyboard(string $lang): array
    {
        return [
            'keyboard' => [[['text' => Tr::get('bot.share_btn', [], $lang), 'request_contact' => true]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];
    }

    private function forget(int $chatId, array $state, string $field): void
    {
        $state[$field] = null;
        $this->save($chatId, $state);
    }

    private function guessLocale(?string $code): string
    {
        $code = substr((string) $code, 0, 2);

        return in_array($code, config('hh.locales'), true) ? $code : config('hh.default_locale');
    }

    private function state(int $chatId): array
    {
        return Cache::get($this->stateKey($chatId), ['lang' => null, 'code' => null, 'phone' => null]);
    }

    private function save(int $chatId, array $state): void
    {
        Cache::put($this->stateKey($chatId), $state, self::STATE_TTL);
    }

    private function stateKey(int $chatId): string
    {
        return 'tg-flow:'.$chatId;
    }
}
