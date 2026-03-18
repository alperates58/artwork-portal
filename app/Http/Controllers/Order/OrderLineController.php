<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrderLine;
use App\Services\AuditLogService;
use Illuminate\View\View;

class OrderLineController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function show(PurchaseOrderLine $line): View
    {
        $line->load([
            'purchaseOrder.supplier',
            'artwork.activeRevision.uploadedBy',
            'artwork.revisions' => fn ($q) => $q->with('uploadedBy')->orderByDesc('revision_no'),
        ]);

        $this->audit->log('order_line.view', $line);

        return view('orders.line-show', compact('line'));
    }
}
