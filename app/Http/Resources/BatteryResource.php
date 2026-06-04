<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatteryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'capacity_mah'  => $this->capacity_mah,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'cycle_count'   => $this->cycle_count,
            'notes'         => $this->notes,
            'is_active'     => $this->is_active,
            'has_flights'   => ($this->flights_count ?? 0) > 0,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
