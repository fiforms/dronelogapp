<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightRiskScore extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'flight_id',
        'risk_item_id',
        'label',
        'score',
        'mitigation_notes',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    public function riskItem(): BelongsTo
    {
        return $this->belongsTo(RiskItem::class);
    }
}
