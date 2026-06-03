<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBatteryRequest extends FormRequest
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
            'name'          => ['sometimes', 'required', 'string', 'max:50'],
            'capacity_mah'  => ['nullable', 'integer', 'min:1'],
            'purchase_date' => ['nullable', 'date'],
            'cycle_count'   => ['nullable', 'integer', 'min:0'],
            'notes'         => ['nullable', 'string'],
        ];
    }
}
