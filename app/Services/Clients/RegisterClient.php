<?php

namespace App\Services\Clients;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterClient
{
    /** Create the user account, health profile and first measurement from the wizard data. */
    public function handle(array $data, string $lang): User
    {
        return DB::transaction(function () use ($data, $lang) {
            $user = new User([
                'name' => $data['first'].' '.$data['last'],
                'email' => $data['email'],
                'role' => Role::User,
            ]);
            $user->password = $data['password']; // already hashed
            $user->save();

            $client = $user->client()->create([
                'phone' => $data['phone'],
                'first_name' => $data['first'],
                'last_name' => $data['last'],
                'birth_date' => $data['dob'],
                'sex' => $data['sex'],
                'lang' => $lang,
                'reminders' => Client::DEFAULT_REMINDERS,
                'reminder_time' => '18:00',
                'consent_at' => now(),
            ]);

            $client->measurements()->create([
                'measured_on' => now(config('hh.timezone'))->toDateString(),
                'height_cm' => r1((float) $data['h']),
                'weight_kg' => r1((float) $data['w']),
            ]);

            return $user;
        });
    }
}
