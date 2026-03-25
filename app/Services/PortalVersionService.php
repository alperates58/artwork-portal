<?php

namespace App\Services;

class PortalVersionService
{
    public function __construct(
        private ReleaseManifestService $manifestService,
    ) {}

    public function current(): array
    {
        $headPath = base_path('.git/HEAD');
        $appVersion = $this->manifestService->resolveCurrentVersion(config('app.version'));

        if (! is_file($headPath)) {
            return [
                'version' => $appVersion ?: config('app.env'),
                'app_version' => $appVersion,
                'branch' => null,
                'commit' => null,
                'full_commit' => null,
            ];
        }

        $head = trim((string) file_get_contents($headPath));
        $branch = null;
        $commit = null;

        if (str_starts_with($head, 'ref: ')) {
            $ref = trim(substr($head, 5));
            $branch = basename($ref);
            $refPath = base_path('.git/' . $ref);

            if (is_file($refPath)) {
                $commit = trim((string) file_get_contents($refPath));
            }
        } else {
            $commit = $head;
        }

        $shortCommit = $commit ? substr(trim($commit), 0, 7) : null;

        return [
            'version' => $appVersion ?: ($shortCommit ?? config('app.env')),
            'app_version' => $appVersion,
            'branch' => $branch,
            'commit' => $shortCommit,
            'full_commit' => $commit,
        ];
    }
}
