<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlightRequest extends FormRequest
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
            'ended_at'                      => ['nullable', 'date'],
            'duration_minutes'              => ['nullable', 'integer', 'min:0'],
            'battery_pct_end'               => ['nullable', 'integer', 'between:0,100'],
            'post_flight_notes'             => ['nullable', 'string'],
            'location_description'          => ['nullable', 'string', 'max:255'],
            'flight_plan'                   => ['nullable', 'string'],
            'purpose'                       => ['sometimes', 'required', 'in:recreational,commercial'],
            'purpose_notes'                 => ['nullable', 'string'],
            'laanc_status'                  => ['sometimes', 'required', 'in:received,not_needed,na'],
            'laanc_authorization_number'    => ['nullable', 'string', 'max:50'],
            'accessories'                   => ['nullable', 'array'],
            'accessories.*'                 => ['integer', 'exists:accessories,id'],
            'checklist'                     => ['nullable', 'array'],
            'checklist.*.checklist_item_id' => ['required', 'integer', 'exists:checklist_items,id'],
            'checklist.*.checked'           => ['required', 'boolean'],
            'checklist.*.comment'           => ['nullable', 'string', 'max:1000'],
        ];
    }
}
