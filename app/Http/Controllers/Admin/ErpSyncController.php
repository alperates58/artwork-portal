<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncAllActiveSuppliersJob;
use App\Jobs\SyncStockCardsJob;
use App\Jobs\SyncSupplierOrdersJob;
use App\Models\Supplier;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;

class ErpSyncController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function sync(): RedirectResponse
    {
        SyncAllActiveSuppliersJob::dispatch();

        $this->audit->log('erp.sync', null, [
            'triggered_by' => 'manual',
            'mode' => 'all_active_suppliers',
        ]);

        return back()->with('success', 'Mikro sipariş senkronizasyonu tüm aktif tedarikçiler için kuyruğa alındı.');
    }

    public function syncStockCards(): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        SyncStockCardsJob::dispatch();

        $this->audit->log('erp.sync', null, [
            'triggered_by' => 'manual',
            'mode'         => 'stock_cards',
        ]);

        return back()->with('success', 'Stok kartı senkronizasyonu kuyruğa alındı.');
    }

    public function syncSupplier(Supplier $supplier): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        SyncSupplierOrdersJob::dispatch($supplier->id);

        $this->audit->log('erp.sync', $supplier, [
            'triggered_by' => 'manual',
            'mode' => 'single_supplier',
            'supplier_id' => $supplier->id,
        ]);

        return back()->with('success', 'Tedarikçi için Mikro sipariş senkronizasyonu kuyruğa alındı.');
    }
}
