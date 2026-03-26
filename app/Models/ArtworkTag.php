<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ArtworkTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function galleryItems(): BelongsToMany
    {
        return $this->belongsToMany(ArtworkGallery::class, 'artwork_gallery_tag', 'tag_id', 'artwork_gallery_id');
    }
}
