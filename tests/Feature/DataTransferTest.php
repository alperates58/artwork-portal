<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\ArtworkRevision;
use App\Models\DataTransferRecord;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DataTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DataTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_export_payload_can_be_exported_multiple_times(): void
    {
        $admin = User::factory()->admin()->create();
        Supplier::factory()->create([
            'name' => 'Akdeniz Tedarik',
            'code' => 'TED-100',
        ]);

        $payload = [
            'fields' => [
                'suppliers' => ['name', 'code'],
            ],
            'only_new' => '1',
        ];

        $firstResponse = $this->actingAs($admin)->get(route('admin.data-transfer.export', $payload));
        $firstResponse->assertOk();

        $firstXml = simplexml_load_string($firstResponse->getContent());
        $this->assertCount(1, $firstXml->suppliers->supplier);

        $secondResponse = $this->actingAs($admin)->get(route('admin.data-transfer.export', $payload));
        $secondResponse->assertOk();

        $secondXml = simplexml_load_string($secondResponse->getContent());
        $this->assertCount(1, $secondXml->suppliers->supplier);

        Supplier::query()->where('code', 'TED-100')->update(['name' => 'Akdeniz Tedarik Güncel']);

        $thirdResponse = $this->actingAs($admin)->get(route('admin.data-transfer.export', $payload));
        $thirdResponse->assertOk();

        $thirdXml = simplexml_load_string($thirdResponse->getContent());
        $this->assertCount(1, $thirdXml->suppliers->supplier);
    }

    public function test_import_uses_supplier_and_order_number_together_for_duplicate_check(): void
    {
        $admin = User::factory()->admin()->create();
        $existingSupplier = Supplier::factory()->create([
            'name' => 'Mevcut Tedarikçi',
            'code' => 'TED-001',
        ]);

        PurchaseOrder::factory()->create([
            'supplier_id' => $existingSupplier->id,
            'order_no' => 'PO-2026-0001',
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<portal_export exported_at="2026-03-29T15:00:00+03:00" version="3" include_media="0">
    <selection_hash>demo</selection_hash>
    <suppliers>
        <supplier entity_key="supplier:TED-002">
            <name>Yeni Tedarikçi</name>
            <code>TED-002</code>
            <is_active>1</is_active>
        </supplier>
    </suppliers>
    <purchase_orders>
        <purchase_order entity_key="order:TED-002|PO-2026-0001">
            <supplier_ref>TED-002</supplier_ref>
            <order_no>PO-2026-0001</order_no>
            <status>active</status>
            <lines type="json">[{"line_no":1,"product_code":"STK-1","description":"Deneme","quantity":10,"shipped_quantity":0,"unit":"Adet","artwork_status":"pending","notes":null}]</lines>
        </purchase_order>
    </purchase_orders>
</portal_export>
XML;

        $file = UploadedFile::fake()->createWithContent('import.xml', $xml);

        $response = $this->actingAs($admin)->post(route('admin.data-transfer.import'), [
            'xml_file' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('suppliers', ['code' => 'TED-002']);
        $this->assertDatabaseHas('purchase_orders', [
            'order_no' => 'PO-2026-0001',
            'supplier_id' => Supplier::query()->where('code', 'TED-002')->value('id'),
        ]);
        $this->assertEquals(2, PurchaseOrder::count());
    }

    public function test_import_download_logs_uses_fallback_ip_address_when_source_payload_has_none(): void
    {
        $admin = User::factory()->admin()->create();
        $downloader = User::factory()->create([
            'email' => 'indirici@example.com',
        ]);
        $supplier = Supplier::factory()->create([
            'code' => 'TED-777',
        ]);
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'order_no' => 'PO-2026-0777',
            'created_by' => $admin->id,
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $order->id,
            'line_no' => 7,
        ]);
        $artwork = Artwork::factory()->create([
            'order_line_id' => $line->id,
        ]);
        $revision = ArtworkRevision::factory()->create([
            'artwork_id' => $artwork->id,
            'revision_no' => 1,
        ]);

        $artwork->update(['active_revision_id' => $revision->id]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<portal_export exported_at="2026-03-29T20:00:00+03:00" version="3" include_media="0">
    <selection_hash>demo</selection_hash>
    <download_logs>
        <log entity_key="revision:TED-777|PO-2026-0777|7|1|indirici@example.com|20260329120000">
            <revision_ref>revision:TED-777|PO-2026-0777|7|1</revision_ref>
            <user_email>indirici@example.com</user_email>
            <supplier_ref>TED-777</supplier_ref>
            <downloaded_at>2026-03-29T12:00:00+03:00</downloaded_at>
        </log>
    </download_logs>
</portal_export>
XML;

        $file = UploadedFile::fake()->createWithContent('download-logs.xml', $xml);

        $response = $this->actingAs($admin)->post(route('admin.data-transfer.import'), [
            'xml_file' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('artwork_download_logs', [
            'artwork_revision_id' => $revision->id,
            'user_id' => $downloader->id,
            'supplier_id' => $supplier->id,
            'ip_address' => '0.0.0.0',
        ]);
    }

    public function test_destroy_imported_resets_import_tracking_and_allows_reimporting_the_same_file(): void
    {
        $admin = User::factory()->admin()->create();

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<portal_export exported_at="2026-03-29T21:00:00+03:00" version="3" include_media="0">
    <selection_hash>demo</selection_hash>
    <suppliers>
        <supplier entity_key="supplier:TED-900">
            <name>Yeni Tedarikçi</name>
            <code>TED-900</code>
            <email>tedarikci@example.com</email>
            <is_active>1</is_active>
        </supplier>
    </suppliers>
    <users>
        <user entity_key="user:portal@example.com">
            <name>Portal Kullanıcısı</name>
            <email>portal@example.com</email>
            <role>supplier</role>
            <is_active>1</is_active>
            <supplier_ref>TED-900</supplier_ref>
        </user>
    </users>
    <purchase_orders>
        <purchase_order entity_key="order:TED-900|PO-2026-0900">
            <supplier_ref>TED-900</supplier_ref>
            <order_no>PO-2026-0900</order_no>
            <status>active</status>
            <lines type="json">[{"line_no":1,"product_code":"STK-900","description":"Deneme siparişi","quantity":10,"shipped_quantity":0,"unit":"Adet","artwork_status":"pending","notes":null}]</lines>
        </purchase_order>
    </purchase_orders>
</portal_export>
XML;

        $file = UploadedFile::fake()->createWithContent('reimport.xml', $xml);

        $firstImportResponse = $this->actingAs($admin)->post(route('admin.data-transfer.import'), [
            'xml_file' => $file,
        ]);

        $firstImportResponse->assertSessionHasNoErrors();
        $firstImportResponse->assertSessionHas('success');

        $this->assertDatabaseHas('suppliers', ['code' => 'TED-900']);
        $this->assertDatabaseHas('users', ['email' => 'portal@example.com']);
        $this->assertDatabaseHas('purchase_orders', ['order_no' => 'PO-2026-0900']);
        $this->assertGreaterThan(0, DataTransferRecord::query()->where('direction', 'import')->count());

        $destroyResponse = $this->actingAs($admin)->delete(route('admin.data-transfer.destroy-imported'));
        $destroyResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('suppliers', ['code' => 'TED-900']);
        $this->assertDatabaseMissing('users', ['email' => 'portal@example.com']);
        $this->assertDatabaseMissing('purchase_orders', ['order_no' => 'PO-2026-0900']);
        $this->assertSame(0, DataTransferRecord::query()->where('direction', 'import')->count());
        $this->assertFalse(SystemSetting::query()->where('group', 'data_transfer')->where('key', 'imported_ids')->exists());

        $secondFile = UploadedFile::fake()->createWithContent('reimport.xml', $xml);

        $secondImportResponse = $this->actingAs($admin)->post(route('admin.data-transfer.import'), [
            'xml_file' => $secondFile,
        ]);

        $secondImportResponse->assertSessionHasNoErrors();
        $secondImportResponse->assertSessionHas('success');

        $this->assertDatabaseHas('suppliers', ['code' => 'TED-900']);
        $this->assertDatabaseHas('users', ['email' => 'portal@example.com']);
        $this->assertDatabaseHas('purchase_orders', ['order_no' => 'PO-2026-0900']);

        $importedCount = app(DataTransferService::class)->buildExportOptions()['imported_count'];

        $this->assertSame(1, $importedCount['suppliers']);
        $this->assertSame(1, $importedCount['users']);
        $this->assertSame(1, $importedCount['purchase_orders']);
    }

    public function test_import_recreates_missing_records_even_when_old_import_tracking_exists(): void
    {
        $admin = User::factory()->admin()->create();

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<portal_export exported_at="2026-03-29T22:00:00+03:00" version="3" include_media="0">
    <selection_hash>demo</selection_hash>
    <suppliers>
        <supplier entity_key="supplier:TED-901">
            <name>Yeniden Oluşacak Tedarikçi</name>
            <code>TED-901</code>
            <email>yeniden@example.com</email>
            <is_active>1</is_active>
        </supplier>
    </suppliers>
    <users>
        <user entity_key="user:yeniden-kullanici@example.com">
            <name>Yeniden Kullanıcı</name>
            <email>yeniden-kullanici@example.com</email>
            <role>supplier</role>
            <is_active>1</is_active>
            <supplier_ref>TED-901</supplier_ref>
        </user>
    </users>
    <purchase_orders>
        <purchase_order entity_key="order:TED-901|PO-2026-0901">
            <supplier_ref>TED-901</supplier_ref>
            <order_no>PO-2026-0901</order_no>
            <status>active</status>
            <lines type="json">[{"line_no":1,"product_code":"STK-901","description":"Tekrar import","quantity":5,"shipped_quantity":0,"unit":"Adet","artwork_status":"pending","notes":null}]</lines>
        </purchase_order>
    </purchase_orders>
</portal_export>
XML;

        $firstFile = UploadedFile::fake()->createWithContent('stale-tracking.xml', $xml);

        $firstImportResponse = $this->actingAs($admin)->post(route('admin.data-transfer.import'), [
            'xml_file' => $firstFile,
        ]);

        $firstImportResponse->assertSessionHasNoErrors();
        $firstImportResponse->assertSessionHas('success');
        $this->assertGreaterThan(0, DataTransferRecord::query()->where('direction', 'import')->count());

        PurchaseOrderLine::query()->delete();
        PurchaseOrder::query()->delete();
        User::query()->where('email', 'yeniden-kullanici@example.com')->delete();
        Supplier::query()->where('code', 'TED-901')->forceDelete();
        SystemSetting::query()
            ->where('group', 'data_transfer')
            ->where('key', 'imported_ids')
            ->delete();

        $secondFile = UploadedFile::fake()->createWithContent('stale-tracking.xml', $xml);

        $secondImportResponse = $this->actingAs($admin)->post(route('admin.data-transfer.import'), [
            'xml_file' => $secondFile,
        ]);

        $secondImportResponse->assertSessionHasNoErrors();
        $secondImportResponse->assertSessionHas('success');

        $this->assertDatabaseHas('suppliers', ['code' => 'TED-901']);
        $this->assertDatabaseHas('users', ['email' => 'yeniden-kullanici@example.com']);
        $this->assertDatabaseHas('purchase_orders', ['order_no' => 'PO-2026-0901']);

        $importedCount = app(DataTransferService::class)->buildExportOptions()['imported_count'];

        $this->assertSame(1, $importedCount['suppliers']);
        $this->assertSame(1, $importedCount['users']);
        $this->assertSame(1, $importedCount['purchase_orders']);
    }
}
