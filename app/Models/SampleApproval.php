<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleApproval extends Model
{
    protected $fillable = [
        'purchase_order_line_id',
        'supplier_id',
        'status',
        'sample_reference',
        'supplier_notes',
        'reviewer_notes',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at'  => 'datetime',
        ];
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'           => 'Bekliyor',
            'submitted'         => 'Gönderildi',
            'approved'          => 'Onaylandı',
            'rejected'          => 'Reddedildi',
            'revision_required' => 'Revizyon Gerekli',
            default             => $this->status,
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'approved'          => 'badge-success',
            'rejected'          => 'badge-danger',
            'revision_required' => 'badge-warning',
            'submitted'         => 'badge-info',
            default             => 'badge-gray',
        };
    }
}
