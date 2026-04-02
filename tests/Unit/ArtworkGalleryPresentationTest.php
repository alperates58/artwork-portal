<?php

namespace Tests\Unit;

use App\Models\ArtworkGallery;
use PHPUnit\Framework\TestCase;

class ArtworkGalleryPresentationTest extends TestCase
{
    public function test_gallery_display_name_normalizes_mojibake_for_presentation(): void
    {
        $galleryItem = new ArtworkGallery([
            'name' => 'TedarikÃ§i-etiketi.pdf',
            'revision_note' => 'GÃ¼ncel sÃ¼rÃ¼m',
        ]);

        $this->assertSame('Tedarikçi-etiketi.pdf', $galleryItem->display_name);
        $this->assertSame('Güncel sürüm', $galleryItem->display_revision_note);
    }

    public function test_gallery_file_type_group_detects_design_files(): void
    {
        $galleryItem = new ArtworkGallery([
            'name' => 'master-artwork.ai',
            'file_type' => 'application/postscript',
        ]);

        $this->assertSame('design', $galleryItem->file_type_group);
        $this->assertSame('AI', $galleryItem->file_type_display);
        $this->assertSame('Tasarım dosyası', $galleryItem->file_type_description);
    }

    public function test_browser_previewable_gallery_items_prefer_original_file_over_generated_preview(): void
    {
        $galleryItem = new ArtworkGallery([
            'name' => 'urun-fotografi.jpg',
            'file_type' => 'image/jpeg',
            'file_disk' => 'spaces',
            'file_path' => 'artworks/gallery/original/urun-fotografi.jpg',
            'preview_file_disk' => 'spaces',
            'preview_file_path' => 'artworks/gallery/preview/urun-fotografi.png',
            'preview_file_type' => 'image/png',
            'preview_file_name' => 'urun-fotografi-preview.png',
        ]);

        $this->assertSame('spaces', $galleryItem->preview_disk);
        $this->assertSame('artworks/gallery/original/urun-fotografi.jpg', $galleryItem->preview_path);
        $this->assertSame('image/jpeg', $galleryItem->preview_mime_type);
        $this->assertSame('urun-fotografi.jpg', $galleryItem->preview_filename);
    }
}
