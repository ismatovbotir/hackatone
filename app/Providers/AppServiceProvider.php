<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Settings::apply();

        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => (new MailMessage)
            ->subject(t('mail.verify.subject'))
            ->line(t('mail.verify.line'))
            ->action(t('mail.verify.action'), $url)
            ->line(t('mail.verify.ignore')));
    }
}
