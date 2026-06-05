<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlightRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_uuid'                   => ['required', 'uuid'],
            'drone_id'                      => ['nullable', 'integer', 'exists:drones,id'],
            'battery_id'                    => ['nullable', 'integer', 'exists:batteries,id'],
            'battery_pct_start'             => ['nullable', 'integer', 'between:0,100'],
            'started_at'                    => ['required', 'date'],
            'ended_at'                      => ['nullable', 'date', 'after:started_at'],
            'lat'                           => ['nullable', 'numeric', 'between:-90,90'],
            'lng'                           => ['nullable', 'numeric', 'between:-180,180'],
            'location_description'          => ['nullable', 'string', 'max:255'],
            'flight_plan'                   => ['nullable', 'string'],
            'purpose'                       => ['required', 'in:recreational,commercial'],
            'purpose_notes'                 => ['nullable', 'string'],
            'laanc_status'                  => ['required', 'in:received,not_needed,na'],
            'laanc_authorization_number'    => ['nullable', 'string', 'max:50'],
            'post_flight_notes'             => ['nullable', 'string'],
            'is_retrospective'              => ['boolean'],
            'battery_pct_end'               => ['nullable', 'integer', 'between:0,100'],
            'duration_minutes'              => ['nullable', 'integer', 'min:0'],
            'accessories'                   => ['nullable', 'array'],
            'accessories.*'                 => ['integer', 'exists:accessories,id'],
            'checklist'                     => ['nullable', 'array'],
            'checklist.*.checklist_item_id' => ['required', 'integer', 'exists:checklist_items,id'],
            'checklist.*.checked'           => ['required', 'boolean'],
            'checklist.*.comment'           => ['nullable', 'string', 'max:1000'],
            'status'                        => ['nullable', 'in:flown,aborted,deleted'],
            'risk_scores'                   => ['nullable', 'array'],
            'risk_scores.*.risk_item_id'    => ['nullable', 'integer', 'exists:risk_items,id'],
            'risk_scores.*.label'           => ['required', 'string', 'max:255'],
            'risk_scores.*.score'           => ['required', 'integer', 'between:0,3'],
            'risk_scores.*.mitigation_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
