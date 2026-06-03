<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightAccessory extends Model
{
    public $timestamps = false;

    protected $fillable = ['flight_id', 'accessory_id'];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function accessory(): BelongsTo
    {
        return $this->belongsTo(Accessory::class);
    }
}
