<?php

namespace Tests\Unit;

use App\Models\ArtworkRevision;
use Tests\TestCase;

class ArtworkRevisionTest extends TestCase
{
    public function test_file_size_formatted_bytes(): void
    {
        $rev = new ArtworkRevision(['file_size' => 512]);
        $this->assertEquals('0.5 KB', $rev->file_size_formatted);
    }

    public function test_file_size_formatted_megabytes(): void
    {
        $rev = new ArtworkRevision(['file_size' => 5 * 1_048_576]);
        $this->assertEquals('5 MB', $rev->file_size_formatted);
    }

    public function test_file_size_formatted_gigabytes(): void
    {
        $rev = new ArtworkRevision(['file_size' => 1_073_741_824]);
        $this->assertEquals('1 GB', $rev->file_size_formatted);
    }

    public function test_extension_is_uppercased(): void
    {
        $rev = new ArtworkRevision(['original_filename' => 'design.pdf']);
        $this->assertEquals('PDF', $rev->extension);
    }

    public function test_extension_works_for_ai_files(): void
    {
        $rev = new ArtworkRevision(['original_filename' => 'logo-v3.ai']);
        $this->assertEquals('AI', $rev->extension);
    }

    public function test_is_visible_to_supplier_when_active(): void
    {
        $rev = new ArtworkRevision(['is_active' => true]);
        $this->assertTrue($rev->isVisibleToSupplier());
    }

    public function test_is_not_visible_to_supplier_when_archived(): void
    {
        $rev = new ArtworkRevision(['is_active' => false]);
        $this->assertFalse($rev->isVisibleToSupplier());
    }
}
