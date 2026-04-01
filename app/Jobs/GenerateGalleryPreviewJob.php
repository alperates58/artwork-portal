<?php

namespace App\Jobs;

use App\Models\ArtworkGallery;
use App\Services\ArtworkPreviewGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateGalleryPreviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public readonly int $galleryItemId
    ) {}

    public function handle(ArtworkPreviewGenerator $generator): void
    {
        $galleryItem = ArtworkGallery::find($this->galleryItemId);

        if (! $galleryItem) {
            return;
        }

        $generator->generateForGalleryItem($galleryItem);
    }
}
