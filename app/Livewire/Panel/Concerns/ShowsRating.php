<?php

namespace App\Livewire\Panel\Concerns;

use App\Services\Clients\Leaderboard;
use Livewire\Attributes\Computed;

/**
 * The client's own place in the points rating (never the list or other people's names).
 * Used next to InteractsWithPlan, which provides $this->client.
 */
trait ShowsRating
{
    /** "month" or "all". */
    public string $ratingPeriod = 'month';

    public function setRatingPeriod(string $period): void
    {
        $this->ratingPeriod = in_array($period, Leaderboard::PERIODS, true) ? $period : 'month';
        unset($this->rating);
    }

    /** @return array{rank: ?int, of: int, points: int, streak: int, to_next: ?int} */
    #[Computed]
    public function rating(): array
    {
        if (! in_array($this->ratingPeriod, Leaderboard::PERIODS, true)) {
            $this->ratingPeriod = 'month';
        }

        return app(Leaderboard::class)->positionOf($this->client, $this->ratingPeriod);
    }
}
