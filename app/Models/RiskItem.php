<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskItem extends Model
{
    protected $fillable = ['team_id', 'sort_order', 'label', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
