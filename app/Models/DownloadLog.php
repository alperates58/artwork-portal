<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Artwork indirme logları — audit_logs'tan ayrı, hızlı sorgulama için
 */
class DownloadLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'artwork_revision_id', 'ip_address',
        'user_agent', 'downloaded_at',
    ];

    protected function casts(): array
    {
        return ['downloaded_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ArtworkRevision::class, 'artwork_revision_id');
    }

    public function scopeForRevision($query, int $revisionId)
    {
        return $query->where('artwork_revision_id', $revisionId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
