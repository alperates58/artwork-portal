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
}
