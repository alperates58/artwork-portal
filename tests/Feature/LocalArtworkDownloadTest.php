<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalArtworkDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_storage_download_works_through_secure_endpoint(): void
    {
        config(['filesystems.default' => 'local']);
        Storage::fake('local');

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
            'revision_no' => 1,
            'is_active' => true,
            'original_filename' => 'lokal-dosya.pdf',
            'spaces_path' => 'artworks/test/lokal-dosya.pdf',
        ]);

        $artwork->update(['active_revision_id' => $revision->id]);

        Storage::disk('local')->put($revision->spaces_path, 'test-content');

        $this->actingAs($user)
            ->get(route('portal.download', $revision))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->assertDatabaseHas('artwork_download_logs', [
            'artwork_revision_id' => $revision->id,
            'user_id' => $user->id,
        ]);
    }
}
