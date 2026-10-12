<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_admins_get_the_main_scheme(): void
    {
        $this->get('/')->assertSee('data-theme="claude"', false);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertSee('data-theme="claude"', false);
    }

    public function test_male_clients_get_green_and_female_clients_get_pink(): void
    {
        $male = Client::factory()->withMeasurement()->create(['sex' => 'M'])->user;
        $female = Client::factory()->withMeasurement()->create(['sex' => 'F'])->user;

        $this->actingAs($male)->get(route('panel.overview'))->assertSee('data-theme="green"', false);
        $this->actingAs($female)->get(route('panel.overview'))->assertSee('data-theme="pink"', false);
        $this->actingAs($female)->get('/')->assertSee('data-theme="pink"', false);
    }
}
