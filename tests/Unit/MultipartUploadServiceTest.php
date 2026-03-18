<?php

namespace Tests\Unit;

use App\Services\MultipartUploadService;
use App\Services\SpacesStorageService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MultipartUploadServiceTest extends TestCase
{
    public function test_small_file_uses_regular_upload(): void
    {
        // 50 MB dosya → multipart eşiği altında → SpacesStorageService::upload kullanmalı
        $spacesMock = $this->mock(SpacesStorageService::class);
        $spacesMock->shouldReceive('upload')->once()->andReturn([
            'spaces_path'       => 'test/path.pdf',
            'original_filename' => 'test.pdf',
            'stored_filename'   => 'uuid.pdf',
            'mime_type'         => 'application/pdf',
            'file_size'         => 52428800,
        ]);

        $file = UploadedFile::fake()->create('small.pdf', 50 * 1024, 'application/pdf');

        $service = app(MultipartUploadService::class);
        $service->upload($file, 'test/path.pdf');
    }

    public function test_spaces_path_format_is_correct(): void
    {
        $service = app(SpacesStorageService::class);

        // SpacesStorageService mock olmadan path formatını test et
        $this->markTestSkipped('Spaces bağlantısı olmadan çalışmaz — entegrasyon testine alın.');
    }
}
