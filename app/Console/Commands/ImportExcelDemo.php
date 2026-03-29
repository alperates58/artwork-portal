<?php

namespace App\Console\Commands;

use App\Enums\ArtworkStatus;
use App\Models\Artwork;
use App\Models\ArtworkCategory;
use App\Models\ArtworkGallery;
use App\Models\ArtworkRevision;
use App\Models\ArtworkTag;
use App\Models\DataTransferRecord;
use App\Models\Department;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ImportExcelDemo extends Command
{
    protected $signature   = 'demo:import-excel {--file=storage/app/demo.xlsx : Excel dosyası yolu}';
    protected $description = 'Excel\'deki demo siparişleri içe aktar, eski verileri sil, artwork oluştur';

    private string $disk = 'local';

    public function handle(): int
    {
        $this->info('Demo verileri hazırlanıyor...');

        // ── 1. Eski verileri temizle ───────────────────────────────────
        $this->clearOldData();

        // ── 2. Excel'i oku ────────────────────────────────────────────
        $rows = $this->readExcel(base_path($this->option('file')));
        if ($rows === []) {
            $this->error('Excel okunamadı veya boş.');
            return 1;
        }
        $this->info('Excel\'den ' . count($rows) . ' satır okundu.');

        // ── 3. Kategoriler ve etiketler ───────────────────────────────
        $categories = $this->seedCategories($rows);
        $tags       = $this->seedTags($rows);

        // ── 4. Tedarikçiler ve kullanıcılar ───────────────────────────
        $suppliers = $this->seedSuppliers($rows);

        // ── 5. Admin kullanıcısını bul ────────────────────────────────
        $admin = User::query()->where('role', 'admin')->first();

        // ── 6. Siparişler ve satırlar ─────────────────────────────────
        $this->seedOrders($rows, $suppliers, $categories, $tags, $admin);

        // ── 7. Artwork galerisi ───────────────────────────────────────
        $this->seedGallery($categories, $tags, $admin);

        $this->newLine();
        $this->info('✓ Demo verileri başarıyla içe aktarıldı!');
        return 0;
    }

    // ──────────────────────────────────────────────────────────────────
    // Temizlik
    // ──────────────────────────────────────────────────────────────────

    private function clearOldData(): void
    {
        $this->info('Eski veriler siliniyor...');

        DB::transaction(function () {
            // Artwork revisions
            $revisionIds = ArtworkRevision::pluck('id');
            foreach ($revisionIds as $id) {
                $revision = ArtworkRevision::find($id);
                if ($revision?->spaces_path) {
                    try { Storage::disk($this->disk)->delete($revision->spaces_path); } catch (\Throwable) {}
                }
            }
            ArtworkRevision::query()->delete();

            // Gallery
            $galleryItems = ArtworkGallery::all();
            foreach ($galleryItems as $item) {
                if ($item->file_path) {
                    try { Storage::disk($this->disk)->delete($item->file_path); } catch (\Throwable) {}
                }
            }
            ArtworkGallery::query()->delete();

            // Artworks
            Artwork::query()->delete();

            // Sipariş satırları ve siparişler
            PurchaseOrderLine::query()->delete();
            PurchaseOrder::query()->delete();

            // Tedarikçi kullanıcıları
            DB::table('supplier_users')->delete();

            // Admin olmayan kullanıcılar
            User::query()->where('role', '!=', 'admin')->delete();

            // Tedarikçiler
            Supplier::withTrashed()->forceDelete();

            // Kategoriler ve etiketler
            ArtworkCategory::query()->delete();
            ArtworkTag::query()->delete();

            // DataTransferRecord
            DataTransferRecord::query()->delete();
        });

        $this->info('  ✓ Eski veriler silindi.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Excel okuma
    // ──────────────────────────────────────────────────────────────────

    private function readExcel(string $path): array
    {
        if (! file_exists($path)) {
            $this->error("Dosya bulunamadı: $path");
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            $this->error('Excel dosyası açılamadı.');
            return [];
        }

        // Shared strings
        $strings = [];
        $ssXml   = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml) {
            $ss = simplexml_load_string($ssXml);
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $strings[] = (string) $si->t;
                } elseif (isset($si->r)) {
                    $val = '';
                    foreach ($si->r as $r) {
                        $val .= (string) $r->t;
                    }
                    $strings[] = $val;
                } else {
                    $strings[] = '';
                }
            }
        }

        // Sheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sheet    = simplexml_load_string($sheetXml);
        $rawRows  = [];

        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $t         = (string) ($cell['t'] ?? '');
                $v         = (string) ($cell->v ?? '');
                $rowData[] = ($t === 's') ? ($strings[(int) $v] ?? '') : $v;
            }
            $rawRows[] = $rowData;
        }
        $zip->close();

        // Header satırını atla
        $headers = array_shift($rawRows);
        // [0]=SiparişNo [1]=Tedarikçi [2]=SiparişTarihi [3]=StokKodu [4]=StokİSmi [5]=Etiketler [6]=Kategori [7]=SiparişMiktarı

        $rows = [];
        foreach ($rawRows as $row) {
            if (empty(array_filter($row))) {
                continue;
            }
            $rows[] = [
                'order_no'    => trim($row[0] ?? ''),
                'supplier'    => trim($row[1] ?? ''),
                'order_date'  => $this->excelDateToString((int) ($row[2] ?? 0)),
                'stock_code'  => trim($row[3] ?? ''),
                'stock_name'  => trim($row[4] ?? ''),
                'tag'         => trim($row[5] ?? ''),
                'category'    => trim($row[6] ?? ''),
                'quantity'    => (int) ($row[7] ?? 0),
            ];
        }

        return $rows;
    }

    /** Excel seri numarasını Y-m-d stringine çevirir */
    private function excelDateToString(int $serial): string
    {
        if ($serial <= 0) {
            return now()->toDateString();
        }
        // Excel 1900 sistem: seri 1 = 1900-01-01, ancak 1900 yanlış artık yılı bug
        $unixTs = ($serial - 25569) * 86400;
        return date('Y-m-d', $unixTs);
    }

    // ──────────────────────────────────────────────────────────────────
    // Kategoriler
    // ──────────────────────────────────────────────────────────────────

    private function seedCategories(array $rows): array
    {
        $categoryNames = collect($rows)->pluck('category')->filter()->unique()->sort()->values();
        $categories    = [];

        foreach ($categoryNames as $name) {
            $category              = ArtworkCategory::query()->firstOrCreate(['name' => $name]);
            $categories[$name]     = $category;
        }

        $this->info('  ✓ ' . count($categories) . ' kategori oluşturuldu.');
        return $categories;
    }

    // ──────────────────────────────────────────────────────────────────
    // Etiketler
    // ──────────────────────────────────────────────────────────────────

    private function seedTags(array $rows): array
    {
        $tagNames = collect($rows)->pluck('tag')->filter()->unique()->sort()->values();
        $tags     = [];

        foreach ($tagNames as $name) {
            $tag         = ArtworkTag::query()->firstOrCreate(['name' => $name]);
            $tags[$name] = $tag;
        }

        $this->info('  ✓ ' . count($tags) . ' etiket oluşturuldu.');
        return $tags;
    }

    // ──────────────────────────────────────────────────────────────────
    // Tedarikçiler
    // ──────────────────────────────────────────────────────────────────

    private function seedSuppliers(array $rows): array
    {
        $supplierNames = collect($rows)->pluck('supplier')->filter()->unique()->sort()->values();

        $supplierDefs = [
            'TUZLA OLUKLU MUK. SAN. VE TİC. A.Ş. (TL)' => [
                'code'  => 'TUZ',
                'email' => 'info@tuzlaoluklu.com',
                'phone' => '+90 216 391 00 00',
            ],
            'AZİM AMBALAJ SANAYİ VE TİCARET ANONİM ŞİRKETİ' => [
                'code'  => 'AZM',
                'email' => 'info@azimambalaj.com',
                'phone' => '+90 212 555 00 01',
            ],
            'DÖRTER MATBAACILIK SAN. VE TİC. A.Ş. (TL)' => [
                'code'  => 'DRT',
                'email' => 'info@dortermatbaa.com',
                'phone' => '+90 212 555 00 02',
            ],
            'GAMA ETİKET SAN. VE TİC. A.Ş. (TL)' => [
                'code'  => 'GAM',
                'email' => 'info@gamaetiket.com',
                'phone' => '+90 212 555 00 03',
            ],
        ];

        $suppliers = [];

        $usedCodes = [];

        foreach ($supplierNames as $name) {
            $def      = $supplierDefs[$name] ?? [];
            $baseCode = $def['code'] ?? strtoupper(
                substr(preg_replace('/[^A-Za-z]/u', '', iconv('UTF-8', 'ASCII//TRANSLIT', $name)), 0, 4)
            );

            // Benzersiz kod üret
            $code = $baseCode;
            $suffix = 2;
            while (in_array($code, $usedCodes, true)) {
                $code = substr($baseCode, 0, 3) . $suffix++;
            }
            $usedCodes[] = $code;

            $supplier = Supplier::create([
                'name'      => $name,
                'code'      => $code,
                'email'     => $def['email'] ?? strtolower($code) . '@demo.com',
                'phone'     => $def['phone'] ?? null,
                'is_active' => true,
            ]);

            // Tedarikçi kullanıcısı oluştur
            $userName  = $this->supplierShortName($name);
            $userEmail = strtolower($code) . '@demo.com';

            $user = User::create([
                'name'     => $userName,
                'email'    => $userEmail,
                'password' => Hash::make('Demo1234!'),
                'role'     => 'supplier',
                'is_active'=> true,
            ]);

            DB::table('supplier_users')->insert([
                'supplier_id'  => $supplier->id,
                'user_id'      => $user->id,
                'is_primary'   => true,
                'can_download' => true,
                'can_approve'  => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $suppliers[$name] = $supplier;
        }

        $this->info('  ✓ ' . count($suppliers) . ' tedarikçi ve kullanıcı oluşturuldu.');
        return $suppliers;
    }

    private function supplierShortName(string $name): string
    {
        return match (true) {
            str_contains($name, 'TUZLA')  => 'Tuzla Oluklu',
            str_contains($name, 'AZİM')   => 'Azim Ambalaj',
            str_contains($name, 'DÖRTER') => 'Dörter Matbaacılık',
            str_contains($name, 'GAMA')   => 'Gama Etiket',
            default                       => Str::title(Str::words($name, 3, '')),
        };
    }

    // ──────────────────────────────────────────────────────────────────
    // Siparişler
    // ──────────────────────────────────────────────────────────────────

    private function seedOrders(array $rows, array $suppliers, array $categories, array $tags, ?User $admin): void
    {
        // Siparişleri grupla: order_no + supplier + order_date
        $grouped = collect($rows)->groupBy(fn ($r) => $r['order_no'] . '|' . $r['supplier']);

        $orderCount   = 0;
        $lineCount    = 0;
        $artworkCount = 0;
        $manualCount  = 0;
        $lineIndex    = 0;

        // Artwork status dağılımı (tekrarlayan pattern)
        // pending:pending:uploaded:approved:pending:revision:approved:uploaded
        $statusPattern = [
            ArtworkStatus::PENDING,
            ArtworkStatus::PENDING,
            ArtworkStatus::UPLOADED,
            ArtworkStatus::APPROVED,
            ArtworkStatus::PENDING,
            ArtworkStatus::REVISION,
            ArtworkStatus::APPROVED,
            ArtworkStatus::UPLOADED,
        ];

        // Manuel işaretlenecek pozisyonlar (her 7 pending satırdan 2'si)
        $manualNotes = [
            'Bu ürünün tasarımı daha önce mail ile paylaşılmıştı, yeni siparişte aynı çalışma kullanılacak.',
            'Tedarikçi baskı dosyasını fiziksel olarak teslim etti.',
            'Önceki sipariş artwork\'ü aynen devam ediyor.',
        ];

        foreach ($grouped as $key => $orderRows) {
            $firstRow    = $orderRows->first();
            $supplierObj = $suppliers[$firstRow['supplier']] ?? null;

            if (! $supplierObj) {
                continue;
            }

            $orderDate = $firstRow['order_date'];
            $order = PurchaseOrder::create([
                'supplier_id' => $supplierObj->id,
                'order_no'    => $firstRow['order_no'],
                'status'      => 'active',
                'order_date'  => $orderDate,
                'due_date'    => date('Y-m-d', strtotime($orderDate . ' +30 days')),
                'created_by'  => $admin?->id,
            ]);

            $orderCount++;
            $lineNo = 1;
            $pendingInOrder = 0;

            foreach ($orderRows as $row) {
                $artworkStatus = $statusPattern[$lineIndex % count($statusPattern)];
                $lineIndex++;

                $line = PurchaseOrderLine::create([
                    'purchase_order_id' => $order->id,
                    'line_no'           => $lineNo++,
                    'product_code'      => $row['stock_code'],
                    'description'       => $row['stock_name'],
                    'quantity'          => $row['quantity'],
                    'shipped_quantity'  => 0,
                    'unit'              => 'AD',
                    'artwork_status'    => $artworkStatus,
                    'notes'             => null,
                ]);

                $lineCount++;

                if ($artworkStatus === ArtworkStatus::PENDING) {
                    $pendingInOrder++;
                    // Her 3 pending satırdan 1'ini manuel işaretle
                    if ($pendingInOrder % 3 === 0 && $admin) {
                        $note = $manualNotes[($pendingInOrder / 3 - 1) % count($manualNotes)];
                        DB::table('purchase_order_lines')
                            ->where('id', $line->id)
                            ->update([
                                'manual_artwork_completed_at'  => date('Y-m-d H:i:s', strtotime($orderDate . ' +2 days')),
                                'manual_artwork_completed_by'  => $admin->id,
                                'manual_artwork_note'          => $note,
                            ]);
                        $manualCount++;
                    }
                }

                // Artwork oluştur (pending değilse)
                if ($artworkStatus !== ArtworkStatus::PENDING) {
                    $artwork = Artwork::create([
                        'order_line_id' => $line->id,
                        'title'         => Str::limit($row['stock_name'], 80),
                    ]);

                    $this->createDemoRevision(
                        artwork: $artwork,
                        row: $row,
                        admin: $admin,
                        status: $artworkStatus,
                        orderDate: $orderDate,
                    );

                    $artworkCount++;
                }
            }
        }

        $this->info("  ✓ $orderCount sipariş, $lineCount satır, $artworkCount artwork, $manualCount manuel oluşturuldu.");
    }

    // ──────────────────────────────────────────────────────────────────
    // Demo Artwork Revision
    // ──────────────────────────────────────────────────────────────────

    private function createDemoRevision(
        Artwork $artwork,
        array $row,
        ?User $admin,
        ArtworkStatus $status,
        string $orderDate,
    ): ArtworkRevision {
        $filename  = Str::slug(Str::limit($row['stock_name'], 40)) . '.png';
        $path      = 'demo/artworks/' . Str::uuid() . '.png';
        $imageData = $this->generateDemoImage($row['stock_name'], $row['category'], $row['tag']);

        Storage::disk($this->disk)->put($path, $imageData);

        $approvalStatus = match ($status) {
            ArtworkStatus::APPROVED  => 'approved',
            ArtworkStatus::REVISION  => 'rejected',
            default                  => 'pending',
        };

        // Upload tarihi: sipariş tarihinden 3-14 gün sonra
        $uploadOffsetDays = rand(3, 14);
        $uploadAt = date('Y-m-d H:i:s', strtotime($orderDate . " +{$uploadOffsetDays} days") + rand(28800, 61200));
        $approvedAt = $status === ArtworkStatus::APPROVED
            ? date('Y-m-d H:i:s', strtotime($uploadAt . ' +2 days'))
            : null;

        $revision = new ArtworkRevision([
            'artwork_id'        => $artwork->id,
            'revision_no'       => 1,
            'original_filename' => $filename,
            'stored_filename'   => basename($path),
            'spaces_path'       => $path,
            'mime_type'         => 'image/png',
            'file_size'         => strlen($imageData),
            'is_active'         => true,
            'uploaded_by'       => $admin?->id,
            'approval_status'   => $approvalStatus,
            'notes'             => $status === ArtworkStatus::REVISION ? 'Renk tonları düzeltilmeli.' : null,
            'approved_at'       => $approvedAt,
        ]);

        $revision->timestamps = false;
        $revision->created_at = $uploadAt;
        $revision->updated_at = $uploadAt;
        $revision->save();

        return $revision;
    }

    // ──────────────────────────────────────────────────────────────────
    // Artwork Galerisi
    // ──────────────────────────────────────────────────────────────────

    private function seedGallery(array $categories, array $tags, ?User $admin): void
    {
        $galleryItems = [
            ['name' => 'Koli Tasarım Şablonu - Standart', 'category' => 'KOLI',     'tag' => 'AIRWAY'],
            ['name' => 'Etiket Masterfile - Kronik',       'category' => 'ETIKET',   'tag' => 'CHRONIC MEN'],
            ['name' => 'İç Kutu - Breesal Serisi',         'category' => 'ICKUTU',   'tag' => 'BREESAL'],
            ['name' => 'Seperatör Masterfile',             'category' => 'SEPERATOR','tag' => 'DAYCARE'],
            ['name' => 'Koli Tasarım - Bandido',           'category' => 'KOLI',     'tag' => 'BANDIDO'],
            ['name' => 'Etiket - 4Ward Serisi',            'category' => 'ETIKET',   'tag' => '4WARD'],
            ['name' => 'İç Kutu - Bright AF',              'category' => 'KOLI',     'tag' => 'BRIGHT'],
            ['name' => 'Koli Şablonu - Air Classic',       'category' => 'KOLI',     'tag' => 'AIR'],
        ];

        $count = 0;
        foreach ($galleryItems as $item) {
            $categoryObj = $categories[$item['category']] ?? null;
            $tagObj      = $tags[$item['tag']] ?? null;

            $imageData = $this->generateDemoImage($item['name'], $item['category'], $item['tag']);
            $path      = 'gallery/demo/' . Str::uuid() . '.png';
            Storage::disk($this->disk)->put($path, $imageData);

            $gallery = ArtworkGallery::create([
                'name'      => $item['name'],
                'stock_code' => null,
                'category_id' => $categoryObj?->id,
                'file_path'  => $path,
                'file_disk'  => $this->disk,
                'file_size'  => strlen($imageData),
                'file_type'  => 'image/png',
                'uploaded_by' => $admin?->id,
            ]);

            if ($tagObj) {
                $gallery->tags()->sync([$tagObj->id]);
            }
            $count++;
        }

        $this->info("  ✓ $count galeri kaydı oluşturuldu.");
    }

    // ──────────────────────────────────────────────────────────────────
    // Demo PNG Üretici (GD)
    // ──────────────────────────────────────────────────────────────────

    private function generateDemoImage(string $title, string $category, string $brand): string
    {
        $w = 800;
        $h = 600;
        $img = imagecreatetruecolor($w, $h);

        // Kategori bazlı arka plan rengi
        $bgColors = [
            'KOLI'      => [34, 139, 34],
            'ETIKET'    => [70, 130, 180],
            'ICKUTU'    => [178, 34, 34],
            'SEPERATOR' => [148, 0, 211],
        ];

        [$r, $g, $b] = $bgColors[$category] ?? [80, 80, 80];

        $bg      = imagecolorallocate($img, $r, $g, $b);
        $white   = imagecolorallocate($img, 255, 255, 255);
        $lightBg = imagecolorallocate($img, min(255, $r + 60), min(255, $g + 60), min(255, $b + 60));
        $dark    = imagecolorallocate($img, 30, 30, 30);
        $gray    = imagecolorallocate($img, 200, 200, 200);

        imagefill($img, 0, 0, $bg);

        // Dekoratif border
        imagerectangle($img, 10, 10, $w - 10, $h - 10, $white);
        imagerectangle($img, 14, 14, $w - 14, $h - 14, $lightBg);

        // Üst bant
        imagefilledrectangle($img, 10, 10, $w - 10, 80, $white);

        // Kategori etiketi
        imagefilledrectangle($img, 20, 20, 160, 70, $bg);
        imagestring($img, 5, 30, 38, $category, $white);

        // Marka adı
        imagestring($img, 5, 180, 30, strtoupper($brand), $dark);

        // Ürün ismi (birden fazla satır)
        $lines = str_split($title, 60);
        $yStart = 120;
        foreach (array_slice($lines, 0, 4) as $i => $line) {
            imagestring($img, 4, 40, $yStart + ($i * 24), $line, $white);
        }

        // Alt bant
        imagefilledrectangle($img, 10, $h - 60, $w - 10, $h - 10, $white);
        imagestring($img, 3, 30, $h - 46, 'DEMO | Artwork Portal', $dark);
        imagestring($img, 3, $w - 200, $h - 46, date('Y-m-d'), $dark);

        // Watermark diagonal
        imagestringup($img, 2, (int)($w / 2), (int)($h / 2) + 80, 'DEMO ARTWORK', $lightBg);

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return $data;
    }
}
