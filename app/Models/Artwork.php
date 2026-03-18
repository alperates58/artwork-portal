<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Artwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_line_id',
        'title',
        'active_revision_id',
    ];

    // ─── Relations ───────────────────────────────────────────────

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'order_line_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArtworkRevision::class)->orderByDesc('revision_no');
    }

    public function activeRevision(): HasOne
    {
        return $this->hasOne(ArtworkRevision::class)->where('is_active', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function getNextRevisionNoAttribute(): int
    {
        return ($this->revisions()->max('revision_no') ?? 0) + 1;
    }

    public function activateRevision(ArtworkRevision $revision): void
    {
        // Önce tüm revizyonları pasife al
        $this->revisions()->update(['is_active' => false]);

        // Seçilen revizyonu aktif yap
        $revision->update(['is_active' => true]);

        // Pointer güncelle
        $this->update(['active_revision_id' => $revision->id]);
    }
}
