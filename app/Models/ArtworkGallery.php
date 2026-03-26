<?php

namespace App\Models;

use App\Support\DisplayText;
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
        $bytes = (int) $this->file_size;

        if ($bytes <= 0) {
            return '0 KB';
        }

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

    public function getDisplayNameAttribute(): string
    {
        return DisplayText::normalize($this->name);
    }

    public function getDisplayRevisionNoteAttribute(): string
    {
        return DisplayText::normalize($this->revision_note);
    }

    public function getFileTypeGroupAttribute(): string
    {
        $extension = strtolower((string) pathinfo($this->name, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) $this->file_type);

        if (str_starts_with($mimeType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tif', 'tiff'], true)) {
            return 'image';
        }

        if ($mimeType === 'application/pdf' || $extension === 'pdf') {
            return 'pdf';
        }

        if (in_array($extension, ['ai', 'eps', 'psd', 'indd'], true)) {
            return 'design';
        }

        return 'other';
    }

    public function getIsImageAttribute(): bool
    {
        return $this->file_type_group === 'image';
    }

    public function getFileTypeIconAttribute(): string
    {
        return match ($this->file_type_group) {
            'image' => 'image',
            'pdf' => 'pdf',
            'design' => 'design',
            default => 'file',
        };
    }

    public function getFileTypeDisplayAttribute(): string
    {
        return $this->extension !== '' ? $this->extension : strtoupper($this->file_type_group);
    }

    public function getFileTypeDescriptionAttribute(): string
    {
        return match ($this->file_type_group) {
            'image' => 'Görsel',
            'pdf' => 'PDF',
            'design' => 'Tasarım dosyası',
            default => 'Dosya',
        };
    }

    public function getUsageCountAttribute(): int
    {
        if (array_key_exists('usages_count', $this->attributes)) {
            return (int) $this->attributes['usages_count'];
        }

        if ($this->relationLoaded('usages')) {
            return $this->usages->count();
        }

        return 0;
    }

    public function getLastUsedAtAttribute(): mixed
    {
        return $this->attributes['usages_max_used_at']
            ?? ($this->relationLoaded('usages') ? optional($this->usages->first())->used_at : null);
    }
}
