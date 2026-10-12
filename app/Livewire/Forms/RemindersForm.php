<?php

namespace App\Livewire\Forms;

use App\Models\Client;
use Livewire\Form;

class RemindersForm extends Form
{
    /** @var array<string, bool> */
    public array $reminders = [];

    public string $reminder_time = '18:00';

    public function load(Client $client): void
    {
        $this->reminders = collect(Client::REMINDER_KEYS)->mapWithKeys(fn ($k) => [$k => $client->reminderEnabled($k)])->all();
        $this->reminder_time = substr($client->reminder_time, 0, 5);
    }

    public function save(Client $client): void
    {
        $this->validate([
            'reminders' => ['array'],
            'reminders.*' => ['boolean'],
            'reminder_time' => ['required', 'date_format:H:i'],
        ], ['reminder_time.*' => t('e.time')]);

        $client->update([
            'reminders' => collect(Client::REMINDER_KEYS)->mapWithKeys(fn ($k) => [$k => (bool) ($this->reminders[$k] ?? false)])->all(),
            'reminder_time' => $this->reminder_time,
        ]);
    }
}
