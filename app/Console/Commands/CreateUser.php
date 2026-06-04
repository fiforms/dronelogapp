<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\PromptsForPassword;
use App\Models\DefaultChecklistItem;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    use PromptsForPassword;

    protected $signature = 'user:create';
    protected $description = 'Create a new user account with a personal team and default checklist';

    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email {$email} already exists.");
            return self::FAILURE;
        }

        $password = $this->askPassword('Password');
        $confirm  = $this->askPassword('Confirm password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $team = Team::create(['name' => "{$user->name}'s Team"]);
        $team->users()->attach($user, ['role' => 'owner']);
        DefaultChecklistItem::seedTeam($team);

        $this->info("Created user {$email} with team \"{$team->name}\".");
        return self::SUCCESS;
    }
}
