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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RevisionRequestVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_order_views_show_revision_request_owner_and_note(): void
    {
        Notification::fake();

        [$order, $supplierUser] = $this->createRejectedRevisionScenario();
        $adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->actingAs($adminUser)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Talep eden:')
            ->assertSee($supplierUser->name)
            ->assertSee('Renk tonu güncellensin.');

        $this->actingAs($adminUser)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Revizyon talebi')
            ->assertSee($supplierUser->name)
            ->assertSee('Renk tonu güncellensin.');
    }

    public function test_supplier_order_views_show_revision_request_context(): void
    {
        Notification::fake();

        [$order, $supplierUser] = $this->createRejectedRevisionScenario();

        $this->actingAs($supplierUser)
            ->get(route('portal.orders.index'))
            ->assertOk()
            ->assertSee('1 revizyon bekliyor')
            ->assertSee('Revizyon Gerekli');

        $this->actingAs($supplierUser)
            ->get(route('portal.orders.show', $order))
            ->assertOk()
            ->assertSee('Revizyon talebi')
            ->assertSee($supplierUser->name)
            ->assertSee('Renk tonu güncellensin.');
    }

    /**
     * @return array{0: PurchaseOrder, 1: User}
     */
    private function createRejectedRevisionScenario(): array
    {
        $supplier = Supplier::factory()->create();
        $supplierUser = User::factory()->create([
            'role' => UserRole::SUPPLIER,
            'supplier_id' => $supplier->id,
            'name' => 'Ayşe Tedarikçi',
        ]);

        SupplierUser::firstOrCreate([
            'supplier_id' => $supplier->id,
            'user_id' => $supplierUser->id,
        ], [
            'is_primary' => true,
            'can_download' => true,
            'can_approve' => true,
        ]);

        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
        ]);

        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'artwork_status' => 'uploaded',
            'product_code' => 'STK-REV-01',
        ]);

        $artwork = Artwork::factory()->create([
            'order_line_id' => $line->id,
        ]);

        $revision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'is_active' => true,
            'revision_no' => 1,
            'original_filename' => 'etiket-revizyon.pdf',
        ]);

        $artwork->update([
            'active_revision_id' => $revision->id,
        ]);

        app(ArtworkApprovalService::class)->reject($revision, $supplierUser, 'Renk tonu güncellensin.');

        return [$order->fresh(), $supplierUser];
    }
}
