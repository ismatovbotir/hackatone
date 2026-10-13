<?php

namespace App\Livewire\Forms;

use App\Models\Client;
use App\Services\Health\HealthAnalyzer;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * The metrics a client registered in Telegram adds in the web app. Same limits as the website registration.
 * Email and password are optional: they only add email sign-in.
 */
class ProfileForm extends Form
{
    public string $dob = '';

    public string $sex = '';

    public string $w = '';

    public string $h = '';

    public bool $consent = false;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save(Client $client): void
    {
        $user = $client->user;

        $this->validate([
            'dob' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', function (string $a, mixed $v, Closure $fail) {
                $age = HealthAnalyzer::age(Carbon::parse($v));
                if ($age > 100) {
                    $fail(t('e.old'));
                } elseif ($age < 5) {
                    $fail(t('e.young'));
                }
            }],
            'sex' => ['required', Rule::in(['M', 'F'])],
            'w' => ['required', 'numeric', 'between:10,300'],
            'h' => ['required', 'numeric', 'between:80,250'],
            'consent' => ['accepted'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => $this->email !== '' && ! $user->password ? ['required', 'string', 'min:8', 'confirmed'] : ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'dob.required' => t('e.dob'),
            'dob.date_format' => t('e.dob'),
            'dob.before_or_equal' => t('e.old'),
            'sex.*' => t('e.sex'),
            'w.*' => t('e.weight'),
            'h.*' => t('e.height'),
            'consent.*' => t('e.consent'),
            'email.unique' => t('e.email_taken'),
            'email.*' => t('e.email'),
            'password.*' => t('e.password'),
        ]);

        DB::transaction(function () use ($client, $user) {
            $client->update(['birth_date' => $this->dob, 'sex' => $this->sex, 'consent_at' => $client->consent_at ?? now()]);

            if (! $client->measurements()->exists()) {
                $client->measurements()->create([
                    'measured_on' => now(config('hh.timezone'))->toDateString(),
                    'height_cm' => r1((float) $this->h),
                    'weight_kg' => r1((float) $this->w),
                ]);
            }

            if ($this->email !== '') {
                $user->email = mb_strtolower(trim($this->email));
            }
            if ($this->password !== '') {
                $user->password = $this->password;
            }
            $user->save();
        });

        $this->password = $this->password_confirmation = '';
    }
}
