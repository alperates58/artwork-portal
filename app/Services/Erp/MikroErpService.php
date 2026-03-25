<?php

namespace App\Services\Erp;

use App\Models\Supplier;
use App\Services\Mikro\MikroClient;
use Illuminate\Support\Facades\Log;

class MikroErpService
{
    public function __construct(
        private MikroClient $mikro,
        private MikroOrderService $mikroOrderService
    ) {}

    public function syncOrders(): array
    {
        if (! $this->mikro->isEnabled()) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        }

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        Supplier::query()
            ->where('is_active', true)
            ->whereHas('mikroAccounts', fn ($query) => $query->active())
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($suppliers) use (&$stats): void {
                foreach ($suppliers as $supplier) {
                    $result = $this->mikroOrderService->syncSupplier((int) $supplier->id);

                    $stats['created'] += $result['orders_created'];
                    $stats['updated'] += $result['orders_updated'];
                    $stats['errors'] += $result['failed'] + $result['conflicts'];
                }
            });

        Log::info('ERP sync tamamlandi', $stats);

        return $stats;
    }
}
