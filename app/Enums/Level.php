<?php

namespace App\Enums;

enum Level: string
{
    case Easy = 'easy';
    case Mid = 'mid';
    case Strong = 'strong';

    /** WHO/FAO physical activity level multiplier. */
    public function activityFactor(): float
    {
        return match ($this) {
            self::Easy => 1.45,
            self::Mid => 1.65,
            self::Strong => 1.85,
        };
    }

    /** Our assumption (not WHO): share of the typical growth pace reached on this level. */
    public function growthPace(): float
    {
        return match ($this) {
            self::Easy => 0.9,
            self::Mid => 1.0,
            self::Strong => 1.05,
        };
    }

    public function proteinPerKg(bool $youth): float
    {
        return match ($this) {
            self::Easy => 1.0,
            self::Mid => $youth ? 1.1 : 1.3,
            self::Strong => $youth ? 1.2 : 1.6,
        };
    }
}
