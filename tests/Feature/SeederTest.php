<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_comes_from_env_settings_and_is_updated_on_reseed(): void
    {
        config(['hh.admin' => ['name' => 'Boss', 'email' => 'Boss@Example.com', 'password' => 'first-secret']]);
        $this->seed(DatabaseSeeder::class);

        $admin = User::firstWhere('email', 'boss@example.com');
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertTrue(Hash::check('first-secret', $admin->password));

        config(['hh.admin.password' => 'second-secret']);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(1, User::where('email', 'boss@example.com')->count());
        $this->assertTrue(Hash::check('second-secret', $admin->fresh()->password));
    }

    public function test_no_admin_without_a_password(): void
    {
        config(['hh.admin' => ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => null]]);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::where('role', 'admin')->count());
    }

    public function test_demo_clients_are_not_seeded_outside_local(): void
    {
        config(['hh.admin' => ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'first-secret']]);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::count());
    }
}
