<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChecklistTemplateRequest extends FormRequest
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
            'name'                    => ['required', 'string', 'max:100'],
            'is_default'              => ['boolean'],
            'items'                   => ['nullable', 'array'],
            'items.*.label'           => ['required', 'string', 'max:255'],
            'items.*.sort_order'      => ['nullable', 'integer', 'min:0'],
            'items.*.has_comment_box' => ['boolean'],
        ];
    }
}
