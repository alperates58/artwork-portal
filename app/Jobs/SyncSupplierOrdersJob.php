<?php

namespace App\Jobs;

use App\Enums\ErpSyncStatus;
use App\Models\Supplier;
use App\Services\Erp\MikroOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSupplierOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries = 3;

    public function __construct(public readonly int $supplierId) {}

    public function handle(MikroOrderService $service): void
    {
        $result = $service->syncSupplier($this->supplierId);

        Log::info('Supplier Mikro sync completed', $result);
    }

    public function tags(): array
    {
        return ['mikro-sync', 'supplier:' . $this->supplierId];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncSupplierOrdersJob permanently failed', [
            'supplier_id' => $this->supplierId,
            'error' => $exception->getMessage(),
        ]);

        Supplier::query()
            ->find($this->supplierId)
            ?->mikroAccounts()
            ->active()
            ->update([
                'last_sync_at' => now(),
                'last_sync_status' => ErpSyncStatus::FAILED->value,
                'last_sync_error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
    }
}
