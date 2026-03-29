<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierUser;
use App\Models\User;
use App\Services\Faz2\ArtworkApprovalService;
use App\Services\MultipartUploadService;
use App\Services\SpacesStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardMetricCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_dashboard_pending_metric_is_invalidated_after_artwork_upload(): void
    {
        $supplier = Supplier::factory()->create();
        $graphicUser = User::factory()->create(['role' => UserRole::GRAPHIC]);
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'pending',
        ]);

        $this->actingAs($adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['pending_artwork'] === 1);

        $this->assertNotNull(Cache::get('dashboard.metrics'));

        $this->mock(SpacesStorageService::class, function ($mock) {
            $mock->shouldReceive('buildPath')->andReturn('artworks/test/revision-1.pdf');
        });

        $this->mock(MultipartUploadService::class, function ($mock) {
            $mock->shouldReceive('upload')->andReturn([
                'spaces_path' => 'artworks/test/revision-1.pdf',
                'original_filename' => 'revision-1.pdf',
                'stored_filename' => 'revision-1.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 2048,
            ]);
        });

        $this->actingAs($graphicUser)
            ->post(route('artworks.store', $line), [
                'source_type' => 'upload',
                'artwork_file' => UploadedFile::fake()->create('revision-1.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('order-lines.show', $line));

        $this->actingAs($adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['pending_artwork'] === 0)
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['uploaded_artwork'] === 1);
    }

    public function test_dashboard_pending_metric_is_invalidated_after_supplier_approval(): void
    {
        $supplier = Supplier::factory()->create();
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $supplierUser = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);

        SupplierUser::firstOrCreate([
            'supplier_id' => $supplier->id,
            'user_id' => $supplierUser->id,
        ], [
            'is_primary' => true,
            'can_download' => true,
            'can_approve' => false,
        ]);

        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'revision',
        ]);
        $artwork = Artwork::factory()->create(['order_line_id' => $line->id]);
        $revision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'is_active' => true,
            'revision_no' => 1,
        ]);
        $artwork->update(['active_revision_id' => $revision->id]);

        $this->actingAs($adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['pending_approval'] === 1);

        $this->actingAs($supplierUser)
            ->post(route('approval.approve', $revision))
            ->assertRedirect();

        $this->actingAs($adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['pending_approval'] === 0);
    }

    public function test_dashboard_pending_metric_is_invalidated_after_reject_transition(): void
    {
        $supplier = Supplier::factory()->create();
        $supplierUser = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
        ]);
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);

        SupplierUser::firstOrCreate([
            'supplier_id' => $supplier->id,
            'user_id' => $supplierUser->id,
        ], [
            'is_primary' => true,
            'can_download' => true,
            'can_approve' => false,
        ]);

        $order = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'uploaded',
        ]);
        $artwork = Artwork::factory()->create(['order_line_id' => $line->id]);
        $revision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'is_active' => true,
            'revision_no' => 2,
        ]);
        $artwork->update(['active_revision_id' => $revision->id]);

        $this->actingAs($adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['pending_approval'] === 0);

        app(ArtworkApprovalService::class)->reject($revision, $supplierUser, 'Revizyon gerekli');

        $this->actingAs($adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['pending_approval'] === 1);
    }

    public function test_dashboard_exposes_operational_pressure_metrics(): void
    {
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $supplier = Supplier::factory()->create();

        $oldOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $adminUser->id,
            'status' => 'active',
            'order_date' => now()->subDays(10)->toDateString(),
        ]);

        $freshOrder = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by' => $adminUser->id,
            'status' => 'active',
            'order_date' => now()->subDays(1)->toDateString(),
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $oldOrder->id,
            'artwork_status' => 'pending',
        ]);

        PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $freshOrder->id,
            'artwork_status' => 'uploaded',
        ]);

        $this->actingAs($adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['active_order_lines'] === 2)
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['pending_artwork'] === 1)
            ->assertViewHas('metrics', fn (array $metrics) => (float) $metrics['flow_pressure_pct'] === 50.0)
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['stalled_pending_artwork'] === 1)
            ->assertViewHas('metrics', fn (array $metrics) => $metrics['blocked_orders'] === 1);
    }

    public function test_dashboard_replaces_recent_lists_with_flow_graph_sections(): void
    {
        $adminUser = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($adminUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Son Yüklenen Artwork')
            ->assertDontSee('Son İndirmeler')
            ->assertSee('İş Akışı Görünümü')
            ->assertSee('Alarm Grafiği');
    }
}
