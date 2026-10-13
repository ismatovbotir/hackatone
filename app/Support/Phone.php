<?php

namespace App\Support;

final class Phone
{
    /**
     * Digits with country code, or null if invalid. Uzbek numbers written locally get +998:
     * "90 123 45 67" (9 digits), and "0 90 ..." or the old "8 90 ..." (10 digits starting with 0 or 8).
     * Telegram always sends the full international number, so both sides must end up as 998XXXXXXXXX.
     */
    public static function normalize(?string $raw): ?string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (strlen($d) === 9) {
            $d = '998'.$d;
        } elseif (strlen($d) === 10 && ($d[0] === '0' || $d[0] === '8')) {
            $d = '998'.substr($d, 1);
        }

        return strlen($d) >= 9 && strlen($d) <= 15 ? $d : null;
    }

    /** Only the last two digits visible, e.g. "+998 •• ••• •• 67". */
    public static function mask(string $digits): string
    {
        return str_starts_with($digits, '998') && strlen($digits) === 12
            ? '+998 •• ••• •• '.substr($digits, -2)
            : '+'.str_repeat('•', max(0, strlen($digits) - 2)).substr($digits, -2);
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
