<?php

namespace App\Jobs;

use App\Services\Erp\MikroStockCardSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncStockCardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;

    public function handle(MikroStockCardSyncService $service): void
    {
        $stats = $service->sync();

        Log::info('SyncStockCardsJob finished', $stats);
    }

    public function tags(): array
    {
        return ['erp-sync', 'stock-cards'];
    }
}
