<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtworkGallery extends Model
{
    use HasFactory;

    protected $table = 'artwork_gallery';

    protected $fillable = [
        'name',
        'category_id',
        'file_path',
        'file_disk',
        'file_size',
        'file_type',
        'uploaded_by',
        'revision_note',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArtworkCategory::class, 'category_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ArtworkTag::class, 'artwork_gallery_tag', 'artwork_gallery_id', 'tag_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(ArtworkGalleryUsage::class, 'artwork_gallery_id')->latest('used_at');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArtworkRevision::class, 'artwork_gallery_id');
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1_073_741_824) {
            return round($bytes / 1_073_741_824, 2) . ' GB';
        }

        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2) . ' MB';
        }

        return round($bytes / 1_024, 2) . ' KB';
    }

    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->name, PATHINFO_EXTENSION));
    }
}
