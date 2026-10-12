<?php

namespace App\Livewire\Panel;

use App\Livewire\Panel\Concerns\InteractsWithPlan;
use App\Support\Phone;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::panel')]
class Account extends Component
{
    use InteractsWithPlan;

    /** Delete my data: user, profile, measurements, workout logs and link codes (cascade). */
    public function deleteData()
    {
        $user = auth()->user();
        $locale = app()->getLocale();

        Auth::logout();
        $user->delete();
        session()->invalidate();
        session()->regenerateToken();
        session()->put('locale', $locale);
        session()->flash('status', t('del.done'));

        return $this->redirectRoute('home');
    }

    public function render()
    {
        return view('livewire.panel.account', [
            'user' => auth()->user(),
            'phone' => Phone::format($this->client->phone),
        ])->title(t('panel.account'));
    }
}
