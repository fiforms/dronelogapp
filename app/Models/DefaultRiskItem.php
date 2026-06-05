<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultRiskItem extends Model
{
    protected $fillable = ['sort_order', 'label', 'description'];

    /**
     * Copy the default risk items into a team.
     */
    public static function seedTeam(Team $team): void
    {
        static::orderBy('sort_order')->each(function (self $item) use ($team) {
            $team->riskItems()->create([
                'sort_order'  => $item->sort_order,
                'label'       => $item->label,
                'description' => $item->description,
            ]);
        });
    }
}
