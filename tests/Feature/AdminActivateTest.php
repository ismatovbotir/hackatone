<?php

namespace Tests\Feature;

use App\Livewire\Admin\ClientShow;
use App\Livewire\Admin\Users;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminActivateTest extends TestCase
{
    use RefreshDatabase;

    private function unconfirmed(): Client
    {
        return Client::factory()->withMeasurement()->create(['user_id' => User::factory()->unverified()]);
    }

    public function test_admin_activates_from_the_client_page_and_the_user_gets_in(): void
    {
        $client = $this->unconfirmed();
        $this->actingAs($client->user)->get(route('panel.overview'))->assertRedirect(route('verification.notice'));

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(ClientShow::class, ['client' => $client])->assertSee(t('admin.activate'))->call('activate');

        $this->assertTrue($client->user->fresh()->isConfirmed());
        $this->actingAs($client->user->fresh())->get(route('panel.overview'))->assertOk();
    }

    public function test_admin_activates_from_the_users_list(): void
    {
        $client = $this->unconfirmed();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Users::class)->assertSee(t('admin.not_confirmed'))->call('activate', $client->user_id);

        $this->assertTrue($client->user->fresh()->isConfirmed());
    }

    public function test_users_cannot_reach_the_admin_pages(): void
    {
        $client = $this->unconfirmed();
        $other = Client::factory()->withMeasurement()->create()->user;

        $this->actingAs($other)->get(route('admin.users.index'))->assertForbidden();
        $this->assertFalse($client->user->fresh()->isConfirmed());
    }
}
