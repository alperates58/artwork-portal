<?php

namespace App\Services;

use App\Models\ArtworkGallery;
use App\Models\PurchaseOrderLine;

class ArtworkRevisionNumberService
{
    public function nextUploadRevisionNo(?PurchaseOrderLine $line = null, ?string $stockCode = null): int
    {
        $lineMax = $line?->artwork?->revisions()->max('revision_no');
        $lineMaxRevision = $lineMax !== null ? (int) $lineMax : -1;

        $galleryMaxRevision = $this->maxGalleryRevisionNo($stockCode);

        return max($lineMaxRevision, $galleryMaxRevision) + 1;
    }

    public function maxGalleryRevisionNo(?string $stockCode): int
    {
        $normalizedStockCode = $this->normalizeStockCode($stockCode);

        if (! $normalizedStockCode) {
            return -1;
        }

        $max = ArtworkGallery::query()
            ->where('stock_code', $normalizedStockCode)
            ->max('revision_no');

        return $max !== null ? (int) $max : -1;
    }

    public function normalizeStockCode(?string $stockCode): ?string
    {
        $normalizedStockCode = mb_strtoupper(trim((string) $stockCode));

        return $normalizedStockCode !== '' ? $normalizedStockCode : null;
    }
}
