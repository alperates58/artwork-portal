<?php

namespace App\Jobs;

use App\Models\ArtworkRevision;
use App\Services\ArtworkPreviewGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateArtworkPreviewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public function __construct(
        public readonly int $revisionId
    ) {}

    public function handle(ArtworkPreviewGenerator $generator): void
    {
        $revision = ArtworkRevision::query()
            ->with([
                'galleryItem:id,file_disk,preview_file_disk',
                'artwork.orderLine.purchaseOrder:id,supplier_id,order_no',
            ])
            ->find($this->revisionId);

        if (! $revision) {
            return;
        }

        $generator->generateForRevision($revision);
    }
}
