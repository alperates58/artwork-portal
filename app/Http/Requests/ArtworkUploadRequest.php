<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArtworkUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_type' => ['required', 'in:upload,gallery'],
            'artwork_file' => [
                'required_if:source_type,upload',
                'nullable',
                'file',
                'mimes:pdf,zip,ai,eps,svg,png,jpg,jpeg,tif,tiff,psd,indd',
                'max:1228800',
            ],
            'gallery_item_id' => ['required_if:source_type,gallery', 'nullable', 'integer', 'exists:artwork_gallery,id'],
            'title' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'gallery_name' => ['nullable', 'string', 'max:200'],
            'stock_code' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:artwork_categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:artwork_tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_type.required' => 'Lütfen bir kaynak tipi seçin.',
            'artwork_file.required_if' => 'Yeni dosya yükleme için bir dosya seçin.',
            'artwork_file.file' => 'Geçersiz dosya.',
            'artwork_file.mimes' => 'İzin verilen formatlar: PDF, ZIP, AI, EPS, SVG, PNG, JPG, TIF, PSD, INDD.',
            'artwork_file.max' => 'Maksimum dosya boyutu 1.2 GB\'dir.',
            'gallery_item_id.required_if' => 'Galeriden seçim için bir artwork seçin.',
        ];
    }
}
