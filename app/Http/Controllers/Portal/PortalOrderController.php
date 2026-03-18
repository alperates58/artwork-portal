<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tedarikçi portalı — sadece kendi siparişlerini görebilir
 */
class PortalOrderController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request): View
    {
        $supplierId = auth()->user()->supplier_id;

        $orders = PurchaseOrder::query()
            ->where('supplier_id', $supplierId)
            ->with(['lines.artwork.activeRevision'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->orderByDesc('order_date')
            ->paginate(20)
            ->withQueryString();

        return view('portal.orders.index', compact('orders'));
    }

    public function show(PurchaseOrder $order): View
    {
        // Tedarikçi sadece kendi siparişini açabilir
        abort_unless(auth()->user()->canAccessOrder($order), 403);

        $order->load([
            'lines.artwork.activeRevision.uploadedBy',
        ]);

        $this->audit->log('portal.order.view', $order, [
            'order_no' => $order->order_no,
        ]);

        return view('portal.orders.show', compact('order'));
    }
}
