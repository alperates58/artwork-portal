<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ArtworkGalleryUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'stock_code' => filled($this->input('stock_code'))
                ? mb_strtoupper(trim((string) $this->input('stock_code')))
                : null,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'stock_code' => ['nullable', 'string', 'max:100', 'exists:stock_cards,stock_code'],
            'revision_note' => ['nullable', 'string', 'max:1000'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:artwork_tags,id'],
        ];
    }
}
