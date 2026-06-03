<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightChecklist extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'flight_id',
        'checklist_item_id',
        'checked',
        'comment',
    ];

    protected $casts = [
        'checked' => 'boolean',
    ];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }
}
