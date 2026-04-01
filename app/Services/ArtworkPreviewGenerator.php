<?php

namespace App\Services;

use App\Models\ArtworkRevision;
use App\Support\ArtworkFileName;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ArtworkPreviewGenerator
{
    private const PREVIEW_EXTENSION = 'png';

    private const PREVIEW_MAX_WIDTH = 1600;

    private const PREVIEW_DENSITY = 144;

    private const GHOSTSCRIPT_EXTENSIONS = ['ai', 'eps', 'pdf'];

    private const MAGICK_EXTENSIONS = ['ai', 'eps', 'pdf', 'psd'];

    public function __construct(
        private SpacesStorageService $spaces,
        private PortalSettings $settings,
        private AuditLogService $audit,
    ) {}

    public function supports(ArtworkRevision $revision): bool
    {
        return in_array($this->extension($revision), [...self::GHOSTSCRIPT_EXTENSIONS, 'psd'], true);
    }

    public function supportsGalleryItem(\App\Models\ArtworkGallery $galleryItem): bool
    {
        $ext = strtolower(pathinfo($galleryItem->name, PATHINFO_EXTENSION));

        return in_array($ext, [...self::GHOSTSCRIPT_EXTENSIONS, 'psd'], true);
    }

    public function generateForGalleryItem(\App\Models\ArtworkGallery $galleryItem): bool
    {
        if (! $this->supportsGalleryItem($galleryItem) || filled($galleryItem->preview_file_path)) {
            return false;
        }

        $ext = strtolower(pathinfo($galleryItem->name, PATHINFO_EXTENSION));

        $this->audit->log('artwork.preview.started', $galleryItem, [
            'source_extension' => $ext,
        ]);

        $tempDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'artwork-preview-' . Str::uuid();

        try {
            if (! mkdir($tempDirectory, 0777, true) && ! is_dir($tempDirectory)) {
                throw new RuntimeException('Geçici preview klasörü oluşturulamadı.');
            }

            $inputPath  = $tempDirectory . DIRECTORY_SEPARATOR . 'source.' . $ext;
            $outputPath = $tempDirectory . DIRECTORY_SEPARATOR . 'preview.' . self::PREVIEW_EXTENSION;

            $disk   = $galleryItem->file_disk ?: $this->settings->filesystemDisk();
            $stream = Storage::disk($disk)->readStream($galleryItem->file_path);

            if (! is_resource($stream)) {
                throw new RuntimeException('Orijinal artwork dosyası okunamadı.');
            }

            $targetStream = fopen($inputPath, 'wb');

            if (! is_resource($targetStream)) {
                fclose($stream);
                throw new RuntimeException('Geçici artwork dosyası yazılamadı.');
            }

            stream_copy_to_stream($stream, $targetStream);
            fclose($stream);
            fclose($targetStream);

            $this->runConversion($ext, $inputPath, $outputPath);

            if (! is_file($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('PNG önizleme dosyası üretilemedi.');
            }

            $uuid            = (string) Str::uuid();
            $destinationPath = sprintf('artworks/gallery/%d/preview/%s.%s', $galleryItem->id, $uuid, self::PREVIEW_EXTENSION);

            $previewData = $this->spaces->uploadFileFromPath(
                sourcePath: $outputPath,
                destinationPath: $destinationPath,
                originalFilename: \App\Support\ArtworkFileName::preview(
                    stockCode: $galleryItem->stock_code,
                    revisionNo: (int) ($galleryItem->revision_no ?? 1),
                    fallback: $galleryItem->stock_code ?? 'gallery',
                ),
                mimeType: 'image/png',
                disk: $disk,
            );

            $galleryItem->forceFill([
                'preview_file_name' => $previewData['original_filename'],
                'preview_file_path' => $previewData['spaces_path'],
                'preview_file_disk' => $disk,
                'preview_file_size' => $previewData['file_size'],
                'preview_file_type' => $previewData['mime_type'],
            ])->save();

            $this->audit->log('artwork.preview.success', $galleryItem, [
                'preview_filename' => $previewData['original_filename'],
                'preview_file_size' => $previewData['file_size'],
            ]);

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            $this->audit->log('artwork.preview.failed', $galleryItem, [
                'error' => $exception->getMessage(),
            ]);

            return false;
        } finally {
            $this->deleteDirectory($tempDirectory);
        }
    }

    public function generateForRevision(ArtworkRevision $revision): bool
    {
        if (! $this->supports($revision) || $revision->has_preview) {
            return false;
        }

        $this->audit->log('artwork.preview.started', $revision, [
            'revision_no' => $revision->revision_no,
            'original_filename' => $revision->original_filename,
            'source_extension' => $this->extension($revision),
        ]);

        $tempDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'artwork-preview-' . Str::uuid();

        try {
            if (! mkdir($tempDirectory, 0777, true) && ! is_dir($tempDirectory)) {
                throw new RuntimeException('Geçici preview klasörü oluşturulamadı.');
            }

            $inputPath = $tempDirectory . DIRECTORY_SEPARATOR . 'source.' . $this->extension($revision);
            $outputPath = $tempDirectory . DIRECTORY_SEPARATOR . 'preview.' . self::PREVIEW_EXTENSION;

            $this->copyOriginalToTemp($revision, $inputPath);
            $this->runConversion($this->extension($revision), $inputPath, $outputPath);

            if (! is_file($outputPath) || filesize($outputPath) === 0) {
                throw new RuntimeException('PNG önizleme dosyası üretilemedi.');
            }

            $previewData = $this->spaces->uploadFileFromPath(
                sourcePath: $outputPath,
                destinationPath: $this->previewDestinationPath($revision),
                originalFilename: ArtworkFileName::preview(
                    stockCode: $revision->artwork?->orderLine?->product_code
                        ?? $revision->galleryItem?->stock_code
                        ?? pathinfo($revision->original_filename, PATHINFO_FILENAME),
                    revisionNo: (int) $revision->revision_no,
                    fallback: $revision->artwork?->orderLine?->product_code
                        ?? $revision->galleryItem?->stock_code,
                ),
                mimeType: 'image/png',
                disk: $revision->galleryItem?->file_disk ?: $this->settings->filesystemDisk(),
            );

            $revision->forceFill([
                'preview_original_filename' => $previewData['original_filename'],
                'preview_stored_filename' => $previewData['stored_filename'],
                'preview_spaces_path' => $previewData['spaces_path'],
                'preview_mime_type' => $previewData['mime_type'],
                'preview_file_size' => $previewData['file_size'],
            ])->save();

            if ($revision->galleryItem) {
                $revision->galleryItem->forceFill([
                    'preview_file_name' => $previewData['original_filename'],
                    'preview_file_path' => $previewData['spaces_path'],
                    'preview_file_disk' => $revision->galleryItem->file_disk ?: $this->settings->filesystemDisk(),
                    'preview_file_size' => $previewData['file_size'],
                    'preview_file_type' => $previewData['mime_type'],
                ])->save();
            }

            $this->audit->log('artwork.preview.success', $revision, [
                'revision_no' => $revision->revision_no,
                'original_filename' => $revision->original_filename,
                'preview_filename' => $previewData['original_filename'],
                'preview_file_size' => $previewData['file_size'],
            ]);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            $this->audit->log('artwork.preview.failed', $revision, [
                'revision_no' => $revision->revision_no,
                'original_filename' => $revision->original_filename,
                'error' => $exception->getMessage(),
            ]);

            return false;
        } finally {
            $this->deleteDirectory($tempDirectory);
        }
    }

    private function copyOriginalToTemp(ArtworkRevision $revision, string $targetPath): void
    {
        $disk = $revision->galleryItem?->file_disk ?: $this->settings->filesystemDisk();
        $stream = Storage::disk($disk)->readStream($revision->spaces_path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Orijinal artwork dosyası okunamadı.');
        }

        $targetStream = fopen($targetPath, 'wb');

        if (! is_resource($targetStream)) {
            fclose($stream);
            throw new RuntimeException('Geçici artwork dosyası yazılamadı.');
        }

        stream_copy_to_stream($stream, $targetStream);

        fclose($stream);
        fclose($targetStream);
    }

    private function runConversion(string $extension, string $inputPath, string $outputPath): void
    {
        $commands = $this->conversionCommands($extension, $inputPath, $outputPath);
        $lastError = null;

        foreach ($commands as $command) {
            $result = Process::timeout(120)->run($command);

            if ($result->successful() && is_file($outputPath) && filesize($outputPath) > 0) {
                return;
            }

            $lastError = trim($result->errorOutput() ?: $result->output()) ?: 'Bilinmeyen dönüşüm hatası.';
        }

        throw new RuntimeException($lastError ?: 'PNG önizleme üretimi başarısız oldu.');
    }

    private function conversionCommands(string $extension, string $inputPath, string $outputPath): array
    {
        $commands = [];

        if (in_array($extension, self::GHOSTSCRIPT_EXTENSIONS, true) && $this->binaryAvailable('gs')) {
            $ghostscript = [
                'gs',
                '-dSAFER',
                '-dBATCH',
                '-dNOPAUSE',
                '-sDEVICE=pngalpha',
                '-dTextAlphaBits=4',
                '-dGraphicsAlphaBits=4',
                '-r' . self::PREVIEW_DENSITY,
                '-dFirstPage=1',
                '-dLastPage=1',
            ];

            if (in_array($extension, ['ai', 'eps'], true)) {
                $ghostscript[] = '-dEPSCrop';
            }

            $ghostscript[] = '-sOutputFile=' . $outputPath;
            $ghostscript[] = $inputPath;

            $commands[] = $ghostscript;
        }

        if (in_array($extension, self::MAGICK_EXTENSIONS, true)) {
            foreach (['magick', 'convert'] as $binary) {
                if (! $this->binaryAvailable($binary)) {
                    continue;
                }

                $commands[] = [
                    $binary,
                    '-density',
                    (string) self::PREVIEW_DENSITY,
                    $inputPath . '[0]',
                    '-resize',
                    self::PREVIEW_MAX_WIDTH . 'x' . self::PREVIEW_MAX_WIDTH . '>',
                    '-background',
                    'white',
                    '-alpha',
                    'remove',
                    '-alpha',
                    'off',
                    $outputPath,
                ];
            }
        }

        if ($commands === []) {
            throw new RuntimeException('Preview üretimi için Ghostscript veya ImageMagick aracı bulunamadı.');
        }

        return $commands;
    }

    private function previewDestinationPath(ArtworkRevision $revision): string
    {
        // Gallery-only revisions have no artwork (order) association
        if ($revision->artwork && $revision->artwork->orderLine) {
            $orderLine = $revision->artwork->orderLine;

            return $this->spaces->buildVariantPath(
                supplierId: $orderLine->purchaseOrder->supplier_id,
                orderNo: $orderLine->purchaseOrder->order_no,
                lineId: $orderLine->id,
                revisionNo: $revision->revision_no,
                variant: 'preview',
                extension: self::PREVIEW_EXTENSION,
            );
        }

        // Gallery-only revision: use gallery item path convention
        $galleryId = $revision->artwork_gallery_id ?? 0;
        $uuid = (string) \Illuminate\Support\Str::uuid();

        return sprintf(
            'artworks/gallery/%d/rev/%d/preview/%s.%s',
            $galleryId,
            $revision->revision_no ?? 0,
            $uuid,
            self::PREVIEW_EXTENSION
        );
    }

    private function extension(ArtworkRevision $revision): string
    {
        return strtolower((string) pathinfo($revision->original_filename, PATHINFO_EXTENSION));
    }

    private function binaryAvailable(string $binary): bool
    {
        try {
            $result = Process::timeout(5)->run([$binary, '-version']);

            return $result->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = scandir($path) ?: [];

        foreach ($files as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
                continue;
            }

            @unlink($fullPath);
        }

        @rmdir($path);
    }
}
