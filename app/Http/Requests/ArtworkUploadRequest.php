<?php

namespace App\Http\Requests;

use App\Models\ArtworkGallery;
use App\Services\ArtworkRevisionNumberService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ArtworkUploadRequest extends FormRequest
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
            'source_type' => ['required', 'in:upload,gallery'],
            'artwork_file' => [
                'required_if:source_type,upload',
                'nullable',
                'file',
                'mimes:pdf,zip,ai,eps,svg,png,jpg,jpeg,tif,tiff,psd,indd',
                'max:1228800',
            ],
            'gallery_item_id' => ['required_if:source_type,gallery', 'nullable', 'integer', 'exists:artwork_gallery,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'stock_code' => ['required', 'string', 'max:100', 'exists:stock_cards,stock_code'],
            'stock_name' => ['nullable', 'string', 'max:200'],
            'category_name' => ['nullable', 'string', 'max:120'],
            'revision_no' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $line = $this->route('line');

            if (! $line) {
                return;
            }

            $requestedRevision = (int) $this->input('revision_no');
            $selectedGalleryItem = null;

            if ($this->input('source_type') === 'gallery' && $this->filled('gallery_item_id')) {
                $selectedGalleryItem = ArtworkGallery::query()->find($this->integer('gallery_item_id'));
            }

            if ($this->input('source_type') === 'gallery' && $selectedGalleryItem) {
                if (! $selectedGalleryItem->is_active) {
                    $validator->errors()->add(
                        'gallery_item_id',
                        'Pasif galeri kaydı siparişte tekrar kullanılamaz.'
                    );
                }

                if ($this->input('stock_code') !== $selectedGalleryItem->stock_code) {
                    $validator->errors()->add(
                        'stock_code',
                        'Galeriden seçilen kayıt ile aynı stok kodu kullanılmalıdır.'
                    );
                }

                if ((int) $selectedGalleryItem->revision_no !== $requestedRevision) {
                    $validator->errors()->add(
                        'revision_no',
                        'Galeriden seçilen kayıt Rev.' . str_pad((string) $selectedGalleryItem->revision_no, 2, '0', STR_PAD_LEFT) . ' olarak kullanılmalıdır.'
                    );
                }

                return;
            }

            $nextRevisionNo = app(ArtworkRevisionNumberService::class)
                ->nextUploadRevisionNo($line, $this->input('stock_code'));

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
            'source_type.required' => 'Lütfen bir kaynak tipi seçin.',
            'artwork_file.required_if' => 'Yeni dosya yükleme için bir dosya seçin.',
            'artwork_file.file' => 'Geçersiz dosya.',
            'artwork_file.mimes' => 'İzin verilen formatlar: PDF, ZIP, AI, EPS, SVG, PNG, JPG, TIF, PSD, INDD.',
            'artwork_file.max' => 'Maksimum dosya boyutu 1.2 GB\'dir.',
            'gallery_item_id.required_if' => 'Galeriden seçim için bir artwork seçin.',
            'stock_code.required' => 'Artwork yüklemek için stok kodu zorunludur.',
            'stock_code.exists' => 'Girilen stok kodu için tanımlı stok kartı bulunamadı.',
            'revision_no.required' => 'Revizyon numarası zorunludur.',
            'revision_no.integer' => 'Revizyon numarası sayısal olmalıdır.',
            'revision_no.min' => 'Revizyon numarası en az 0 olmalıdır.',
        ];
    }
}
