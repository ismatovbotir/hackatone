<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\TelegramSettingsForm;
use App\Services\Telegram\AdminNotifier;
use App\Services\Telegram\TelegramApi;
use App\Support\Settings as AppSettings;
use App\Support\Tr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts::panel')]
class Settings extends Component
{
    public TelegramSettingsForm $form;

    /** Result of the last action: [tone, message]. */
    public ?array $result = null;

    /** Empty means "keep the saved token"; the saved token is never sent to the browser. */
    public string $hfToken = '';

    public string $videoModel = '';

    public function mount(): void
    {
        $this->form->load();
        $this->videoModel = (string) config('services.huggingface.video_model');
    }

    public function saveAi(): void
    {
        $this->validate([
            'hfToken' => ['nullable', 'string', 'regex:/^hf_[A-Za-z0-9]{20,}$/'],
            'videoModel' => ['required', 'string', 'regex:/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/'],
        ], [
            'hfToken.regex' => t('e.hf_token'),
            'videoModel.*' => t('e.model'),
        ]);

        $values = ['ai.video_model' => $this->videoModel];
        if ($this->hfToken !== '') {
            $values['ai.hf_token'] = $this->hfToken;
        }
        AppSettings::save($values);
        $this->hfToken = '';
        session()->flash('status', t('set.saved'));
    }

    public function testAi(): void
    {
        $this->saveAi();
        $token = config('services.huggingface.token');
        $who = $token ? rescue(fn () => Http::withToken($token)->timeout(10)->get('https://huggingface.co/api/whoami-v2'), null, false) : null;

        $this->result = $who?->successful()
            ? ['good', t('set.ai.ok', $who->json('name'))]
            : ['bad', t('set.ai.fail')];
    }

    public function save(): void
    {
        $this->form->save();
        session()->flash('status', t('set.saved'));
    }

    public function generateSecret(): void
    {
        $this->form->secret = Str::random(48);
    }

    public function testToken(TelegramApi $api): void
    {
        $this->form->save();
        $me = $api->configured() ? rescue(fn () => $api->getMe(), null, false) : null;

        if (! $me) {
            $this->result = ['bad', t('set.test.fail')];

            return;
        }

        AppSettings::save(['telegram.bot_username' => $me['username']]);
        $this->form->username = $me['username'];
        $this->result = ['good', t('set.test.ok', $me['username'])];
    }

    public function setWebhook(TelegramApi $api): void
    {
        $this->form->save();
        if (! $api->configured() || blank(config('services.telegram.webhook_secret'))) {
            $this->result = ['warn', t('set.hook.need')];

            return;
        }

        $response = rescue(fn () => $api->setWebhook(route('telegram.webhook'), config('services.telegram.webhook_secret')), null, false);
        $ok = (bool) $response?->json('ok');
        $this->result = [$ok ? 'good' : 'bad', t('set.hook.ok', $response?->json('description') ?? t('set.test.fail'))];
    }

    public function deleteWebhook(TelegramApi $api): void
    {
        if (! $api->configured()) {
            $this->result = ['warn', t('set.hook.need')];

            return;
        }

        $response = rescue(fn () => $api->deleteWebhook(), null, false);
        $this->result = [$response?->json('ok') ? 'good' : 'bad', t('set.hook.ok', $response?->json('description') ?? t('set.test.fail'))];
    }

    public function testAdminAlert(TelegramApi $api, AdminNotifier $notifier): void
    {
        $this->form->save();
        $sent = 0;
        foreach ($notifier->chatIds() as $id) {
            try {
                $sent += $api->sendMessage($id, Tr::get('notify.test', [], config('hh.default_locale')))->successful() ? 1 : 0;
            } catch (Throwable) {
            }
        }
        $this->result = [$sent > 0 ? 'good' : 'bad', $sent > 0 ? t('set.admin_ids.sent', $sent) : t('set.test.fail')];
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'hasToken' => filled(config('services.telegram.bot_token')),
            'hasHfToken' => filled(config('services.huggingface.token')),
            'webhookUrl' => route('telegram.webhook'),
            'cron' => '* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1',
        ])->title(t('admin.settings'));
    }
}
