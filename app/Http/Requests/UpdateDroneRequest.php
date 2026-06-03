<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDroneRequest extends FormRequest
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
            'name'                => ['sometimes', 'required', 'string', 'max:100'],
            'model'               => ['sometimes', 'required', 'string', 'max:100'],
            'serial'              => ['sometimes', 'required', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'notes'               => ['nullable', 'string'],
        ];
    }
}
