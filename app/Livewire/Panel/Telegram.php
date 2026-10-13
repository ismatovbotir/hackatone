<?php

namespace App\Livewire\Panel;

use App\Livewire\Forms\RemindersForm;
use App\Livewire\Panel\Concerns\InteractsWithPlan;
use App\Support\LinkCodeSession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::panel')]
class Telegram extends Component
{
    use InteractsWithPlan;

    public RemindersForm $remindersForm;

    #[Url]
    public bool $welcome = false;

    public function mount(): void
    {
        $this->remindersForm->load($this->client);
    }

    public function saveReminders(): void
    {
        $this->remindersForm->save($this->client);
        $this->refreshPlan();
        session()->flash('status', t('tg.saved'));
    }

    public function newCode(): void
    {
        if (! $this->client->isLinked()) {
            LinkCodeSession::renew($this->client);
        }
    }

    /** Polled while not linked, so the page flips to "Connected" once the bot accepts the code. */
    public function checkLinked(): void
    {
        $this->refreshPlan();
    }

    public function render()
    {
        $linked = $this->client->isLinked();
        $code = $linked ? null : LinkCodeSession::current($this->client);

        return view('livewire.panel.telegram', [
            'linked' => $linked,
            'code' => $code,
            'link' => LinkCodeSession::botLink(),
        ])->title(t('tab.tg'));
    }
}
