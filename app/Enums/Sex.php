<?php

namespace App\Enums;

enum Sex: string
{
    case Male = 'M';
    case Female = 'F';

    /** Approximate age when the growth window closes. */
    public function growthEndAge(): int
    {
        return $this === self::Female ? 17 : 19;
    }
}
