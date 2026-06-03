<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name'  => 'Test Pilot',
            'email' => 'pilot@example.com',
        ]);

        $team = Team::create(['name' => "{$user->name}'s Team"]);
        $team->users()->attach($user, ['role' => 'owner']);

        $this->call([
            DefaultChecklistSeeder::class,
            SampleFleetSeeder::class,
        ]);
    }
}
