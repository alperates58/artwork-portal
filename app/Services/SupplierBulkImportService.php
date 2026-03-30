<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SupplierBulkImportService
{
    /**
     * Örnek Excel şablonu oluşturur ve çıktıya yazar.
     */
    public function streamTemplate(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tedarikçiler');

        // Başlık satırı
        $headers = [
            'A1' => 'Tedarikçi Kodu *',
            'B1' => 'Tedarikçi Adı *',
            'C1' => 'Email',
            'D1' => 'Telefon',
            'E1' => 'Adres',
            'F1' => 'Notlar',
            'G1' => 'Aktif (1/0)',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Zorunlu sütunlar — turuncu arka plan
        $sheet->getStyle('A1:B1')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFE0B2');

        // Tüm başlık satırı — kalın + ortalı
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Örnek satır
        $sheet->setCellValue('A2', 'TED001');
        $sheet->setCellValue('B2', 'Örnek Tedarikçi A.Ş.');
        $sheet->setCellValue('C2', 'info@ornek.com');
        $sheet->setCellValue('D2', '+90 212 000 00 00');
        $sheet->setCellValue('E2', 'İstanbul');
        $sheet->setCellValue('F2', '');
        $sheet->setCellValue('G2', '1');

        // Sütun genişlikleri
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(26);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(30);
        $sheet->getColumnDimension('G')->setWidth(15);

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="tedarikci_import_sablonu.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Yüklenen Excel dosyasını işler, tedarikçileri veritabanına ekler.
     *
     * @return array{imported: int, skipped: int, error_count: int, errors: array}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows) || count($rows) < 2) {
            return ['imported' => 0, 'skipped' => 0, 'error_count' => 0, 'errors' => []];
        }

        $header = array_map(fn ($v) => trim((string) $v), $rows[0]);
        $colMap = $this->mapColumns($header);

        if (! isset($colMap['code']) || ! isset($colMap['name'])) {
            throw new \InvalidArgumentException(
                '"Tedarikçi Kodu" ve "Tedarikçi Adı" sütunları bulunamadı. Şablonu indirip kullandığınızdan emin olun.'
            );
        }

        $errors = [];
        $imported = 0;
        $skipped = 0;
        $seenCodes = [];

        DB::transaction(function () use ($rows, $colMap, &$errors, &$imported, &$skipped, &$seenCodes) {
            foreach (array_slice($rows, 1) as $index => $row) {
                $rowNum = $index + 2; // Excel satır numarası (başlık = 1)

                // Boş satırı atla
                $filled = array_filter(array_map(fn ($v) => trim((string) $v), $row));
                if (empty($filled)) {
                    continue;
                }

                $code = trim((string) ($row[$colMap['code']] ?? ''));
                $name = trim((string) ($row[$colMap['name']] ?? ''));

                // Zorunlu alan kontrolü
                if ($code === '') {
                    $errors[] = ['row' => $rowNum, 'code' => '—', 'message' => 'Tedarikçi Kodu boş bırakılamaz.'];
                    continue;
                }

                if ($name === '') {
                    $errors[] = ['row' => $rowNum, 'code' => $code, 'message' => 'Tedarikçi Adı boş bırakılamaz.'];
                    continue;
                }

                // Dosya içi tekrar kontrolü
                $codeKey = mb_strtolower($code);
                if (isset($seenCodes[$codeKey])) {
                    $errors[] = [
                        'row' => $rowNum,
                        'code' => $code,
                        'message' => "Tedarikçi Kodu dosya içinde tekrar ediyor (ilk görüldüğü satır: {$seenCodes[$codeKey]}).",
                    ];
                    $skipped++;
                    continue;
                }
                $seenCodes[$codeKey] = $rowNum;

                // Veritabanında mevcut mu?
                if (Supplier::withTrashed()->where('code', $code)->exists()) {
                    $errors[] = ['row' => $rowNum, 'code' => $code, 'message' => 'Bu tedarikçi kodu zaten kayıtlı (atlandı).'];
                    $skipped++;
                    continue;
                }

                // Payload oluştur
                $payload = ['code' => $code, 'name' => $name];

                foreach (['email', 'phone', 'address', 'notes'] as $field) {
                    if (isset($colMap[$field])) {
                        $val = trim((string) ($row[$colMap[$field]] ?? ''));
                        if ($val !== '') {
                            $payload[$field] = $val;
                        }
                    }
                }

                if (isset($colMap['is_active'])) {
                    $raw = mb_strtolower(trim((string) ($row[$colMap['is_active']] ?? '')));
                    $payload['is_active'] = ! in_array($raw, ['0', 'false', 'hayır', 'no', 'pasif'], true);
                } else {
                    $payload['is_active'] = true;
                }

                Supplier::create($payload);
                $imported++;
            }
        });

        return [
            'imported'    => $imported,
            'skipped'     => $skipped,
            'error_count' => count($errors),
            'errors'      => $errors,
        ];
    }

    /**
     * Başlık satırını alan adlarıyla eşleştirir.
     *
     * @return array<string, int>
     */
    private function mapColumns(array $header): array
    {
        $aliases = [
            'code'      => ['tedarikçi kodu *', 'tedarikci kodu *', 'tedarikçi kodu', 'tedarikci kodu', 'kod', 'code'],
            'name'      => ['tedarikçi adı *', 'tedarikci adi *', 'tedarikçi adı', 'tedarikci adi', 'ad', 'adi', 'name'],
            'email'     => ['email', 'e-posta', 'eposta', 'e posta'],
            'phone'     => ['telefon', 'phone', 'tel'],
            'address'   => ['adres', 'address'],
            'notes'     => ['notlar', 'not', 'notes', 'açıklama', 'aciklama'],
            'is_active' => ['aktif (1/0)', 'aktif', 'active', 'durum'],
        ];

        $map = [];
        foreach ($header as $i => $col) {
            $colLower = mb_strtolower($col);
            foreach ($aliases as $key => $list) {
                if (! isset($map[$key]) && in_array($colLower, $list, true)) {
                    $map[$key] = $i;
                    break;
                }
            }
        }

        return $map;
    }
}
