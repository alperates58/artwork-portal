<?php

namespace App\Console\Commands;

use App\Services\GithubUpdateChecker;
use Illuminate\Console\Command;

class PortalUpdateCheckCommand extends Command
{
    protected $signature = 'portal:update:check';

    protected $description = 'GitHub uzerinden son commit bilgisini kontrol eder ve kaydeder.';

    public function handle(GithubUpdateChecker $checker): int
    {
        $result = $checker->checkAndStore(triggerSource: 'cli');

        $this->line('Branch: ' . ($result['branch'] ?? 'bilinmiyor'));
        $this->line('Kurulu commit: ' . ($result['current_commit'] ?? 'bilinmiyor'));
        $this->line('Remote commit: ' . ($result['remote_commit'] ?? 'bilinmiyor'));
        $this->line('Mesaj: ' . $result['message']);

        return $result['status'] === 'success'
            ? self::SUCCESS
            : self::FAILURE;
    }
}
