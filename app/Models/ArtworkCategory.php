<?php

namespace App\Models;

use App\Support\DisplayText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArtworkCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function galleryItems(): HasMany
    {
        return $this->hasMany(ArtworkGallery::class, 'category_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return DisplayText::normalize($this->name);
    }
}
