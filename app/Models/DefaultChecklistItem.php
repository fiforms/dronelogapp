<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultChecklistItem extends Model
{
    protected $fillable = ['sort_order', 'label', 'has_comment_box'];

    protected $casts = ['has_comment_box' => 'boolean'];

    /**
     * Copy the default checklist into a team as a new "Standard Pre-Flight" template.
     */
    public static function seedTeam(Team $team): void
    {
        $template = $team->checklistTemplates()->create([
            'name'       => 'Standard Pre-Flight',
            'is_default' => true,
        ]);

        static::orderBy('sort_order')->each(function (self $item) use ($template) {
            $template->items()->create([
                'sort_order'      => $item->sort_order,
                'label'           => $item->label,
                'has_comment_box' => $item->has_comment_box,
            ]);
        });
    }
}
