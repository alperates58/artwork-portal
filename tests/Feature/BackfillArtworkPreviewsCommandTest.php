<?php

namespace Tests\Feature;

use App\Jobs\GenerateArtworkPreviewJob;
use App\Models\Artwork;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BackfillArtworkPreviewsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_jobs_for_supported_revisions_without_preview(): void
    {
        Queue::fake();

        [$epsRevision, $pngRevision, $readyRevision] = $this->seedRevisions();

        $this->artisan('artwork:preview-backfill', ['--limit' => 10])
            ->expectsOutputToContain('Preview backfill komutlari kuyruga alindi.')
            ->assertSuccessful();

        Queue::assertPushed(GenerateArtworkPreviewJob::class, fn (GenerateArtworkPreviewJob $job) => $job->revisionId === $epsRevision->id);
        Queue::assertNotPushed(GenerateArtworkPreviewJob::class, fn (GenerateArtworkPreviewJob $job) => $job->revisionId === $pngRevision->id);
        Queue::assertNotPushed(GenerateArtworkPreviewJob::class, fn (GenerateArtworkPreviewJob $job) => $job->revisionId === $readyRevision->id);
    }

    public function test_command_respects_dry_run_and_active_only_filters(): void
    {
        Queue::fake();

        [$activeRevision, $pngRevision, $readyRevision, $inactiveRevision] = $this->seedRevisions(includeInactive: true);

        $this->artisan('artwork:preview-backfill', [
            '--limit' => 10,
            '--dry-run' => true,
            '--active-only' => true,
        ])
            ->expectsOutputToContain('Dry-run tamamlandi.')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    /**
     * @return array<int, ArtworkRevision>
     */
    private function seedRevisions(bool $includeInactive = false): array
    {
        $supplier = Supplier::factory()->create();
        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $line = PurchaseOrderLine::factory()->create(['purchase_order_id' => $order->id]);
        $artwork = Artwork::factory()->create(['order_line_id' => $line->id]);

        $galleryItem = ArtworkGallery::factory()->create([
            'file_disk' => 'spaces',
            'file_path' => 'artworks/example/rev1/original/file.eps',
        ]);

        $epsRevision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'artwork_gallery_id' => $galleryItem->id,
            'revision_no' => 1,
            'original_filename' => 'kapak.eps',
            'mime_type' => 'application/postscript',
            'spaces_path' => 'artworks/example/rev1/original/file.eps',
            'preview_spaces_path' => null,
            'is_active' => true,
        ]);

        $pngRevision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'revision_no' => 2,
            'original_filename' => 'kapak.png',
            'mime_type' => 'image/png',
            'spaces_path' => 'artworks/example/rev2/original/file.png',
            'preview_spaces_path' => null,
            'is_active' => true,
        ]);

        $readyRevision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'revision_no' => 3,
            'original_filename' => 'kapak.ai',
            'mime_type' => 'application/postscript',
            'spaces_path' => 'artworks/example/rev3/original/file.ai',
            'preview_spaces_path' => 'artworks/example/rev3/preview/file.png',
            'is_active' => true,
        ]);

        $revisions = [$epsRevision, $pngRevision, $readyRevision];

        if ($includeInactive) {
            $revisions[] = ArtworkRevision::factory()->create([
                'artwork_id' => $artwork->id,
                'revision_no' => 4,
                'original_filename' => 'arsiv.pdf',
                'mime_type' => 'application/pdf',
                'spaces_path' => 'artworks/example/rev4/original/file.pdf',
                'preview_spaces_path' => null,
                'is_active' => false,
            ]);
        }

        return $revisions;
    }
}
