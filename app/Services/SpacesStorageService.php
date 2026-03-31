<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

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
        return $this->buildVariantPath($supplierId, $orderNo, $lineId, $revisionNo, 'original', $extension);
    }

    public function buildVariantPath(
        int $supplierId,
        string $orderNo,
        int $lineId,
        int $revisionNo,
        string $variant,
        string $extension
    ): string {
        $uuid = (string) Str::uuid();

        return sprintf(
            'artworks/supplier/%d/orders/%s/lines/%d/rev/%d/%s/%s.%s',
            $supplierId,
            Str::of($orderNo)
                ->replace(['/', '\\'], ' ')
                ->slug(),
            $lineId,
            $revisionNo,
            Str::slug($variant),
            $uuid,
            strtolower($extension)
        );
    }

    public function upload(UploadedFile $file, string $path, ?string $disk = null): array
    {
        $stream = fopen($file->getRealPath(), 'rb');
        $resolvedDisk = $this->diskName($disk);
        $options = [
            'ContentType' => $file->getMimeType(),
        ];

        if ($this->usesSpaces($resolvedDisk)) {
            $options['visibility'] = 'private';
        }

        Storage::disk($resolvedDisk)->put($path, $stream, $options);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->normalizeLocalPermissions($resolvedDisk, $path);

        return [
            'spaces_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    public function uploadFileFromPath(
        string $sourcePath,
        string $destinationPath,
        string $originalFilename,
        ?string $mimeType = null,
        ?string $disk = null,
    ): array {
        $stream = fopen($sourcePath, 'rb');
        $resolvedDisk = $this->diskName($disk);
        $options = [
            'ContentType' => $mimeType ?? 'image/png',
        ];

        if (! is_resource($stream)) {
            throw new RuntimeException('Önizleme dosyası okunamadı.');
        }

        if ($this->usesSpaces($resolvedDisk)) {
            $options['visibility'] = 'private';
        }

        Storage::disk($resolvedDisk)->put($destinationPath, $stream, $options);

        fclose($stream);

        $this->normalizeLocalPermissions($resolvedDisk, $destinationPath);

        return [
            'spaces_path' => $destinationPath,
            'original_filename' => $originalFilename,
            'stored_filename' => basename($destinationPath),
            'mime_type' => $mimeType ?? 'image/png',
            'file_size' => filesize($sourcePath) ?: 0,
        ];
    }

    public function presignedUrl(string $path, int $minutes = 0, ?string $disk = null, ?string $downloadName = null): string
    {
        if (! $this->usesSpaces($disk)) {
            throw new RuntimeException('Presigned URL yalnızca Spaces diski yapılandırıldığında üretilebilir.');
        }

        if ($minutes === 0) {
            $minutes = (int) config('artwork.download_ttl', 15);
        }

        $client = $this->client();
        $spaces = $this->settings->spacesConfig();

        $command = $client->getCommand('GetObject', [
            'Bucket' => $spaces['bucket'],
            'Key' => $path,
            'ResponseContentDisposition' => $this->contentDisposition('attachment', $downloadName ?: basename($path)),
        ]);

        $request = $client->createPresignedRequest($command, "+{$minutes} minutes");

        return (string) $request->getUri();
    }

    public function presignedInlineUrl(string $path, int $minutes = 5, ?string $disk = null): string
    {
        if (! $this->usesSpaces($disk)) {
            throw new RuntimeException('Presigned URL yalnızca Spaces diski yapılandırıldığında üretilebilir.');
        }

        $client = $this->client();
        $spaces = $this->settings->spacesConfig();

        $command = $client->getCommand('GetObject', [
            'Bucket' => $spaces['bucket'],
            'Key' => $path,
            'ResponseContentDisposition' => 'inline; filename="' . basename($path) . '"',
        ]);

        $request = $client->createPresignedRequest($command, "+{$minutes} minutes");

        return (string) $request->getUri();
    }

    public function delete(string $path, ?string $disk = null): bool
    {
        return Storage::disk($this->diskName($disk))->delete($path);
    }

    public function exists(string $path, ?string $disk = null): bool
    {
        return Storage::disk($this->diskName($disk))->exists($path);
    }

    public function usesSpaces(?string $disk = null): bool
    {
        return $this->diskName($disk) === 'spaces';
    }

    private function diskName(?string $disk = null): string
    {
        return $disk ?: $this->settings->filesystemDisk();
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

    private function contentDisposition(string $disposition, string $filename): string
    {
        $filename = trim(str_replace(["\r", "\n", '"'], '', $filename));

        if ($filename === '') {
            $filename = 'download';
        }

        $fallbackName = trim((string) Str::ascii(pathinfo($filename, PATHINFO_FILENAME)));
        $extension = trim((string) pathinfo($filename, PATHINFO_EXTENSION));

        if ($fallbackName === '') {
            $fallbackName = 'download';
        }

        if ($extension !== '') {
            $fallbackName .= '.' . $extension;
        }

        return sprintf(
            "%s; filename=\"%s\"; filename*=UTF-8''%s",
            $disposition,
            addcslashes($fallbackName, "\\\""),
            rawurlencode($filename),
        );
    }

    private function normalizeLocalPermissions(string $disk, string $path): void
    {
        if ($disk === 'spaces') {
            return;
        }

        $filesystem = Storage::disk($disk);
        $absolutePath = $filesystem->path($path);

        if (! is_file($absolutePath)) {
            return;
        }

        $rootPath = rtrim($filesystem->path(''), DIRECTORY_SEPARATOR);
        $currentDir = dirname($absolutePath);
        $directories = [];

        while ($currentDir !== '' && str_starts_with($currentDir, $rootPath)) {
            $directories[] = $currentDir;

            if ($currentDir === $rootPath) {
                break;
            }

            $parentDir = dirname($currentDir);

            if ($parentDir === $currentDir) {
                break;
            }

            $currentDir = $parentDir;
        }

        foreach (array_reverse($directories) as $directory) {
            $this->applyOwnership($directory, true);
        }

        $this->applyOwnership($absolutePath, false);

        $this->normalizeArtworkTree($filesystem, $absolutePath);
    }

    private function normalizeArtworkTree($filesystem, string $absolutePath): void
    {
        $artworksRoot = rtrim($filesystem->path('artworks'), DIRECTORY_SEPARATOR);

        if (! str_starts_with($absolutePath, $artworksRoot) || ! is_dir($artworksRoot)) {
            return;
        }

        $this->applyOwnership($artworksRoot, true);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($artworksRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $this->applyOwnership($item->getPathname(), $item->isDir());
        }
    }

    private function applyOwnership(string $path, bool $isDirectory): void
    {
        @chown($path, 'www-data');
        @chgrp($path, 'www-data');
        @chmod($path, $isDirectory ? 0775 : 0664);
    }
}
