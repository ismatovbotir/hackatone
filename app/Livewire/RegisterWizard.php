<?php

namespace App\Livewire;

use App\Enums\Sex;
use App\Livewire\Forms\RegistrationForm;
use App\Services\Clients\RegisterClient;
use App\Services\Health\GrowthReference;
use App\Services\Health\HealthAnalyzer;
use App\Services\Telegram\AdminNotifier;
use App\Support\Confirmation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

#[Layout('layouts::app')]
class RegisterWizard extends Component
{
    public RegistrationForm $form;

    #[Locked]
    public string $step = 'phone';

    /** @var list<string> */
    #[Locked]
    public array $done = [];

    public function mount(): void
    {
        $this->form->restore();
        $this->done = session('registration.done', []);

        // Come back to the step the client was on (e.g. after switching language), if it is still reachable.
        $saved = session('registration.step');
        $this->step = $saved && $this->reachable($saved) ? $saved : $this->furthest();
    }

    /** Keep typed answers (never the password) so a language switch or reload does not lose them. */
    public function updated(string $property): void
    {
        if (str_starts_with($property, 'form.') && ! str_starts_with($property, 'form.password')) {
            $this->form->remember();
        }
    }

    public function dehydrate(): void
    {
        session()->put('registration.step', $this->step);
    }

    public function next(RegisterClient $register, AdminNotifier $notifier)
    {
        $this->form->validateStep($this->step);

        if ($this->step !== 'review') {
            $this->done = array_values(array_unique([...$this->done, $this->step]));
            session()->put('registration.done', $this->done);
            $this->form->remember();
            $this->step = $this->furthest();

            return null;
        }

        if ($bad = $this->form->firstInvalidStep()) {
            $this->step = $bad;

            return null;
        }

        $user = $register->handle($this->form->payload(), app()->getLocale());
        $this->form->forget();

        if (Confirmation::skipped()) {
            $user->markEmailAsVerified(); // local development only
        }

        event(new Registered($user)); // sends the verification email unless already verified
        Auth::login($user);
        session()->regenerate();
        rescue(fn () => $notifier->newRegistration($user->client), report: false);

        return $this->redirectRoute(Confirmation::skipped() ? 'panel.overview' : 'verification.notice');
    }

    /** Local development only: fill every step with sample data and jump to the review step. */
    public function fillExample(): void
    {
        abort_unless(app()->isLocal(), 404);

        $female = random_int(0, 1) === 1;
        $this->form->fill([
            'phone' => '90'.random_int(1000000, 9999999),
            'first' => $female ? 'Malika' : 'Aziz',
            'last' => $female ? 'Yusupova' : 'Karimov',
            'email' => 'test'.now()->format('His').random_int(10, 99).'@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'dob' => now()->subYears(14)->subMonths(6)->toDateString(),
            'sex' => $female ? 'F' : 'M',
            'w' => $female ? '48' : '52',
            'h' => $female ? '157' : '161',
        ]);

        foreach (array_slice(RegistrationForm::STEPS, 0, -1) as $step) {
            $this->form->validateStep($step); // also hashes the password into the session
        }

        $this->done = array_slice(RegistrationForm::STEPS, 0, -1);
        session()->put('registration.done', $this->done);
        $this->form->remember();
        $this->step = 'review';
    }

    public function back(): void
    {
        $i = array_search($this->step, RegistrationForm::STEPS, true);
        $this->step = RegistrationForm::STEPS[max(0, $i - 1)];
        $this->resetValidation();
    }

    public function goTo(string $step): void
    {
        if ($this->reachable($step)) {
            $this->step = $step;
            $this->resetValidation();
        }
    }

    public function clear(): void
    {
        $this->form->forget();
        $this->done = [];
        $this->step = 'phone';
        $this->resetValidation();
    }

    private function reachable(string $step): bool
    {
        $target = array_search($step, RegistrationForm::STEPS, true);

        return $target !== false && $target <= array_search($this->furthest(), RegistrationForm::STEPS, true);
    }

    private function furthest(): string
    {
        foreach (RegistrationForm::STEPS as $step) {
            if ($step === 'review' || ! in_array($step, $this->done, true)) {
                return $step;
            }
        }

        return 'review';
    }

    /** Live preview, as in the prototype: WHO range for the age, then BMI and level once the body step has values. */
    #[Computed]
    public function preview(): ?array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->form->dob) || ! in_array($this->form->sex, ['M', 'F'], true)) {
            return null;
        }

        try {
            $age = HealthAnalyzer::age(Carbon::parse($this->form->dob));
        } catch (Throwable) {
            return null;
        }
        if ($age < 5 || $age > 100) {
            return null;
        }

        $sex = Sex::from($this->form->sex);
        $w = (float) $this->form->w;
        $h = (float) $this->form->h;

        return [
            'age' => $age,
            'sex' => $sex,
            'median' => GrowthReference::heightMedian($sex, $age),
            'sd' => GrowthReference::heightSd($sex, $age),
            'endAge' => $sex->growthEndAge(),
            'a' => $w >= 10 && $w <= 300 && $h >= 80 && $h <= 250
                ? (new HealthAnalyzer)->analyzeAge($sex, $age, $h, $w)
                : null,
        ];
    }

    public function render()
    {
        return view('livewire.register-wizard')->title(t('wz.title'));
    }
}
