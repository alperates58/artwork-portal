<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpacesStorageService
{
    private ?S3Client $client = null;

    public function __construct(private PortalSettings $settings) {}

    public function buildPath(
        int $supplierId,
        string $orderNo,
        int $lineId,
        int $revisionNo,
        string $extension
    ): string {
        $uuid = (string) Str::uuid();

        return sprintf(
            'artworks/supplier/%d/orders/%s/lines/%d/rev/%d/%s.%s',
            $supplierId,
            Str::slug($orderNo),
            $lineId,
            $revisionNo,
            $uuid,
            strtolower($extension)
        );
    }

    public function upload(UploadedFile $file, string $path): array
    {
        $stream = fopen($file->getRealPath(), 'rb');

        Storage::disk($this->diskName())->put($path, $stream, [
            'visibility' => 'private',
            'ContentType' => $file->getMimeType(),
        ]);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return [
            'spaces_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    public function presignedUrl(string $path, int $minutes = 0): string
    {
        if (! $this->usesSpaces()) {
            throw new \RuntimeException('Presigned URL yalnızca Spaces diski yapılandırıldığında üretilebilir.');
        }

        if ($minutes === 0) {
            $minutes = (int) config('artwork.download_ttl', 15);
        }

        $client = $this->client();
        $spaces = $this->settings->spacesConfig();

        $command = $client->getCommand('GetObject', [
            'Bucket' => $spaces['bucket'],
            'Key' => $path,
            'ResponseContentDisposition' => 'attachment; filename="' . basename($path) . '"',
        ]);

        $request = $client->createPresignedRequest($command, "+{$minutes} minutes");

        return (string) $request->getUri();
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->diskName())->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->diskName())->exists($path);
    }

    public function usesSpaces(): bool
    {
        return $this->diskName() === 'spaces';
    }

    private function diskName(): string
    {
        return $this->settings->filesystemDisk();
    }

    private function client(): S3Client
    {
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
            'use_path_style_endpoint' => false,
        ]);

        return $this->client;
    }
}
