<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * 1 GB+ dosyalar için DO Spaces multipart upload
 *
 * Strateji:
 * - 100 MB altı → tek request (mevcut SpacesStorageService::upload)
 * - 100 MB üstü → S3 Multipart API (5 MB minimum chunk, maks 10.000 chunk)
 *
 * Avantajlar:
 * - Upload kesintisinde kaldığı yerden devam edebilir
 * - Her chunk ayrı HTTP isteği → timeout riski ortadan kalkar
 * - Paralel chunk upload ile hız artışı sağlanabilir
 */
class MultipartUploadService
{
    private const CHUNK_SIZE        = 50 * 1024 * 1024;  // 50 MB per chunk
    private const MULTIPART_THRESHOLD = 100 * 1024 * 1024; // 100 MB threshold

    private S3Client $client;
    private string   $bucket;

    public function __construct()
    {
        $this->client = new S3Client([
            'version'     => 'latest',
            'region'      => config('filesystems.disks.spaces.region'),
            'endpoint'    => config('filesystems.disks.spaces.endpoint'),
            'credentials' => [
                'key'    => config('filesystems.disks.spaces.key'),
                'secret' => config('filesystems.disks.spaces.secret'),
            ],
        ]);

        $this->bucket = config('filesystems.disks.spaces.bucket');
    }

    /**
     * Dosya boyutuna göre otomatik yükleme stratejisi seçer
     */
    public function upload(UploadedFile $file, string $path): array
    {
        if ($file->getSize() >= self::MULTIPART_THRESHOLD) {
            return $this->multipartUpload($file, $path);
        }

        return app(SpacesStorageService::class)->upload($file, $path);
    }

    /**
     * S3 Multipart Upload — büyük dosyalar için
     */
    private function multipartUpload(UploadedFile $file, string $path): array
    {
        // 1. Upload başlat
        $response = $this->client->createMultipartUpload([
            'Bucket'      => $this->bucket,
            'Key'         => $path,
            'ContentType' => $file->getMimeType(),
            'ACL'         => 'private',
        ]);

        $uploadId = $response['UploadId'];
        $parts    = [];
        $handle   = fopen($file->getRealPath(), 'rb');
        $partNum  = 1;

        try {
            while (! feof($handle)) {
                $chunk = fread($handle, self::CHUNK_SIZE);

                if ($chunk === false || strlen($chunk) === 0) {
                    break;
                }

                $partResponse = $this->client->uploadPart([
                    'Bucket'     => $this->bucket,
                    'Key'        => $path,
                    'UploadId'   => $uploadId,
                    'PartNumber' => $partNum,
                    'Body'       => $chunk,
                ]);

                $parts[] = [
                    'PartNumber' => $partNum,
                    'ETag'       => $partResponse['ETag'],
                ];

                $partNum++;
            }

            fclose($handle);

            // 2. Upload tamamla
            $this->client->completeMultipartUpload([
                'Bucket'          => $this->bucket,
                'Key'             => $path,
                'UploadId'        => $uploadId,
                'MultipartUpload' => ['Parts' => $parts],
            ]);

        } catch (\Exception $e) {
            // Hata durumunda yarım upload'ı temizle
            fclose($handle);
            $this->client->abortMultipartUpload([
                'Bucket'   => $this->bucket,
                'Key'      => $path,
                'UploadId' => $uploadId,
            ]);

            throw $e;
        }

        return [
            'spaces_path'       => $path,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename'   => basename($path),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
        ];
    }
}
