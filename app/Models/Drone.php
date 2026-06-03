<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drone extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'model',
        'serial',
        'registration_number',
        'notes',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class);
    }
}
