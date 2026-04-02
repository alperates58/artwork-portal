<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\ArtworkRevision;
use App\Models\AuditLog;
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
            'artwork.activeRevision.galleryItem',
            'artwork.activeRevision.latestRejectedApproval.user:id,name',
            'artwork.activeRevision.latestRejectedApproval.supplier:id,name',
            'artwork.revisions' => fn ($q) => $q->with(['uploadedBy', 'galleryItem', 'rejectedApprovals.user:id,name', 'rejectedApprovals.supplier:id,name'])->orderByDesc('revision_no'),
            'lineNotes.user:id,name',
            'lineNotes.replies.user:id,name',
        ]);

        $this->audit->log('order_line.view', $line);

        // ─── Timeline ────────────────────────────────────────────────────
        $timeline = collect();

        foreach ($line->artwork?->revisions ?? [] as $revision) {
            $timeline->push([
                'at'    => $revision->created_at,
                'icon'  => 'upload',
                'color' => 'blue',
                'title' => "Revizyon #{$revision->revision_no} yüklendi",
                'sub'   => $revision->uploadedBy?->name ?? '—',
            ]);
        }

        foreach ($line->lineNotes as $note) {
            $timeline->push([
                'at'    => $note->created_at,
                'icon'  => 'note',
                'color' => 'amber',
                'title' => 'Satır açıklaması eklendi',
                'sub'   => $note->user?->name ?? '—',
                'body'  => mb_strimwidth($note->body, 0, 120, '…'),
            ]);

            foreach ($note->replies as $reply) {
                $timeline->push([
                    'at'    => $reply->created_at,
                    'icon'  => 'reply',
                    'color' => 'amber',
                    'title' => 'Açıklama yanıtlandı',
                    'sub'   => $reply->user?->name ?? '—',
                    'body'  => mb_strimwidth($reply->body, 0, 120, '…'),
                ]);
            }
        }

        // Manual artwork completion logs
        $manualArtworkLogs = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'model_type', 'model_id', 'payload', 'created_at'])
            ->with('user:id,name')
            ->where('model_type', PurchaseOrderLine::class)
            ->where('model_id', $line->id)
            ->where('action', 'order_line.manual_artwork.complete')
            ->orderByDesc('created_at')
            ->get();

        foreach ($manualArtworkLogs as $log) {
            $payload = $log->payload ?? [];
            $timeline->push([
                'at'    => $log->created_at,
                'icon'  => 'mail',
                'color' => 'emerald',
                'title' => 'Manuel gönderildi olarak işaretlendi',
                'sub'   => $log->user?->name ?? '—',
                'body'  => $payload['note'] ?? null,
            ]);
        }

        $revisionCompletionLogs = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'model_type', 'model_id', 'payload', 'created_at'])
            ->with('user:id,name')
            ->where('model_type', PurchaseOrderLine::class)
            ->where('model_id', $line->id)
            ->where('action', 'order_line.revision.complete')
            ->orderByDesc('created_at')
            ->get();

        foreach ($revisionCompletionLogs as $log) {
            $payload = $log->payload ?? [];
            $timeline->push([
                'at'    => $log->created_at,
                'icon'  => 'check',
                'color' => 'emerald',
                'title' => 'Revizyon tamamlandı olarak işaretlendi',
                'sub'   => $log->user?->name ?? '—',
                'body'  => $payload['summary'] ?? null,
            ]);
        }

        // Rejection logs
        $revisionIds = collect($line->artwork?->revisions ?? [])->pluck('id');
        if ($revisionIds->isNotEmpty()) {
            $rejectionLogs = AuditLog::query()
                ->select(['id', 'user_id', 'action', 'model_type', 'model_id', 'payload', 'created_at'])
                ->with('user:id,name')
                ->where('model_type', ArtworkRevision::class)
                ->whereIn('model_id', $revisionIds)
                ->where('action', 'artwork.rejected')
                ->orderByDesc('created_at')
                ->get();

            foreach ($rejectionLogs as $log) {
                $payload = $log->payload ?? [];
                $timeline->push([
                    'at'    => $log->created_at,
                    'icon'  => 'x',
                    'color' => 'red',
                    'title' => 'Revizyon talebi oluşturuldu',
                    'sub'   => $payload['supplier_name'] ?? $log->user?->name ?? '—',
                    'body'  => $payload['notes'] ?? null,
                ]);
            }
        }

        $sorted = $timeline->sortByDesc('at')->values();
        $timeline = $sorted->map(function ($event, $idx) use ($sorted) {
            $next = $sorted->get($idx + 1);
            $event['days_gap'] = $next
                ? round(abs($event['at']->diffInMinutes($next['at'])) / 1440, 1)
                : null;
            return $event;
        });

        return view('orders.line-show', compact('line', 'timeline'));
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

    public function markRevisionComplete(PurchaseOrderLine $line): RedirectResponse
    {
        $this->authorize('completeRevision', $line);

        $line->loadMissing([
            'purchaseOrder:id,order_no,supplier_id',
            'artwork.activeRevision:id,artwork_id,revision_no',
        ]);

        abort_if(! $line->hasActiveArtwork(), 422, 'Aktif artwork olmayan satır revizyon tamamlandı olarak işaretlenemez.');
        abort_if(! $line->requiresRevision(), 422, 'Bu satır için açık bir revizyon talebi bulunmuyor.');

        DB::transaction(function () use ($line) {
            $line->update([
                'artwork_status' => 'uploaded',
            ]);

            $this->audit->log('order_line.revision.complete', $line, [
                'order_no' => $line->purchaseOrder->order_no,
                'line_no' => $line->line_no,
                'product_code' => $line->product_code,
                'revision_no' => $line->activeRevision?->revision_no,
                'summary' => 'Aktif revizyon tekrar incelenmeye hazır olarak işaretlendi.',
            ]);

            $this->dashboardCache->forgetMetricsAfterCommit();
        });

        return back()->with('success', 'Revizyon talebi tamamlandı olarak işaretlendi.');
    }
}
