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
        'from_version',
        'to_version',
        'release_title',
        'release_summary',
        'change_summary',
        'changed_modules',
        'migrations_included',
        'schema_changes',
        'warnings',
        'post_update_notes',
        'applied_migrations',
        'release_date',
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
            'change_summary' => 'array',
            'changed_modules' => 'array',
            'migrations_included' => 'boolean',
            'schema_changes' => 'array',
            'warnings' => 'array',
            'post_update_notes' => 'array',
            'applied_migrations' => 'array',
            'release_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
