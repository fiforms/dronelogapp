<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Battery extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'capacity_mah',
        'purchase_date',
        'cycle_count',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'is_active'     => 'boolean',
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
