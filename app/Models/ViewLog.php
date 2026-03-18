<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Artwork görüntüleme logları — tedarikçi "gördüm" aksiyonları dahil
 */
class ViewLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'artwork_revision_id',
        'action',  // 'view', 'confirm_seen', 'approve'
        'ip_address', 'viewed_at',
    ];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ArtworkRevision::class, 'artwork_revision_id');
    }
}
