<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtworkApproval extends Model
{
    protected $fillable = [
        'artwork_revision_id',
        'user_id',
        'supplier_id',
        'status',
        'notes',
        'actioned_at',
    ];

    protected function casts(): array
    {
        return [
            'actioned_at' => 'datetime',
        ];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ArtworkRevision::class, 'artwork_revision_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'viewed'   => 'Görüldü',
            'approved' => 'Onaylandı',
            'rejected' => 'Reddedildi',
            default    => $this->status,
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            default    => 'badge-info',
        };
    }
}
