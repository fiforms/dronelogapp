<?php

namespace Database\Seeders;

use App\Models\DefaultChecklistItem;
use Illuminate\Database\Seeder;

class DefaultChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Weather Check: Wind speed, precipitation and visibility within limits',
            'Equipment Check: Arms fully extended, propellers in visually good condition, gimbal clear of obstructions, battery secure.',
            'Location Check: Acceptable visibility of flight area, flight path clear of obstructions',
            'Clearance Check: Permitted to fly in airspace (LAANC check, no TFRs), and permitted to launch and control from launch location',
        ];

        foreach ($items as $i => $label) {
            DefaultChecklistItem::firstOrCreate(
                ['label' => $label],
                ['sort_order' => $i + 1, 'has_comment_box' => false]
            );
        }
    }
}
