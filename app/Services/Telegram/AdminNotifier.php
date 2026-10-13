<?php

namespace App\Services\Telegram;

use App\Models\Client;
use App\Services\Health\HealthAnalyzer;
use App\Support\Tr;
use Throwable;

/** Tells admins in Telegram about new registrations. Sends no phone numbers or measurements. */
class AdminNotifier
{
    public function __construct(private TelegramApi $api) {}

    /** @return list<int> */
    public function chatIds(): array
    {
        return collect(explode(',', (string) config('services.telegram.admin_chat_ids')))
            ->map(fn ($id) => trim($id))
            ->filter(fn ($id) => preg_match('/^-?\d+$/', $id))
            ->map(fn ($id) => (int) $id)
            ->values()->all();
    }

    public function newRegistration(Client $client): void
    {
        if (! $this->api->configured()) {
            return;
        }

        $locale = config('hh.default_locale');
        $text = Tr::get('notify.new_reg', [
            $client->first_name.' '.mb_substr($client->last_name, 0, 1).'.',
            Tr::get(match (true) {
                $client->birth_date === null => 'notify.age_later', // Telegram sign-up: age comes with the profile
                HealthAnalyzer::age($client->birth_date) < 18 => 'notify.minor',
                default => 'notify.adult',
            }, [], $locale),
            strtoupper($client->lang),
            Client::count(),
            route('admin.clients.show', $client),
        ], $locale);

        foreach ($this->chatIds() as $chatId) {
            try {
                $this->api->sendMessage($chatId, $text);
            } catch (Throwable) {
                // A notification must never block a registration.
            }
        }
    }
}
