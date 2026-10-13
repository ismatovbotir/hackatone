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
 * Bot conversation that links a Telegram chat to a registered client:
 *   1. /start (the QR on the site opens the bot with "/start start"; an old "/start 123456" link still works)
 *   2. choose a language (inline buttons)
 *   3. share own phone number (contact button); it must match the phone used at registration
 *   4. send the 6-digit code from the website (skipped if it came with /start)
 * Conversation state is kept in the cache per chat for 30 minutes.
 */
class UpdateHandler
{
    private const STATE_TTL = 1800;

    public function __construct(private TelegramApi $api, private LinkCodeService $codes) {}

    public function handle(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->onCallback($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? null;
        $chatId = $message['chat']['id'] ?? null;
        if (! $chatId || ($message['chat']['type'] ?? 'private') !== 'private') {
            return;
        }

        $state = $this->state($chatId);
        $text = trim((string) ($message['text'] ?? ''));

        if (isset($message['contact'])) {
            $this->onContact($chatId, $message, $state);

            return;
        }

        if (preg_match('/^\/start(?:\s+(\d{6}))?/', $text, $m)) {
            $state = ['lang' => null, 'phone' => null, 'code' => $m[1] ?? null];
            $this->save($chatId, $state);
            $this->askLanguage($chatId);

            return;
        }

        if (preg_match('/^\d{3}\s?\d{3}$/', $text)) {
            $state['code'] = preg_replace('/\D/', '', $text);
            $this->save($chatId, $state);
            $this->next($chatId, $state);

            return;
        }

        $this->next($chatId, $state);
    }

    private function onCallback(array $cb): void
    {
        $chatId = $cb['message']['chat']['id'] ?? null;
        rescue(fn () => $this->api->answerCallback($cb['id']), report: false);

        if ($chatId && preg_match('/^lang:(uz|en|ru)$/', (string) ($cb['data'] ?? ''), $m)) {
            $state = $this->state($chatId);
            $state['lang'] = $m[1];
            $this->save($chatId, $state);
            $this->next($chatId, $state);
        }
    }

    private function onContact(int $chatId, array $message, array $state): void
    {
        $lang = $state['lang'] ?? config('hh.default_locale');

        // Only the person's own number proves they own the phone used at registration.
        if (($message['contact']['user_id'] ?? null) !== ($message['from']['id'] ?? false)) {
            $this->api->sendMessage($chatId, Tr::get('bot.own_number', [], $lang), $this->contactKeyboard($lang));

            return;
        }

        $state['phone'] = Phone::normalize($message['contact']['phone_number'] ?? '');
        $this->save($chatId, $state);
        $this->next($chatId, $state);
    }

    /** Ask for whatever is missing, or finish when everything is there. */
    private function next(int $chatId, array $state): void
    {
        if (empty($state['lang'])) {
            $this->askLanguage($chatId);

            return;
        }
        $lang = $state['lang'];

        if (empty($state['phone'])) {
            $this->api->sendMessage($chatId, Tr::get('bot.share', [], $lang), $this->contactKeyboard($lang));

            return;
        }

        if (empty($state['code'])) {
            $this->api->sendMessage($chatId, Tr::get('bot.send_code', [], $lang), ['remove_keyboard' => true]);

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
            $this->forgetCode($chatId, $state);
            $this->api->sendMessage($chatId, Tr::get('bot.invalid', [], $lang), ['remove_keyboard' => true]);

            return;
        }

        if ($client->phone !== $state['phone']) {
            // Do not burn the code: the client may share the right number next.
            $this->forgetCode($chatId, $state);
            $this->api->sendMessage($chatId, Tr::get('bot.phone_mismatch', [], $lang), ['remove_keyboard' => true]);

            return;
        }

        if (! $this->codes->use($row)) {
            $this->forgetCode($chatId, $state);
            $this->api->sendMessage($chatId, Tr::get('bot.invalid', [], $lang), ['remove_keyboard' => true]);

            return;
        }

        $this->link($client, $chatId, $lang);
        RateLimiter::clear($key);
        Cache::forget($this->stateKey($chatId));

        $this->api->sendMessage($chatId, Tr::get('bot.linked', [$client->first_name, substr($client->reminder_time, 0, 5)], $lang), ['remove_keyboard' => true]);
    }

    private function link(Client $client, int $chatId, string $lang): void
    {
        DB::transaction(function () use ($client, $chatId, $lang) {
            // One Telegram chat belongs to one client.
            Client::where('telegram_chat_id', $chatId)->whereKeyNot($client->id)
                ->update(['telegram_chat_id' => null, 'telegram_linked_at' => null]);

            $local = Carbon::now(config('hh.timezone'));
            $client->forceFill([
                'telegram_chat_id' => $chatId,
                'telegram_linked_at' => now(),
                // Reminders come in the language chosen in the bot.
                'lang' => $lang,
                // Don't send today's reminder straight away if its time has already passed.
                'last_reminded_on' => $local->format('H:i') >= substr($client->reminder_time, 0, 5) ? $local->toDateString() : null,
            ])->save();
        });
    }

    private function askLanguage(int $chatId): void
    {
        // The language is not known yet, so the question is in all three.
        $text = collect(config('hh.locales'))->map(fn ($l) => Tr::get('bot.choose_lang', [], $l))->implode("\n");

        $this->api->sendMessage($chatId, $text, ['inline_keyboard' => [[
            ['text' => 'O‘zbekcha', 'callback_data' => 'lang:uz'],
            ['text' => 'Русский', 'callback_data' => 'lang:ru'],
            ['text' => 'English', 'callback_data' => 'lang:en'],
        ]]]);
    }

    private function contactKeyboard(string $lang): array
    {
        return [
            'keyboard' => [[['text' => Tr::get('bot.share_btn', [], $lang), 'request_contact' => true]]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];
    }

    private function forgetCode(int $chatId, array $state): void
    {
        $state['code'] = null;
        $this->save($chatId, $state);
    }

    private function state(int $chatId): array
    {
        return Cache::get($this->stateKey($chatId), ['lang' => null, 'phone' => null, 'code' => null]);
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
