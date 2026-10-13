<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;

/**
 * Keeps bulk sends inside Telegram's limits: about 30 messages a second for a bot (we use 25 to leave room) and a
 * cap per cron run so a run finishes well within a minute on shared hosting; the rest is picked up next minute.
 * A 429 "Too Many Requests" pauses all sending for the retry_after Telegram asks for.
 */
class SendThrottle
{
    private const PAUSE_KEY = 'telegram:paused-until';

    private int $sent = 0;

    private float $last = 0.0;

    public function __construct(
        private int $perSecond = 25,
        private int $perRun = 600,
    ) {}

    public static function fromConfig(): self
    {
        return new self((int) config('hh.telegram.per_second', 25), (int) config('hh.telegram.per_run', 600));
    }

    /** True when this run must stop: cap reached or Telegram asked to wait. */
    public function stop(): bool
    {
        return $this->sent >= $this->perRun || self::paused();
    }

    /** Call before each send: waits just enough to stay under the per-second limit. */
    public function wait(): void
    {
        $gap = 1 / max(1, $this->perSecond);
        $elapsed = microtime(true) - $this->last;
        if ($this->last > 0 && $elapsed < $gap) {
            Sleep::usleep((int) (($gap - $elapsed) * 1_000_000));
        }
        $this->last = microtime(true);
    }

    /** Call after each send. Returns false when Telegram rate-limited us (the run should stop). */
    public function record(Response $response): bool
    {
        $this->sent++;

        if ($response->status() === 429) {
            $retry = (int) ($response->json('parameters.retry_after') ?? 30);
            Cache::put(self::PAUSE_KEY, now()->addSeconds($retry)->getTimestamp(), $retry + 5);

            return false;
        }

        return true;
    }

    public static function paused(): bool
    {
        return (int) Cache::get(self::PAUSE_KEY, 0) > now()->getTimestamp();
    }
}
