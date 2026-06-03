<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChecklistItemRequest extends FormRequest
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
            'label'           => ['required', 'string', 'max:255'],
            'sort_order'      => ['nullable', 'integer', 'min:0'],
            'has_comment_box' => ['boolean'],
        ];
    }
}
