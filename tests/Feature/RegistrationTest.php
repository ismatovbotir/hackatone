<?php

namespace Tests\Feature;

use App\Livewire\RegisterWizard;
use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** Walk the wizard up to (not including) a step. The step property is locked, so tests go through next(). */
    private function upTo(string $stop)
    {
        $c = Livewire::test(RegisterWizard::class);
        $fill = [
            'lang' => fn ($c) => $c->set('form.lang', 'uz'),
            'phone' => fn ($c) => $c->set('form.phone', '90 123 45 67'),
            'name' => fn ($c) => $c->set('form.first', 'Aziz')->set('form.last', 'Karimov'),
            'account' => fn ($c) => $c->set('form.email', 'Aziz@Example.com')->set('form.password', 'secret123')->set('form.password_confirmation', 'secret123'),
            'about' => fn ($c) => $c->set('form.dob', now()->subYears(14)->subMonths(6)->toDateString())->set('form.sex', 'M'),
            'body' => fn ($c) => $c->set('form.w', '52')->set('form.h', '161'),
        ];
        foreach ($fill as $step => $set) {
            if ($step === $stop) {
                break;
            }
            $set($c)->call('next');
        }

        return $c->assertSet('step', $stop);
    }

    private function fillAll()
    {
        return $this->upTo('review');
    }

    public function test_full_registration_creates_user_client_and_measurement(): void
    {
        Notification::fake();

        $this->fillAll()
            ->assertSet('step', 'review')
            ->set('form.consent', true)->call('next')
            ->assertRedirect(route('verification.notice'));

        $user = User::firstWhere('email', 'aziz@example.com');
        $this->assertNotNull($user);
        $this->assertSame('user', $user->role->value);
        $this->assertSame('998901234567', $user->client->phone);
        $this->assertSame(161.0, $user->client->measurements->first()->height_cm);
        $this->assertNotNull($user->client->consent_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_password_is_not_kept_in_the_component(): void
    {
        $this->upTo('account')
            ->set('form.email', 'a@b.uz')->set('form.password', 'secret123')->set('form.password_confirmation', 'secret123')
            ->call('next')
            ->assertSet('form.password', '');
    }

    public function test_consent_is_required(): void
    {
        $this->fillAll()->call('next')->assertHasErrors('form.consent');
        $this->assertSame(0, User::count());
    }

    public function test_under_five_is_refused(): void
    {
        $this->upTo('about')
            ->set('form.dob', now()->subYears(4)->toDateString())->set('form.sex', 'F')
            ->call('next')
            ->assertHasErrors('form.dob')
            ->assertSee(t('e.young'));
    }

    public function test_phone_and_body_limits(): void
    {
        $c = $this->upTo('phone');
        $c->set('form.phone', '12345678')->call('next')->assertHasErrors('form.phone');
        $c->set('form.phone', '1234567890123456')->call('next')->assertHasErrors('form.phone');

        $c = $this->upTo('body');
        $c->set('form.w', '9.9')->set('form.h', '80')->call('next')->assertHasErrors('form.w');
        $c->set('form.w', '300')->set('form.h', '250.1')->call('next')->assertHasErrors('form.h');
    }

    public function test_duplicate_phone_is_refused(): void
    {
        Client::factory()->create(['phone' => '998901234567']);

        $this->upTo('phone')
            ->set('form.phone', '+998 90 123 45 67')->call('next')
            ->assertHasErrors('form.phone');
    }

    public function test_live_preview_suggests_a_level(): void
    {
        Livewire::test(RegisterWizard::class)
            ->set('form.dob', now()->subYears(14)->subMonths(6)->toDateString())
            ->set('form.sex', 'M')->set('form.w', '52')->set('form.h', '161')
            ->assertSee(t('lv.mid'));
    }

    public function test_switching_language_keeps_answers_and_step(): void
    {
        $this->upTo('name')->set('form.first', 'Aziz');

        $this->get(route('locale', 'ru'));

        Livewire::test(RegisterWizard::class)
            ->assertSet('step', 'name')
            ->assertSet('form.first', 'Aziz')
            ->assertSet('form.phone', '90 123 45 67');
    }

    public function test_local_dev_can_skip_confirmation(): void
    {
        Notification::fake();
        $this->app->detectEnvironment(fn () => 'local');
        config(['hh.skip_confirmation' => true]);

        $this->fillAll()->set('form.consent', true)->call('next')->assertRedirect(route('panel.overview'));

        $this->assertTrue(User::firstWhere('email', 'aziz@example.com')->hasVerifiedEmail());
        Notification::assertNothingSent();
    }

    public function test_skip_flag_is_ignored_outside_local(): void
    {
        Notification::fake();
        $this->app->detectEnvironment(fn () => 'production');
        config(['hh.skip_confirmation' => true]);

        $this->fillAll()->set('form.consent', true)->call('next')->assertRedirect(route('verification.notice'));
        $this->assertFalse(User::firstWhere('email', 'aziz@example.com')->hasVerifiedEmail());
    }

    public function test_fill_example_is_local_only(): void
    {
        Livewire::test(RegisterWizard::class)->assertDontSee(t('wz.sample'))->call('fillExample')->assertNotFound();

        Notification::fake();
        $this->app->detectEnvironment(fn () => 'local');
        Livewire::test(RegisterWizard::class)
            ->assertSee(t('wz.sample'))
            ->call('fillExample')
            ->assertSet('step', 'review')
            ->assertSet('form.password', '')
            ->set('form.consent', true)->call('next')
            ->assertHasNoErrors();

        $this->assertSame(1, User::where('role', 'user')->count());
    }

    public function test_language_is_the_first_step_and_is_used_for_the_account(): void
    {
        Livewire::test(RegisterWizard::class)
            ->assertSet('step', 'lang')
            ->call('next')->assertHasErrors('form.lang')
            ->call('selectLang', 'ru')
            ->assertRedirect(route('register'));

        $this->assertSame('ru', session('locale'));
        Livewire::test(RegisterWizard::class)->assertSet('step', 'phone')->assertSet('form.lang', 'ru');

        Notification::fake();
        $c = Livewire::test(RegisterWizard::class)
            ->set('form.phone', '901234567')->call('next')
            ->set('form.first', 'Aziz')->set('form.last', 'Karimov')->call('next')
            ->set('form.email', 'ru@example.com')->set('form.password', 'secret123')->set('form.password_confirmation', 'secret123')->call('next')
            ->set('form.dob', now()->subYears(12)->toDateString())->set('form.sex', 'M')->call('next')
            ->set('form.w', '40')->set('form.h', '150')->call('next')
            ->set('form.consent', true)->call('next');

        $this->assertSame('ru', User::firstWhere('email', 'ru@example.com')->client->lang);
    }
}
