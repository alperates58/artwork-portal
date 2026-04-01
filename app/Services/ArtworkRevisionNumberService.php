<?php

namespace App\Services;

use App\Models\ArtworkGallery;
use App\Models\PurchaseOrderLine;

class ArtworkRevisionNumberService
{
    public function nextUploadRevisionNo(?PurchaseOrderLine $line = null, ?string $stockCode = null): int
    {
        $lineMaxRevision = (int) ($line?->artwork?->revisions()->max('revision_no') ?? 0);
        $galleryMaxRevision = $this->maxGalleryRevisionNo($stockCode);

        return max($lineMaxRevision, $galleryMaxRevision) + 1;
    }

    public function maxGalleryRevisionNo(?string $stockCode): int
    {
        $normalizedStockCode = $this->normalizeStockCode($stockCode);

        if (! $normalizedStockCode) {
            return 0;
        }

        return (int) (ArtworkGallery::query()
            ->where('stock_code', $normalizedStockCode)
            ->max('revision_no') ?? 0);
    }

    public function normalizeStockCode(?string $stockCode): ?string
    {
        $normalizedStockCode = mb_strtoupper(trim((string) $stockCode));

        return $normalizedStockCode !== '' ? $normalizedStockCode : null;
    }
}
