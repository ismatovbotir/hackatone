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
        $locale = $request->session()->get('locale')
            ?? $request->user()?->client?->lang
            ?? $request->getPreferredLanguage($locales)
            ?? config('hh.default_locale');

        app()->setLocale(in_array($locale, $locales, true) ? $locale : config('hh.default_locale'));

        return $next($request);
    }
}
