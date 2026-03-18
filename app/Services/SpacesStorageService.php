<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpacesStorageService
{
    private ?S3Client $client = null;

    public function __construct()
    {
        if ($this->usesSpaces()) {
            $this->client = new S3Client([
                'version'     => 'latest',
                'region'      => config('filesystems.disks.spaces.region'),
                'endpoint'    => config('filesystems.disks.spaces.endpoint'),
                'credentials' => [
                    'key'    => config('filesystems.disks.spaces.key'),
                    'secret' => config('filesystems.disks.spaces.secret'),
                ],
                'use_path_style_endpoint' => false,
            ]);
        }
    }

    /**
     * DO Spaces için klasör yolu üretir
     * artworks/supplier/42/orders/PO-2024-001/lines/7/rev/3/uuid.pdf
     */
    public function buildPath(
        int    $supplierId,
        string $orderNo,
        int    $lineId,
        int    $revisionNo,
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

    /**
     * Dosyayı Spaces'e yükler — stream ile hafıza verimli (1 GB+ için)
     */
    public function upload(UploadedFile $file, string $path): array
    {
        $stream = fopen($file->getRealPath(), 'rb');

        Storage::disk($this->diskName())->put($path, $stream, [
            'visibility'  => 'private',
            'ContentType' => $file->getMimeType(),
        ]);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return [
            'spaces_path'       => $path,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename'   => basename($path),
            'mime_type'         => $file->getMimeType(),
            'file_size'         => $file->getSize(),
        ];
    }

    /**
     * Geçici presigned URL üretir — dosya public değil
     *
     * @param string $path      Spaces içindeki dosya yolu
     * @param int    $minutes   Kaç dakika geçerli olacak (default: .env'den)
     */
    public function presignedUrl(string $path, int $minutes = 0): string
    {
        if (! $this->usesSpaces()) {
            throw new \RuntimeException('Presigned URL yalnızca Spaces diski yapılandırıldığında üretilebilir.');
        }

        if ($minutes === 0) {
            $minutes = (int) config('artwork.download_ttl', 15);
        }

        $client = $this->client;

        if (! $client) {
            throw new \RuntimeException('Spaces istemcisi başlatılamadı.');
        }

        $command = $client->getCommand('GetObject', [
            'Bucket'                     => config('filesystems.disks.spaces.bucket'),
            'Key'                        => $path,
            'ResponseContentDisposition' => 'attachment; filename="' . basename($path) . '"',
        ]);

        $request = $client->createPresignedRequest($command, "+{$minutes} minutes");

        return (string) $request->getUri();
    }

    /**
     * Dosyayı Spaces'ten sil (arşivleme yerine gerçek silme — nadiren kullanılacak)
     */
    public function delete(string $path): bool
    {
        return Storage::disk($this->diskName())->delete($path);
    }

    /**
     * Dosya var mı kontrolü
     */
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
        return (string) config('filesystems.default', 'local');
    }
}
