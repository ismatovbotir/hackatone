<?php

namespace App\Services\Telegram;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use App\Support\Phone;
use App\Support\Tr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The bot conversation. Two ways in:
 *
 * A. Confirm a website registration: "/start 123456" (QR / link on the site) or the code typed, then the mobile
 *    number via the contact button. Linked only if the contact is this Telegram account and the code belongs to the
 *    client with that number.
 *
 * B. Register in Telegram: plain "/start", then language (buttons), mobile number (contact button, own account),
 *    full name. The account is created without email or password and linked; the bot then offers the web app to add
 *    the metrics (age, gender, height, weight, consent). If the number already belongs to an account that is not
 *    linked yet, that account is linked instead (Telegram has just proven the person owns the number).
 *
 * Conversation state is kept in the cache per chat for 30 minutes.
 */
class UpdateHandler
{
    private const STATE_TTL = 1800;

    public function __construct(private TelegramApi $api, private LinkCodeService $codes, private AdminNotifier $notifier) {}

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
        $state['lang'] ??= $this->guessLocale($message['from']['language_code'] ?? null);
        $text = trim((string) ($message['text'] ?? ''));

        if (isset($message['contact'])) {
            $this->onContact($chatId, $message, $state);

            return;
        }

        if (preg_match('/^\/start\s+(\d{6})\b/', $text, $m) || preg_match('/^(\d{3})\s?(\d{3})$/', $text, $m2)) {
            // Way A: a code from the website.
            $code = isset($m[1]) ? $m[1] : $m2[1].$m2[2];
            $state = ['mode' => 'confirm', 'lang' => $state['lang'], 'code' => $code, 'phone' => null, 'step' => null];
            $this->save($chatId, $state);
            $this->nextConfirm($chatId, $state);

            return;
        }

        if (str_starts_with($text, '/start')) {
            // Way B: register here (or welcome back).
            if ($client = Client::where('telegram_chat_id', $chatId)->first()) {
                $this->welcomeBack($chatId, $client);

                return;
            }
            $state = ['mode' => 'register', 'lang' => $state['lang'], 'code' => null, 'phone' => null, 'step' => 'lang'];
            $this->save($chatId, $state);
            $this->askLanguage($chatId);

            return;
        }

        if (($state['mode'] ?? null) === 'register' && ($state['step'] ?? null) === 'name') {
            $this->onName($chatId, $text, $state);

            return;
        }

        $this->api->sendMessage($chatId, Tr::get('bot.help', [], $state['lang']));
    }

    // ---- shared -------------------------------------------------------------

    private function onCallback(array $cb): void
    {
        $chatId = $cb['message']['chat']['id'] ?? null;
        rescue(fn () => $this->api->answerCallback($cb['id']), report: false);

        $state = $chatId ? $this->state($chatId) : [];
        if (! $chatId || ($state['mode'] ?? null) !== 'register' || ! preg_match('/^lang:(uz|en|ru)$/', (string) ($cb['data'] ?? ''), $m)) {
            return;
        }

        $state['lang'] = $m[1];
        $state['step'] = 'phone';
        $this->save($chatId, $state);
        $this->api->sendMessage($chatId, Tr::get('bot.share_reg', [], $state['lang']), $this->contactKeyboard($state['lang']));
    }

    private function onContact(int $chatId, array $message, array $state): void
    {
        // The shared contact must be this Telegram account, not someone else's card.
        if (($message['contact']['user_id'] ?? null) !== ($message['from']['id'] ?? false)) {
            $this->api->sendMessage($chatId, Tr::get('bot.own_number', [], $state['lang']), $this->contactKeyboard($state['lang']));

            return;
        }

        $state['phone'] = Phone::normalize($message['contact']['phone_number'] ?? '');
        $this->save($chatId, $state);

        match ($state['mode'] ?? null) {
            'confirm' => $this->nextConfirm($chatId, $state),
            'register' => $this->registerPhone($chatId, $state),
            default => $this->api->sendMessage($chatId, Tr::get('bot.help', [], $state['lang']), ['remove_keyboard' => true]),
        };
    }

    // ---- A: confirm a website registration --------------------------------------

    private function nextConfirm(int $chatId, array $state): void
    {
        if (empty($state['phone'])) {
            $this->api->sendMessage($chatId, Tr::get('bot.share', [], $state['lang']), $this->contactKeyboard($state['lang']));

            return;
        }

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
            $this->forget($chatId);
            $this->api->sendMessage($chatId, Tr::get('bot.invalid', [], $lang), ['remove_keyboard' => true]);

            return;
        }

        if ($client->phone !== $state['phone']) {
            // Keep the code unused: the owner may share the right number next. Show only the last two digits.
            $shared = $state['phone'];
            $state['phone'] = null;
            $this->save($chatId, $state);
            $this->api->sendMessage($chatId, Tr::get('bot.phone_mismatch', [Phone::mask($client->phone), Phone::mask($shared)], $lang), $this->contactKeyboard($lang));

            return;
        }

        if (! $this->codes->use($row)) {
            $this->forget($chatId);
            $this->api->sendMessage($chatId, Tr::get('bot.invalid', [], $lang), ['remove_keyboard' => true]);

            return;
        }

        RateLimiter::clear($key);
        $this->link($client, $chatId);
        $this->forget($chatId);
        $this->sendLinked($chatId, $client);
    }

    // ---- B: register in Telegram ------------------------------------------------

    private function registerPhone(int $chatId, array $state): void
    {
        $lang = $state['lang'];
        $existing = Client::where('phone', $state['phone'])->first();

        if ($existing) {
            if ((int) $existing->telegram_chat_id === $chatId) {
                $this->forget($chatId);
                $this->welcomeBack($chatId, $existing);
            } elseif ($existing->telegram_chat_id !== null) {
                $this->forget($chatId);
                $this->api->sendMessage($chatId, Tr::get('bot.phone_taken', [], $lang), ['remove_keyboard' => true]);
            } else {
                // Registered on the website but not linked: Telegram has just proven this number, so link it.
                $this->link($existing, $chatId);
                $this->forget($chatId);
                $this->sendLinked($chatId, $existing);
            }

            return;
        }

        $state['step'] = 'name';
        $this->save($chatId, $state);
        $this->api->sendMessage($chatId, Tr::get('bot.ask_name', [], $lang), ['remove_keyboard' => true]);
    }

    private function onName(int $chatId, string $text, array $state): void
    {
        $lang = $state['lang'];
        $name = preg_replace('/\s+/u', ' ', trim($text));

        // Letters (any script), spaces, apostrophes and hyphens; 2 to 100 characters; no digits.
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100 || ! preg_match("/^[\\p{L}\\p{M}'ʻʼ‘’`.\\- ]+$/u", $name)) {
            $this->api->sendMessage($chatId, Tr::get('bot.bad_name', [], $lang));

            return;
        }

        $key = 'tg-register:'.$chatId;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->api->sendMessage($chatId, Tr::get('bot.limited', [], $lang));

            return;
        }
        RateLimiter::hit($key, 3600);

        [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');

        $client = DB::transaction(function () use ($chatId, $state, $name, $first, $last, $lang) {
            // A number registered meanwhile (website) wins; never create a duplicate.
            if (Client::where('phone', $state['phone'])->exists()) {
                return null;
            }

            $user = new User(['name' => $name, 'role' => Role::User]);
            $user->forceFill(['email' => null, 'password' => null])->save();

            $client = $user->client()->create([
                'phone' => $state['phone'],
                'first_name' => $first,
                'last_name' => $last,
                'lang' => $lang,
                'reminders' => Client::DEFAULT_REMINDERS,
                'reminder_time' => '18:00',
                'source' => 'telegram',
            ]);
            $this->link($client, $chatId);

            return $client;
        });

        $this->forget($chatId);

        if (! $client) {
            $this->api->sendMessage($chatId, Tr::get('bot.phone_taken', [], $lang));

            return;
        }

        rescue(fn () => $this->notifier->newRegistration($client), report: false);
        $this->api->sendMessage($chatId, Tr::get('bot.registered', [$client->first_name], $lang));
        $this->api->sendMessage($chatId, Tr::get('bot.fill_profile_hint', [], $lang), $this->appButton($client, 'profile', 'bot.fill_profile'));
    }

    // ---- helpers --------------------------------------------------------------

    private function sendLinked(int $chatId, Client $client): void
    {
        $this->api->sendMessage($chatId, Tr::get('bot.linked', [$client->first_name, substr($client->reminder_time, 0, 5)], $client->lang), ['remove_keyboard' => true]);

        $complete = $client->isComplete();
        $this->api->sendMessage(
            $chatId,
            Tr::get($complete ? 'bot.app_hint' : 'bot.fill_profile_hint', [], $client->lang),
            $this->appButton($client, $complete ? null : 'profile', $complete ? 'bot.open_app' : 'bot.fill_profile'),
        );
    }

    private function welcomeBack(int $chatId, Client $client): void
    {
        $complete = $client->isComplete();
        $this->api->sendMessage(
            $chatId,
            Tr::get($complete ? 'bot.welcome_back' : 'bot.fill_profile_hint', [$client->first_name], $client->lang),
            $this->appButton($client, $complete ? null : 'profile', $complete ? 'bot.open_app' : 'bot.fill_profile'),
        );
    }

    /** Web app button (HTTPS sites); otherwise a normal link to sign in with phone + Telegram code. */
    private function appButton(Client $client, ?string $open, string $labelKey): array
    {
        $label = Tr::get($labelKey, [], $client->lang);

        return WebApp::url()
            ? ['inline_keyboard' => [[['text' => $label, 'web_app' => ['url' => route('tg.app', array_filter(['open' => $open]))]]]]]
            : ['inline_keyboard' => [[['text' => $label, 'url' => route('login')]]]];
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

    private function askLanguage(int $chatId): void
    {
        // The language is not chosen yet, so the question is in all three.
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

    private function guessLocale(?string $code): string
    {
        $code = substr((string) $code, 0, 2);

        return in_array($code, config('hh.locales'), true) ? $code : config('hh.default_locale');
    }

    private function state(int $chatId): array
    {
        return Cache::get($this->stateKey($chatId), ['mode' => null, 'lang' => null, 'code' => null, 'phone' => null, 'step' => null]);
    }

    private function save(int $chatId, array $state): void
    {
        Cache::put($this->stateKey($chatId), $state, self::STATE_TTL);
    }

    private function forget(int $chatId): void
    {
        Cache::forget($this->stateKey($chatId));
    }

    private function stateKey(int $chatId): string
    {
        return 'tg-flow:'.$chatId;
    }
}
