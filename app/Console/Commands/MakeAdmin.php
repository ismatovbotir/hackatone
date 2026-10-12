<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('hh:admin {email} {--name=Admin}')]
#[Description('Create an admin user, or give an existing user the admin role')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->forceFill(['role' => Role::Admin])->save();
            $this->info("{$email} is now an admin.");

            return self::SUCCESS;
        }

        $password = $this->secret('Password (min 8 characters)');
        $v = Validator::make(['email' => $email, 'password' => $password], ['email' => 'email', 'password' => 'required|min:8']);
        if ($v->fails()) {
            $this->error($v->errors()->first());

            return self::FAILURE;
        }

        User::create(['name' => $this->option('name'), 'email' => $email, 'password' => $password, 'role' => Role::Admin])
            ->forceFill(['email_verified_at' => now()])->save();
        $this->info("Admin {$email} created.");

        return self::SUCCESS;
    }
}
