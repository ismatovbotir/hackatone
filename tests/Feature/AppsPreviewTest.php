<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppsPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_the_mobile_app_preview(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.apps'))
            ->assertOk()
            ->assertSee(t('apps.s.today'))
            ->assertSee(t('apps.sample'))
            ->assertSee(t('apps.roadmap'));
    }

    public function test_only_admins(): void
    {
        $this->actingAs(Client::factory()->withMeasurement()->create()->user)->get(route('admin.apps'))->assertForbidden();
    }
}
