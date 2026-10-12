<?php

namespace App\Http\Controllers;

use App\Services\Telegram\UpdateHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, UpdateHandler $handler): Response
    {
        $secret = (string) config('services.telegram.webhook_secret');
        abort_unless($secret !== '' && hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        rescue(fn () => $handler->handle($request->all()), report: true);

        // Always 200 so Telegram does not resend the update.
        return response()->noContent(200);
    }
}
