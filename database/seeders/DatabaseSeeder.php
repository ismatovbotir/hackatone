<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();

        if (app()->isLocal()) {
            $this->seedDemoClients();
        }
    }

    /** Admin from HH_ADMIN_EMAIL / HH_ADMIN_PASSWORD in .env. Running the seeder again updates the password. */
    private function seedAdmin(): void
    {
        ['name' => $name, 'email' => $email, 'password' => $password] = config('hh.admin');

        if (blank($email) || blank($password) || strlen($password) < 8) {
            $this->command?->warn('Admin not created: set HH_ADMIN_EMAIL and HH_ADMIN_PASSWORD (8+ characters) in .env.');

            return;
        }

        $admin = User::updateOrCreate(
            ['email' => mb_strtolower($email)],
            ['name' => $name, 'password' => $password, 'role' => Role::Admin],
        );
        $admin->forceFill(['email_verified_at' => $admin->email_verified_at ?? now()])->save();

        $this->command?->info("Admin {$admin->email} is ready.");
    }

    /** Local demo data. Demo users have the password "password". */
    private function seedDemoClients(): void
    {
        Client::factory()->withMeasurement(161, 52)->create([
            'user_id' => User::factory()->state(['name' => 'Aziz Karimov', 'email' => 'aziz@example.com']),
            'first_name' => 'Aziz',
            'last_name' => 'Karimov',
            'birth_date' => now()->subYears(14)->subMonths(6),
            'sex' => 'M',
        ]);

        Client::factory()->withMeasurement(158, 50)->create([
            'user_id' => User::factory()->state(['name' => 'Malika Yusupova', 'email' => 'malika@example.com']),
            'first_name' => 'Malika',
            'last_name' => 'Yusupova',
            'birth_date' => now()->subYears(13)->subMonths(2),
            'sex' => 'F',
        ]);

        Client::factory()->count(12)->withMeasurement()->create();
    }
}
