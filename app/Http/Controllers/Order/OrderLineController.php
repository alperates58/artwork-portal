<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrderLine;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderLineController extends Controller
{
    public function __construct(
        private AuditLogService $audit,
        private DashboardCacheService $dashboardCache,
    ) {}

    public function show(PurchaseOrderLine $line): View
    {
        $line->load([
            'purchaseOrder.supplier',
            'manualArtworkCompletedBy:id,name',
            'artwork.activeRevision.uploadedBy',
            'artwork.revisions' => fn ($q) => $q->with('uploadedBy')->orderByDesc('revision_no'),
        ]);

        $this->audit->log('order_line.view', $line);

        return view('orders.line-show', compact('line'));
    }

    public function markManualArtwork(Request $request, PurchaseOrderLine $line): RedirectResponse
    {
        $this->authorize('manualArtwork', $line);

        $validated = $request->validate([
            'manual_artwork_note' => ['required', 'string', 'max:2000'],
        ], [
            'manual_artwork_note.required' => 'Manuel gönderim için açıklama notu zorunludur.',
        ]);

        DB::transaction(function () use ($line, $validated) {
            $line->update([
                'manual_artwork_completed_at' => now(),
                'manual_artwork_completed_by' => auth()->id(),
                'manual_artwork_note' => $validated['manual_artwork_note'],
            ]);

            $this->audit->log('order_line.manual_artwork.complete', $line, [
                'order_no' => $line->purchaseOrder->order_no,
                'line_no' => $line->line_no,
                'product_code' => $line->product_code,
                'note' => $validated['manual_artwork_note'],
            ]);

            $this->dashboardCache->forgetMetricsAfterCommit();
        });

        return back()->with('success', 'Sipariş satırı manuel gönderildi olarak işaretlendi.');
    }
}
