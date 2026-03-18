<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SpacesStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ArtworkDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $supplier1User;
    private User $supplier2User;
    private User $graphicUser;
    private ArtworkRevision $activeRevision;
    private ArtworkRevision $archivedRevision;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock SpacesStorageService — gerçek Spaces'e bağlanma
        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('exists')->andReturn(true);
            $mock->shouldReceive('presignedUrl')->andReturn('https://spaces.example.com/fake-presigned-url');
        });

        // Tedarikçi 1
        $supplier1 = Supplier::factory()->create();
        $this->supplier1User = User::factory()->create([
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplier1->id,
        ]);

        // Tedarikçi 2 (farklı)
        $supplier2 = Supplier::factory()->create();
        $this->supplier2User = User::factory()->create([
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplier2->id,
        ]);

        // Grafik kullanıcı
        $this->graphicUser = User::factory()->create(['role' => UserRole::GRAPHIC]);

        // Sipariş → satır → artwork → revizyon
        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier1->id]);
        $line  = PurchaseOrderLine::factory()->create(['purchase_order_id' => $order->id]);
        $artwork = Artwork::factory()->create(['order_line_id' => $line->id]);

        $this->activeRevision = ArtworkRevision::factory()->create([
            'artwork_id'  => $artwork->id,
            'revision_no' => 1,
            'is_active'   => true,
            'spaces_path' => 'artworks/test/file.pdf',
        ]);

        $this->archivedRevision = ArtworkRevision::factory()->create([
            'artwork_id'  => $artwork->id,
            'revision_no' => 2,
            'is_active'   => false,
            'spaces_path' => 'artworks/test/file-old.pdf',
        ]);

        $artwork->update(['active_revision_id' => $this->activeRevision->id]);
    }

    // ─── Tedarikçi testleri ───────────────────────────────────────

    public function test_supplier_can_download_their_active_artwork(): void
    {
        $this->actingAs($this->supplier1User)
             ->get(route('portal.download', $this->activeRevision))
             ->assertRedirect('https://spaces.example.com/fake-presigned-url');
    }

    public function test_supplier_cannot_download_archived_revision(): void
    {
        $this->actingAs($this->supplier1User)
             ->get(route('portal.download', $this->archivedRevision))
             ->assertForbidden();
    }

    public function test_supplier_cannot_download_other_suppliers_artwork(): void
    {
        $this->actingAs($this->supplier2User)
             ->get(route('portal.download', $this->activeRevision))
             ->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get(route('portal.download', $this->activeRevision))
             ->assertRedirect('/login');
    }

    // ─── İç kullanıcı testleri ────────────────────────────────────

    public function test_graphic_user_can_download_active_revision(): void
    {
        $this->actingAs($this->graphicUser)
             ->get(route('artwork.download', $this->activeRevision))
             ->assertRedirect();
    }

    public function test_graphic_user_can_download_archived_revision(): void
    {
        $this->actingAs($this->graphicUser)
             ->get(route('artwork.download', $this->archivedRevision))
             ->assertRedirect();
    }

    // ─── Download log testleri ────────────────────────────────────

    public function test_download_creates_audit_log(): void
    {
        $this->actingAs($this->supplier1User)
             ->get(route('portal.download', $this->activeRevision));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->supplier1User->id,
            'action'  => 'artwork.download',
        ]);
    }
}
