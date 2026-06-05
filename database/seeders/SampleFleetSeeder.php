<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class SampleFleetSeeder extends Seeder
{
    /**
     * Seeds a sample fleet for the first team. Run in dev only.
     */
    public function run(): void
    {
        $team = Team::first();

        if (! $team) {
            return;
        }

        $team->drones()->createMany([
            ['name' => 'Mini 4 Pro', 'model' => 'DJI Mini 4 Pro', 'serial' => 'MIN4P-001', 'registration_number' => null],
        ]);

        $team->batteries()->createMany([
            ['name' => 'Battery #1', 'capacity_mah' => 2453],
            ['name' => 'Battery #2', 'capacity_mah' => 2453],
            ['name' => 'Battery #3', 'capacity_mah' => 2453],
        ]);

        $team->accessories()->createMany([
            ['name' => 'ND16 Filter',         'type' => 'filter'],
            ['name' => 'ND32 Filter',         'type' => 'filter'],
            ['name' => 'CPL Filter',          'type' => 'filter'],
            ['name' => 'Anti-Collision Light','type' => 'light'],
        ]);
    }
}
