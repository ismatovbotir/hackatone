<?php

namespace App\Livewire\Forms;

use App\Support\Settings;
use Closure;
use Illuminate\Support\Str;
use Livewire\Form;

class TelegramSettingsForm extends Form
{
    /** Empty means "keep the saved token"; the saved token is never sent to the browser. */
    public string $token = '';

    public string $username = '';

    public string $secret = '';

    public string $adminChatIds = '';

    public function load(): void
    {
        $this->username = (string) Settings::get('telegram.bot_username');
        $this->secret = (string) Settings::get('telegram.webhook_secret');
        $this->adminChatIds = (string) Settings::get('telegram.admin_chat_ids');
    }

    public function save(): void
    {
        // The webhook always needs a secret; make one if the admin left it empty.
        if (trim($this->secret) === '') {
            $this->secret = Str::random(48);
        }

        $this->validate([
            'token' => ['nullable', 'string', 'regex:/^\d+:[A-Za-z0-9_-]{30,}$/'],
            'username' => ['nullable', 'string', 'regex:/^@?[A-Za-z][A-Za-z0-9_]{3,31}$/'],
            'secret' => ['nullable', 'string', 'regex:/^[A-Za-z0-9_-]{16,256}$/'],
            'adminChatIds' => ['nullable', 'string', function (string $a, mixed $v, Closure $fail) {
                foreach (array_filter(array_map('trim', explode(',', (string) $v))) as $id) {
                    if (! preg_match('/^-?\d+$/', $id)) {
                        $fail(t('e.chat_ids'));
                    }
                }
            }],
        ]);

        $values = [
            'telegram.bot_username' => ltrim($this->username, '@') ?: null,
            'telegram.webhook_secret' => $this->secret ?: null,
            'telegram.admin_chat_ids' => implode(',', array_filter(array_map('trim', explode(',', $this->adminChatIds)))) ?: null,
        ];
        if ($this->token !== '') {
            $values['telegram.bot_token'] = $this->token;
        }

        Settings::save($values);
        $this->token = '';
    }
}
