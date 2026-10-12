<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = config('hh.locales');
        // Uzbek unless the visitor picked a language with the switch, or a signed-in client saved one.
        // The browser's language is deliberately ignored.
        $locale = $request->session()->get('locale')
            ?? $request->user()?->client?->lang
            ?? config('hh.default_locale');

        app()->setLocale(in_array($locale, $locales, true) ? $locale : config('hh.default_locale'));

        return $next($request);
    }
}
