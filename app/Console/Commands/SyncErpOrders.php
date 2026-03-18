<?php

namespace App\Console\Commands;

use App\Services\Erp\MikroErpService;
use Illuminate\Console\Command;

class SyncErpOrders extends Command
{
    protected $signature   = 'erp:sync {--dry-run : Değişiklik yapmadan sadece göster}';
    protected $description = 'Mikro ERP\'den sipariş verilerini portala aktarır';

    public function handle(MikroErpService $erp): int
    {
        $this->info('Mikro ERP senkronizasyonu başlıyor...');

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN modu — veritabanına yazılmayacak.');
        }

        try {
            $stats = $erp->syncOrders();

            $this->table(
                ['İşlem', 'Sayı'],
                [
                    ['Oluşturulan sipariş', $stats['created']],
                    ['Güncellenen sipariş', $stats['updated']],
                    ['Atlanan (değişmemiş)', $stats['skipped']],
                    ['Hata', $stats['errors']],
                ]
            );

            if ($stats['errors'] > 0) {
                $this->warn("⚠ {$stats['errors']} sipariş aktarılamadı. Logları kontrol edin.");
                return self::FAILURE;
            }

            $this->info('✓ ERP senkronizasyonu tamamlandı.');
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("ERP bağlantı hatası: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
