<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\Telegram\WebApp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** The site opened inside Telegram: signs a linked client in from Telegram's signed initData. */
class TelegramWebAppController extends Controller
{
    public function show(): View
    {
        return view('telegram.app');
    }

    public function auth(Request $request): JsonResponse
    {
        $user = WebApp::user((string) $request->input('init_data'), config('services.telegram.bot_token'));
        if (! $user) {
            return response()->json(['status' => 'invalid'], 401);
        }

        // Private chat id = Telegram user id, which the bot saved when the client linked.
        $client = Client::where('telegram_chat_id', (int) $user['id'])->first();
        if (! $client) {
            return response()->json(['status' => 'not_linked'], 404);
        }

        Auth::login($client->user, true);
        $request->session()->regenerate();
        $request->session()->put('locale', $client->lang);
        // Inside Telegram only today's calendar day can be changed (App\Livewire\Panel\Calendar).
        $request->session()->put('via_telegram', true);

        $redirect = match (true) {
            ! $client->isComplete() => route('panel.profile'), // registered in Telegram: add the metrics first
            $request->input('open') === 'today' => route('panel.calendar', ['day' => 'today']),
            default => route('panel.overview'),
        };

        return response()->json(['status' => 'ok', 'redirect' => $redirect]);
    }
}
