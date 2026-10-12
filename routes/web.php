<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\TelegramWebhookController;
use App\Livewire\Admin;
use App\Livewire\ConfirmAccount;
use App\Livewire\Panel;
use App\Livewire\RegisterWizard;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');
Route::get('/lang/{locale}', LocaleController::class)->name('locale');

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->middleware('throttle:120,1')->name('telegram.webhook');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::livewire('/register', RegisterWizard::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::livewire('/confirm', ConfirmAccount::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')->name('verification.send');
});

// Panel for the "user" role: their own plan.
Route::middleware(['auth', 'confirmed', 'role:user'])->prefix('panel')->name('panel.')->group(function () {
    Route::livewire('/', Panel\Overview::class)->name('overview');
    Route::livewire('/training', Panel\Training::class)->name('training');
    Route::livewire('/calendar', Panel\Calendar::class)->name('calendar');
    Route::livewire('/feeding', Panel\Feeding::class)->name('feeding');
    Route::livewire('/achievements', Panel\Achievements::class)->name('achievements');
    Route::livewire('/telegram', Panel\Telegram::class)->name('telegram');
    Route::livewire('/account', Panel\Account::class)->name('account');
});

// Panel for the "admin" role.
Route::middleware(['auth', 'confirmed', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', Admin\Dashboard::class)->name('dashboard');
    Route::livewire('/clients', Admin\Clients::class)->name('clients.index');
    Route::livewire('/clients/{client}', Admin\ClientShow::class)->name('clients.show');
    Route::livewire('/users', Admin\Users::class)->name('users.index');
    Route::livewire('/videos', Admin\Videos::class)->name('videos');
    Route::livewire('/settings', Admin\Settings::class)->name('settings');
});
