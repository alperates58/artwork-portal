<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileRoutesCommand extends Command
{
    protected $signature = 'profile:routes {--json : Sonucu JSON olarak yazdir}';

    protected $description = 'Admin ve portal sayfalarinin request suresi ve query yogunlugunu olcer.';

    public function handle(Kernel $kernel): int
    {
        $results = collect()
            ->merge($this->profileAdminRoutes($kernel))
            ->merge($this->profileSupplierRoutes($kernel))
            ->sortByDesc('duration_ms')
            ->values()
            ->all();

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->table(
            ['scope', 'route', 'path', 'status', 'ms', 'queries', 'query_ms', 'app_ms', 'response_kb', 'bottleneck'],
            array_map(fn (array $row) => [
                $row['scope'],
                $row['route'],
                $row['path'],
                $row['status'],
                $row['duration_ms'],
                $row['query_count'],
                $row['query_time_ms'],
                $row['app_time_ms'],
                $row['response_kb'],
                $row['bottleneck'],
            ], $results)
        );

        return self::SUCCESS;
    }

    private function profileAdminRoutes(Kernel $kernel): array
    {
        $user = User::query()
            ->where('role', UserRole::ADMIN)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $user) {
            return [];
        }

        $order = PurchaseOrder::query()->latest('id')->first();
        $supplier = Supplier::query()->latest('id')->first();

        $paths = array_filter([
            '/admin/kullanicilar',
            '/admin/tedarikciler',
            $supplier ? "/admin/tedarikciler/{$supplier->id}" : null,
            '/admin/raporlar',
            '/admin/loglar',
            '/admin/ayarlar',
            '/siparisler',
            $order ? "/siparisler/{$order->id}" : null,
            '/',
        ]);

        return $this->profilePaths($kernel, $user, 'admin', $paths);
    }

    private function profileSupplierRoutes(Kernel $kernel): array
    {
        $user = User::query()
            ->where('role', UserRole::SUPPLIER)
            ->where('is_active', true)
            ->with('supplierMappings')
            ->orderBy('id')
            ->first();

        if (! $user) {
            return [];
        }

        $order = PurchaseOrder::query()
            ->whereIn('supplier_id', $user->accessibleSupplierIds()->all())
            ->latest('id')
            ->first();

        $paths = array_filter([
            '/portal/siparisler',
            $order ? "/portal/siparisler/{$order->id}" : null,
        ]);

        return $this->profilePaths($kernel, $user, 'portal', $paths);
    }

    private function profilePaths(Kernel $kernel, User $user, string $scope, array $paths): array
    {
        return array_map(fn (string $path) => $this->profileRequest($kernel, $user, $scope, $path), $paths);
    }

    private function profileRequest(Kernel $kernel, User $user, string $scope, string $path): array
    {
        $startedAt = hrtime(true);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Auth::shouldUse('web');
        Auth::setUser($user);

        $request = Request::create($path, 'GET', ['profile' => 1], [], [], [
            'HTTP_HOST' => 'localhost',
            'HTTP_X_PORTAL_PROFILE' => '1',
            'HTTPS' => 'on',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = $kernel->handle($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $responseContent = (string) $response->getContent();
        $routeName = $response->headers->get('X-Portal-Profile-Route', 'unknown');
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $queryTimeMs = array_sum(array_map(fn (array $query) => (float) ($query['time'] ?? 0), $queries));
        $appTimeMs = max(0, $durationMs - $queryTimeMs);

        $kernel->terminate($request, $response);
        Auth::logout();
        DB::disableQueryLog();

        return [
            'scope' => $scope,
            'path' => $path,
            'route' => $routeName,
            'status' => $response->getStatusCode(),
            'duration_ms' => round($durationMs, 2),
            'query_count' => $queryCount,
            'query_time_ms' => round($queryTimeMs, 2),
            'app_time_ms' => round($appTimeMs, 2),
            'response_kb' => round(strlen($responseContent) / 1024, 1),
            'bottleneck' => $this->bottleneckLabel($durationMs, $queryTimeMs, strlen($responseContent)),
        ];
    }

    private function bottleneckLabel(float $durationMs, float $queryTimeMs, int $responseBytes): string
    {
        $queryRatio = $durationMs > 0 ? $queryTimeMs / $durationMs : 0;

        if ($queryRatio >= 0.55) {
            return 'query/db';
        }

        if ($responseBytes >= 40 * 1024 && ($durationMs - $queryTimeMs) >= 30) {
            return 'render/html';
        }

        return 'app/middleware';
    }
}
