<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ListUsers extends Command
{
    protected $signature = 'user:list';
    protected $description = 'List all user accounts';

    public function handle(): int
    {
        $users = User::orderBy('name')->get(['name', 'email', 'created_at']);

        if ($users->isEmpty()) {
            $this->info('No users found.');
            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'Email', 'Member Since'],
            $users->map(fn ($u) => [
                $u->name,
                $u->email,
                $u->created_at->format('Y-m-d'),
            ])
        );

        return self::SUCCESS;
    }
}
