<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Faz2\SyncErpOrdersJob;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;

class ErpSyncController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function sync(): RedirectResponse
    {
        // Queue'ya at — synchronous değil, arka planda çalışır
        SyncErpOrdersJob::dispatch();

        $this->audit->log('erp.sync', null, ['triggered_by' => 'manual']);

        return back()->with('success', 'ERP senkronizasyonu kuyruğa alındı. Birkaç dakika içinde tamamlanır.');
    }
}
