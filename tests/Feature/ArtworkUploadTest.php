<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Models\ArtworkTag;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\MultipartUploadService;
use App\Services\SpacesStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtworkUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $graphicUser;
    private User $adminUser;
    private User $supplierUser;
    private User $purchasingUser;
    private PurchaseOrderLine $line;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('spaces');

        $supplier = Supplier::factory()->create();

        $this->adminUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->graphicUser = User::factory()->create(['role' => UserRole::GRAPHIC]);
        $this->purchasingUser = User::factory()->create(['role' => UserRole::PURCHASING]);
        $this->supplierUser = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $this->line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'pending',
        ]);
    }

    public function test_graphic_user_can_see_upload_form(): void
    {
        $this->actingAs($this->graphicUser)
            ->get(route('artworks.create', $this->line))
            ->assertOk()
            ->assertViewIs('artworks.create');
    }

    public function test_admin_can_see_upload_form(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('artworks.create', $this->line))
            ->assertOk()
            ->assertViewIs('artworks.create');
    }

    public function test_supplier_cannot_see_upload_form(): void
    {
        $this->actingAs($this->supplierUser)
            ->get(route('artworks.create', $this->line))
            ->assertForbidden();
    }

    public function test_purchasing_cannot_see_upload_form(): void
    {
        $this->actingAs($this->purchasingUser)
            ->get(route('artworks.create', $this->line))
            ->assertForbidden();
    }

    public function test_graphic_user_can_upload_artwork_and_create_gallery_usage(): void
    {
        $category = ArtworkCategory::factory()->create();
        $tag = ArtworkTag::factory()->create();

        SystemSetting::query()->create([
            'group' => 'spaces',
            'key' => 'spaces.disk',
            'value' => 'spaces',
        ]);

        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('buildPath')->andReturn('artworks/supplier/1/orders/PO-001/lines/1/rev/1/uuid.pdf');
        });

        $this->mock(MultipartUploadService::class, function ($mock) {
            $mock->shouldReceive('upload')->andReturn([
                'spaces_path' => 'artworks/supplier/1/orders/PO-001/lines/1/rev/1/uuid.pdf',
                'original_filename' => 'test-artwork.pdf',
                'stored_filename' => 'uuid.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
            ]);
        });

        $file = UploadedFile::fake()->create('test-artwork.pdf', 100, 'application/pdf');

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'upload',
                'artwork_file' => $file,
                'notes' => 'Ilk revizyon',
                'category_id' => $category->id,
                'tag_ids' => [$tag->id],
            ])
            ->assertRedirect(route('order-lines.show', $this->line));

        $this->assertDatabaseHas('artworks', ['order_line_id' => $this->line->id]);
        $this->assertDatabaseHas('artwork_revisions', [
            'revision_no' => 1,
            'is_active' => true,
            'original_filename' => 'test-artwork.pdf',
        ]);
        $this->assertDatabaseHas('purchase_order_lines', [
            'id' => $this->line->id,
            'artwork_status' => 'uploaded',
        ]);
        $this->assertDatabaseHas('artwork_gallery', [
            'name' => 'test-artwork.pdf',
            'category_id' => $category->id,
            'uploaded_by' => $this->graphicUser->id,
            'file_disk' => 'spaces',
        ]);
        $this->assertDatabaseHas('artwork_gallery_usages', [
            'purchase_order_line_id' => $this->line->id,
            'usage_type' => 'upload',
        ]);
        $this->assertDatabaseHas('artwork_gallery_tag', [
            'tag_id' => $tag->id,
        ]);
    }

    public function test_gallery_reuse_creates_revision_without_uploading_new_file(): void
    {
        $galleryItem = ArtworkGallery::factory()->create([
            'name' => 'master-artwork.pdf',
            'file_path' => 'artworks/gallery/master-artwork.pdf',
            'uploaded_by' => $this->adminUser->id,
        ]);

        $this->mock(MultipartUploadService::class, function ($mock) {
            $mock->shouldNotReceive('upload');
        });

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'gallery',
                'gallery_item_id' => $galleryItem->id,
                'notes' => 'Galeriden reuse edildi',
            ])
            ->assertRedirect(route('order-lines.show', $this->line));

        $this->assertDatabaseHas('artwork_revisions', [
            'artwork_gallery_id' => $galleryItem->id,
            'spaces_path' => 'artworks/gallery/master-artwork.pdf',
            'original_filename' => 'master-artwork.pdf',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('artwork_gallery_usages', [
            'artwork_gallery_id' => $galleryItem->id,
            'purchase_order_line_id' => $this->line->id,
            'usage_type' => 'reuse',
        ]);
    }

    public function test_upload_form_shows_gallery_card_filters_preview_and_metadata(): void
    {
        $category = ArtworkCategory::factory()->create(['name' => 'Kutu']);
        $tag = ArtworkTag::factory()->create(['name' => 'Onaylı']);
        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $this->adminUser->id,
            'category_id' => $category->id,
            'name' => 'lider-kutu.ai',
            'file_type' => 'application/postscript',
        ]);
        $galleryItem->tags()->attach($tag);

        $this->actingAs($this->graphicUser)
            ->get(route('artworks.create', $this->line, [
                'gallery_search' => 'lider',
                'gallery_category_id' => $category->id,
                'gallery_tag_id' => $tag->id,
            ]))
            ->assertOk()
            ->assertSee('Görüntüle')
            ->assertSee('lider-kutu.ai')
            ->assertSee('Kutu')
            ->assertSee('Onaylı');
    }

    public function test_graphic_user_can_preview_gallery_image(): void
    {
        Storage::disk('local')->put('artworks/gallery/preview.png', 'fake-image');

        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $this->adminUser->id,
            'file_disk' => 'local',
            'file_path' => 'artworks/gallery/preview.png',
            'file_type' => 'image/png',
            'name' => 'preview.png',
        ]);

        $this->actingAs($this->graphicUser)
            ->get(route('artworks.gallery.preview', $galleryItem))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_purchasing_user_cannot_preview_gallery_image(): void
    {
        Storage::disk('local')->put('artworks/gallery/purchasing-preview.png', 'fake-image');

        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $this->adminUser->id,
            'file_disk' => 'local',
            'file_path' => 'artworks/gallery/purchasing-preview.png',
            'file_type' => 'image/png',
            'name' => 'purchasing-preview.png',
        ]);

        $this->actingAs($this->purchasingUser)
            ->get(route('artworks.gallery.preview', $galleryItem))
            ->assertForbidden();
    }

    public function test_uploading_new_revision_deactivates_old(): void
    {
        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('buildPath')->andReturn('artworks/test/rev2.pdf');
        });

        $this->mock(MultipartUploadService::class, function ($mock) {
            $mock->shouldReceive('upload')->andReturn([
                'spaces_path' => 'artworks/test/rev2.pdf',
                'original_filename' => 'revision-2.pdf',
                'stored_filename' => 'rev2.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 2048,
            ]);
        });

        $artwork = Artwork::factory()->create(['order_line_id' => $this->line->id]);
        $rev1 = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'revision_no' => 1,
            'is_active' => true,
        ]);
        $artwork->update(['active_revision_id' => $rev1->id]);

        $file = UploadedFile::fake()->create('revision-2.pdf', 200, 'application/pdf');

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'upload',
                'artwork_file' => $file,
            ]);

        $this->assertDatabaseHas('artwork_revisions', ['id' => $rev1->id, 'is_active' => false]);
        $this->assertDatabaseHas('artwork_revisions', ['revision_no' => 2, 'is_active' => true]);
    }

    public function test_upload_validates_file_type(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'upload',
                'artwork_file' => $file,
            ])
            ->assertSessionHasErrors('artwork_file');
    }

    public function test_upload_requires_file_for_upload_source(): void
    {
        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'upload',
            ])
            ->assertSessionHasErrors('artwork_file');
    }

    public function test_gallery_source_requires_gallery_item(): void
    {
        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'gallery',
            ])
            ->assertSessionHasErrors('gallery_item_id');
    }

    public function test_upload_creates_audit_logs_for_revision_and_gallery(): void
    {
        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('buildPath')->andReturn('artworks/test/file.pdf');
        });

        $this->mock(MultipartUploadService::class, function ($mock) {
            $mock->shouldReceive('upload')->andReturn([
                'spaces_path' => 'artworks/test/file.pdf',
                'original_filename' => 'test.pdf',
                'stored_filename' => 'file.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 512,
            ]);
        });

        $file = UploadedFile::fake()->create('test.pdf', 50, 'application/pdf');

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'upload',
                'artwork_file' => $file,
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->graphicUser->id,
            'action' => 'artwork.upload',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->graphicUser->id,
            'action' => 'artwork.gallery.create',
        ]);
    }
}
