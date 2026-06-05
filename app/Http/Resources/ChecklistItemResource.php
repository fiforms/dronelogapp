<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'template_id'     => $this->template_id,
            'sort_order'      => $this->sort_order,
            'label'           => $this->label,
            'has_comment_box' => $this->has_comment_box,
            'is_active'       => $this->is_active,
        ];
    }
}
