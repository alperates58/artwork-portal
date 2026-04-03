<?php

namespace App\Services\Erp;

use App\Models\MikroViewMapping;
use App\Models\StockCard;
use App\Services\ArtworkCategoryService;
use App\Services\Mikro\MikroClient;
use App\Services\Mikro\MikroException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MikroStockCardSyncService
{
    public function __construct(
        private MikroClient $mikro,
        private ArtworkCategoryService $categories,
    ) {}

    /**
     * Mikro'daki stok kartı view'inden tüm satırları çekerek portala aktarır.
     * Mevcut stok kodu varsa adını ve kategorisini her zaman günceller.
     */
    public function sync(): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'failed'  => 0,
            'errors'  => [],
        ];

        $mapping = $this->activeMapping();

        if ($mapping === null) {
            $stats['errors'][] = 'Stok kartı için aktif Mikro view mapping bulunamadı.';

            return $stats;
        }

        $rows = $this->fetchRows($mapping);

        foreach ($rows as $index => $row) {
            try {
                $result = DB::transaction(fn () => $this->upsertRow($row, $mapping));
                $result ? $stats['created']++ : $stats['updated']++;
            } catch (\Throwable $e) {
                $stats['failed']++;
                $stats['errors'][] = sprintf('Satır %d: %s', $index + 1, $e->getMessage());
            }
        }

        Log::info('Mikro stock card sync completed', [
            'mapping_id'   => $mapping->id,
            'total_rows'   => count($rows),
            'created'      => $stats['created'],
            'updated'      => $stats['updated'],
            'failed'       => $stats['failed'],
        ]);

        return $stats;
    }

    private function fetchRows(MikroViewMapping $mapping): array
    {
        if (! $this->mikro->isEnabled()) {
            return [];
        }

        try {
            $response = $this->mikro->get($mapping->endpoint_path);
        } catch (MikroException $e) {
            throw new \RuntimeException('Mikro bağlantı hatası: ' . $e->getMessage(), previous: $e);
        }

        return Arr::wrap($response->json('data', []));
    }

    /**
     * Tek bir view satırını portala yazar.
     * Dönen değer: true = yeni oluşturuldu, false = güncellendi.
     */
    private function upsertRow(array $row, MikroViewMapping $mapping): bool
    {
        $fieldMap   = $mapping->mapping_payload ?? [];
        $stockCode  = $this->resolveField($row, $fieldMap['stock_code'] ?? null);
        $stockName  = $this->resolveField($row, $fieldMap['stock_name'] ?? null);
        $categoryName = $this->resolveField($row, $fieldMap['category'] ?? null);

        if (blank($stockCode)) {
            throw new \InvalidArgumentException('Stok kodu boş.');
        }

        if (blank($stockName)) {
            throw new \InvalidArgumentException("Stok adı boş (kod: {$stockCode}).");
        }

        if (blank($categoryName)) {
            throw new \InvalidArgumentException("Kategori boş (kod: {$stockCode}).");
        }

        $stockCode = mb_strtoupper(trim($stockCode));
        $category  = $this->categories->findOrCreate($categoryName);

        $existing = StockCard::query()->where('stock_code', $stockCode)->first();

        if ($existing) {
            $existing->update([
                'stock_name'  => $stockName,
                'category_id' => $category->id,
            ]);

            // Galeri öğelerinin kategori bilgisini de güncelle
            $this->syncGalleryCategory($stockCode, $category->id);

            return false;
        }

        $card = StockCard::create([
            'stock_code'  => $stockCode,
            'stock_name'  => $stockName,
            'category_id' => $category->id,
        ]);

        // Mevcut galeri öğelerini yeni karta bağla
        $this->syncGalleryCategory($stockCode, $category->id, $card->id);

        return true;
    }

    private function syncGalleryCategory(string $stockCode, int $categoryId, ?int $stockCardId = null): void
    {
        $update = ['category_id' => $categoryId, 'updated_at' => now()];

        if ($stockCardId !== null) {
            $update['stock_card_id'] = $stockCardId;
        }

        DB::table('artwork_gallery')
            ->where('stock_code', $stockCode)
            ->update($update);
    }

    private function activeMapping(): ?MikroViewMapping
    {
        return MikroViewMapping::query()
            ->where('entity_type', 'stock_cards')
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();
    }

    private function resolveField(array $row, ?string $sourceColumn): ?string
    {
        if (blank($sourceColumn)) {
            return null;
        }

        $value = Arr::get($row, $sourceColumn);

        return is_scalar($value) ? trim((string) $value) : null;
    }
}
