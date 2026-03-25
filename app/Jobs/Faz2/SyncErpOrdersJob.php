<?php

namespace App\Jobs\Faz2;

use App\Services\Faz2\MikroErpSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Legacy uyumluluk icin elde tutulan ERP sync job'u.
 * Artik zamanlanmis ana yol supplier bazli SyncAllActiveSuppliersJob'dur.
 */
class SyncErpOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 3;

    public function handle(MikroErpSyncService $service): void
    {
        Log::info('ERP sync started');

        $result = $service->syncOrders();

        Log::info('ERP sync completed', $result);

        if ($result['status'] === 'failed') {
            $this->fail(new \RuntimeException('ERP sync failed: ' . implode(', ', $result['errors'])));
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncErpOrdersJob permanently failed', ['error' => $e->getMessage()]);
    }
}
