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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtworkUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $graphicUser;
    private User $supplierUser;
    private PurchaseOrderLine $line;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('spaces');

        $supplier = Supplier::factory()->create();

        $this->graphicUser = User::factory()->create(['role' => UserRole::GRAPHIC]);
        $this->supplierUser = User::factory()->create([
            'role'        => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $this->line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status'    => 'pending',
        ]);
    }

    public function test_graphic_user_can_see_upload_form(): void
    {
        $this->actingAs($this->graphicUser)
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

    public function test_graphic_user_can_upload_artwork(): void
    {
        // SpacesStorageService mock et
        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('buildPath')->andReturn('artworks/supplier/1/orders/PO-001/lines/1/rev/1/uuid.pdf');
            $mock->shouldReceive('upload')->andReturn([
                'spaces_path'       => 'artworks/supplier/1/orders/PO-001/lines/1/rev/1/uuid.pdf',
                'original_filename' => 'test-artwork.pdf',
                'stored_filename'   => 'uuid.pdf',
                'mime_type'         => 'application/pdf',
                'file_size'         => 1024,
            ]);
        });

        $file = UploadedFile::fake()->create('test-artwork.pdf', 100, 'application/pdf');

        $this->actingAs($this->graphicUser)
             ->post(route('artworks.store', $this->line), [
                 'artwork_file' => $file,
                 'notes'        => 'İlk revizyon',
             ])
             ->assertRedirect(route('order-lines.show', $this->line));

        // Artwork kaydı oluştu mu?
        $this->assertDatabaseHas('artworks', ['order_line_id' => $this->line->id]);

        // Revizyon oluştu mu?
        $this->assertDatabaseHas('artwork_revisions', [
            'revision_no'       => 1,
            'is_active'         => true,
            'original_filename' => 'test-artwork.pdf',
        ]);

        // Sipariş satırı durumu güncellendi mi?
        $this->assertDatabaseHas('purchase_order_lines', [
            'id'             => $this->line->id,
            'artwork_status' => 'uploaded',
        ]);
    }

    public function test_uploading_new_revision_deactivates_old(): void
    {
        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('buildPath')->andReturn('artworks/test/rev2.pdf');
            $mock->shouldReceive('upload')->andReturn([
                'spaces_path'       => 'artworks/test/rev2.pdf',
                'original_filename' => 'revision-2.pdf',
                'stored_filename'   => 'rev2.pdf',
                'mime_type'         => 'application/pdf',
                'file_size'         => 2048,
            ]);
        });

        // Mevcut aktif revizyon oluştur
        $artwork = Artwork::factory()->create(['order_line_id' => $this->line->id]);
        $rev1 = ArtworkRevision::factory()->create([
            'artwork_id'  => $artwork->id,
            'revision_no' => 1,
            'is_active'   => true,
        ]);
        $artwork->update(['active_revision_id' => $rev1->id]);

        // Yeni revizyon yükle
        $file = UploadedFile::fake()->create('revision-2.pdf', 200, 'application/pdf');

        $this->actingAs($this->graphicUser)
             ->post(route('artworks.store', $this->line), ['artwork_file' => $file]);

        // Rev 1 pasife alındı mı?
        $this->assertDatabaseHas('artwork_revisions', ['id' => $rev1->id, 'is_active' => false]);

        // Rev 2 aktif mi?
        $this->assertDatabaseHas('artwork_revisions', ['revision_no' => 2, 'is_active' => true]);
    }

    public function test_upload_validates_file_type(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $this->actingAs($this->graphicUser)
             ->post(route('artworks.store', $this->line), ['artwork_file' => $file])
             ->assertSessionHasErrors('artwork_file');
    }

    public function test_upload_requires_file(): void
    {
        $this->actingAs($this->graphicUser)
             ->post(route('artworks.store', $this->line), [])
             ->assertSessionHasErrors('artwork_file');
    }

    public function test_upload_creates_audit_log(): void
    {
        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('buildPath')->andReturn('artworks/test/file.pdf');
            $mock->shouldReceive('upload')->andReturn([
                'spaces_path' => 'artworks/test/file.pdf',
                'original_filename' => 'test.pdf',
                'stored_filename'   => 'file.pdf',
                'mime_type'         => 'application/pdf',
                'file_size'         => 512,
            ]);
        });

        $file = UploadedFile::fake()->create('test.pdf', 50, 'application/pdf');

        $this->actingAs($this->graphicUser)
             ->post(route('artworks.store', $this->line), ['artwork_file' => $file]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->graphicUser->id,
            'action'  => 'artwork.upload',
        ]);
    }
}
