<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetPassword extends Command
{
    protected $signature = 'user:reset-password {email? : The user\'s email address}';
    protected $description = "Reset a user's password";

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Email');
        $user  = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with email {$email}.");
            return self::FAILURE;
        }

        $password = $this->secret('New password');
        $confirm  = $this->secret('Confirm new password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');
            return self::FAILURE;
        }

        $user->update(['password' => Hash::make($password)]);

        $this->info("Password updated for {$email}.");
        return self::SUCCESS;
    }
}
