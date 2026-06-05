<?php

namespace App\Models;

use App\Enums\LaancStatusEnum;
use App\Enums\PurposeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flight extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'drone_id',
        'battery_id',
        'battery_pct_start',
        'battery_pct_end',
        'client_uuid',
        'started_at',
        'ended_at',
        'duration_minutes',
        'lat',
        'lng',
        'location_description',
        'flight_plan',
        'purpose',
        'purpose_notes',
        'laanc_status',
        'laanc_authorization_number',
        'post_flight_notes',
        'is_retrospective',
        'status',
        'synced_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'ended_at'     => 'datetime',
        'synced_at'    => 'datetime',
        'lat'          => 'decimal:7',
        'lng'          => 'decimal:7',
        'purpose'          => PurposeEnum::class,
        'laanc_status'     => LaancStatusEnum::class,
        'is_retrospective' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function drone(): BelongsTo
    {
        return $this->belongsTo(Drone::class);
    }

    public function battery(): BelongsTo
    {
        return $this->belongsTo(Battery::class);
    }

    public function accessories(): BelongsToMany
    {
        return $this->belongsToMany(Accessory::class, 'flight_accessories');
    }

    public function checklistEntries(): HasMany
    {
        return $this->hasMany(FlightChecklist::class);
    }

    public function riskScores(): HasMany
    {
        return $this->hasMany(FlightRiskScore::class);
    }
}
