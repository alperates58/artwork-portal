<?php

namespace App\Services\Faz2;

use App\Models\ArtworkApproval;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Notifications\ArtworkApprovedNotification;
use App\Notifications\ArtworkRejectedNotification;
use App\Services\AuditLogService;

class ArtworkApprovalService
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Tedarikçi "Gördüm" aksiyonu
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
     * Tedarikçi "Onayladım" aksiyonu
     */
    public function approve(ArtworkRevision $revision, User $user, ?string $notes = null): ArtworkApproval
    {
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

        // Sipariş satırı durumunu güncelle
        $revision->artwork->orderLine->update(['artwork_status' => 'approved']);

        // İç kullanıcılara bildirim gönder
        $this->notifyInternalUsers($revision, 'approved');

        $this->audit->log('artwork.approved', $revision, [
            'supplier_id' => $user->supplier_id,
            'notes'       => $notes,
        ]);

        return $approval;
    }

    /**
     * Tedarikçi "Reddettim" aksiyonu
     */
    public function reject(ArtworkRevision $revision, User $user, string $notes): ArtworkApproval
    {
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

        // Sipariş satırı durumunu revizyon gerekli olarak işaretle
        $revision->artwork->orderLine->update(['artwork_status' => 'revision']);

        // İç kullanıcılara bildirim gönder
        $this->notifyInternalUsers($revision, 'rejected', $notes);

        $this->audit->log('artwork.rejected', $revision, [
            'supplier_id' => $user->supplier_id,
            'notes'       => $notes,
        ]);

        return $approval;
    }

    private function notifyInternalUsers(ArtworkRevision $revision, string $action, ?string $notes = null): void
    {
        // Grafik departmanı kullanıcılarını bilgilendir
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
                // Bildirim hatası ana akışı bloklamamalı
                \Illuminate\Support\Facades\Log::warning('Notification failed', ['error' => $e->getMessage()]);
            }
        }
    }
}
