<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierRegistration extends Model
{
    protected $fillable = [
        'company_name',
        'company_email',
        'contact_name',
        'phone',
        'notes',
        'status',
        'ip_address',
        'user_agent',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
        'user_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
