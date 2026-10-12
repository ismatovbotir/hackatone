<?php

namespace App\Services\Telegram;

use App\Models\Client;
use App\Models\TelegramLinkCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LinkCodeService
{
    /**
     * Issue a new 6-digit code and expire the client's earlier unused ones.
     *
     * @return array{code:string, expires_at:Carbon}
     */
    public function issue(Client $client): array
    {
        $client->linkCodes()->whereNull('used_at')->where('expires_at', '>', now())->update(['expires_at' => now()]);

        do {
            $code = (string) random_int(100000, 999999);
            $hash = $this->hash($code);
        } while (TelegramLinkCode::where('code_hash', $hash)->whereNull('used_at')->where('expires_at', '>', now())->exists());

        $expires = now()->addMinutes(config('hh.link_code_ttl_minutes'));
        $client->linkCodes()->create(['code_hash' => $hash, 'expires_at' => $expires]);

        return ['code' => $code, 'expires_at' => $expires];
    }

    /** A valid (unused, unexpired) code, without using it. */
    public function find(string $code): ?TelegramLinkCode
    {
        return TelegramLinkCode::where('code_hash', $this->hash($code))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /** Use a code once. Returns false if another request used it first. */
    public function use(TelegramLinkCode $row): bool
    {
        // Conditional update: only one request can flip used_at from null.
        return TelegramLinkCode::whereKey($row->id)->whereNull('used_at')->update(['used_at' => now()]) === 1;
    }

    /** Find and use in one go. Returns the client it belongs to, or null if unknown, expired or already used. */
    public function consume(string $code): ?Client
    {
        return DB::transaction(function () use ($code) {
            $row = $this->find($code);

            return $row && $this->use($row) ? $row->client : null;
        });
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
