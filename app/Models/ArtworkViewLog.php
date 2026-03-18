<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtworkViewLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'artwork_revision_id',
        'user_id',
        'supplier_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
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
}
