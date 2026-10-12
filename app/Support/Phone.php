<?php

namespace App\Support;

final class Phone
{
    /** Digits with country code, or null if invalid. Nine digits are treated as Uzbek (+998). */
    public static function normalize(?string $raw): ?string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (strlen($d) === 9) {
            $d = '998'.$d;
        }

        return strlen($d) >= 9 && strlen($d) <= 15 ? $d : null;
    }

    /** "+998 90 123 45 67" for Uzbek numbers, "+<digits>" otherwise. */
    public static function format(?string $raw): ?string
    {
        $d = self::normalize($raw);
        if ($d === null) {
            return null;
        }
        if (str_starts_with($d, '998') && strlen($d) === 12) {
            return '+998 '.substr($d, 3, 2).' '.substr($d, 5, 3).' '.substr($d, 8, 2).' '.substr($d, 10);
        }

        return '+'.$d;
    }
}
