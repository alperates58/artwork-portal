<?php

namespace App\Http\Controllers\Faz2;

use App\Http\Controllers\Controller;
use App\Models\ArtworkApproval;
use App\Models\ArtworkRevision;
use App\Services\Faz2\ArtworkApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ApprovalController extends Controller
{
    public function __construct(
        private ArtworkApprovalService $approvalService
    ) {}

    public function confirmSeen(ArtworkRevision $revision): RedirectResponse
    {
        abort_unless(auth()->user()->isSupplier(), 403);
        abort_unless(
            auth()->user()->canAccessOrder($revision->artwork->orderLine->purchaseOrder),
            403
        );

        $this->approvalService->markViewed($revision, auth()->user());

        return back()->with('success', 'Dosyayi gordugunuz kaydedildi.');
    }

    public function approve(ArtworkRevision $revision): RedirectResponse
    {
        abort_unless(auth()->user()->isSupplier(), 403);
        abort_unless(
            auth()->user()->canAccessOrder($revision->artwork->orderLine->purchaseOrder),
            403
        );

        $this->approvalService->approve($revision, auth()->user());

        return back()->with('success', 'Artwork onaylandi. Tesekkurler.');
    }

    public function status(ArtworkRevision $revision): JsonResponse
    {
        $this->authorize('view', $revision->artwork);

        return response()->json([
            'revision_id' => $revision->id,
            'is_active' => $revision->is_active,
            'approved_at' => $revision->approved_at,
            'approved_by' => $revision->approvedBy?->name,
            'seen_count' => ArtworkApproval::where('artwork_revision_id', $revision->id)
                ->where('status', 'viewed')
                ->count(),
            'approve_count' => ArtworkApproval::where('artwork_revision_id', $revision->id)
                ->where('status', 'approved')
                ->count(),
        ]);
    }
}
