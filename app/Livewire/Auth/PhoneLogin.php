<?php

namespace App\Livewire\Auth;

use App\Services\Telegram\LoginCodeService;
use App\Support\Phone;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Sign in with a phone number and a 6-digit code sent by the Telegram bot. Embedded on the login page. */
class PhoneLogin extends Component
{
    public string $phone = '';

    public string $code = '';

    public bool $remember = false;

    #[Locked]
    public bool $sent = false;

    public function sendCode(LoginCodeService $codes): void
    {
        $this->validate(['phone' => ['required', 'string']], ['phone.*' => t('e.phone')]);
        if (Phone::normalize($this->phone) === null) {
            $this->addError('phone', t('e.phone'));

            return;
        }

        if (! $codes->send($this->phone, request()->ip())) {
            $this->addError('phone', t('plogin.too_many'));

            return;
        }

        $this->sent = true;
        $this->code = '';
    }

    public function verify(LoginCodeService $codes)
    {
        $client = $codes->verify($this->phone, $this->code, request()->ip());

        if (! $client) {
            $this->addError('code', t('plogin.wrong'));

            return null;
        }

        Auth::login($client->user, $this->remember);
        session()->regenerate();

        return $this->redirectIntended(route('panel.overview'), navigate: true);
    }

    public function back(): void
    {
        $this->sent = false;
        $this->code = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.auth.phone-login');
    }
}
