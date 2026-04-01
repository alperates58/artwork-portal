<?php

namespace App\Http\Requests\Admin;

use App\Services\ArtworkRevisionNumberService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ArtworkGalleryDirectUploadRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'stock_code' => mb_strtoupper(trim((string) $this->input('stock_code'))),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'artwork_file' => [
                'required',
                'file',
                'mimes:pdf,zip,ai,eps,svg,png,jpg,jpeg,tif,tiff,psd,indd',
                'max:1228800',
            ],
            'stock_code' => ['required', 'string', 'max:100', 'exists:stock_cards,stock_code'],
            'revision_no' => ['required', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $requestedRevision = (int) $this->input('revision_no');
            $nextRevisionNo = app(ArtworkRevisionNumberService::class)
                ->nextUploadRevisionNo(stockCode: $this->input('stock_code'));

            if ($requestedRevision < $nextRevisionNo) {
                $validator->errors()->add(
                    'revision_no',
                    'Bu stok kodu için kullanılabilir en düşük yeni revizyon Rev.'
                    . str_pad((string) $nextRevisionNo, 2, '0', STR_PAD_LEFT)
                    . ' olmalıdır.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'artwork_file.required' => 'Galeriye yüklemek için bir dosya seçin.',
            'artwork_file.file' => 'Geçersiz dosya.',
            'artwork_file.mimes' => 'İzin verilen formatlar: PDF, ZIP, AI, EPS, SVG, PNG, JPG, TIF, PSD, INDD.',
            'artwork_file.max' => 'Maksimum dosya boyutu 1.2 GB\'dir.',
            'stock_code.required' => 'Artwork yüklemek için stok kodu zorunludur.',
            'stock_code.exists' => 'Girilen stok kodu için tanımlı stok kartı bulunamadı.',
            'revision_no.required' => 'Revizyon numarası zorunludur.',
            'revision_no.integer' => 'Revizyon numarası sayısal olmalıdır.',
            'revision_no.min' => 'Revizyon numarası en az 1 olmalıdır.',
            'revision_no.max' => 'Revizyon numarası en fazla 99 olabilir.',
        ];
    }
}
