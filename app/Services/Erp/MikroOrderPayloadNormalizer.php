<?php

namespace App\Services\Erp;

use App\Enums\MikroSyncConflictCode;
use App\Models\SupplierMikroAccount;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class MikroOrderPayloadNormalizer
{
    /**
     * Normalize disparate endpoint payloads to the ERP VIEW contract expected by the portal.
     *
     * TODO: Gercek Mikro endpoint shape production ortaminda netlestiginde yalniz bu sinif guncellenmeli.
     */
    public function normalizeOrder(array $payload, SupplierMikroAccount $account): array
    {
        $lines = Arr::wrap($payload['lines'] ?? Arr::get($payload, 'order_lines', []));

        $normalized = [
            'order_no' => $this->stringValue($payload, ['order_no']),
            'line_items' => array_map(fn (mixed $line) => $this->normalizeLine(is_array($line) ? $line : []), $lines),
            'stock_code' => $this->stringValue($payload, ['stock_code']),
            'stock_name' => $this->stringValue($payload, ['stock_name']),
            'order_qty' => $this->intValue($payload, ['order_qty']),
            'supplier_code' => $this->stringValue($payload, ['supplier_code']),
            'supplier_name' => $this->stringValue($payload, ['supplier_name']),
            'order_date' => $payload['order_date'] ?? null,
            'status' => $payload['status'] ?? 'active',
            'due_date' => $payload['due_date'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'shipment_status' => Arr::get($payload, 'shipment.status', Arr::get($payload, 'shipment_status')),
            'shipment_reference' => Arr::get($payload, 'shipment.reference', Arr::get($payload, 'shipment_reference')),
            'shipment_payload' => Arr::get($payload, 'shipment'),
            'source_metadata' => $this->sourceMetadata($payload, $account),
        ];

        if (($normalized['supplier_code'] ?? null) === null && filled($account->mikro_cari_kod)) {
            $normalized['supplier_code'] = $account->mikro_cari_kod;
        }

        return $normalized;
    }

    public function validateOrder(array $order, SupplierMikroAccount $account): array
    {
        $issues = [];

        if (! filled($order['order_no'] ?? null)) {
            $issues[] = [
                'code' => MikroSyncConflictCode::ENDPOINT_PAYLOAD_MISMATCH->value,
                'message' => 'order_no alani eksik.',
            ];
        }

        $supplierCode = (string) ($order['supplier_code'] ?? '');

        if ($supplierCode === '') {
            $issues[] = [
                'code' => MikroSyncConflictCode::MISSING_SUPPLIER_MAPPING->value,
                'message' => 'supplier_code alani eksik.',
            ];
        } elseif ($supplierCode !== (string) $account->mikro_cari_kod) {
            $issues[] = [
                'code' => MikroSyncConflictCode::ENDPOINT_PAYLOAD_MISMATCH->value,
                'message' => sprintf(
                    'supplier_code mismatch. Beklenen %s, gelen %s',
                    $account->mikro_cari_kod,
                    $supplierCode
                ),
            ];
        }

        foreach ($order['line_items'] ?? [] as $index => $line) {
            if (! filled($line['line_no'] ?? null)) {
                $issues[] = [
                    'code' => MikroSyncConflictCode::INVALID_LINE_IDENTITY->value,
                    'message' => 'Satir kimligi eksik. line_no gercek ERP satir kimligi sip_satirno olmalidir. Index: ' . $index,
                ];
            }
        }

        return $issues;
    }

    private function normalizeLine(array $payload): array
    {
        return [
            'line_no' => $this->stringValue($payload, ['line_no', 'sip_satirno']),
            'stock_code' => $this->stringValue($payload, ['stock_code', 'product_code', 'sip_stok_kod']),
            'stock_name' => $this->stringValue($payload, ['stock_name', 'description', 'sto_isim']),
            'order_qty' => $this->intValue($payload, ['order_qty', 'quantity', 'sip_miktar']),
            'shipped_quantity' => Arr::get($payload, 'shipped_qty', Arr::get($payload, 'shipped_quantity')),
            'unit' => $payload['unit'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ];
    }

    private function sourceMetadata(array $payload, SupplierMikroAccount $account): array
    {
        $rawOrder = Arr::except($payload, ['lines', 'order_lines']);

        return [
            'source' => 'mikro',
            'contract' => (string) config('mikro.view_contract.name', 'supplier_order_view_v1'),
            'supplier_code' => $this->stringValue($payload, ['supplier_code']) ?? $account->mikro_cari_kod,
            'supplier_name' => $this->stringValue($payload, ['supplier_name']),
            'mikro_account_id' => $account->id,
            'received_at' => Carbon::now()->toIso8601String(),
            'payload_snapshot' => $rawOrder === [] ? null : $rawOrder,
        ];
    }

    private function stringValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function intValue(array $payload, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);

            if ($value !== null && $value !== '') {
                return (int) $value;
            }
        }

        return null;
    }
}
