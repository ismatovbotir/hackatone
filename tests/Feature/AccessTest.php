<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        return Client::factory()->withMeasurement(150, 42)->create(['birth_date' => now()->subYears(12)])->user;
    }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('HackaTone Health');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_user_panel_pages_render(): void
    {
        $user = $this->client();

        foreach (['overview', 'training', 'calendar', 'feeding', 'achievements', 'telegram', 'account'] as $page) {
            $this->actingAs($user)->get(route("panel.$page"))->assertOk();
        }
    }

    public function test_user_cannot_open_admin(): void
    {
        $this->actingAs($this->client())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->withMeasurement()->create();

        foreach (['admin.dashboard', 'admin.clients.index', 'admin.users.index', 'admin.videos', 'admin.settings'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.clients.show', $client))->assertOk();
    }

    public function test_admin_is_sent_from_user_panel_to_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('panel.overview'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_unverified_user_must_confirm_email(): void
    {
        $user = Client::factory()->withMeasurement()->create(['user_id' => User::factory()->unverified()])->user;

        $this->actingAs($user)->get(route('panel.overview'))->assertRedirect(route('verification.notice'));
    }

    public function test_login_sends_each_role_to_its_panel(): void
    {
        $user = $this->client();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('panel.overview'));

        auth()->logout();
        $admin = User::factory()->admin()->create();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_language_switch_updates_client_language(): void
    {
        $user = $this->client();
        $this->actingAs($user)->get(route('locale', 'ru'))->assertRedirect();
        $this->assertSame('ru', $user->client->fresh()->lang);
    }

    public function test_linking_telegram_confirms_the_account_without_email(): void
    {
        $user = Client::factory()->withMeasurement()->create(['user_id' => User::factory()->unverified()])->user;

        $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertSee(t('confirm.tg.t'));

        $user->client->forceFill(['telegram_chat_id' => 123])->save();
        $this->actingAs($user->fresh())->get(route('panel.overview'))->assertOk();
        $this->actingAs($user->fresh())->get(route('verification.notice'))->assertRedirect(route('panel.overview'));
    }
}
