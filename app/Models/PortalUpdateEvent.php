<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalUpdateEvent extends Model
{
    protected $fillable = [
        'type',
        'status',
        'trigger_source',
        'actor_id',
        'branch',
        'local_commit',
        'local_version',
        'remote_commit',
        'remote_version',
        'update_available',
        'message',
        'meta',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'update_available' => 'boolean',
            'meta' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
