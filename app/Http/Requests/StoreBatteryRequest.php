<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatteryRequest extends FormRequest
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
            'name'          => ['required', 'string', 'max:50'],
            'capacity_mah'  => ['nullable', 'integer', 'min:1'],
            'purchase_date' => ['nullable', 'date'],
            'notes'         => ['nullable', 'string'],
        ];
    }
}
