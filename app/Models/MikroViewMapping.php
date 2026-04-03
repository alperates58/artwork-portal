<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MikroViewMapping extends Model
{
    protected $fillable = [
        'name',
        'entity_type',
        'view_name',
        'endpoint_path',
        'payload_mode',
        'line_array_key',
        'mapping_payload',
        'sample_payload',
        'notes',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'mapping_payload' => 'array',
            'sample_payload' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
