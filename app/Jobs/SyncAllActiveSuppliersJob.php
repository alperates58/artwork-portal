<?php

namespace App\Jobs;

use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAllActiveSuppliersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;

    public function handle(): void
    {
        $supplierCount = 0;
        $sampleSupplierIds = [];

        Supplier::query()
            ->where('is_active', true)
            ->whereHas('mikroAccounts', fn ($query) => $query->active())
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($suppliers) use (&$supplierCount, &$sampleSupplierIds): void {
                foreach ($suppliers as $supplier) {
                    $supplierCount++;
                    if (count($sampleSupplierIds) < 10) {
                        $sampleSupplierIds[] = $supplier->id;
                    }

                    SyncSupplierOrdersJob::dispatch((int) $supplier->id);
                }
            });

        Log::info('Dispatched supplier Mikro sync jobs', [
            'supplier_count' => $supplierCount,
            'sample_supplier_ids' => $sampleSupplierIds,
        ]);
    }
}
