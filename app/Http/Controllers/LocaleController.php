<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('hh.locales'), true), 404);

        $request->session()->put('locale', $locale);
        // Bot messages follow the language the client uses on the site.
        $request->user()?->client?->update(['lang' => $locale]);

        return back();
    }
}
