<?php

namespace App\Services;

use App\Models\StockCard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockCardBulkImportService
{
    public function __construct(
        private ArtworkCategoryService $categories,
    ) {}

    public function streamTemplate(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stok Kartları');

        $headers = [
            'A1' => 'Stok Kodu *',
            'B1' => 'Stok Adı *',
            'C1' => 'Kategori *',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $sheet->getStyle('A1:C1')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFE0B2');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('A2', 'STK-1001');
        $sheet->setCellValue('B2', 'Lider Nemlendirici Kutu');
        $sheet->setCellValue('C2', 'Kutu');

        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(24);

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="stok_karti_import_sablonu.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows) || count($rows) < 2) {
            return ['imported' => 0, 'skipped' => 0, 'error_count' => 0, 'errors' => []];
        }

        $header = array_map(fn ($value) => trim((string) $value), $rows[0]);
        $columnMap = $this->mapColumns($header);

        if (! isset($columnMap['stock_code'], $columnMap['stock_name'], $columnMap['category'])) {
            throw new \InvalidArgumentException(
                '"Stok Kodu", "Stok Adı" ve "Kategori" sütunları bulunamadı. Lütfen şablonu kullanın.'
            );
        }

        $errors = [];
        $imported = 0;
        $skipped = 0;
        $seenCodes = [];

        DB::transaction(function () use ($rows, $columnMap, &$errors, &$imported, &$skipped, &$seenCodes) {
            foreach (array_slice($rows, 1) as $index => $row) {
                $rowNumber = $index + 2;
                $filled = array_filter(array_map(fn ($value) => trim((string) $value), $row));

                if (empty($filled)) {
                    continue;
                }

                $stockCode = $this->normalizeStockCode((string) ($row[$columnMap['stock_code']] ?? ''));
                $stockName = trim((string) ($row[$columnMap['stock_name']] ?? ''));
                $categoryName = trim((string) ($row[$columnMap['category']] ?? ''));

                if ($stockCode === '') {
                    $errors[] = ['row' => $rowNumber, 'code' => '—', 'message' => 'Stok kodu boş bırakılamaz.'];
                    continue;
                }

                if ($stockName === '') {
                    $errors[] = ['row' => $rowNumber, 'code' => $stockCode, 'message' => 'Stok adı boş bırakılamaz.'];
                    continue;
                }

                if ($categoryName === '') {
                    $errors[] = ['row' => $rowNumber, 'code' => $stockCode, 'message' => 'Kategori boş bırakılamaz.'];
                    continue;
                }

                if (isset($seenCodes[$stockCode])) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'code' => $stockCode,
                        'message' => "Stok kodu dosya içinde tekrar ediyor (ilk satır: {$seenCodes[$stockCode]}).",
                    ];
                    $skipped++;
                    continue;
                }

                $seenCodes[$stockCode] = $rowNumber;

                if (StockCard::query()->where('stock_code', $stockCode)->exists()) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'code' => $stockCode,
                        'message' => 'Bu stok kodu zaten kayıtlı olduğu için atlandı.',
                    ];
                    $skipped++;
                    continue;
                }

                $category = $this->categories->findOrCreate($categoryName);

                $stockCard = StockCard::create([
                    'stock_code' => $stockCode,
                    'stock_name' => $stockName,
                    'category_id' => $category->id,
                ]);

                $this->syncGalleryItems($stockCard);
                $imported++;
            }
        });

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    private function syncGalleryItems(StockCard $stockCard): void
    {
        DB::table('artwork_gallery')
            ->where('stock_code', $stockCard->stock_code)
            ->update([
                'stock_card_id' => $stockCard->id,
                'category_id' => $stockCard->category_id,
                'updated_at' => now(),
            ]);
    }

    private function mapColumns(array $header): array
    {
        $aliases = [
            'stock_code' => ['stok kodu *', 'stok kodu', 'stock code', 'stock_code', 'kod'],
            'stock_name' => ['stok adı *', 'stok adi *', 'stok adı', 'stok adi', 'stock name', 'stock_name', 'adı', 'adi'],
            'category' => ['kategori *', 'kategori', 'category'],
        ];

        $map = [];

        foreach ($header as $index => $column) {
            $columnLower = mb_strtolower($column);

            foreach ($aliases as $key => $values) {
                if (! isset($map[$key]) && in_array($columnLower, $values, true)) {
                    $map[$key] = $index;
                    break;
                }
            }
        }

        return $map;
    }

    private function normalizeStockCode(string $value): string
    {
        return mb_strtoupper(trim($value));
    }
}
