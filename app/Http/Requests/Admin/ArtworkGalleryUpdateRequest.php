<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ArtworkGalleryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'stock_code' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:artwork_categories,id'],
            'revision_note' => ['nullable', 'string', 'max:1000'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:artwork_tags,id'],
        ];
    }
}
