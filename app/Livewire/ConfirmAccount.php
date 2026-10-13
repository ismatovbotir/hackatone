<?php

namespace App\Livewire;

use App\Support\LinkCodeSession;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Last step of registration: confirm the account by the email link or by sending the 6-digit code to the Telegram bot.
 * Polls so the page moves on by itself once the bot accepts the code.
 */
#[Layout('layouts::app')]
class ConfirmAccount extends Component
{
    public bool $sent = false;

    public function mount()
    {
        return $this->redirectIfConfirmed();
    }

    public function check()
    {
        auth()->user()->unsetRelation('client');
        auth()->user()->refresh();

        return $this->redirectIfConfirmed();
    }

    public function resend(): void
    {
        $user = auth()->user();
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $this->sent = true;
        }
    }

    public function newCode(): void
    {
        if ($client = auth()->user()->client) {
            LinkCodeSession::renew($client);
        }
    }

    private function redirectIfConfirmed()
    {
        $user = auth()->user();
        if (! $user->isConfirmed()) {
            return null;
        }

        return $this->redirectRoute($user->isAdmin() ? 'admin.dashboard' : 'panel.overview', navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $code = $user->client ? LinkCodeSession::current($user->client) : null;

        return view('livewire.confirm-account', [
            'email' => $user->email,
            'code' => $code,
            'link' => $code ? LinkCodeSession::deepLink($code['code']) : null,
        ])->title(t('confirm.title'));
    }
}
