<?php

namespace App\Services\Clients;

use App\Models\Client;
use App\Models\DailyLog;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rating by points from the calendar (see Points), with the current training streak.
 * Periods: "month" (this calendar month, local time) or "all" (all time).
 * Ranks are competition ranks: equal points share a rank.
 */
class Leaderboard
{
    public const PERIODS = ['month', 'all'];

    public function __construct(private TrainingCalendar $calendar, private ClientHealth $health) {}

    /**
     * @return Collection<int, array{rank:int, client:Client, points:int, streak:int}>
     */
    public function top(string $period, int $limit = 20): Collection
    {
        $rows = $this->totals($period)
            ->having('points', '>', 0)
            ->orderByDesc('points')->orderBy('client_id')
            ->limit($limit)
            ->get();

        $clients = Client::with(['user', 'latestMeasurement'])->findMany($rows->pluck('client_id'))->keyBy('id');

        $list = $rows
            ->filter(fn ($r) => $clients->has($r->client_id))
            ->map(fn ($r) => [
                'client' => $c = $clients[$r->client_id],
                'points' => (int) $r->points,
                'streak' => $this->streak($c),
            ])
            ->sortBy([['points', 'desc'], ['streak', 'desc']])
            ->values();

        // Competition ranking on points (1, 2, 2, 4).
        $rank = 0;
        $previous = null;

        return $list->map(function ($row, $i) use (&$rank, &$previous) {
            if ($row['points'] !== $previous) {
                $rank = $i + 1;
                $previous = $row['points'];
            }

            return ['rank' => $rank] + $row;
        });
    }

    /**
     * A client's own place. rank is null until they have points in the period.
     *
     * @return array{rank: ?int, of: int, points: int, streak: int, to_next: ?int}
     */
    public function positionOf(Client $client, string $period): array
    {
        // first() keeps the SUM(...) select; value() would replace it with the raw column.
        $points = (int) ($this->totals($period)->where('client_id', $client->id)->first()?->points ?? 0);
        $ranked = DB::query()->fromSub($this->totals($period), 't')->where('points', '>', 0);
        $above = (clone $ranked)->where('points', '>', $points);

        // Points needed to pass the next person up (null for first place or no points).
        $next = $points > 0 ? (clone $above)->min('points') : null;

        return [
            'rank' => $points > 0 ? $above->count() + 1 : null,
            'of' => $ranked->count(),
            'points' => $points,
            'streak' => $this->streak($client),
            'to_next' => $next !== null ? (int) $next - $points + 1 : null,
        ];
    }

    private function totals(string $period): Builder
    {
        $query = DailyLog::query()->toBase()
            ->select('client_id', DB::raw('SUM(points) as points'))
            ->groupBy('client_id');

        if ($period === 'month') {
            $query->where('log_date', '>=', Carbon::now(config('hh.timezone'))->startOfMonth()->toDateString());
        }

        return $query;
    }

    private function streak(Client $client): int
    {
        $a = $this->health->analyze($client);

        return $a ? $this->calendar->streak($client, $this->health->level($client, $a)) : 0;
    }
}
