<?php

namespace App\Services\Faz3;

use App\Models\PurchaseOrderLine;
use App\Models\SampleApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SampleApprovalService
{
    /**
     * Tedarikçi numune gönderir
     */
    public function submit(
        PurchaseOrderLine $line,
        array $data,
        User $submitter
    ): SampleApproval {
        return DB::transaction(function () use ($line, $data, $submitter) {

            $sampleNo = sprintf(
                'SMP-%s-%03d',
                $line->purchaseOrder->order_no,
                SampleApproval::where('order_line_id', $line->id)->count() + 1
            );

            return SampleApproval::create([
                'order_line_id' => $line->id,
                'sample_no'     => $sampleNo,
                'status'        => 'submitted',
                'submitted_by'  => $submitter->id,
                'submitted_at'  => now(),
                'notes'         => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * İç kullanıcı numuneyi onaylar
     */
    public function approve(SampleApproval $sample, User $reviewer, ?string $notes = null): void
    {
        $sample->update([
            'status'      => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'notes'       => $notes,
        ]);

        // Sipariş satırı artwork durumunu güncelle
        $sample->orderLine->update(['artwork_status' => 'approved']);
    }

    /**
     * İç kullanıcı numuneyi reddeder
     */
    public function reject(SampleApproval $sample, User $reviewer, string $reason): void
    {
        $sample->update([
            'status'           => 'rejected',
            'reviewed_by'      => $reviewer->id,
            'reviewed_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Revizyon gerekiyor
     */
    public function requestRevision(SampleApproval $sample, User $reviewer, string $notes): void
    {
        $sample->update([
            'status'          => 'revision_needed',
            'reviewed_by'     => $reviewer->id,
            'reviewed_at'     => now(),
            'revision_notes'  => $notes,
        ]);
    }
}
