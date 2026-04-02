<?php

namespace App\Services\Faz2;

use App\Models\ArtworkApproval;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Notifications\ArtworkApprovedNotification;
use App\Notifications\ArtworkRejectedNotification;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ArtworkApprovalService
{
    public function __construct(
        private AuditLogService $audit,
        private DashboardCacheService $dashboardCache,
        private NotificationService $notifications,
    ) {}

    /**
     * TedarikÃ§i "GÃ¶rdÃ¼m" aksiyonu
     */
    public function markViewed(ArtworkRevision $revision, User $user): ArtworkApproval
    {
        $approval = ArtworkApproval::updateOrCreate(
            [
                'artwork_revision_id' => $revision->id,
                'user_id'             => $user->id,
            ],
            [
                'supplier_id' => $user->supplier_id,
                'status'      => 'viewed',
                'actioned_at' => now(),
            ]
        );

        $this->audit->log('artwork.viewed', $revision, [
            'supplier_id' => $user->supplier_id,
        ]);

        return $approval;
    }

    /**
     * TedarikÃ§i "OnayladÄ±m" aksiyonu
     */
    public function approve(ArtworkRevision $revision, User $user, ?string $notes = null): ArtworkApproval
    {
        $approval = DB::transaction(function () use ($revision, $user, $notes) {
            $approval = ArtworkApproval::updateOrCreate(
                [
                    'artwork_revision_id' => $revision->id,
                    'user_id'             => $user->id,
                ],
                [
                    'supplier_id' => $user->supplier_id,
                    'status'      => 'approved',
                    'notes'       => $notes,
                    'actioned_at' => now(),
                ]
            );

            $revision->artwork->orderLine->update(['artwork_status' => 'approved']);

            $this->audit->log('artwork.approved', $revision, [
                'supplier_id' => $user->supplier_id,
                'notes'       => $notes,
            ]);

            $this->dashboardCache->forgetMetricsAfterCommit();

            return $approval;
        });

        $this->notifyInternalUsers($revision, 'approved');

        return $approval;
    }

    /**
     * Tedarikçi "Revizyon Talebi" aksiyonu
     */
    public function reject(ArtworkRevision $revision, User $user, string $notes): ArtworkApproval
    {
        $approval = DB::transaction(function () use ($revision, $user, $notes) {
            $approval = ArtworkApproval::updateOrCreate(
                [
                    'artwork_revision_id' => $revision->id,
                    'user_id'             => $user->id,
                ],
                [
                    'supplier_id' => $user->supplier_id,
                    'status'      => 'rejected',
                    'notes'       => $notes,
                    'actioned_at' => now(),
                ]
            );

            $revision->artwork->orderLine->update(['artwork_status' => 'revision']);

            $this->audit->log('artwork.rejected', $revision, [
                'supplier_id'  => $user->supplier_id,
                'supplier_name' => $user->supplier?->name ?? $user->name,
                'notes'        => $notes,
                'line_id'      => $revision->artwork->orderLine->id,
                'product_code' => $revision->artwork->orderLine->product_code,
            ]);

            $this->dashboardCache->forgetMetricsAfterCommit();

            return $approval;
        });

        $order = $revision->artwork->orderLine->purchaseOrder;
        $line  = $revision->artwork->orderLine;

        $this->notifications->notifyDepartment(
            null,
            'artwork_revision_requested',
            "Revizyon talebi: {$order->order_no}",
            "{$user->name} ({$user->supplier?->name}) · {$line->product_code} için revizyon istedi: {$notes}",
            route('order-lines.show', $line),
        );

        $this->notifyInternalUsers($revision, 'rejected', $notes);

        return $approval;
    }

    private function notifyInternalUsers(ArtworkRevision $revision, string $action, ?string $notes = null): void
    {
        // Grafik departmanÄ± kullanÄ±cÄ±larÄ±nÄ± bilgilendir
        $users = \App\Models\User::whereIn('role', ['admin', 'graphic'])
            ->where('is_active', true)
            ->get();

        foreach ($users as $user) {
            try {
                if ($action === 'approved') {
                    $user->notify(new ArtworkApprovedNotification($revision));
                } else {
                    $user->notify(new ArtworkRejectedNotification($revision, $notes));
                }
            } catch (\Exception $e) {
                // Bildirim hatasÄ± ana akÄ±ÅŸÄ± bloklamamalÄ±
                \Illuminate\Support\Facades\Log::warning('Notification failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
