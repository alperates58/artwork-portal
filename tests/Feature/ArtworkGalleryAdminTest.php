<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\ArtworkTag;
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

    public function test_graphic_cannot_access_admin_gallery_index(): void
    {
        $graphic = User::factory()->create(['role' => UserRole::GRAPHIC]);

        $this->actingAs($graphic)
            ->get(route('admin.artwork-gallery.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_category_and_tag_and_update_gallery_assignment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $galleryItem = ArtworkGallery::factory()->create(['uploaded_by' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('admin.artwork-gallery.categories.store'), ['name' => 'Kutu'])
            ->assertRedirect(route('admin.artwork-gallery.index'));

        $this->actingAs($admin)
            ->post(route('admin.artwork-gallery.tags.store'), ['name' => 'Onayli'])
            ->assertRedirect(route('admin.artwork-gallery.index'));

        $category = ArtworkCategory::query()->where('name', 'Kutu')->firstOrFail();
        $tag = ArtworkTag::query()->where('name', 'Onayli')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.artwork-gallery.update', $galleryItem), [
                'name' => 'guncel-master.pdf',
                'category_id' => $category->id,
                'revision_note' => 'Revizyon notu guncellendi',
                'tag_ids' => [$tag->id],
            ])
            ->assertRedirect(route('admin.artwork-gallery.edit', $galleryItem));

        $this->assertDatabaseHas('artwork_gallery', [
            'id' => $galleryItem->id,
            'name' => 'guncel-master.pdf',
            'category_id' => $category->id,
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
}
