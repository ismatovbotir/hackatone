<?php

namespace App\Support;

/**
 * Translation in the prototype's style: JSON keys with {0}, {1} placeholders and per-language plural rules.
 */
final class Tr
{
    public static function get(string $key, array $args = [], ?string $locale = null): string
    {
        $s = __($key, [], $locale);

        return preg_replace_callback('/\{(\d+)\}/', fn ($m) => array_key_exists((int) $m[1], $args) ? (string) $args[(int) $m[1]] : $m[0], $s);
    }

    /** Plural forms separated by "|": en 2 forms, ru 3 forms, uz 1 form. */
    public static function choice(string $key, int $n, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $parts = explode('|', __($key, [], $locale));
        $i = min(self::pluralIndex($locale, $n), count($parts) - 1);

        return str_replace('{0}', (string) $n, $parts[$i]);
    }

    public static function pluralIndex(string $locale, int $n): int
    {
        return match ($locale) {
            'ru' => $n % 10 === 1 && $n % 100 !== 11 ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2),
            'uz' => 0,
            default => $n === 1 ? 0 : 1,
        };
    }

    /** Carbon locale for dates. Plain "uz" is Cyrillic in Carbon; the site writes Uzbek in Latin. */
    public static function dateLocale(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === 'uz' ? 'uz_Latn' : $locale;
    }

    /** "5 months" under two years, "2.5 years" after. */
    public static function months(?int $n): string
    {
        if ($n === null) {
            return '-';
        }

        return $n < 24 ? self::choice('mo', $n) : self::get('yr', [number_format($n / 12, 1)]);
    }
}
