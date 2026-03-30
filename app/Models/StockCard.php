<?php

namespace App\Models;

use App\Support\DisplayText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_code',
        'stock_name',
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArtworkCategory::class, 'category_id');
    }

    public function galleryItems(): HasMany
    {
        return $this->hasMany(ArtworkGallery::class, 'stock_card_id');
    }

    public function getDisplayStockNameAttribute(): string
    {
        return DisplayText::normalize($this->stock_name);
    }
}
