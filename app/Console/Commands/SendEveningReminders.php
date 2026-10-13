<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\DailyLog;
use App\Services\Telegram\EveningReport;
use App\Services\Telegram\SendThrottle;
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
 * End of the day, one Telegram message per linked client (after HH_EVENING_TIME, once a day; a missed cron minute is
 * caught up):
 *  - nothing filled in today: a reminder with a button that opens the web app on today's calendar form;
 *  - already filled in: an achievements report for the day, as text.
 */
#[Signature('reminders:evening')]
#[Description('Evening Telegram message: reminder to fill in today, or the day\'s achievements report')]
class SendEveningReminders extends Command
{
    public function handle(TelegramApi $api, EveningReport $report): int
    {
        $now = Carbon::now(config('hh.timezone'));
        if (! $api->configured() || $now->format('H:i') < config('hh.evening_time')) {
            return self::SUCCESS;
        }

        $today = $now->toDateString();
        $sent = 0;
        $throttle = SendThrottle::fromConfig();

        Client::query()->whereNotNull('telegram_chat_id')->with('latestMeasurement')->chunkById(100, function ($clients) use ($api, $report, $now, $today, $throttle, &$sent) {
            foreach ($clients as $client) {
                if ($throttle->stop()) {
                    return false; // Telegram limits: the rest is sent on the next run
                }

                $key = "evening-reminder:{$client->id}:{$today}";
                if (! $client->reminderEnabled('evening') || Cache::has($key)) {
                    continue;
                }

                $log = $client->dailyLogs()->where('log_date', $today)->first();

                $throttle->wait();
                try {
                    $response = ! $client->isComplete()
                        ? $api->sendMessage($client->telegram_chat_id, Tr::get('rem.profile', [$client->first_name], $client->lang), $this->profileButton($client))
                        : ($this->filledIn($log)
                        ? $api->sendMessage($client->telegram_chat_id, $report->compose($client, $log, $now))
                        : $api->sendMessage($client->telegram_chat_id, Tr::get('rem.evening', [$client->first_name], $client->lang), $this->button($client)));
                } catch (Throwable) {
                    continue; // tried again next minute
                }

                if (! $throttle->record($response)) {
                    return false; // 429: pause, retried on a later run
                }

                if ($response->status() === 403) {
                    $client->forceFill(['telegram_chat_id' => null, 'telegram_linked_at' => null])->save(); // bot blocked
                } elseif ($response->successful()) {
                    Cache::put($key, true, now()->addDays(2));
                    $sent++;
                }
            }
        });

        $this->info("Evening messages sent: {$sent}.");

        return self::SUCCESS;
    }

    /** Anything filled in today: training, meals, water or reading (a plan alone does not count). */
    private function filledIn(?DailyLog $log): bool
    {
        return $log !== null && ($log->workout_done || $log->pages_read > 0 || $log->water_ml > 0 || array_filter($log->meals ?? []) !== []);
    }

    private function profileButton(Client $client): array
    {
        $label = Tr::get('bot.fill_profile', [], $client->lang);

        return WebApp::url()
            ? ['inline_keyboard' => [[['text' => $label, 'web_app' => ['url' => route('tg.app', ['open' => 'profile'])]]]]]
            : ['inline_keyboard' => [[['text' => $label, 'url' => route('login')]]]];
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
