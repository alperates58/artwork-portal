<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;

class MultipartUploadService
{
    private const CHUNK_SIZE = 50 * 1024 * 1024;
    private const MULTIPART_THRESHOLD = 100 * 1024 * 1024;

    private ?S3Client $client = null;
    private string $bucket = '';

    public function __construct(private PortalSettings $settings) {}

    public function upload(UploadedFile $file, string $path): array
    {
        if (! $this->usesSpaces()) {
            return app(SpacesStorageService::class)->upload($file, $path);
        }

        if ($file->getSize() >= self::MULTIPART_THRESHOLD) {
            return $this->multipartUpload($file, $path);
        }

        return app(SpacesStorageService::class)->upload($file, $path);
    }

    private function multipartUpload(UploadedFile $file, string $path): array
    {
        $client = $this->client();

        $response = $client->createMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $path,
            'ContentType' => $file->getMimeType(),
            'ACL' => 'private',
        ]);

        $uploadId = $response['UploadId'];
        $parts = [];
        $handle = fopen($file->getRealPath(), 'rb');
        $partNum = 1;

        try {
            while (! feof($handle)) {
                $chunk = fread($handle, self::CHUNK_SIZE);

                if ($chunk === false || strlen($chunk) === 0) {
                    break;
                }

                $partResponse = $client->uploadPart([
                    'Bucket' => $this->bucket,
                    'Key' => $path,
                    'UploadId' => $uploadId,
                    'PartNumber' => $partNum,
                    'Body' => $chunk,
                ]);

                $parts[] = [
                    'PartNumber' => $partNum,
                    'ETag' => $partResponse['ETag'],
                ];

                $partNum++;
            }

            fclose($handle);

            $client->completeMultipartUpload([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'UploadId' => $uploadId,
                'MultipartUpload' => ['Parts' => $parts],
            ]);
        } catch (\Exception $exception) {
            fclose($handle);

            $client->abortMultipartUpload([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'UploadId' => $uploadId,
            ]);

            throw $exception;
        }

        return [
            'spaces_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    private function usesSpaces(): bool
    {
        return $this->settings->filesystemDisk() === 'spaces';
    }

    private function client(): S3Client
    {
        if (! $this->usesSpaces()) {
            throw new \RuntimeException('Multipart upload yalnızca Spaces diski yapılandırıldığında kullanılabilir.');
        }

        if ($this->client instanceof S3Client) {
            return $this->client;
        }

        $spaces = $this->settings->spacesConfig();

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $spaces['region'],
            'endpoint' => $spaces['endpoint'],
            'credentials' => [
                'key' => $spaces['key'],
                'secret' => $spaces['secret'],
            ],
        ]);

        $this->bucket = (string) $spaces['bucket'];

        return $this->client;
    }
}
