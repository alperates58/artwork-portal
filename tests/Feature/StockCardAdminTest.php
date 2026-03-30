<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ArtworkCategory;
use App\Models\StockCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StockCardAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_stock_card_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $stockCard = StockCard::factory()->create([
            'stock_code' => 'STK-9001',
            'stock_name' => 'Lider Onarım Kutusu',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.stock-cards.index'))
            ->assertOk()
            ->assertSee($stockCard->stock_code)
            ->assertSee($stockCard->stock_name);
    }

    public function test_admin_can_create_stock_card_and_reuse_existing_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $category = ArtworkCategory::factory()->create(['name' => 'Kutu']);

        $this->actingAs($admin)
            ->post(route('admin.stock-cards.store'), [
                'stock_code' => 'STK-1010',
                'stock_name' => 'Lider Krem Kutusu',
                'category_name' => 'Kutu',
            ])
            ->assertRedirect(route('admin.stock-cards.index'));

        $this->assertDatabaseHas('stock_cards', [
            'stock_code' => 'STK-1010',
            'stock_name' => 'Lider Krem Kutusu',
            'category_id' => $category->id,
        ]);
        $this->assertSame(1, ArtworkCategory::query()->where('name', 'Kutu')->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'stock_card.create',
        ]);
    }

    public function test_bulk_import_adds_stock_cards_and_reuses_existing_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        ArtworkCategory::factory()->create(['name' => 'Etiket']);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Stok Kodu', 'Stok Adı', 'Kategori'],
            ['STK-2010', 'Lider Etiket 1', 'Etiket'],
            ['STK-2020', 'Lider Etiket 2', 'Etiket'],
        ]);

        $path = storage_path('framework/testing/stock-card-import.xlsx');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
        $file = new UploadedFile($path, 'stock-card-import.xlsx', null, null, true);

        $this->actingAs($admin)
            ->post(route('admin.stock-cards.import'), ['file' => $file])
            ->assertRedirect(route('admin.stock-cards.import.form'));

        $this->assertDatabaseHas('stock_cards', ['stock_code' => 'STK-2010']);
        $this->assertDatabaseHas('stock_cards', ['stock_code' => 'STK-2020']);
        $this->assertSame(1, ArtworkCategory::query()->where('name', 'Etiket')->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'stock_card.import',
        ]);
    }
}
