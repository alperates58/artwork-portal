<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\ArtworkGalleryUsage;
use App\Models\ArtworkRevision;
use App\Models\ArtworkTag;
use App\Models\PurchaseOrderLine;
use App\Models\StockCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Onizleme');
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
        $line = PurchaseOrderLine::factory()->create([
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
            ->assertSee('Lider Kutu')
            ->assertSee('STK-555')
            ->assertSee('Rev.5');
    }
}
