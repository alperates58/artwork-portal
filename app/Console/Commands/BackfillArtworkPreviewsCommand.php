<?php

namespace App\Console\Commands;

use App\Jobs\GenerateArtworkPreviewJob;
use App\Models\ArtworkRevision;
use App\Services\ArtworkPreviewGenerator;
use Illuminate\Console\Command;

class BackfillArtworkPreviewsCommand extends Command
{
    protected $signature = 'artwork:preview-backfill
        {--limit=500 : Kuyruga alinacak maksimum revizyon sayisi}
        {--active-only : Yalnizca aktif revizyonlari tara}
        {--dry-run : Kuyruga almadan sadece sonucu goster}';

    protected $description = 'Onizleme PNGsi olmayan desteklenen artwork revizyonlarini yeniden kuyruga alir.';

    public function handle(ArtworkPreviewGenerator $generator): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $activeOnly = (bool) $this->option('active-only');

        $query = ArtworkRevision::query()
            ->select([
                'id',
                'artwork_id',
                'artwork_gallery_id',
                'revision_no',
                'original_filename',
                'mime_type',
                'spaces_path',
                'preview_spaces_path',
                'is_active',
            ])
            ->with('galleryItem:id,file_disk')
            ->whereNull('preview_spaces_path')
            ->orderBy('id');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $scanned = 0;
        $queued = 0;
        $skipped = 0;

        foreach ($query->lazyById(100, 'id') as $revision) {
            $scanned++;

            if (! $generator->supports($revision) || $revision->has_preview) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $queued++;
            } else {
                GenerateArtworkPreviewJob::dispatch($revision->id);
                $queued++;
            }

            if ($queued >= $limit) {
                break;
            }
        }

        $this->newLine();
        $this->table(
            ['Alan', 'Deger'],
            [
                ['Taranan revizyon', $scanned],
                ['Kuyruga alinan', $queued],
                ['Atlanan', $skipped],
                ['Mod', $dryRun ? 'Dry-run' : 'Canli'],
                ['Filtre', $activeOnly ? 'Sadece aktif revizyonlar' : 'Tum revizyonlar'],
            ]
        );

        if ($queued === 0) {
            $this->info('Kuyruga alinacak uygun artwork revizyonu bulunamadi.');

            return self::SUCCESS;
        }

        $this->info($dryRun
            ? 'Dry-run tamamlandi. Yukaridaki revizyonlar kuyruga alinabilir.'
            : 'Preview backfill komutlari kuyruga alindi.');

        return self::SUCCESS;
    }
}
