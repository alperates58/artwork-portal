<?php

namespace App\Services\Erp;

use App\Models\MikroViewMapping;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MikroStockCardViewMappingService
{
    public function active(): ?MikroViewMapping
    {
        if (! $this->hasMappingsTable()) {
            return null;
        }

        return MikroViewMapping::query()
            ->where('entity_type', 'stock_cards')
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();
    }

    public function formConfig(): array
    {
        $mapping = $this->active();
        $payload = $mapping?->mapping_payload ?? [];

        return [
            'id'            => $mapping?->id,
            'name'          => $mapping?->name ?? 'Varsayılan Stok Kartı Mapping',
            'view_name'     => $mapping?->view_name ?? '',
            'endpoint_path' => $mapping?->endpoint_path ?? '/api/stock-cards',
            'notes'         => $mapping?->notes ?? '',
            'mapping'       => [
                'stock_code' => $payload['stock_code'] ?? '',
                'stock_name' => $payload['stock_name'] ?? '',
                'category'   => $payload['category']   ?? '',
            ],
        ];
    }

    public function save(array $payload, ?User $actor = null): MikroViewMapping
    {
        if (! $this->hasMappingsTable()) {
            throw new \RuntimeException('Mikro view mapping tablosu bulunamadı.');
        }

        $name         = trim($payload['name'] ?? '');
        $viewName     = trim($payload['view_name'] ?? '');
        $endpointPath = $this->normalizeEndpointPath((string) ($payload['endpoint_path'] ?? ''));

        if (blank($name) || blank($viewName) || blank($endpointPath)) {
            throw new \InvalidArgumentException('Mapping adı, view adı ve endpoint yolu zorunludur.');
        }

        $mapping = [
            'stock_code' => trim($payload['mapping']['stock_code'] ?? ''),
            'stock_name' => trim($payload['mapping']['stock_name'] ?? ''),
            'category'   => trim($payload['mapping']['category']   ?? ''),
        ];

        return DB::transaction(function () use ($payload, $name, $viewName, $endpointPath, $mapping, $actor): MikroViewMapping {
            // Yalnızca stok kartı mapping'lerini pasifleştir; sipariş mapping'lerine dokunma
            MikroViewMapping::query()
                ->where('entity_type', 'stock_cards')
                ->update(['is_active' => false]);

            $record = MikroViewMapping::query()->updateOrCreate(
                ['id' => filled($payload['id'] ?? null) ? (int) $payload['id'] : null],
                [
                    'name'            => $name,
                    'entity_type'     => 'stock_cards',
                    'view_name'       => $viewName,
                    'endpoint_path'   => $endpointPath,
                    'payload_mode'    => 'flat_rows',
                    'line_array_key'  => null,
                    'mapping_payload' => $mapping,
                    'sample_payload'  => null,
                    'notes'           => trim($payload['notes'] ?? ''),
                    'is_active'       => true,
                    'created_by'      => $actor?->id,
                ]
            );

            return $record->fresh();
        });
    }

    private function normalizeEndpointPath(string $path): string
    {
        $path = trim($path);

        if (blank($path)) {
            return '';
        }

        return Str::startsWith($path, '/') ? $path : '/' . $path;
    }

    private function hasMappingsTable(): bool
    {
        return Schema::hasTable('mikro_view_mappings');
    }
}
