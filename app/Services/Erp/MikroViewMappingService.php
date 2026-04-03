<?php

namespace App\Services\Erp;

use App\Models\MikroViewMapping;
use App\Models\SupplierMikroAccount;
use App\Models\User;
use App\Services\Mikro\MikroClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MikroViewMappingService
{
    private const ORDER_FIELDS = [
        'supplier_code' => 'Tedarikçi Kodu',
        'supplier_name' => 'Tedarikçi Adı',
        'order_no' => 'Sipariş No',
        'order_date' => 'Sipariş Tarihi',
        'status' => 'Sipariş Durumu',
        'due_date' => 'Termin Tarihi',
        'notes' => 'Sipariş Notu',
        'shipment_status' => 'Sevk Durumu',
        'shipment_reference' => 'Sevk Referansı',
    ];

    private const LINE_FIELDS = [
        'line_no' => 'Satır No',
        'stock_code' => 'Stok Kodu',
        'stock_name' => 'Stok Adı',
        'order_qty' => 'Sipariş Miktarı',
        'shipped_quantity' => 'Sevk Miktarı',
        'unit' => 'Birim',
        'line_notes' => 'Satır Notu',
    ];

    private const REQUIRED_ORDER_FIELDS = [
        'supplier_code',
        'order_no',
    ];

    private const REQUIRED_LINE_FIELDS = [
        'line_no',
        'stock_code',
        'stock_name',
        'order_qty',
    ];

    public function __construct(private MikroClient $mikro) {}

    public function active(): ?MikroViewMapping
    {
        if (! $this->hasMappingsTable()) {
            return null;
        }

        return MikroViewMapping::query()
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();
    }

    public function fieldDefinitions(): array
    {
        return [
            'order' => self::ORDER_FIELDS,
            'line' => self::LINE_FIELDS,
            'required' => [
                'order' => self::REQUIRED_ORDER_FIELDS,
                'line' => self::REQUIRED_LINE_FIELDS,
            ],
        ];
    }

    public function formConfig(): array
    {
        $mapping = $this->active();
        $mappingPayload = $mapping?->mapping_payload ?? [];
        $orderMap = array_fill_keys(array_keys(self::ORDER_FIELDS), '');
        $lineMap = array_fill_keys(array_keys(self::LINE_FIELDS), '');

        return [
            'id' => $mapping?->id,
            'name' => $mapping?->name ?? 'Varsayılan Mikro Sipariş Mapping',
            'view_name' => $mapping?->view_name ?? '',
            'endpoint_path' => $mapping?->endpoint_path ?? '/api/purchase-orders',
            'payload_mode' => $mapping?->payload_mode ?? 'nested_lines',
            'line_array_key' => $mapping?->line_array_key ?? 'lines',
            'is_active' => $mapping?->is_active ?? true,
            'notes' => $mapping?->notes ?? '',
            'mapping' => [
                'order' => array_replace($orderMap, Arr::get($mappingPayload, 'order', [])),
                'line' => array_replace($lineMap, Arr::get($mappingPayload, 'line', [])),
            ],
            'sample_payload' => $mapping?->sample_payload ?? null,
            'field_definitions' => $this->fieldDefinitions(),
        ];
    }

    public function save(array $payload, ?User $actor = null): MikroViewMapping
    {
        if (! $this->hasMappingsTable()) {
            throw new \RuntimeException('Mikro view mapping tablosu bulunamadı. Lütfen migration çalıştırın.');
        }

        $definition = $this->normalizeDefinition($payload, validateMappings: true);

        return DB::transaction(function () use ($definition, $actor): MikroViewMapping {
            MikroViewMapping::query()->where('entity_type', 'orders')->update(['is_active' => false]);

            $mapping = MikroViewMapping::query()->updateOrCreate(
                ['id' => $definition['id'] ?? null],
                [
                    'name' => $definition['name'],
                    'view_name' => $definition['view_name'],
                    'endpoint_path' => $definition['endpoint_path'],
                    'payload_mode' => $definition['payload_mode'],
                    'line_array_key' => $definition['line_array_key'],
                    'mapping_payload' => $definition['mapping'],
                    'sample_payload' => $definition['sample_payload'],
                    'notes' => $definition['notes'],
                    'is_active' => true,
                    'created_by' => $actor?->id,
                ]
            );

            return $mapping->fresh();
        });
    }

    public function fetchSample(array $payload): array
    {
        $definition = $this->normalizeDefinition($payload, validateMappings: false);
        $records = Arr::wrap($this->mikro->get($definition['endpoint_path'], ['limit' => 5])->json('data', []));

        if ($records === []) {
            throw new \RuntimeException('Mikro endpoint örnek veri döndürmedi.');
        }

        $firstRecord = $records[0];

        if (! is_array($firstRecord)) {
            throw new \RuntimeException('Mikro endpoint veri yapısı beklenen JSON nesnesi formatında değil.');
        }

        $columns = $this->extractColumns($records, $definition);
        $normalizedRecords = $definition['payload_mode'] === 'flat_rows'
            ? $this->groupFlatRows($records, $definition)
            : $records;

        $previewRecord = $normalizedRecords[0] ?? null;

        if (! is_array($previewRecord)) {
            throw new \RuntimeException('Önizleme için uygun kayıt üretilemedi.');
        }

        return [
            'columns' => $columns,
            'sample_payload' => [
                'record' => $firstRecord,
                'line_sample' => $columns['line_sample'],
            ],
            'normalized_preview' => $this->normalizePayload(
                $previewRecord,
                $definition,
                new SupplierMikroAccount([
                    'mikro_cari_kod' => $this->mappedValue($previewRecord, Arr::get($definition, 'mapping.order.supplier_code')),
                ])
            ),
        ];
    }

    public function activeEndpointPath(): string
    {
        return $this->active()?->endpoint_path ?: '/api/purchase-orders';
    }

    public function shouldGroupFlatRows(): bool
    {
        return $this->active()?->payload_mode === 'flat_rows';
    }

    public function groupFlatRows(array $rows, MikroViewMapping|array|null $mapping = null): array
    {
        $definition = $this->mappingDefinition($mapping);
        $supplierField = Arr::get($definition, 'mapping.order.supplier_code');
        $orderField = Arr::get($definition, 'mapping.order.order_no');
        $lineArrayKey = $definition['line_array_key'] ?: 'lines';

        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->groupBy(function (array $row) use ($supplierField, $orderField): string {
                $supplierCode = $this->mappedValue($row, $supplierField) ?: 'supplier:none';
                $orderNo = $this->mappedValue($row, $orderField) ?: 'order:none';

                return $supplierCode . '|' . $orderNo;
            })
            ->map(function (Collection $group) use ($lineArrayKey): array {
                $header = $group->first();
                $header[$lineArrayKey] = $group->values()->all();

                return $header;
            })
            ->values()
            ->all();
    }

    public function normalizePayload(
        array $payload,
        MikroViewMapping|array|null $mapping,
        SupplierMikroAccount $account
    ): array {
        $definition = $this->mappingDefinition($mapping);
        $lineArrayKey = $definition['line_array_key'] ?: 'lines';
        $lineItems = is_array($payload[$lineArrayKey] ?? null)
            ? Arr::wrap($payload[$lineArrayKey] ?? [])
            : ($definition['payload_mode'] === 'nested_lines'
                ? Arr::wrap($payload[$lineArrayKey] ?? [])
                : [$payload]);

        $normalizedLines = collect($lineItems)
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $line) => [
                'line_no' => $this->mappedValue($line, Arr::get($definition, 'mapping.line.line_no')),
                'stock_code' => $this->mappedValue($line, Arr::get($definition, 'mapping.line.stock_code')),
                'stock_name' => $this->mappedValue($line, Arr::get($definition, 'mapping.line.stock_name')),
                'order_qty' => $this->mappedNumericValue($line, Arr::get($definition, 'mapping.line.order_qty')),
                'shipped_quantity' => $this->mappedNumericValue($line, Arr::get($definition, 'mapping.line.shipped_quantity')),
                'unit' => $this->mappedValue($line, Arr::get($definition, 'mapping.line.unit')),
                'notes' => $this->mappedValue($line, Arr::get($definition, 'mapping.line.line_notes')),
            ])
            ->values()
            ->all();

        return [
            'order_no' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.order_no')),
            'line_items' => $normalizedLines,
            'stock_code' => $normalizedLines[0]['stock_code'] ?? null,
            'stock_name' => $normalizedLines[0]['stock_name'] ?? null,
            'order_qty' => $normalizedLines[0]['order_qty'] ?? null,
            'supplier_code' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.supplier_code')) ?: $account->mikro_cari_kod,
            'supplier_name' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.supplier_name')),
            'order_date' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.order_date')),
            'status' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.status')) ?: 'active',
            'due_date' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.due_date')),
            'notes' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.notes')),
            'shipment_status' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.shipment_status')),
            'shipment_reference' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.shipment_reference')),
            'shipment_payload' => null,
            'source_metadata' => [
                'source' => 'mikro',
                'contract' => 'mapped_view',
                'mapping_name' => $definition['name'],
                'view_name' => $definition['view_name'],
                'payload_mode' => $definition['payload_mode'],
                'supplier_code' => $this->mappedValue($payload, Arr::get($definition, 'mapping.order.supplier_code')) ?: $account->mikro_cari_kod,
                'payload_snapshot' => Arr::except($payload, [$lineArrayKey]),
            ],
        ];
    }

    private function extractColumns(array $records, array $definition): array
    {
        $firstRecord = $records[0];
        $lineArrayKey = $definition['line_array_key'] ?: 'lines';
        $orderColumns = array_keys(collect($firstRecord)
            ->reject(fn ($value, $key) => is_array($value) && $key === $lineArrayKey)
            ->all());

        if ($definition['payload_mode'] === 'nested_lines') {
            $lineSample = Arr::wrap($firstRecord[$lineArrayKey] ?? []);
            $firstLine = is_array($lineSample[0] ?? null) ? $lineSample[0] : [];

            return [
                'order' => $orderColumns,
                'line' => array_keys($firstLine),
                'line_sample' => $firstLine,
            ];
        }

        return [
            'order' => $orderColumns,
            'line' => $orderColumns,
            'line_sample' => $firstRecord,
        ];
    }

    private function normalizeDefinition(array $payload, bool $validateMappings): array
    {
        $mapping = [
            'order' => $this->cleanMapping(Arr::get($payload, 'mapping.order', []), array_keys(self::ORDER_FIELDS)),
            'line' => $this->cleanMapping(Arr::get($payload, 'mapping.line', []), array_keys(self::LINE_FIELDS)),
        ];

        $definition = [
            'id' => Arr::get($payload, 'id'),
            'name' => trim((string) ($payload['name'] ?? 'Varsayılan Mikro Sipariş Mapping')),
            'view_name' => trim((string) ($payload['view_name'] ?? '')),
            'endpoint_path' => $this->normalizeEndpointPath((string) ($payload['endpoint_path'] ?? '')),
            'payload_mode' => in_array(($payload['payload_mode'] ?? ''), ['flat_rows', 'nested_lines'], true)
                ? $payload['payload_mode']
                : 'nested_lines',
            'line_array_key' => trim((string) ($payload['line_array_key'] ?? 'lines')) ?: 'lines',
            'notes' => trim((string) ($payload['notes'] ?? '')),
            'mapping' => $mapping,
            'sample_payload' => Arr::get($payload, 'sample_payload'),
        ];

        if ($definition['name'] === '' || $definition['view_name'] === '' || $definition['endpoint_path'] === '') {
            throw new \InvalidArgumentException('View adı, mapping adı ve endpoint yolu zorunludur.');
        }

        if ($validateMappings) {
            $this->assertRequiredMappings($definition['mapping']);
        }

        return $definition;
    }

    private function assertRequiredMappings(array $mapping): void
    {
        foreach (self::REQUIRED_ORDER_FIELDS as $field) {
            if (blank($mapping['order'][$field] ?? null)) {
                throw new \InvalidArgumentException('Sipariş alan eşlemesinde "' . self::ORDER_FIELDS[$field] . '" zorunludur.');
            }
        }

        foreach (self::REQUIRED_LINE_FIELDS as $field) {
            if (blank($mapping['line'][$field] ?? null)) {
                throw new \InvalidArgumentException('Satır alan eşlemesinde "' . self::LINE_FIELDS[$field] . '" zorunludur.');
            }
        }
    }

    private function cleanMapping(array $mapping, array $allowedFields): array
    {
        $clean = [];

        foreach ($allowedFields as $field) {
            $clean[$field] = trim((string) ($mapping[$field] ?? ''));
        }

        return $clean;
    }

    private function normalizeEndpointPath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        return Str::startsWith($path, '/') ? $path : '/' . $path;
    }

    private function mappingDefinition(MikroViewMapping|array|null $mapping): array
    {
        if ($mapping instanceof MikroViewMapping) {
            return [
                'name' => $mapping->name,
                'view_name' => $mapping->view_name,
                'endpoint_path' => $mapping->endpoint_path,
                'payload_mode' => $mapping->payload_mode,
                'line_array_key' => $mapping->line_array_key ?: 'lines',
                'mapping' => $mapping->mapping_payload ?? ['order' => [], 'line' => []],
            ];
        }

        if (is_array($mapping)) {
            return $this->normalizeDefinition($mapping, validateMappings: false);
        }

        return $this->normalizeDefinition($this->formConfig(), validateMappings: false);
    }

    private function hasMappingsTable(): bool
    {
        return Schema::hasTable('mikro_view_mappings');
    }

    private function mappedValue(array $payload, ?string $sourceField): ?string
    {
        if (blank($sourceField)) {
            return null;
        }

        $value = Arr::get($payload, $sourceField);

        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? trim((string) $value) : null;
    }

    private function mappedNumericValue(array $payload, ?string $sourceField): ?int
    {
        $value = $this->mappedValue($payload, $sourceField);

        return $value === null ? null : (int) $value;
    }
}
