<?php

namespace App\Services;

use App\Models\PortalUpdateEvent;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class PortalUpdatePreparationService
{
    public function __construct(
        private PortalVersionService $versionService,
        private ReleaseManifestService $manifestService,
    ) {}

    public function prepare(?User $actor, array $incoming, string $triggerSource = 'admin'): PortalUpdateEvent
    {
        if (! $this->hasEventsTable()) {
            throw new \RuntimeException('portal_update_events tablosu bulunamadi.');
        }

        $current = $this->versionService->current();

        PortalUpdateEvent::query()
            ->where('type', 'prepare')
            ->where('status', 'pending')
            ->update([
                'status' => 'superseded',
                'completed_at' => now(),
            ]);

        $payload = $this->manifestService->buildHistoryPayload($incoming['target_release']);

        return PortalUpdateEvent::query()->create([
            'type' => 'prepare',
            'status' => 'pending',
            'trigger_source' => $triggerSource,
            'actor_id' => $actor?->id,
            'branch' => $current['branch'],
            'local_commit' => $current['full_commit'],
            'local_version' => $incoming['current_version'] ?? $current['version'],
            'remote_version' => $incoming['target_version'],
            'update_available' => true,
            'message' => 'Admin tarafinda guncelleme hazirligi onaylandi. Uygulama yalnizca CLI/deploy akisi ile tamamlanmalidir.',
            'meta' => [
                'release_count' => $incoming['release_count'] ?? 1,
                'target_release_version' => $incoming['target_version'] ?? null,
            ],
            'started_at' => now(),
            'from_version' => $incoming['current_version'] ?? $current['version'],
            'to_version' => $incoming['target_version'],
            ...$payload,
        ]);
    }

    private function hasEventsTable(): bool
    {
        return Schema::hasTable('portal_update_events');
    }
}
