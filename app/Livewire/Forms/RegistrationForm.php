<?php

namespace App\Livewire\Forms;

use App\Models\Client;
use App\Services\Health\HealthAnalyzer;
use App\Support\Phone;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

/**
 * Registration wizard data and validation. The plain password never stays in the component:
 * it is hashed into the session as soon as the account step passes.
 */
class RegistrationForm extends Form
{
    public const STEPS = ['phone', 'name', 'account', 'about', 'body', 'review'];

    private const SESSION = 'registration';

    public string $phone = '';

    public string $first = '';

    public string $last = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $dob = '';

    public string $sex = '';

    public string $w = '';

    public string $h = '';

    public bool $consent = false;

    public function rulesFor(string $step): array
    {
        return match ($step) {
            'phone' => ['phone' => ['required', 'string', function (string $attr, mixed $value, Closure $fail) {
                $digits = Phone::normalize($value);
                if ($digits === null) {
                    $fail(t('e.phone'));
                } elseif (Client::where('phone', $digits)->exists()) {
                    $fail(t('e.phone_taken'));
                }
            }]],
            'name' => [
                'first' => ['required', 'string', 'max:100'],
                'last' => ['required', 'string', 'max:100'],
            ],
            'account' => [
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            ],
            'about' => [
                'dob' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', function (string $attr, mixed $value, Closure $fail) {
                    $age = HealthAnalyzer::age(Carbon::parse($value));
                    if ($age > 100) {
                        $fail(t('e.old'));
                    } elseif ($age < 5) {
                        $fail(t('e.young'));
                    }
                }],
                'sex' => ['required', Rule::in(['M', 'F'])],
            ],
            'body' => [
                'w' => ['required', 'numeric', 'between:10,300'],
                'h' => ['required', 'numeric', 'between:80,250'],
            ],
            'review' => ['consent' => ['accepted']],
        };
    }

    public function messages(): array
    {
        return [
            'phone.required' => t('e.phone'),
            'first.*' => t('e.name'),
            'last.*' => t('e.name'),
            'email.unique' => t('e.email_taken'),
            'email.*' => t('e.email'),
            'password.*' => t('e.password'),
            // Only built-in rules; the closure gives its own under-5 / over-100 messages.
            'dob.required' => t('e.dob'),
            'dob.date_format' => t('e.dob'),
            'dob.before_or_equal' => t('e.old'),
            'sex.*' => t('e.sex'),
            'w.*' => t('e.weight'),
            'h.*' => t('e.height'),
            'consent.*' => t('e.consent'),
        ];
    }

    public function validateStep(string $step): void
    {
        if ($step === 'account' && $this->password === '' && $this->hasPasswordHash()) {
            // Coming back to this step: keep the saved password unless a new one is typed.
            $this->validate(['email' => $this->rulesFor('account')['email']], $this->messages());
        } else {
            $this->validate($this->rulesFor($step), $this->messages());
        }

        if ($step === 'account' && $this->password !== '') {
            session()->put(self::SESSION.'.password', bcrypt($this->password));
            $this->password = $this->password_confirmation = '';
        }
    }

    /** First step whose data no longer passes, or null when everything is valid. */
    public function firstInvalidStep(): ?string
    {
        foreach (self::STEPS as $step) {
            try {
                if ($step === 'account') {
                    $this->validate(['email' => $this->rulesFor('account')['email']], $this->messages());
                    if (! $this->hasPasswordHash()) {
                        return 'account';
                    }
                } else {
                    $this->validate($this->rulesFor($step), $this->messages());
                }
            } catch (ValidationException $e) {
                return $step;
            }
        }

        return null;
    }

    public function hasPasswordHash(): bool
    {
        return session()->has(self::SESSION.'.password');
    }

    /** Wizard values for RegisterClient (phone normalised, password hashed). */
    public function payload(): array
    {
        return [
            'phone' => Phone::normalize($this->phone),
            'first' => trim($this->first),
            'last' => trim($this->last),
            'email' => mb_strtolower(trim($this->email)),
            'password' => session(self::SESSION.'.password'),
            'dob' => $this->dob,
            'sex' => $this->sex,
            'w' => $this->w,
            'h' => $this->h,
        ];
    }

    /** Keep answers (never the password) so a page reload does not lose them. */
    public function remember(): void
    {
        session()->put(self::SESSION.'.data', $this->only(['phone', 'first', 'last', 'email', 'dob', 'sex', 'w', 'h']));
    }

    public function restore(): void
    {
        $this->fill(session(self::SESSION.'.data', []));
    }

    public function forget(): void
    {
        session()->forget(self::SESSION);
        $this->reset();
    }
}
