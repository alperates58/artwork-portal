<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\ArtworkGalleryUsage;
use App\Jobs\GenerateGalleryPreviewJob;
use App\Models\PurchaseOrder;
use App\Models\ArtworkRevision;
use App\Models\ArtworkTag;
use App\Models\PurchaseOrderLine;
use App\Models\StockCard;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ArtworkPreviewGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Services\MultipartUploadService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtworkGalleryAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_gallery_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.artwork-gallery.index'))
            ->assertOk()
            ->assertSee('Artwork Galerisi');
    }

    public function test_graphic_can_access_admin_gallery_index_with_gallery_view_permission(): void
    {
        $graphic = User::factory()->create(['role' => UserRole::GRAPHIC]);

        $this->actingAs($graphic)
            ->get(route('admin.artwork-gallery.index'))
            ->assertOk();
    }

    public function test_admin_can_create_category_and_tag_and_update_gallery_assignment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $galleryItem = ArtworkGallery::factory()->create(['uploaded_by' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('admin.artwork-gallery.categories.store'), ['name' => 'Kutu'])
            ->assertRedirect(route('admin.artwork-gallery.manage'));

        $this->actingAs($admin)
            ->post(route('admin.artwork-gallery.tags.store'), ['name' => 'Onayli'])
            ->assertRedirect(route('admin.artwork-gallery.manage'));

        $tag = ArtworkTag::query()->where('name', 'Onayli')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.artwork-gallery.update', $galleryItem), [
                'name' => 'guncel-master.pdf',
                'stock_code' => $galleryItem->stock_code,
                'revision_note' => 'Revizyon notu guncellendi',
                'tag_ids' => [$tag->id],
            ])
            ->assertRedirect(route('admin.artwork-gallery.edit', $galleryItem));

        $this->assertDatabaseHas('artwork_gallery', [
            'id' => $galleryItem->id,
            'name' => 'guncel-master.pdf',
            'revision_note' => 'Revizyon notu guncellendi',
        ]);
        $this->assertDatabaseHas('artwork_gallery_tag', [
            'artwork_gallery_id' => $galleryItem->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'artwork.gallery.update',
        ]);
    }

    public function test_admin_gallery_index_renders_card_actions_and_usage_metadata(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $category = ArtworkCategory::factory()->create(['name' => 'Ambalaj']);
        $tag = ArtworkTag::factory()->create(['name' => 'Onizleme']);
        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'category_id' => $category->id,
            'name' => 'etiket-onizleme.png',
            'file_type' => 'image/png',
            'file_path' => 'artworks/gallery/etiket-onizleme.png',
        ]);
        $galleryItem->tags()->attach($tag);
        ArtworkGalleryUsage::query()->create([
            'artwork_gallery_id' => $galleryItem->id,
            'used_at' => now()->subDay(),
            'usage_type' => 'reuse',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.artwork-gallery.index'))
            ->assertOk()
            ->assertSee('etiket-onizleme.png')
            ->assertSee('Ambalaj')
            ->assertSee('Onizleme')
            ->assertSee('Görüntüle')
            ->assertSee('İndir')
            ->assertSee('Düzenle');
    }

    public function test_admin_gallery_filters_apply_search_category_and_tag_together(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $category = ArtworkCategory::factory()->create(['name' => 'Kutu']);
        $otherCategory = ArtworkCategory::factory()->create(['name' => 'Poster']);
        $tag = ArtworkTag::factory()->create(['name' => 'Onayli']);
        $otherTag = ArtworkTag::factory()->create(['name' => 'Arsiv']);
        $matchingStockCard = StockCard::factory()->create([
            'stock_code' => 'STK-101',
            'stock_name' => 'Lider Kutu',
            'category_id' => $category->id,
        ]);
        $otherStockCard = StockCard::factory()->create([
            'stock_code' => 'STK-202',
            'stock_name' => 'Farkli Poster',
            'category_id' => $otherCategory->id,
        ]);

        $matching = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'stock_card_id' => $matchingStockCard->id,
            'category_id' => $category->id,
            'stock_code' => $matchingStockCard->stock_code,
            'name' => 'lider-kutu.pdf',
        ]);
        $matching->tags()->attach($tag);

        $other = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'stock_card_id' => $otherStockCard->id,
            'category_id' => $otherCategory->id,
            'stock_code' => $otherStockCard->stock_code,
            'name' => 'farkli-poster.pdf',
        ]);
        $other->tags()->attach($otherTag);

        $this->actingAs($admin)
            ->get(route('admin.artwork-gallery.index', [
                'search' => 'lider',
                'category_id' => $category->id,
                'tag_id' => $tag->id,
            ]))
            ->assertOk()
            ->assertSee('lider-kutu.pdf')
            ->assertDontSee('farkli-poster.pdf');
    }

    public function test_gallery_stock_code_filter_renders_revision_summary_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $supplier = Supplier::factory()->create(['name' => 'Lider Tedarik']);
        $category = ArtworkCategory::factory()->create(['name' => 'Kutu']);
        $stockCard = StockCard::factory()->create([
            'stock_code' => 'STK-555',
            'stock_name' => 'Lider Kutu',
            'category_id' => $category->id,
        ]);
        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'stock_card_id' => $stockCard->id,
            'stock_code' => $stockCard->stock_code,
            'category_id' => $category->id,
            'name' => 'lider-kutu.pdf',
        ]);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'order_no' => 'SIP-555',
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'product_code' => $stockCard->stock_code,
        ]);
        $artwork = Artwork::factory()->create(['order_line_id' => $line->id]);

        ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'artwork_gallery_id' => $galleryItem->id,
            'revision_no' => 5,
            'original_filename' => 'lider-kutu.pdf',
            'is_active' => true,
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.artwork-gallery.index', ['stock_code' => 'STK-555']))
            ->assertOk()
            ->assertSee('Stok Koduna Göre Hızlı Eşleşmeler')
            ->assertSee('Artwork Geçmişi')
            ->assertSee('Lider Kutu')
            ->assertSee('STK-555')
            ->assertSee('Sistemde Rev.05')
            ->assertSee('Rev.5')
            ->assertSee('SIP-555')
            ->assertSee('Lider Tedarik');
    }

    public function test_direct_gallery_upload_requires_next_available_revision_for_same_stock_code(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $stockCard = StockCard::factory()->create([
            'stock_code' => 'STK-777',
            'stock_name' => 'Lider Şişe',
        ]);

        ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'stock_code' => $stockCard->stock_code,
            'stock_card_id' => $stockCard->id,
            'category_id' => $stockCard->category_id,
            'revision_no' => 3,
        ]);

        $this->mock(MultipartUploadService::class, function ($mock) {
            $mock->shouldNotReceive('upload');
        });

        $this->actingAs($admin)
            ->post(route('admin.artwork-gallery.direct-upload'), [
                'artwork_file' => UploadedFile::fake()->create('stok-777-rev2.pdf', 50, 'application/pdf'),
                'stock_code' => $stockCard->stock_code,
                'revision_no' => 2,
            ])
            ->assertSessionHasErrors('revision_no');
    }

    public function test_direct_gallery_upload_dispatches_preview_generation_job_for_supported_files(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $stockCard = StockCard::factory()->create([
            'stock_code' => 'STK-888',
            'stock_name' => 'Lider Kutu',
        ]);

        $this->mock(MultipartUploadService::class, function ($mock) {
            $mock->shouldReceive('upload')->once()->andReturn([
                'spaces_path' => 'artworks/gallery/2026/04/test-file.ai',
                'original_filename' => 'master.ai',
                'stored_filename' => 'test-file.ai',
                'mime_type' => 'application/postscript',
                'file_size' => 4096,
            ]);
        });

        $this->actingAs($admin)
            ->post(route('admin.artwork-gallery.direct-upload'), [
                'artwork_file' => UploadedFile::fake()->create('master.ai', 100, 'application/postscript'),
                'stock_code' => $stockCard->stock_code,
                'revision_no' => 1,
            ])
            ->assertRedirect(route('admin.artwork-gallery.index'));

        $galleryItem = ArtworkGallery::query()->latest('id')->firstOrFail();

        Queue::assertPushed(
            GenerateGalleryPreviewJob::class,
            fn (GenerateGalleryPreviewJob $job) => $job->galleryItemId === $galleryItem->id
        );
    }

    public function test_gallery_index_queues_missing_preview_for_supported_direct_gallery_items(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'name' => 'stok-999-rev1.ai',
            'file_type' => 'application/postscript',
            'preview_file_path' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.artwork-gallery.index'))
            ->assertOk();

        Queue::assertPushed(
            GenerateGalleryPreviewJob::class,
            fn (GenerateGalleryPreviewJob $job) => $job->galleryItemId === $galleryItem->id
        );
    }

    public function test_preview_generator_returns_false_instead_of_throwing_for_supported_gallery_item_without_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'name' => 'stok-444-rev1.ai',
            'file_path' => 'artworks/gallery/stok-444-rev1.ai',
            'file_disk' => 'local',
            'file_type' => 'application/postscript',
            'preview_file_path' => null,
        ]);

        $result = app(ArtworkPreviewGenerator::class)->generateForGalleryItem($galleryItem);

        $this->assertFalse($result);
    }

    public function test_gallery_index_shows_active_items_by_default_and_can_filter_inactive_items(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $activeItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'name' => 'aktif-artwork.pdf',
            'is_active' => true,
        ]);

        $inactiveItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'name' => 'pasif-artwork.pdf',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.artwork-gallery.index'))
            ->assertOk()
            ->assertSee($activeItem->name)
            ->assertDontSee($inactiveItem->name);

        $this->actingAs($admin)
            ->get(route('admin.artwork-gallery.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($inactiveItem->name)
            ->assertDontSee($activeItem->name);
    }

    public function test_used_gallery_item_cannot_be_deleted_but_can_be_deactivated_and_reactivated(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $galleryItem = ArtworkGallery::factory()->create([
            'uploaded_by' => $admin->id,
            'is_active' => true,
        ]);

        ArtworkGalleryUsage::query()->create([
            'artwork_gallery_id' => $galleryItem->id,
            'used_at' => now(),
            'usage_type' => 'reuse',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.artwork-gallery.destroy', $galleryItem))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('artwork_gallery', [
            'id' => $galleryItem->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.artwork-gallery.deactivate', $galleryItem))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('artwork_gallery', [
            'id' => $galleryItem->id,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.artwork-gallery.activate', $galleryItem))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('artwork_gallery', [
            'id' => $galleryItem->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'artwork.gallery.deactivate',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'artwork.gallery.activate',
        ]);
    }
}
