<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
use App\Models\Team;
use Illuminate\Database\Seeder;

class DefaultChecklistSeeder extends Seeder
{
    public function run(): void
    {
        Team::all()->each(function (Team $team) {
            // Skip if this team already has a default template
            if ($team->checklistTemplates()->where('is_default', true)->exists()) {
                return;
            }

            $template = $team->checklistTemplates()->create([
                'name'       => 'Standard Pre-Flight',
                'is_default' => true,
            ]);

            $items = [
                'Weather Check: Wind speed, precipitation and visibility within limits',
                'Equipment Check: Arms fully extended, propellers in visually good condition, gimbal clear of obstructions, battery secure.',
                'Location Check: Acceptable visibility of flight area, flight path clear of obstructions',
                'Clearance Check: Permitted to fly in airspace (LAANC check, no TFRs), and permitted to launch and control from launch location',
            ];

            foreach ($items as $index => $label) {
                $template->items()->create([
                    'sort_order'      => $index + 1,
                    'label'           => $label,
                    'has_comment_box' => false,
                ]);
            }
        });
    }
}
