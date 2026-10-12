<?php

namespace App\Support;

final class Confirmation
{
    /** True only on a local machine with HH_SKIP_CONFIRMATION=true. */
    public static function skipped(): bool
    {
        return app()->isLocal() && (bool) config('hh.skip_confirmation');
    }
}
