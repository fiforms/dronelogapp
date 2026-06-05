<?php

namespace Database\Seeders;

use App\Models\DefaultRiskItem;
use App\Models\Team;
use Illuminate\Database\Seeder;

class DefaultRiskSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'sort_order'  => 1,
                'label'       => 'Weather',
                'description' => 'Wind, precipitation, visibility, temperature extremes, solar interference.',
            ],
            [
                'sort_order'  => 2,
                'label'       => 'Take-off / Landing Site Visibility',
                'description' => 'Obstacles obstructing VLOS to planned flight path; overhead obstacles affecting RTH.',
            ],
            [
                'sort_order'  => 3,
                'label'       => 'Take-off / Landing Site Safety',
                'description' => 'People, traffic, or potential pilot distraction or safety concerns. Score 2 or higher for moving vehicle control — document pilot/driver coordination and RTH contingency plans.',
            ],
            [
                'sort_order'  => 4,
                'label'       => 'Airspace Congestion',
                'description' => 'Controlled airspace, nearby airports, helipads, MTRs, or other low-flying aircraft or drones.',
            ],
            [
                'sort_order'  => 5,
                'label'       => 'Urban or Populated Area',
                'description' => 'People, streets with traffic, buildings, and potential RF interference.',
            ],
            [
                'sort_order'  => 6,
                'label'       => 'Pilot Fitness',
                'description' => 'Fatigue, pressure, distractions.',
            ],
            [
                'sort_order'  => 7,
                'label'       => 'Night or Low-Light Operations',
                'description' => 'Reduced visibility affecting situational awareness and aircraft orientation.',
            ],
            [
                'sort_order'  => 8,
                'label'       => 'Unfamiliar Location',
                'description' => 'First visit to this site; unknown hazards, escape routes, or local airspace constraints.',
            ],
        ];

        foreach ($items as $item) {
            DefaultRiskItem::firstOrCreate(['label' => $item['label']], $item);
        }

        // Seed risk items for any existing teams that have none yet
        Team::whereDoesntHave('riskItems')->each(function (Team $team) {
            DefaultRiskItem::seedTeam($team);
        });
    }
}
