<?php

namespace Tests\Unit;

use App\Services\PortalSettings;
use App\Services\SpacesStorageService;
use Tests\TestCase;

class SpacesStorageServiceTest extends TestCase
{
    private SpacesStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Config mock — gerçek S3 bağlantısı olmadan test
        config([
            'filesystems.disks.spaces.key'      => 'test-key',
            'filesystems.disks.spaces.secret'   => 'test-secret',
            'filesystems.disks.spaces.region'   => 'fra1',
            'filesystems.disks.spaces.endpoint' => 'https://fra1.digitaloceanspaces.com',
            'filesystems.disks.spaces.bucket'   => 'test-bucket',
        ]);
        $this->service = new SpacesStorageService(new PortalSettings());
    }

    public function test_build_path_produces_correct_structure(): void
    {
        $path = $this->service->buildPath(
            supplierId: 42,
            orderNo:    'PO-2024-001',
            lineId:     7,
            revisionNo: 3,
            extension:  'pdf'
        );

        $this->assertStringStartsWith('artworks/supplier/42/orders/po-2024-001/lines/7/rev/3/', $path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function test_build_path_sanitizes_order_number(): void
    {
        $path = $this->service->buildPath(42, 'PO 2024/001', 1, 1, 'pdf');

        // Boşluk ve slash slug haline dönmeli
        $this->assertStringContainsString('po-2024-001', $path);
    }

    public function test_build_path_lowercases_extension(): void
    {
        $path = $this->service->buildPath(1, 'PO-001', 1, 1, 'PDF');

        $this->assertStringEndsWith('.pdf', $path);
    }

    public function test_build_path_generates_unique_paths(): void
    {
        $path1 = $this->service->buildPath(1, 'PO-001', 1, 1, 'pdf');
        $path2 = $this->service->buildPath(1, 'PO-001', 1, 1, 'pdf');

        // Her çağrıda UUID farklı olmalı
        $this->assertNotEquals($path1, $path2);
    }

    public function test_path_contains_all_segments(): void
    {
        $path = $this->service->buildPath(5, 'PO-TEST', 10, 2, 'ai');

        $this->assertStringContainsString('supplier/5', $path);
        $this->assertStringContainsString('lines/10', $path);
        $this->assertStringContainsString('rev/2', $path);
        $this->assertStringEndsWith('.ai', $path);
    }
}
