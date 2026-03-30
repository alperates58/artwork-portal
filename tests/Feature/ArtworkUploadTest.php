<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\StockCard;
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
    private StockCard $stockCard;

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
            'product_code' => 'STK-1001',
            'artwork_status' => 'pending',
        ]);

        $this->stockCard = StockCard::factory()->create([
            'stock_code' => 'STK-1001',
            'stock_name' => 'Lider Nemlendirici Kutu',
        ]);
    }

    public function test_graphic_user_can_see_upload_form(): void
    {
        $this->actingAs($this->graphicUser)
            ->get(route('artworks.create', $this->line))
            ->assertOk()
            ->assertViewIs('artworks.create')
            ->assertSee('Stok Kodu')
            ->assertSee('Revizyon No');
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

    public function test_graphic_user_can_upload_artwork_with_stock_card(): void
    {
        SystemSetting::query()->create([
            'group' => 'spaces',
            'key' => 'spaces.disk',
            'value' => 'spaces',
        ]);

        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('buildPath')->andReturn('artworks/supplier/1/orders/PO-001/lines/1/rev/3/uuid.pdf');
        });

        $this->mock(MultipartUploadService::class, function ($mock) {
            $mock->shouldReceive('upload')->andReturn([
                'spaces_path' => 'artworks/supplier/1/orders/PO-001/lines/1/rev/3/uuid.pdf',
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
                'stock_code' => $this->stockCard->stock_code,
                'revision_no' => 3,
                'notes' => 'İlk onay yüklemesi',
            ])
            ->assertRedirect(route('order-lines.show', $this->line));

        $this->assertDatabaseHas('artworks', ['order_line_id' => $this->line->id]);
        $this->assertDatabaseHas('artwork_revisions', [
            'revision_no' => 3,
            'is_active' => true,
            'original_filename' => 'test-artwork.pdf',
        ]);
        $this->assertDatabaseHas('purchase_order_lines', [
            'id' => $this->line->id,
            'artwork_status' => 'uploaded',
        ]);
        $this->assertDatabaseHas('artwork_gallery', [
            'name' => 'test-artwork.pdf',
            'stock_code' => $this->stockCard->stock_code,
            'revision_no' => 3,
            'stock_card_id' => $this->stockCard->id,
            'category_id' => $this->stockCard->category_id,
            'uploaded_by' => $this->graphicUser->id,
            'file_disk' => 'spaces',
        ]);
        $this->assertDatabaseHas('artwork_gallery_usages', [
            'purchase_order_line_id' => $this->line->id,
            'usage_type' => 'upload',
        ]);
    }

    public function test_gallery_reuse_creates_revision_with_explicit_revision_number(): void
    {
        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $this->adminUser->id,
            'stock_code' => $this->stockCard->stock_code,
            'stock_card_id' => $this->stockCard->id,
            'category_id' => $this->stockCard->category_id,
            'revision_no' => 2,
            'name' => 'master-artwork.pdf',
            'file_path' => 'artworks/gallery/master-artwork.pdf',
        ]);

        $this->mock(MultipartUploadService::class, function ($mock) {
            $mock->shouldNotReceive('upload');
        });

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'gallery',
                'gallery_item_id' => $galleryItem->id,
                'stock_code' => $this->stockCard->stock_code,
                'revision_no' => 2,
                'notes' => 'Galeriden reuse edildi',
            ])
            ->assertRedirect(route('order-lines.show', $this->line));

        $this->assertDatabaseHas('artwork_revisions', [
            'artwork_gallery_id' => $galleryItem->id,
            'spaces_path' => 'artworks/gallery/master-artwork.pdf',
            'original_filename' => 'master-artwork.pdf',
            'revision_no' => 2,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('artwork_gallery_usages', [
            'artwork_gallery_id' => $galleryItem->id,
            'purchase_order_line_id' => $this->line->id,
            'usage_type' => 'reuse',
        ]);
    }

    public function test_gallery_reuse_accepts_selected_gallery_revision_even_when_lower_than_next_revision(): void
    {
        $artwork = Artwork::factory()->create(['order_line_id' => $this->line->id]);
        ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'revision_no' => 3,
            'is_active' => true,
        ]);

        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $this->adminUser->id,
            'stock_code' => $this->stockCard->stock_code,
            'stock_card_id' => $this->stockCard->id,
            'category_id' => $this->stockCard->category_id,
            'revision_no' => 2,
            'name' => 'eski-rev2.pdf',
            'file_path' => 'artworks/gallery/eski-rev2.pdf',
        ]);

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'gallery',
                'gallery_item_id' => $galleryItem->id,
                'stock_code' => $this->stockCard->stock_code,
                'revision_no' => 2,
            ])
            ->assertRedirect(route('order-lines.show', $this->line));

        $this->assertDatabaseHas('artwork_revisions', [
            'artwork_gallery_id' => $galleryItem->id,
            'revision_no' => 2,
        ]);
    }

    public function test_stock_card_lookup_returns_auto_fill_payload(): void
    {
        $this->actingAs($this->graphicUser)
            ->getJson(route('stock-cards.lookup', ['stock_code' => $this->stockCard->stock_code]))
            ->assertOk()
            ->assertJson([
                'stock_code' => $this->stockCard->stock_code,
                'stock_name' => $this->stockCard->stock_name,
                'category_id' => $this->stockCard->category_id,
            ]);
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
                'stock_code' => $this->stockCard->stock_code,
                'revision_no' => 2,
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
                'stock_code' => $this->stockCard->stock_code,
                'revision_no' => 1,
            ])
            ->assertSessionHasErrors('artwork_file');
    }

    public function test_upload_requires_stock_code(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 50, 'application/pdf');

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'upload',
                'artwork_file' => $file,
                'revision_no' => 1,
            ])
            ->assertSessionHasErrors('stock_code');
    }

    public function test_upload_requires_revision_number(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 50, 'application/pdf');

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'upload',
                'artwork_file' => $file,
                'stock_code' => $this->stockCard->stock_code,
            ])
            ->assertSessionHasErrors('revision_no');
    }

    public function test_upload_rejects_unknown_stock_code(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 50, 'application/pdf');

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'upload',
                'artwork_file' => $file,
                'stock_code' => 'STK-4040',
                'revision_no' => 1,
            ])
            ->assertSessionHasErrors('stock_code');
    }

    public function test_upload_rejects_duplicate_gallery_revision_for_same_stock_code(): void
    {
        ArtworkGallery::factory()->create([
            'stock_code' => $this->stockCard->stock_code,
            'stock_card_id' => $this->stockCard->id,
            'category_id' => $this->stockCard->category_id,
            'revision_no' => 2,
            'name' => 'mevcut-rev2.pdf',
        ]);

        $file = UploadedFile::fake()->create('yeni-rev2.pdf', 50, 'application/pdf');

        $this->actingAs($this->graphicUser)
            ->post(route('artworks.store', $this->line), [
                'source_type' => 'upload',
                'artwork_file' => $file,
                'stock_code' => $this->stockCard->stock_code,
                'revision_no' => 2,
            ])
            ->assertSessionHasErrors('revision_no');
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
                'stock_code' => $this->stockCard->stock_code,
                'revision_no' => 1,
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
