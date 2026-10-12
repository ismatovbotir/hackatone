<?php

namespace App\Services\Health;

use App\Enums\Level;

final class TrainingPlanner
{
    /** One week, Monday first: [session type, minutes]. */
    public const PATTERNS = [
        'easy' => [['cardio', 30], ['stretch', 20], ['rest', 0], ['cardio', 30], ['strength', 25], ['sport', 45], ['rest', 0]],
        'mid' => [['cardio', 40], ['strength', 40], ['stretch', 30], ['cardio', 45], ['strength', 40], ['sport', 60], ['recover', 30]],
        'strong' => [['cardio', 50], ['strength', 60], ['sport', 50], ['strength', 60], ['cardio', 50], ['sport', 75], ['stretch', 30]],
    ];

    /** Youth target: 60 min a day on average. */
    public const YOUTH_WEEKLY_MINUTES = 420;

    /** @return list<array{0:string,1:int}> */
    public function week(Level $level): array
    {
        return self::PATTERNS[$level->value];
    }

    /** @return array{0:string,1:int} session for a weekday, 0 = Monday */
    public function day(Level $level, int $weekday): array
    {
        return self::PATTERNS[$level->value][$weekday];
    }

    public function weeklyMinutes(Level $level): int
    {
        return array_sum(array_column(self::PATTERNS[$level->value], 1));
    }

    public function sessions(Level $level): int
    {
        return count(array_filter(self::PATTERNS[$level->value], fn ($d) => $d[1] > 0 && $d[0] !== 'recover'));
    }

    /** Extra free-play minutes per day a young person should add to reach 60 a day. */
    public function youthDailyTopUp(Level $level): int
    {
        return (int) max(0, ceil((self::YOUTH_WEEKLY_MINUTES - $this->weeklyMinutes($level)) / 7));
    }
}
