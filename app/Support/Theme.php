<?php

namespace App\Support;

use App\Enums\Sex;

/** Colour scheme for the page: claude (default, guests and admins), green for male clients, pink for female clients. */
final class Theme
{
    public static function forSex(?Sex $sex): string
    {
        return match ($sex) {
            Sex::Male => 'green',
            Sex::Female => 'pink',
            default => 'claude',
        };
    }

    public static function current(): string
    {
        $user = auth()->user();

        return $user && ! $user->isAdmin() ? self::forSex($user->client?->sex) : 'claude';
    }
}
