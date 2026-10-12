<?php

namespace App\Http\Middleware;

use App\Support\Confirmation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** A user is confirmed by the email link or by linking Telegram with the 6-digit code. */
class EnsureAccountConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Confirmation::skipped() && ! $request->user()?->isConfirmed()) {
            return $request->expectsJson() ? abort(403) : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
