<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ArtworkRevision extends Model
{
    use HasFactory;

    private const BROWSER_PREVIEW_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    protected $fillable = [
        'artwork_id',
        'artwork_gallery_id',
        'revision_no',
        'original_filename',
        'stored_filename',
        'preview_original_filename',
        'preview_stored_filename',
        'spaces_path',
        'preview_spaces_path',
        'mime_type',
        'preview_mime_type',
        'file_size',
        'preview_file_size',
        'is_active',
        'uploaded_by',
        'notes',
        'archived_at',
        'approved_by',
        'approved_at',
        'approval_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'file_size'   => 'integer',
            'preview_file_size' => 'integer',
            'revision_no' => 'integer',
            'archived_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function galleryItem(): BelongsTo
    {
        return $this->belongsTo(ArtworkGallery::class, 'artwork_gallery_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ArtworkApproval::class, 'artwork_revision_id');
    }

    public function latestRejectedApproval(): HasOne
    {
        return $this->hasOne(ArtworkApproval::class, 'artwork_revision_id')
            ->ofMany(
                ['actioned_at' => 'max', 'id' => 'max'],
                fn ($query) => $query->where('status', 'rejected')
            );
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * İnsan okunabilir dosya boyutu
     */
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

    /**
     * Dosya uzantısı
     */
    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->original_filename, PATHINFO_EXTENSION));
    }

    public function getHasPreviewAttribute(): bool
    {
        return filled($this->preview_spaces_path) || $this->isBrowserPreviewableOriginal();
    }

    public function getPreviewDiskAttribute(): ?string
    {
        if ($this->isBrowserPreviewableOriginal()) {
            return $this->galleryItem?->file_disk;
        }

        return $this->galleryItem?->preview_disk ?: $this->galleryItem?->file_disk;
    }

    public function getPreviewPathAttribute(): ?string
    {
        return ($this->isBrowserPreviewableOriginal() ? $this->spaces_path : null)
            ?: $this->getAttributeFromArray('preview_spaces_path');
    }

    public function getPreviewMimeTypeAttribute(): ?string
    {
        return ($this->isBrowserPreviewableOriginal() ? $this->mime_type : null)
            ?: $this->getAttributeFromArray('preview_mime_type');
    }

    public function getPreviewFilenameAttribute(): ?string
    {
        return ($this->isBrowserPreviewableOriginal() ? $this->original_filename : null)
            ?: $this->getAttributeFromArray('preview_original_filename');
    }

    private function isBrowserPreviewableOriginal(): bool
    {
        $extension = strtolower((string) pathinfo($this->original_filename, PATHINFO_EXTENSION));
        $mimeType = strtolower((string) $this->mime_type);

        return in_array($extension, self::BROWSER_PREVIEW_EXTENSIONS, true)
            || in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'], true);
    }

    /**
     * Revizyonu arşivle (silme — sadece pasife al)
     */
    public function approvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function archive(): void
    {
        $this->update([
            'is_active'   => false,
            'archived_at' => now(),
        ]);
    }

    /**
     * Tedarikçiye gösterilip gösterilmeyeceği
     */
    public function isVisibleToSupplier(): bool
    {
        return $this->is_active;
    }
}
