<?php

namespace Database\Seeders;

use App\Models\ArtworkCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Tedarikçiler ──────────────────────────────────────────────
        $supplierData = [
            ['name' => 'Azim Ambalaj',   'code' => 'AZM', 'email' => 'info@azimambalaj.com'],
            ['name' => 'Has Sistem',      'code' => 'HAS', 'email' => 'info@hassistem.com'],
            ['name' => 'Doruk Plastik',   'code' => 'DRK', 'email' => 'info@dorukplastik.com'],
            ['name' => 'Kıral Etiket',    'code' => 'KRL', 'email' => 'info@kiraletiket.com'],
        ];

        $suppliers = [];
        foreach ($supplierData as $data) {
            $suppliers[] = Supplier::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }

        // ── Artwork Kategorileri ───────────────────────────────────────
        $categoryNames = ['Aluminyum Tüp', 'İç Kutu', 'Dış Kutu', 'Etiket', 'Poşet', 'Karton'];
        foreach ($categoryNames as $name) {
            ArtworkCategory::firstOrCreate(['name' => $name]);
        }

        // ── Demo Siparişler ────────────────────────────────────────────
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->command->warn('Admin kullanıcı bulunamadı, siparişler oluşturulmuyor.');
            return;
        }

        $products = [
            ['ALU-001', 'Aluminyum Tüp 50ml', 8, 12],
            ['ALU-002', 'Aluminyum Tüp 100ml', 5, 10],
            ['KTN-001', 'İç Kutu A5', 10, 20],
            ['KTN-002', 'Dış Kutu B3', 6, 14],
            ['ETK-001', 'Etiket 90x120mm', 15, 30],
            ['ETK-002', 'Etiket 50x80mm', 12, 25],
            ['PST-001', 'Poşet Standart', 8, 18],
            ['KRT-001', 'Karton Kapak', 4, 9],
        ];

        $statuses        = ['active', 'active', 'active', 'completed', 'draft'];
        $artworkStatuses = ['pending', 'pending', 'uploaded', 'uploaded', 'approved', 'revision'];

        $orderCount = 0;
        foreach ($suppliers as $si => $supplier) {
            for ($month = 5; $month >= 0; $month--) {
                $ordersThisMonth = rand(2, 4);
                for ($o = 0; $o < $ordersThisMonth; $o++) {
                    $orderDate = now()->subMonths($month)->subDays(rand(0, 20));
                    $status    = $statuses[array_rand($statuses)];

                    $order = PurchaseOrder::create([
                        'order_no'    => strtoupper($supplier->code) . '-' . $orderDate->format('Ym') . '-' . str_pad(++$orderCount, 3, '0', STR_PAD_LEFT),
                        'supplier_id' => $supplier->id,
                        'order_date'  => $orderDate,
                        'due_date'    => $orderDate->copy()->addDays(rand(14, 45)),
                        'status'      => $status,
                        'created_by'  => $admin->id,
                    ]);

                    $lineCount = rand(2, 5);
                    $shuffled  = collect($products)->shuffle()->take($lineCount);

                    foreach ($shuffled->values() as $li => $product) {
                        $artworkStatus = $status === 'completed'
                            ? 'approved'
                            : $artworkStatuses[array_rand($artworkStatuses)];

                        PurchaseOrderLine::create([
                            'purchase_order_id' => $order->id,
                            'line_no'           => $li + 1,
                            'product_code'      => $product[0],
                            'description'       => $product[1],
                            'quantity'          => $product[2],
                            'unit'              => 'adet',
                            'artwork_status'    => $artworkStatus,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Demo veriler oluşturuldu: ' . count($suppliers) . ' tedarikçi, ' . count($categoryNames) . ' kategori, ' . $orderCount . ' sipariş.');
    }
}
