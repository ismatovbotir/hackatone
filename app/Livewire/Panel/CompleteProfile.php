<?php

namespace App\Livewire\Panel;

use App\Livewire\Forms\ProfileForm;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** After registering in Telegram: the metrics the plan needs, then the panel. */
#[Layout('layouts::panel')]
class CompleteProfile extends Component
{
    public ProfileForm $form;

    public function mount()
    {
        $client = auth()->user()->client;
        if ($client->isComplete()) {
            return $this->redirectRoute('panel.overview', navigate: true);
        }

        $this->form->dob = $client->birth_date?->toDateString() ?? '';
        $this->form->sex = $client->sex?->value ?? '';
        $this->form->email = (string) auth()->user()->email;

        return null;
    }

    public function save()
    {
        $client = auth()->user()->client;
        $this->form->save($client);
        session()->flash('status', t('prof.saved'));

        return $this->redirectRoute('panel.overview', navigate: true);
    }

    public function render()
    {
        return view('livewire.panel.complete-profile', [
            'client' => auth()->user()->client,
            'hasPassword' => (bool) auth()->user()->password,
        ])->title(t('prof.title'));
    }
}
