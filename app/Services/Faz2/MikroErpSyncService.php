<?php

namespace App\Services\Faz2;

use App\Services\Erp\MikroErpService;

class MikroErpSyncService
{
    public function __construct(private MikroErpService $erpService) {}

    public function syncOrders(): array
    {
        $stats = $this->erpService->syncOrders();

        return [
            'synced' => $stats['created'] + $stats['updated'],
            'failed' => $stats['errors'],
            'errors' => $stats['errors'] > 0 ? ['Supplier bazlı sync sırasında hata oluştu.'] : [],
            'status' => $stats['errors'] > 0 ? 'partial' : 'success',
        ];
    }
}
