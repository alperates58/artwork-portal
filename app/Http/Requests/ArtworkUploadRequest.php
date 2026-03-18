<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArtworkUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Yetki kontrolü controller'da Policy ile yapılıyor
    }

    public function rules(): array
    {
        return [
            'artwork_file' => [
                'required',
                'file',
                // İzin verilen formatlar
                'mimes:pdf,zip,ai,eps,svg,png,jpg,jpeg,tif,tiff,psd,indd',
                // Max 1.2 GB (byte cinsinden)
                'max:1228800',
            ],
            'title' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'artwork_file.required' => 'Lütfen bir dosya seçin.',
            'artwork_file.file'     => 'Geçersiz dosya.',
            'artwork_file.mimes'    => 'İzin verilen formatlar: PDF, ZIP, AI, EPS, SVG, PNG, JPG, TIF, PSD, INDD.',
            'artwork_file.max'      => 'Maksimum dosya boyutu 1.2 GB\'dir.',
        ];
    }
}
