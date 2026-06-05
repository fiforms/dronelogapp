<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'client_uuid'                => $this->client_uuid,
            'drone'                      => new DroneResource($this->whenLoaded('drone')),
            'battery'                    => new BatteryResource($this->whenLoaded('battery')),
            'accessories'                => AccessoryResource::collection($this->whenLoaded('accessories')),
            'battery_pct_start'          => $this->battery_pct_start,
            'battery_pct_end'            => $this->battery_pct_end,
            'started_at'                 => $this->started_at?->toIso8601String(),
            'ended_at'                   => $this->ended_at?->toIso8601String(),
            'duration_minutes'           => $this->duration_minutes,
            'lat'                        => $this->lat,
            'lng'                        => $this->lng,
            'location_description'       => $this->location_description,
            'flight_plan'                => $this->flight_plan,
            'purpose'                    => $this->purpose?->value,
            'purpose_notes'              => $this->purpose_notes,
            'laanc_status'               => $this->laanc_status?->value,
            'laanc_authorization_number' => $this->laanc_authorization_number,
            'post_flight_notes'          => $this->post_flight_notes,
            'checklist'                  => FlightChecklistResource::collection($this->whenLoaded('checklistEntries')),
            'synced_at'                  => $this->synced_at?->toIso8601String(),
            'created_at'                 => $this->created_at,
            'updated_at'                 => $this->updated_at,
        ];
    }
}
