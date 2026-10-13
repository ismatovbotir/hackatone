<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Clients who registered in Telegram fill in their profile (age, gender, height, weight, consent) before anything else. */
class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user()?->client;

        if ($client && ! $client->isComplete()) {
            return redirect()->route('panel.profile');
        }

        return $next($request);
    }
}
