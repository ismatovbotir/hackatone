<?php

namespace App\Services\Telegram;

use App\Models\Client;
use App\Support\Phone;
use App\Support\Tr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Sign in by phone: a 6-digit code goes to the Telegram chat linked to that phone, and the person types it on the site.
 * Answers never reveal whether a phone is registered or linked. Codes last 5 minutes, work once, and are rate-limited.
 */
class LoginCodeService
{
    public const TTL_MINUTES = 5;

    public function __construct(private TelegramApi $api) {}

    /** Send a code if the phone belongs to a client with Telegram linked. Returns false only when rate-limited. */
    public function send(string $phone, string $ip): bool
    {
        $digits = Phone::normalize($phone);
        $phoneKey = 'login-send:'.hash('sha256', (string) $digits);
        $ipKey = 'login-send-ip:'.$ip;

        if (RateLimiter::tooManyAttempts($phoneKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return false;
        }
        RateLimiter::hit($phoneKey, 15 * 60);
        RateLimiter::hit($ipKey, 60 * 60);

        $client = $digits ? Client::where('phone', $digits)->whereNotNull('telegram_chat_id')->first() : null;
        if (! $client || ! $this->api->configured()) {
            return true; // same answer as a real send
        }

        // Only the newest code works.
        $client->linkCodes()->where('purpose', 'login')->whereNull('used_at')->update(['expires_at' => now()]);
        $code = (string) random_int(100000, 999999);
        $client->linkCodes()->create([
            'purpose' => 'login',
            'code_hash' => $this->hash($client, $code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        try {
            $this->api->sendMessage($client->telegram_chat_id, Tr::get('bot.login_code', [substr($code, 0, 3).' '.substr($code, 3), self::TTL_MINUTES], $client->lang));
        } catch (Throwable) {
            // Telegram down: the person simply gets no code and can try again.
        }

        return true;
    }

    /** The client the code signs in, or null. Five wrong codes per phone lock it until a new code is requested. */
    public function verify(string $phone, string $code, string $ip): ?Client
    {
        $digits = Phone::normalize($phone);
        $code = preg_replace('/\D/', '', $code);
        $phoneKey = 'login-verify:'.hash('sha256', (string) $digits);
        $ipKey = 'login-verify-ip:'.$ip;

        if (! $digits || strlen($code) !== 6 || RateLimiter::tooManyAttempts($phoneKey, 5) || RateLimiter::tooManyAttempts($ipKey, 20)) {
            RateLimiter::hit($ipKey, 60 * 60);

            return null;
        }

        $client = Client::where('phone', $digits)->whereNotNull('telegram_chat_id')->first();
        $row = $client?->linkCodes()
            ->where('purpose', 'login')
            ->where('code_hash', $this->hash($client, $code))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        // Conditional update: a code can be used once, even by two requests at the same moment.
        $used = $row && DB::table('telegram_link_codes')->where('id', $row->id)->whereNull('used_at')->update(['used_at' => now()]) === 1;

        if (! $used) {
            RateLimiter::hit($phoneKey, 15 * 60);
            RateLimiter::hit($ipKey, 60 * 60);

            return null;
        }

        RateLimiter::clear($phoneKey);
        RateLimiter::clear('login-send:'.hash('sha256', $digits));

        return $client;
    }

    /** Bound to the client, so a code can never sign in someone else. */
    private function hash(Client $client, string $code): string
    {
        return hash_hmac('sha256', 'login:'.$client->id.':'.$code, (string) config('app.key'));
    }
}
