<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\Telegram\TelegramApi;
use App\Services\Telegram\WebApp;
use App\Support\Tr;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * End of the day: one Telegram message to linked clients who have not logged anything today, with a button that
 * opens the Telegram web app on today's calendar form. Runs every minute from cron; acts after HH_EVENING_TIME,
 * once per client per day (a missed cron minute is caught up).
 */
#[Signature('reminders:evening')]
#[Description('Remind clients in Telegram to fill in today in the calendar')]
class SendEveningReminders extends Command
{
    public function handle(TelegramApi $api): int
    {
        $now = Carbon::now(config('hh.timezone'));
        if (! $api->configured() || $now->format('H:i') < config('hh.evening_time')) {
            return self::SUCCESS;
        }

        $today = $now->toDateString();
        $sent = 0;

        Client::query()->whereNotNull('telegram_chat_id')->chunkById(100, function ($clients) use ($api, $today, &$sent) {
            foreach ($clients as $client) {
                $key = "evening-reminder:{$client->id}:{$today}";
                if (! $client->reminderEnabled('evening') || Cache::has($key) || $this->loggedToday($client, $today)) {
                    continue;
                }

                try {
                    $response = $api->sendMessage($client->telegram_chat_id, Tr::get('rem.evening', [$client->first_name], $client->lang), $this->button($client));
                } catch (Throwable) {
                    continue; // tried again next minute
                }

                if ($response->status() === 403) {
                    $client->forceFill(['telegram_chat_id' => null, 'telegram_linked_at' => null])->save(); // bot blocked
                } elseif ($response->successful()) {
                    Cache::put($key, true, now()->addDays(2));
                    $sent++;
                }
            }
        });

        $this->info("Evening reminders sent: {$sent}.");

        return self::SUCCESS;
    }

    /** Anything filled in today: training, meals, water or reading. */
    private function loggedToday(Client $client, string $today): bool
    {
        $log = $client->dailyLogs()->where('log_date', $today)->first();

        return $log !== null && ($log->workout_done || $log->pages_read > 0 || $log->water_ml > 0 || array_filter($log->meals ?? []) !== []);
    }

    private function button(Client $client): array
    {
        $label = Tr::get('rem.evening_btn', [], $client->lang);

        // In Telegram: the web app on today's form (HTTPS only); otherwise a normal link to the site.
        return WebApp::url()
            ? ['inline_keyboard' => [[['text' => $label, 'web_app' => ['url' => route('tg.app', ['open' => 'today'])]]]]]
            : ['inline_keyboard' => [[['text' => $label, 'url' => route('panel.calendar', ['day' => 'today'])]]]];
    }
}
