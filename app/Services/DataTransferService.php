<?php

namespace App\Services;

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
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;

class DataTransferService
{
    private const IMPORT_TRACKING_KEY = 'imported_ids';
    private const SETTING_GROUP = 'data_transfer';
    private const MEDIA_EXPORT_LIMIT_BYTES = 52428800; // 50 MB

    public function __construct(
        private PortalSettings $settings,
        private DashboardCacheService $dashboardCache,
    ) {}

    public function sectionDefinitions(): array
    {
        return [
            'suppliers' => [
                'label' => 'Tedarikçiler',
                'description' => 'Tedarikçi kartları ve tedarikçi-kullanıcı bağları aktarılır.',
                'supports_media' => false,
                'default' => true,
                'fields' => [
                    'name' => 'Ad',
                    'code' => 'Kod',
                    'email' => 'E-posta',
                    'phone' => 'Telefon',
                    'address' => 'Adres',
                    'is_active' => 'Aktiflik durumu',
                    'notes' => 'Notlar',
                    'created_at' => 'Oluşturulma tarihi',
                    'updated_at' => 'Güncellenme tarihi',
                    'deleted_at' => 'Silinme tarihi',
                    'user_mappings' => 'Kullanıcı eşleşmeleri ve yetkiler',
                ],
            ],
            'users' => [
                'label' => 'Kullanıcılar',
                'description' => 'Admin dışı kullanıcılar, departman ve iletişim bilgileriyle aktarılır.',
                'supports_media' => false,
                'default' => true,
                'fields' => [
                    'name' => 'Ad soyad',
                    'email' => 'E-posta',
                    'role' => 'Rol',
                    'is_active' => 'Aktiflik durumu',
                    'department_name' => 'Departman',
                    'supplier_ref' => 'Birincil tedarikçi referansı',
                    'permissions' => 'Özel yetkiler',
                    'phone' => 'Telefon',
                    'linkedin_url' => 'LinkedIn adresi',
                    'contact_email' => 'İletişim e-postası',
                    'bio' => 'Biyografi',
                    'created_at' => 'Oluşturulma tarihi',
                    'updated_at' => 'Güncellenme tarihi',
                ],
            ],
            'purchase_orders' => [
                'label' => 'Siparişler',
                'description' => 'Sipariş başlığı ve satır detayları birlikte aktarılır.',
                'supports_media' => false,
                'default' => true,
                'fields' => [
                    'supplier_ref' => 'Tedarikçi referansı',
                    'order_no' => 'Sipariş numarası',
                    'status' => 'Durum',
                    'order_date' => 'Sipariş tarihi',
                    'due_date' => 'Termin tarihi',
                    'notes' => 'Notlar',
                    'created_by_email' => 'Oluşturan kullanıcı',
                    'created_at' => 'Oluşturulma tarihi',
                    'updated_at' => 'Güncellenme tarihi',
                    'lines' => 'Sipariş satırları',
                ],
            ],
            'artwork_gallery' => [
                'label' => 'Artwork galerisi',
                'description' => 'Galeri dosyaları, kategori ve etiket bilgileriyle aktarılır.',
                'supports_media' => true,
                'default' => false,
                'fields' => [
                    'name' => 'Dosya adı',
                    'stock_code' => 'Stok kodu',
                    'category_name' => 'Kategori',
                    'tag_names' => 'Etiketler',
                    'revision_note' => 'Revizyon notu',
                    'file_type' => 'Dosya tipi',
                    'file_size' => 'Dosya boyutu',
                    'uploaded_by_email' => 'Yükleyen kullanıcı',
                    'created_at' => 'Oluşturulma tarihi',
                    'updated_at' => 'Güncellenme tarihi',
                ],
            ],
            'artwork_revisions' => [
                'label' => 'Artwork revizyonları',
                'description' => 'Sipariş satırlarına bağlı revizyonlar ve onay metadatası aktarılır.',
                'supports_media' => true,
                'default' => false,
                'fields' => [
                    'supplier_ref' => 'Tedarikçi referansı',
                    'order_no' => 'Sipariş numarası',
                    'line_no' => 'Satır numarası',
                    'revision_no' => 'Revizyon numarası',
                    'original_filename' => 'Orijinal dosya adı',
                    'mime_type' => 'MIME türü',
                    'file_size' => 'Dosya boyutu',
                    'is_active' => 'Aktif revizyon bilgisi',
                    'approval_status' => 'Onay durumu',
                    'notes' => 'Notlar',
                    'uploaded_by_email' => 'Yükleyen kullanıcı',
                    'created_at' => 'Oluşturulma tarihi',
                    'approved_at' => 'Onay tarihi',
                    'archived_at' => 'Arşiv tarihi',
                    'gallery_ref' => 'Galeri ilişkisi',
                ],
            ],
        ];
    }

    public function buildExportOptions(): array
    {
        $definitions = $this->sectionDefinitions();
        $lastExportAt = DataTransferRecord::query()
            ->where('direction', 'export')
            ->max('transferred_at');

        return [
            'sections' => collect($definitions)->map(function (array $definition, string $section): array {
                $tracked = DataTransferRecord::query()
                    ->where('direction', 'export')
                    ->where('entity_type', $section)
                    ->distinct()
                    ->count('entity_key');

                return [
                    ...$definition,
                    'key' => $section,
                    'stats' => [
                        'total' => $this->totalCountForSection($section),
                        'tracked' => $tracked,
                    ],
                ];
            })->values()->all(),
            'last_export_at' => $lastExportAt ? Carbon::parse($lastExportAt) : null,
            'imported_count' => $this->importedCount(),
        ];
    }

    public function validateSelection(array $input): array
    {
        $definitions = $this->sectionDefinitions();
        $validated = [];

        foreach (($input['fields'] ?? []) as $section => $fields) {
            if (! isset($definitions[$section])) {
                continue;
            }

            $allowedFields = array_keys($definitions[$section]['fields']);
            $selectedFields = collect(Arr::wrap($fields))
                ->map(fn ($field) => (string) $field)
                ->filter(fn ($field) => in_array($field, $allowedFields, true))
                ->unique()
                ->values()
                ->all();

            if ($selectedFields !== []) {
                $validated[$section] = $selectedFields;
            }
        }

        return $validated;
    }

    public function export(array $selection, bool $includeMedia = false, bool $onlyNew = true): array
    {
        if ($includeMedia) {
            $this->guardMediaExportSize($selection);
        }

        $batchUuid = (string) Str::uuid();
        $selectionHash = $this->selectionHash($selection, $includeMedia);

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><portal_export/>');
        $xml->addAttribute('exported_at', now()->toIso8601String());
        $xml->addAttribute('version', '3');
        $xml->addAttribute('include_media', $includeMedia ? '1' : '0');
        $xml->addChild('selection_hash', $selectionHash);

        $stats = [];

        foreach ($selection as $section => $fields) {
            if ($fields === []) {
                continue;
            }

            $method = 'append' . Str::studly($section) . 'Section';

            if (! method_exists($this, $method)) {
                continue;
            }

            $stats[$section] = $this->{$method}($xml, $fields, $includeMedia, $onlyNew, $selectionHash, $batchUuid);
        }

        return [
            'xml' => $xml->asXML(),
            'filename' => 'portal-export-' . now()->format('Y-m-d-His') . '.xml',
            'stats' => $stats,
        ];
    }

    public function import(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if (! $xml instanceof SimpleXMLElement) {
            return [
                'ok' => false,
                'message' => 'Geçersiz XML dosyası.',
            ];
        }

        $batchUuid = (string) Str::uuid();
        $importedIds = $this->getImportedIds();
        $stats = [
            'suppliers' => 0,
            'users' => 0,
            'purchase_orders' => 0,
            'artwork_gallery' => 0,
            'artwork_revisions' => 0,
            'skipped' => 0,
        ];

        DB::transaction(function () use ($xml, $batchUuid, &$importedIds, &$stats): void {
            $supplierMap = $this->importSuppliers($xml, $batchUuid, $importedIds, $stats);
            $this->importUsers($xml, $supplierMap, $batchUuid, $importedIds, $stats);
            $this->importSupplierMappings($xml, $supplierMap);
            $this->importPurchaseOrders($xml, $supplierMap, $batchUuid, $importedIds, $stats);
            $galleryMap = $this->importArtworkGallery($xml, $batchUuid, $importedIds, $stats);
            $this->importArtworkRevisions($xml, $galleryMap, $batchUuid, $importedIds, $stats);
        });

        $this->saveImportedIds($importedIds);

        return [
            'ok' => true,
            'message' => "İçe aktarma tamamlandı: {$stats['suppliers']} tedarikçi, {$stats['users']} kullanıcı, {$stats['purchase_orders']} sipariş, {$stats['artwork_gallery']} galeri kaydı ve {$stats['artwork_revisions']} revizyon eklendi. {$stats['skipped']} kayıt atlandı.",
            'stats' => $stats,
        ];
    }

    public function destroyImported(): void
    {
        $importedIds = $this->getImportedIds();

        DB::transaction(function () use ($importedIds): void {
            $revisionIds = $importedIds['artwork_revisions'] ?? [];
            if ($revisionIds !== []) {
                ArtworkRevision::query()->whereIn('id', $revisionIds)->delete();
            }

            $artworkIds = $importedIds['artworks'] ?? [];
            if ($artworkIds !== []) {
                Artwork::query()->whereIn('id', $artworkIds)->delete();
            }

            $galleryIds = $importedIds['artwork_gallery'] ?? [];
            if ($galleryIds !== []) {
                ArtworkGallery::query()->whereIn('id', $galleryIds)->delete();
            }

            $orderIds = $importedIds['purchase_orders'] ?? [];
            if ($orderIds !== []) {
                PurchaseOrderLine::query()->whereIn('purchase_order_id', $orderIds)->delete();
                PurchaseOrder::query()->whereIn('id', $orderIds)->delete();
            }

            $userIds = $importedIds['users'] ?? [];
            if ($userIds !== []) {
                DB::table('supplier_users')->whereIn('user_id', $userIds)->delete();
                User::query()->whereIn('id', $userIds)->delete();
            }

            $supplierIds = $importedIds['suppliers'] ?? [];
            if ($supplierIds !== []) {
                DB::table('supplier_users')->whereIn('supplier_id', $supplierIds)->delete();
                Supplier::query()->whereIn('id', $supplierIds)->forceDelete();
            }
        });

        SystemSetting::query()
            ->where('group', self::SETTING_GROUP)
            ->where('key', self::IMPORT_TRACKING_KEY)
            ->delete();
    }

    private function importedCount(): array
    {
        $importedIds = $this->getImportedIds();

        return [
            'suppliers' => count($importedIds['suppliers'] ?? []),
            'users' => count($importedIds['users'] ?? []),
            'purchase_orders' => count($importedIds['purchase_orders'] ?? []),
            'artwork_gallery' => count($importedIds['artwork_gallery'] ?? []),
            'artwork_revisions' => count($importedIds['artwork_revisions'] ?? []),
        ];
    }

    private function getImportedIds(): array
    {
        $setting = SystemSetting::query()
            ->where('group', self::SETTING_GROUP)
            ->where('key', self::IMPORT_TRACKING_KEY)
            ->first();

        return $setting ? (json_decode((string) $setting->value, true) ?? []) : [];
    }

    private function saveImportedIds(array $ids): void
    {
        SystemSetting::query()->updateOrCreate(
            ['group' => self::SETTING_GROUP, 'key' => self::IMPORT_TRACKING_KEY],
            ['value' => json_encode($ids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'is_encrypted' => false]
        );
    }

    private function totalCountForSection(string $section): int
    {
        return match ($section) {
            'suppliers' => Supplier::withTrashed()->count(),
            'users' => User::query()->where('role', '!=', 'admin')->count(),
            'purchase_orders' => PurchaseOrder::query()->count(),
            'artwork_gallery' => ArtworkGallery::query()->count(),
            'artwork_revisions' => ArtworkRevision::query()->count(),
            default => 0,
        };
    }

    private function wasTransferred(string $direction, string $entityType, string $entityKey, ?string $selectionHash, string $payloadHash): bool
    {
        return DataTransferRecord::query()
            ->where('direction', $direction)
            ->where('entity_type', $entityType)
            ->where('entity_key', $entityKey)
            ->where('selection_hash', $selectionHash)
            ->where('payload_hash', $payloadHash)
            ->exists();
    }

    private function markTransferred(string $direction, string $entityType, string $entityKey, ?string $selectionHash, string $payloadHash, string $batchUuid): void
    {
        DataTransferRecord::query()->firstOrCreate([
            'direction' => $direction,
            'entity_type' => $entityType,
            'entity_key' => $entityKey,
            'selection_hash' => $selectionHash,
            'payload_hash' => $payloadHash,
        ], [
            'batch_uuid' => $batchUuid,
            'transferred_at' => now(),
        ]);
    }

    private function selectionHash(array $selection, bool $includeMedia): string
    {
        $normalized = collect($selection)
            ->map(fn ($fields) => collect($fields)->sort()->values()->all())
            ->sortKeys()
            ->all();

        return hash('sha256', json_encode([
            'selection' => $normalized,
            'include_media' => $includeMedia,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function payloadHash(array $payload): string
    {
        $normalized = $payload;

        if (isset($normalized['media']) && is_array($normalized['media'])) {
            unset($normalized['media']['content']);
        }

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function appendSuppliersSection(SimpleXMLElement $xml, array $fields, bool $includeMedia, bool $onlyNew, string $selectionHash, string $batchUuid): array
    {
        $suppliersNode = $xml->addChild('suppliers');
        $suppliersNode->addAttribute('fields', implode(',', $fields));
        $exported = 0;

        foreach (Supplier::withTrashed()->with('allUsers')->get() as $supplier) {
            $payload = $this->supplierPayload($supplier, $fields);
            $entityKey = $this->supplierEntityKey($supplier);
            $payloadHash = $this->payloadHash($payload);

            if ($onlyNew && $this->wasTransferred('export', 'suppliers', $entityKey, $selectionHash, $payloadHash)) {
                continue;
            }

            $node = $suppliersNode->addChild('supplier');
            $node->addAttribute('entity_key', $entityKey);
            $this->writePayloadToNode($node, $payload);
            $this->markTransferred('export', 'suppliers', $entityKey, $selectionHash, $payloadHash, $batchUuid);
            $exported++;
        }

        return ['count' => $exported];
    }

    private function appendUsersSection(SimpleXMLElement $xml, array $fields, bool $includeMedia, bool $onlyNew, string $selectionHash, string $batchUuid): array
    {
        $usersNode = $xml->addChild('users');
        $usersNode->addAttribute('fields', implode(',', $fields));
        $exported = 0;

        foreach (User::query()->where('role', '!=', 'admin')->with('department:id,name')->get() as $user) {
            $payload = $this->userPayload($user, $fields);
            $entityKey = $this->userEntityKey($user);
            $payloadHash = $this->payloadHash($payload);

            if ($onlyNew && $this->wasTransferred('export', 'users', $entityKey, $selectionHash, $payloadHash)) {
                continue;
            }

            $node = $usersNode->addChild('user');
            $node->addAttribute('entity_key', $entityKey);
            $this->writePayloadToNode($node, $payload);
            $this->markTransferred('export', 'users', $entityKey, $selectionHash, $payloadHash, $batchUuid);
            $exported++;
        }

        return ['count' => $exported];
    }

    private function appendPurchaseOrdersSection(SimpleXMLElement $xml, array $fields, bool $includeMedia, bool $onlyNew, string $selectionHash, string $batchUuid): array
    {
        $ordersNode = $xml->addChild('purchase_orders');
        $ordersNode->addAttribute('fields', implode(',', $fields));
        $exported = 0;

        foreach (PurchaseOrder::query()->with(['lines', 'createdBy:id,email', 'supplier:id,code'])->get() as $order) {
            $payload = $this->purchaseOrderPayload($order, $fields);
            $entityKey = $this->purchaseOrderEntityKey($order);
            $payloadHash = $this->payloadHash($payload);

            if ($onlyNew && $this->wasTransferred('export', 'purchase_orders', $entityKey, $selectionHash, $payloadHash)) {
                continue;
            }

            $node = $ordersNode->addChild('purchase_order');
            $node->addAttribute('entity_key', $entityKey);
            $this->writePayloadToNode($node, $payload);
            $this->markTransferred('export', 'purchase_orders', $entityKey, $selectionHash, $payloadHash, $batchUuid);
            $exported++;
        }

        return ['count' => $exported];
    }

    private function appendArtworkGallerySection(SimpleXMLElement $xml, array $fields, bool $includeMedia, bool $onlyNew, string $selectionHash, string $batchUuid): array
    {
        $galleryNode = $xml->addChild('artwork_gallery');
        $galleryNode->addAttribute('fields', implode(',', $fields));
        $galleryNode->addAttribute('include_media', $includeMedia ? '1' : '0');
        $exported = 0;

        foreach (ArtworkGallery::query()->with(['category:id,name', 'tags:id,name', 'uploadedBy:id,email'])->get() as $item) {
            $payload = $this->artworkGalleryPayload($item, $fields, $includeMedia);
            $entityKey = $this->artworkGalleryEntityKey($item);
            $payloadHash = $this->payloadHash($payload);

            if ($onlyNew && $this->wasTransferred('export', 'artwork_gallery', $entityKey, $selectionHash, $payloadHash)) {
                continue;
            }

            $node = $galleryNode->addChild('item');
            $node->addAttribute('entity_key', $entityKey);
            $this->writePayloadToNode($node, $payload);
            $this->markTransferred('export', 'artwork_gallery', $entityKey, $selectionHash, $payloadHash, $batchUuid);
            $exported++;
        }

        return ['count' => $exported];
    }

    private function appendArtworkRevisionsSection(SimpleXMLElement $xml, array $fields, bool $includeMedia, bool $onlyNew, string $selectionHash, string $batchUuid): array
    {
        $revisionsNode = $xml->addChild('artwork_revisions');
        $revisionsNode->addAttribute('fields', implode(',', $fields));
        $revisionsNode->addAttribute('include_media', $includeMedia ? '1' : '0');
        $exported = 0;

        foreach (ArtworkRevision::query()->with([
            'artwork.orderLine.purchaseOrder.supplier:id,code',
            'artwork.orderLine',
            'galleryItem:id,name,stock_code,file_disk,file_path',
            'uploadedBy:id,email',
        ])->get() as $revision) {
            $payload = $this->artworkRevisionPayload($revision, $fields, $includeMedia);
            $entityKey = $this->artworkRevisionEntityKey($revision);
            $payloadHash = $this->payloadHash($payload);

            if ($onlyNew && $this->wasTransferred('export', 'artwork_revisions', $entityKey, $selectionHash, $payloadHash)) {
                continue;
            }

            $node = $revisionsNode->addChild('revision');
            $node->addAttribute('entity_key', $entityKey);
            $this->writePayloadToNode($node, $payload);
            $this->markTransferred('export', 'artwork_revisions', $entityKey, $selectionHash, $payloadHash, $batchUuid);
            $exported++;
        }

        return ['count' => $exported];
    }

    private function supplierPayload(Supplier $supplier, array $fields): array
    {
        $payload = [];

        foreach ($fields as $field) {
            $payload[$field] = match ($field) {
                'name' => $supplier->name,
                'code' => $supplier->code,
                'email' => $supplier->email,
                'phone' => $supplier->phone,
                'address' => $supplier->address,
                'is_active' => $supplier->is_active,
                'notes' => $supplier->notes,
                'created_at' => $supplier->created_at?->toIso8601String(),
                'updated_at' => $supplier->updated_at?->toIso8601String(),
                'deleted_at' => $supplier->deleted_at?->toIso8601String(),
                'user_mappings' => $supplier->allUsers->map(fn (User $user) => [
                    'user_email' => $user->email,
                    'title' => $user->pivot->title,
                    'is_primary' => (bool) $user->pivot->is_primary,
                    'can_download' => (bool) $user->pivot->can_download,
                    'can_approve' => (bool) $user->pivot->can_approve,
                ])->values()->all(),
                default => null,
            };
        }

        return $payload;
    }

    private function userPayload(User $user, array $fields): array
    {
        $supplierRef = $user->supplier_id
            ? (Supplier::withTrashed()->find($user->supplier_id)?->code ?: 'supplier:' . $user->supplier_id)
            : null;

        $payload = [];

        foreach ($fields as $field) {
            $payload[$field] = match ($field) {
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
                'department_name' => $user->department?->name,
                'supplier_ref' => $supplierRef,
                'permissions' => $user->permissions ?? [],
                'phone' => $user->phone,
                'linkedin_url' => $user->linkedin_url,
                'contact_email' => $user->contact_email,
                'bio' => $user->bio,
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
                default => null,
            };
        }

        return $payload;
    }

    private function purchaseOrderPayload(PurchaseOrder $order, array $fields): array
    {
        $payload = [];

        foreach ($fields as $field) {
            $payload[$field] = match ($field) {
                'supplier_ref' => $order->supplier?->code ?: 'supplier:' . $order->supplier_id,
                'order_no' => $order->order_no,
                'status' => $order->status,
                'order_date' => $order->order_date?->format('Y-m-d'),
                'due_date' => $order->due_date?->format('Y-m-d'),
                'notes' => $order->notes,
                'created_by_email' => $order->createdBy?->email,
                'created_at' => $order->created_at?->toIso8601String(),
                'updated_at' => $order->updated_at?->toIso8601String(),
                'lines' => $order->lines->map(fn (PurchaseOrderLine $line) => [
                    'line_no' => $line->line_no,
                    'product_code' => $line->product_code,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'shipped_quantity' => $line->shipped_quantity,
                    'unit' => $line->unit,
                    'artwork_status' => $line->artwork_status?->value,
                    'notes' => $line->notes,
                    'created_at' => $line->created_at?->toIso8601String(),
                    'updated_at' => $line->updated_at?->toIso8601String(),
                ])->values()->all(),
                default => null,
            };
        }

        return $payload;
    }

    private function artworkGalleryPayload(ArtworkGallery $item, array $fields, bool $includeMedia): array
    {
        $payload = [];

        foreach ($fields as $field) {
            $payload[$field] = match ($field) {
                'name' => $item->name,
                'stock_code' => $item->stock_code,
                'category_name' => $item->category?->name,
                'tag_names' => $item->tags->pluck('name')->values()->all(),
                'revision_note' => $item->revision_note,
                'file_type' => $item->file_type,
                'file_size' => $item->file_size,
                'uploaded_by_email' => $item->uploadedBy?->email,
                'created_at' => $item->created_at?->toIso8601String(),
                'updated_at' => $item->updated_at?->toIso8601String(),
                default => null,
            };
        }

        $media = $this->readMediaPayload($item->file_disk ?: $this->settings->filesystemDisk(), $item->file_path, $item->name, $includeMedia);
        $payload['media_checksum'] = $media['checksum'];

        if ($includeMedia && $media['content'] !== null) {
            $payload['media'] = $media;
        }

        return $payload;
    }

    private function artworkRevisionPayload(ArtworkRevision $revision, array $fields, bool $includeMedia): array
    {
        $order = $revision->artwork?->orderLine?->purchaseOrder;
        $line = $revision->artwork?->orderLine;
        $payload = [];

        foreach ($fields as $field) {
            $payload[$field] = match ($field) {
                'supplier_ref' => $order?->supplier?->code ?: ($order ? 'supplier:' . $order->supplier_id : null),
                'order_no' => $order?->order_no,
                'line_no' => $line?->line_no,
                'revision_no' => $revision->revision_no,
                'original_filename' => $revision->original_filename,
                'mime_type' => $revision->mime_type,
                'file_size' => $revision->file_size,
                'is_active' => $revision->is_active,
                'approval_status' => $revision->approval_status,
                'notes' => $revision->notes,
                'uploaded_by_email' => $revision->uploadedBy?->email,
                'created_at' => $revision->created_at?->toIso8601String(),
                'approved_at' => $revision->approved_at?->toIso8601String(),
                'archived_at' => $revision->archived_at?->toIso8601String(),
                'gallery_ref' => $revision->galleryItem ? $this->artworkGalleryEntityKey($revision->galleryItem) : null,
                default => null,
            };
        }

        $disk = $revision->galleryItem?->file_disk ?: $this->settings->filesystemDisk();
        $media = $this->readMediaPayload($disk, $revision->spaces_path, $revision->original_filename, $includeMedia);
        $payload['media_checksum'] = $media['checksum'];

        if ($includeMedia && $media['content'] !== null) {
            $payload['media'] = $media;
        }

        return $payload;
    }

    private function readMediaPayload(?string $disk, ?string $path, ?string $filename, bool $includeMedia): array
    {
        try {
            if (! $path || ! $disk || ! Storage::disk($disk)->exists($path)) {
                return [
                    'checksum' => null,
                    'content' => null,
                    'filename' => $filename,
                    'extension' => pathinfo((string) $filename, PATHINFO_EXTENSION),
                ];
            }

            $binary = Storage::disk($disk)->get($path);

            return [
                'checksum' => hash('sha256', $binary),
                'content' => $includeMedia ? base64_encode($binary) : null,
                'filename' => $filename,
                'extension' => strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION)),
            ];
        } catch (\Throwable) {
            return [
                'checksum' => null,
                'content' => null,
                'filename' => $filename,
                'extension' => pathinfo((string) $filename, PATHINFO_EXTENSION),
            ];
        }
    }

    private function writePayloadToNode(SimpleXMLElement $node, array $payload): void
    {
        foreach ($payload as $key => $value) {
            $child = $node->addChild($key);

            if (is_bool($value)) {
                $child[0] = $value ? '1' : '0';
                continue;
            }

            if (is_array($value)) {
                $child->addAttribute('type', 'json');
                $child[0] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                continue;
            }

            $child[0] = htmlspecialchars((string) ($value ?? ''));
        }
    }

    private function importSuppliers(SimpleXMLElement $xml, string $batchUuid, array &$importedIds, array &$stats): array
    {
        $supplierMap = [];

        foreach ($xml->suppliers->supplier ?? [] as $node) {
            $payload = $this->nodePayload($node);
            $entityKey = (string) ($node['entity_key'] ?? '');
            $payloadHash = $this->payloadHash($payload);

            if ($entityKey !== '' && $this->wasTransferred('import', 'suppliers', $entityKey, null, $payloadHash)) {
                $stats['skipped']++;
                $existing = Supplier::withTrashed()->where('code', $payload['code'] ?? null)->first();
                if ($existing) {
                    $supplierMap[$entityKey] = $existing->id;
                }
                continue;
            }

            $existing = filled($payload['code'] ?? null)
                ? Supplier::withTrashed()->where('code', $payload['code'])->first()
                : null;

            if ($existing) {
                $supplierMap[$entityKey] = $existing->id;
                $this->markTransferred('import', 'suppliers', $entityKey, null, $payloadHash, $batchUuid);
                $stats['skipped']++;
                continue;
            }

            $supplier = Supplier::create([
                'name' => $payload['name'] ?? 'Tedarikçi',
                'code' => $payload['code'] ?? null,
                'email' => $payload['email'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'address' => $payload['address'] ?? null,
                'is_active' => $this->toBool($payload['is_active'] ?? true),
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->syncTimestamps($supplier->getTable(), $supplier->id, $payload['created_at'] ?? null, $payload['updated_at'] ?? null, $payload['deleted_at'] ?? null);

            $importedIds['suppliers'][] = $supplier->id;
            $supplierMap[$entityKey] = $supplier->id;
            $this->markTransferred('import', 'suppliers', $entityKey, null, $payloadHash, $batchUuid);
            $stats['suppliers']++;
        }

        return $supplierMap;
    }

    private function importUsers(SimpleXMLElement $xml, array $supplierMap, string $batchUuid, array &$importedIds, array &$stats): void
    {
        $defaultPassword = Hash::make('Import@' . now()->year);

        foreach ($xml->users->user ?? [] as $node) {
            $payload = $this->nodePayload($node);
            $entityKey = (string) ($node['entity_key'] ?? '');
            $payloadHash = $this->payloadHash($payload);

            if ($entityKey !== '' && $this->wasTransferred('import', 'users', $entityKey, null, $payloadHash)) {
                $stats['skipped']++;
                continue;
            }

            $email = (string) ($payload['email'] ?? '');

            if ($email !== '' && User::query()->where('email', $email)->exists()) {
                $this->markTransferred('import', 'users', $entityKey ?: 'user:' . $email, null, $payloadHash, $batchUuid);
                $stats['skipped']++;
                continue;
            }

            $departmentId = null;
            if (filled($payload['department_name'] ?? null)) {
                $departmentId = Department::query()->firstOrCreate(['name' => $payload['department_name']])->id;
            }

            $supplierId = filled($payload['supplier_ref'] ?? null)
                ? ($supplierMap[$payload['supplier_ref']] ?? Supplier::withTrashed()->where('code', $payload['supplier_ref'])->value('id'))
                : null;

            $user = new User([
                'name' => $payload['name'] ?? 'İçe Aktarılan Kullanıcı',
                'email' => $email,
                'password' => $defaultPassword,
                'role' => $payload['role'] ?? 'supplier',
                'is_active' => $this->toBool($payload['is_active'] ?? true),
                'supplier_id' => $supplierId,
                'department_id' => $departmentId,
                'permissions' => is_array($payload['permissions'] ?? null) ? $payload['permissions'] : null,
                'phone' => $payload['phone'] ?? null,
                'linkedin_url' => $payload['linkedin_url'] ?? null,
                'contact_email' => $payload['contact_email'] ?? null,
                'bio' => $payload['bio'] ?? null,
            ]);
            $user->save();

            $this->syncTimestamps($user->getTable(), $user->id, $payload['created_at'] ?? null, $payload['updated_at'] ?? null);

            $importedIds['users'][] = $user->id;
            $this->markTransferred('import', 'users', $entityKey ?: 'user:' . $email, null, $payloadHash, $batchUuid);
            $stats['users']++;
        }
    }

    private function importSupplierMappings(SimpleXMLElement $xml, array $supplierMap): void
    {
        foreach ($xml->suppliers->supplier ?? [] as $node) {
            $payload = $this->nodePayload($node);
            $entityKey = (string) ($node['entity_key'] ?? '');
            $supplierId = $supplierMap[$entityKey] ?? null;

            if (! $supplierId || ! is_array($payload['user_mappings'] ?? null)) {
                continue;
            }

            foreach ($payload['user_mappings'] as $mapping) {
                $email = $mapping['user_email'] ?? null;
                if (! $email) {
                    continue;
                }

                $user = User::query()->where('email', $email)->first();
                if (! $user) {
                    continue;
                }

                $exists = DB::table('supplier_users')
                    ->where('supplier_id', $supplierId)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('supplier_users')->insert([
                    'supplier_id' => $supplierId,
                    'user_id' => $user->id,
                    'title' => $mapping['title'] ?? null,
                    'is_primary' => $this->toBool($mapping['is_primary'] ?? false),
                    'can_download' => $this->toBool($mapping['can_download'] ?? false),
                    'can_approve' => $this->toBool($mapping['can_approve'] ?? false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function importPurchaseOrders(SimpleXMLElement $xml, array $supplierMap, string $batchUuid, array &$importedIds, array &$stats): void
    {
        foreach ($xml->purchase_orders->purchase_order ?? [] as $node) {
            $payload = $this->nodePayload($node);
            $entityKey = (string) ($node['entity_key'] ?? '');
            $payloadHash = $this->payloadHash($payload);

            if ($entityKey !== '' && $this->wasTransferred('import', 'purchase_orders', $entityKey, null, $payloadHash)) {
                $stats['skipped']++;
                continue;
            }

            $supplierRef = $payload['supplier_ref'] ?? null;
            $supplierId = $supplierRef
                ? ($supplierMap[$supplierRef] ?? Supplier::withTrashed()->where('code', $supplierRef)->value('id'))
                : null;

            if (! $supplierId || blank($payload['order_no'] ?? null)) {
                $stats['skipped']++;
                continue;
            }

            $existing = PurchaseOrder::query()
                ->where('supplier_id', $supplierId)
                ->where('order_no', $payload['order_no'])
                ->first();

            if ($existing) {
                $this->markTransferred('import', 'purchase_orders', $entityKey ?: $this->purchaseOrderNaturalKey($supplierRef, $payload['order_no']), null, $payloadHash, $batchUuid);
                $stats['skipped']++;
                continue;
            }

            $createdBy = filled($payload['created_by_email'] ?? null)
                ? User::query()->where('email', $payload['created_by_email'])->value('id')
                : auth()->id();

            $order = PurchaseOrder::create([
                'supplier_id' => $supplierId,
                'order_no' => $payload['order_no'],
                'status' => $payload['status'] ?? 'active',
                'order_date' => $payload['order_date'] ?? now()->toDateString(),
                'due_date' => $payload['due_date'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'created_by' => $createdBy,
            ]);

            $this->syncTimestamps($order->getTable(), $order->id, $payload['created_at'] ?? null, $payload['updated_at'] ?? null);

            foreach (($payload['lines'] ?? []) as $linePayload) {
                $line = PurchaseOrderLine::create([
                    'purchase_order_id' => $order->id,
                    'line_no' => (int) ($linePayload['line_no'] ?? 0),
                    'product_code' => $linePayload['product_code'] ?? null,
                    'description' => $linePayload['description'] ?? null,
                    'quantity' => (int) ($linePayload['quantity'] ?? 0),
                    'shipped_quantity' => (int) ($linePayload['shipped_quantity'] ?? 0),
                    'unit' => $linePayload['unit'] ?? null,
                    'artwork_status' => $linePayload['artwork_status'] ?? 'pending',
                    'notes' => $linePayload['notes'] ?? null,
                ]);

                $this->syncTimestamps($line->getTable(), $line->id, $linePayload['created_at'] ?? null, $linePayload['updated_at'] ?? null);
            }

            $importedIds['purchase_orders'][] = $order->id;
            $this->markTransferred('import', 'purchase_orders', $entityKey ?: $this->purchaseOrderNaturalKey($supplierRef, $payload['order_no']), null, $payloadHash, $batchUuid);
            $stats['purchase_orders']++;
        }
    }

    private function importArtworkGallery(SimpleXMLElement $xml, string $batchUuid, array &$importedIds, array &$stats): array
    {
        $galleryMap = [];

        foreach ($xml->artwork_gallery->item ?? [] as $node) {
            $payload = $this->nodePayload($node);
            $entityKey = (string) ($node['entity_key'] ?? '');
            $payloadHash = $this->payloadHash($payload);

            if ($entityKey !== '' && $this->wasTransferred('import', 'artwork_gallery', $entityKey, null, $payloadHash)) {
                $stats['skipped']++;
                $existing = $this->findExistingGalleryItem($payload);
                if ($existing) {
                    $galleryMap[$entityKey] = $existing;
                }
                continue;
            }

            $existing = $this->findExistingGalleryItem($payload);
            if ($existing) {
                $galleryMap[$entityKey] = $existing;
                $this->markTransferred('import', 'artwork_gallery', $entityKey, null, $payloadHash, $batchUuid);
                $stats['skipped']++;
                continue;
            }

            if (! is_array($payload['media'] ?? null) || blank($payload['media']['content'] ?? null)) {
                $stats['skipped']++;
                continue;
            }

            $binary = base64_decode((string) $payload['media']['content'], true);
            if ($binary === false) {
                $stats['skipped']++;
                continue;
            }

            $disk = $this->settings->filesystemDisk();
            $extension = strtolower((string) ($payload['media']['extension'] ?? pathinfo((string) ($payload['name'] ?? 'file.bin'), PATHINFO_EXTENSION)));
            $path = 'imports/gallery/' . Str::uuid() . ($extension ? '.' . $extension : '');
            Storage::disk($disk)->put($path, $binary, ['visibility' => 'private']);

            $categoryId = null;
            if (filled($payload['category_name'] ?? null)) {
                $categoryId = ArtworkCategory::query()->firstOrCreate(['name' => $payload['category_name']])->id;
            }

            $uploadedBy = filled($payload['uploaded_by_email'] ?? null)
                ? User::query()->where('email', $payload['uploaded_by_email'])->value('id')
                : null;

            $galleryItem = ArtworkGallery::create([
                'name' => $payload['name'] ?? ($payload['media']['filename'] ?? 'İçe Aktarılan Dosya'),
                'stock_code' => $payload['stock_code'] ?? null,
                'category_id' => $categoryId,
                'file_path' => $path,
                'file_disk' => $disk,
                'file_size' => strlen($binary),
                'file_type' => $payload['file_type'] ?? null,
                'uploaded_by' => $uploadedBy,
                'revision_note' => $payload['revision_note'] ?? null,
            ]);

            if (is_array($payload['tag_names'] ?? null)) {
                $tagIds = collect($payload['tag_names'])
                    ->filter()
                    ->map(fn ($name) => ArtworkTag::query()->firstOrCreate(['name' => $name])->id)
                    ->values()
                    ->all();
                $galleryItem->tags()->sync($tagIds);
            }

            $this->syncTimestamps($galleryItem->getTable(), $galleryItem->id, $payload['created_at'] ?? null, $payload['updated_at'] ?? null);

            $importedIds['artwork_gallery'][] = $galleryItem->id;
            $galleryMap[$entityKey] = $galleryItem;
            $this->markTransferred('import', 'artwork_gallery', $entityKey, null, $payloadHash, $batchUuid);
            $stats['artwork_gallery']++;
        }

        return $galleryMap;
    }

    private function importArtworkRevisions(SimpleXMLElement $xml, array $galleryMap, string $batchUuid, array &$importedIds, array &$stats): void
    {
        foreach ($xml->artwork_revisions->revision ?? [] as $node) {
            $payload = $this->nodePayload($node);
            $entityKey = (string) ($node['entity_key'] ?? '');
            $payloadHash = $this->payloadHash($payload);

            if ($entityKey !== '' && $this->wasTransferred('import', 'artwork_revisions', $entityKey, null, $payloadHash)) {
                $stats['skipped']++;
                continue;
            }

            $supplierRef = $payload['supplier_ref'] ?? null;
            $orderNo = $payload['order_no'] ?? null;
            $lineNo = (int) ($payload['line_no'] ?? 0);

            if (! $supplierRef || ! $orderNo || ! $lineNo) {
                $stats['skipped']++;
                continue;
            }

            $supplierId = Supplier::withTrashed()->where('code', $supplierRef)->value('id');
            $order = $supplierId
                ? PurchaseOrder::query()->where('supplier_id', $supplierId)->where('order_no', $orderNo)->first()
                : null;
            $line = $order?->lines()->where('line_no', $lineNo)->first();

            if (! $line) {
                $stats['skipped']++;
                continue;
            }

            $artwork = $line->artwork ?? Artwork::create([
                'order_line_id' => $line->id,
                'title' => pathinfo((string) ($payload['original_filename'] ?? ('Revizyon ' . ($payload['revision_no'] ?? ''))), PATHINFO_FILENAME),
            ]);

            if (! in_array($artwork->id, $importedIds['artworks'] ?? [], true)) {
                $importedIds['artworks'][] = $artwork->id;
            }

            $revisionNo = (int) ($payload['revision_no'] ?? 0);
            $existing = $artwork->revisions()->where('revision_no', $revisionNo)->first();

            if ($existing) {
                $this->markTransferred('import', 'artwork_revisions', $entityKey ?: $this->artworkRevisionNaturalKey($supplierRef, $orderNo, $lineNo, $revisionNo), null, $payloadHash, $batchUuid);
                $stats['skipped']++;
                continue;
            }

            $galleryItem = filled($payload['gallery_ref'] ?? null)
                ? ($galleryMap[$payload['gallery_ref']] ?? null)
                : null;

            $spacesPath = $galleryItem?->file_path;
            $mimeType = $payload['mime_type'] ?? null;
            $fileSize = (int) ($payload['file_size'] ?? 0);

            if (is_array($payload['media'] ?? null) && filled($payload['media']['content'] ?? null)) {
                $binary = base64_decode((string) $payload['media']['content'], true);

                if ($binary !== false) {
                    if (! $galleryItem) {
                        $disk = $this->settings->filesystemDisk();
                        $extension = strtolower((string) ($payload['media']['extension'] ?? pathinfo((string) ($payload['original_filename'] ?? 'file.bin'), PATHINFO_EXTENSION)));
                        $path = 'imports/artworks/' . Str::uuid() . ($extension ? '.' . $extension : '');
                        Storage::disk($disk)->put($path, $binary, ['visibility' => 'private']);
                        $spacesPath = $path;
                        $fileSize = strlen($binary);
                    } else {
                        $spacesPath = $galleryItem->file_path;
                        $fileSize = $galleryItem->file_size;
                        $mimeType = $galleryItem->file_type ?: $mimeType;
                    }
                }
            }

            if (! $spacesPath) {
                $stats['skipped']++;
                continue;
            }

            $uploadedBy = filled($payload['uploaded_by_email'] ?? null)
                ? User::query()->where('email', $payload['uploaded_by_email'])->value('id')
                : auth()->id();

            $revision = ArtworkRevision::create([
                'artwork_id' => $artwork->id,
                'artwork_gallery_id' => $galleryItem?->id,
                'revision_no' => $revisionNo,
                'original_filename' => $payload['original_filename'] ?? basename($spacesPath),
                'stored_filename' => basename($spacesPath),
                'spaces_path' => $spacesPath,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'is_active' => $this->toBool($payload['is_active'] ?? false),
                'uploaded_by' => $uploadedBy,
                'notes' => $payload['notes'] ?? null,
                'approval_status' => $payload['approval_status'] ?? null,
                'approved_at' => $payload['approved_at'] ?? null,
                'archived_at' => $payload['archived_at'] ?? null,
            ]);

            $this->syncTimestamps($revision->getTable(), $revision->id, $payload['created_at'] ?? null, $payload['created_at'] ?? null);

            if ($revision->is_active) {
                $artwork->revisions()->where('id', '!=', $revision->id)->update(['is_active' => false]);
                $artwork->update(['active_revision_id' => $revision->id]);
                $line->update(['artwork_status' => 'uploaded']);
            }

            $importedIds['artwork_revisions'][] = $revision->id;
            $this->markTransferred('import', 'artwork_revisions', $entityKey ?: $this->artworkRevisionNaturalKey($supplierRef, $orderNo, $lineNo, $revisionNo), null, $payloadHash, $batchUuid);
            $stats['artwork_revisions']++;
        }

        $this->dashboardCache->forgetAllAfterCommit();
    }

    private function nodePayload(SimpleXMLElement $node): array
    {
        $payload = [];

        foreach ($node->children() as $child) {
            $key = $child->getName();
            $type = (string) ($child['type'] ?? '');
            $value = trim((string) $child);

            if ($type === 'json') {
                $payload[$key] = json_decode($value, true) ?? [];
                continue;
            }

            $payload[$key] = $value;
        }

        return $payload;
    }

    private function findExistingGalleryItem(array $payload): ?ArtworkGallery
    {
        $stockCode = $payload['stock_code'] ?? null;
        $name = $payload['name'] ?? null;

        if (! filled($stockCode) && ! filled($name)) {
            return null;
        }

        return ArtworkGallery::query()
            ->when(filled($stockCode), fn ($query) => $query->where('stock_code', $stockCode))
            ->when(filled($name), fn ($query) => $query->where('name', $name))
            ->first();
    }

    private function supplierEntityKey(Supplier $supplier): string
    {
        if (filled($supplier->code)) {
            return 'supplier:' . $supplier->code;
        }

        if (filled($supplier->email)) {
            return 'supplier-email:' . $supplier->email;
        }

        return 'supplier-id:' . $supplier->id;
    }

    private function userEntityKey(User $user): string
    {
        return 'user:' . $user->email;
    }

    private function purchaseOrderEntityKey(PurchaseOrder $order): string
    {
        $supplierRef = $order->supplier?->code ?: 'supplier:' . $order->supplier_id;

        return $this->purchaseOrderNaturalKey($supplierRef, $order->order_no);
    }

    private function purchaseOrderNaturalKey(string $supplierRef, string $orderNo): string
    {
        return 'order:' . $supplierRef . '|' . $orderNo;
    }

    private function artworkGalleryEntityKey(ArtworkGallery $item): string
    {
        return implode('|', [
            'gallery',
            $item->stock_code ?: 'stock:none',
            $item->name,
        ]);
    }

    private function artworkRevisionEntityKey(ArtworkRevision $revision): string
    {
        $order = $revision->artwork?->orderLine?->purchaseOrder;
        $line = $revision->artwork?->orderLine;
        $supplierRef = $order?->supplier?->code ?: ($order ? 'supplier:' . $order->supplier_id : 'supplier:none');

        return $this->artworkRevisionNaturalKey(
            $supplierRef,
            (string) ($order?->order_no ?? ''),
            (int) ($line?->line_no ?? 0),
            (int) $revision->revision_no
        );
    }

    private function artworkRevisionNaturalKey(string $supplierRef, string $orderNo, int $lineNo, int $revisionNo): string
    {
        return 'revision:' . $supplierRef . '|' . $orderNo . '|' . $lineNo . '|' . $revisionNo;
    }

    private function syncTimestamps(string $table, int $id, ?string $createdAt, ?string $updatedAt, ?string $deletedAt = null): void
    {
        $payload = [];

        if ($createdAt) {
            $payload['created_at'] = $createdAt;
        }

        if ($updatedAt) {
            $payload['updated_at'] = $updatedAt;
        }

        if ($deletedAt && Schema::hasColumn($table, 'deleted_at')) {
            $payload['deleted_at'] = $deletedAt;
        }

        if ($payload !== []) {
            DB::table($table)->where('id', $id)->update($payload);
        }
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'yes'], true);
    }

    private function guardMediaExportSize(array $selection): void
    {
        $totalBytes = 0;

        if (array_key_exists('artwork_gallery', $selection)) {
            $totalBytes += (int) ArtworkGallery::query()->sum('file_size');
        }

        if (array_key_exists('artwork_revisions', $selection)) {
            $totalBytes += (int) ArtworkRevision::query()->sum('file_size');
        }

        if ($totalBytes > self::MEDIA_EXPORT_LIMIT_BYTES) {
            throw new \RuntimeException('Medya dahil dışa aktarım paketi 50 MB sınırını aşıyor. Lütfen medya seçimini kaldırın veya aktarımı bölüm bölüm yapın.');
        }
    }
}
