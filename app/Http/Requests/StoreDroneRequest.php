<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDroneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:100'],
            'model'               => ['required', 'string', 'max:100'],
            'serial'              => ['required', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'notes'               => ['nullable', 'string'],
        ];
    }
}
