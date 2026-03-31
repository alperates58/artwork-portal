<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtworkRevision extends Model
{
    use HasFactory;

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
        return filled($this->preview_spaces_path);
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
