<?php

namespace App\Http\Controllers\Faz2;

use App\Http\Controllers\Controller;
use App\Models\ArtworkRevision;
use App\Models\ViewLog;
use App\Services\Faz2\ArtworkApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ApprovalController extends Controller
{
    public function __construct(
        private ArtworkApprovalService $approvalService
    ) {}

    /**
     * Tedarikçi "Gördüm" aksiyonu
     */
    public function confirmSeen(ArtworkRevision $revision): RedirectResponse
    {
        abort_unless(auth()->user()->isSupplier(), 403);
        abort_unless(auth()->user()->canAccessOrder(
            $revision->artwork->orderLine->purchaseOrder
        ), 403);

        ViewLog::create([
            'user_id'             => auth()->id(),
            'artwork_revision_id' => $revision->id,
            'action'              => 'confirm_seen',
            'ip_address'          => request()->ip(),
            'viewed_at'           => now(),
        ]);

        return back()->with('success', 'Dosyayı gördüğünüz kaydedildi.');
    }

    /**
     * Tedarikçi "Onayladım" aksiyonu
     */
    public function approve(ArtworkRevision $revision): RedirectResponse
    {
        abort_unless(auth()->user()->isSupplier(), 403);
        abort_unless(auth()->user()->canAccessOrder(
            $revision->artwork->orderLine->purchaseOrder
        ), 403);

        $this->approvalService->supplierApprove($revision, auth()->user());

        return back()->with('success', 'Artwork onaylandı. Teşekkürler.');
    }

    /**
     * Onay durumunu JSON olarak döner (dashboard widget için)
     */
    public function status(ArtworkRevision $revision): JsonResponse
    {
        $this->authorize('view', $revision->artwork);

        return response()->json([
            'revision_id'   => $revision->id,
            'is_active'     => $revision->is_active,
            'approved_at'   => $revision->approved_at,
            'approved_by'   => $revision->approvedBy?->name,
            'seen_count'    => ViewLog::where('artwork_revision_id', $revision->id)
                                      ->where('action', 'confirm_seen')->count(),
            'approve_count' => ViewLog::where('artwork_revision_id', $revision->id)
                                      ->where('action', 'approve')->count(),
        ]);
    }
}
