<?php

namespace App\Console\Commands;

use App\Services\Erp\MikroStockCardSyncService;
use Illuminate\Console\Command;

class SyncErpStockCards extends Command
{
    protected $signature   = 'erp:sync-stock-cards';
    protected $description = 'Mikro ERP\'den stok kartlarını portala aktarır (her zaman günceller)';

    public function handle(MikroStockCardSyncService $service): int
    {
        $this->info('Stok kartı senkronizasyonu başlıyor...');

        try {
            $stats = $service->sync();

            $this->table(
                ['İşlem', 'Sayı'],
                [
                    ['Oluşturulan', $stats['created']],
                    ['Güncellenen', $stats['updated']],
                    ['Hata',        $stats['failed']],
                ]
            );

            if ($stats['errors']) {
                foreach (array_slice($stats['errors'], 0, 5) as $error) {
                    $this->warn($error);
                }
            }

            if ($stats['failed'] > 0) {
                $this->warn("⚠ {$stats['failed']} satır aktarılamadı.");
                return self::FAILURE;
            }

            $this->info('✓ Stok kartı senkronizasyonu tamamlandı.');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Hata: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
