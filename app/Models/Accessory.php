<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Accessory extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'type',
        'notes',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function flights(): BelongsToMany
    {
        return $this->belongsToMany(Flight::class, 'flight_accessories');
    }
}
