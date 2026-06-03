<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightChecklistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'checklist_item_id' => $this->checklist_item_id,
            'label'             => $this->whenLoaded('checklistItem', fn () => $this->checklistItem->label),
            'checked'           => $this->checked,
            'comment'           => $this->comment,
        ];
    }
}
