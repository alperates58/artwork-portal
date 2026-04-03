<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtworkPreviewSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_cannot_inline_preview_svg_original_revision(): void
    {
        $supplier = Supplier::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $line = PurchaseOrderLine::factory()->create(['purchase_order_id' => $order->id]);
        $artwork = Artwork::factory()->create(['order_line_id' => $line->id]);
        $revision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'is_active' => true,
            'original_filename' => 'zararli.svg',
            'mime_type' => 'image/svg+xml',
            'spaces_path' => 'artworks/test/zararli.svg',
            'preview_spaces_path' => null,
        ]);

        $artwork->update(['active_revision_id' => $revision->id]);

        $this->actingAs($user)
            ->get(route('portal.preview', $revision))
            ->assertNotFound();
    }

    public function test_internal_users_cannot_inline_preview_svg_gallery_original(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'name' => 'zararli.svg',
            'file_type' => 'image/svg+xml',
            'file_path' => 'artworks/gallery/zararli.svg',
            'preview_file_path' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('artworks.gallery.preview', $galleryItem))
            ->assertNotFound();
    }
}
