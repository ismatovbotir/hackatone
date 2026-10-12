<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone' => '99890'.fake()->unique()->numerify('#######'),
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-40 years', '-6 years')->format('Y-m-d'),
            'sex' => fake()->randomElement(['M', 'F']),
            'lang' => 'uz',
            'level' => null,
            'reminders' => Client::DEFAULT_REMINDERS,
            'reminder_time' => '18:00',
            'consent_at' => now(),
        ];
    }

    public function withMeasurement(float $height = 160, float $weight = 52): static
    {
        return $this->afterCreating(fn (Client $client) => $client->measurements()->create([
            'measured_on' => now()->toDateString(),
            'height_cm' => $height,
            'weight_kg' => $weight,
        ]));
    }
}
