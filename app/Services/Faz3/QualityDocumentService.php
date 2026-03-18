<?php

namespace App\Services\Faz3;

use App\Models\PurchaseOrderLine;
use App\Models\QualityDocument;
use App\Models\User;
use App\Services\SpacesStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class QualityDocumentService
{
    public function __construct(
        private SpacesStorageService $spaces
    ) {}

    public function upload(
        PurchaseOrderLine $line,
        UploadedFile $file,
        array $meta,
        User $uploader
    ): QualityDocument {
        return DB::transaction(function () use ($line, $file, $meta, $uploader) {

            $nextVersion = QualityDocument::where('order_line_id', $line->id)
                ->where('document_type', $meta['document_type'])
                ->max('version') + 1;

            $path = sprintf(
                'quality/%d/orders/%s/lines/%d/%s/v%d/%s.%s',
                $line->purchaseOrder->supplier_id,
                $line->purchaseOrder->order_no,
                $line->id,
                $meta['document_type'],
                $nextVersion,
                \Str::uuid(),
                $file->getClientOriginalExtension()
            );

            $fileData = $this->spaces->upload($file, $path);

            return QualityDocument::create([
                'order_line_id'     => $line->id,
                'document_type'     => $meta['document_type'],
                'title'             => $meta['title'] ?? $file->getClientOriginalName(),
                'original_filename' => $fileData['original_filename'],
                'spaces_path'       => $fileData['spaces_path'],
                'mime_type'         => $fileData['mime_type'],
                'file_size'         => $fileData['file_size'],
                'version'           => $nextVersion,
                'status'            => 'pending_approval',
                'uploaded_by'       => $uploader->id,
                'notes'             => $meta['notes'] ?? null,
            ]);
        });
    }

    public function approve(QualityDocument $doc, User $reviewer, ?string $notes = null): void
    {
        $doc->update([
            'status'      => 'approved',
            'approved_by' => $reviewer->id,
            'approved_at' => now(),
            'notes'       => $notes,
        ]);
    }

    public function reject(QualityDocument $doc, User $reviewer, string $reason): void
    {
        $doc->update([
            'status'           => 'rejected',
            'approved_by'      => $reviewer->id,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
