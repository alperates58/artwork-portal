<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SetupController extends Controller
{
    // Kurulum adımları
    private array $steps = [
        1 => ['key' => 'site',     'label' => 'Site Ayarları'],
        2 => ['key' => 'database', 'label' => 'Veritabanı'],
        3 => ['key' => 'spaces',   'label' => 'DO Spaces'],
        4 => ['key' => 'admin',    'label' => 'Admin Kullanıcı'],
    ];

    // ─── Adım göster ──────────────────────────────────────────────

    public function index(): RedirectResponse
    {
        return redirect()->route('setup.step', 1);
    }

    public function step(int $step): View|RedirectResponse
    {
        if ($step < 1 || $step > 4) {
            return redirect()->route('setup.step', 1);
        }

        // Önceki adımlar tamamlanmış mı?
        for ($i = 1; $i < $step; $i++) {
            if (! session("setup.step_{$i}_done")) {
                return redirect()->route('setup.step', $i);
            }
        }

        return view("setup.step-{$step}", [
            'step'       => $step,
            'steps'      => $this->steps,
            'totalSteps' => count($this->steps),
        ]);
    }

    // ─── Adım 1: Site Ayarları ────────────────────────────────────

    public function saveSite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name'     => ['required', 'string', 'max:100'],
            'app_url'      => ['required', 'url'],
            'app_timezone' => ['required', 'timezone'],
        ], [
            'app_name.required'     => 'Uygulama adı zorunludur.',
            'app_url.required'      => 'URL zorunludur.',
            'app_url.url'           => 'Geçerli bir URL girin (https:// ile başlamalı).',
            'app_timezone.required' => 'Saat dilimi zorunludur.',
            'app_timezone.timezone' => 'Geçersiz saat dilimi.',
        ]);

        session(['setup.site' => $validated, 'setup.step_1_done' => true]);

        return redirect()->route('setup.step', 2);
    }

    // ─── Adım 2: Veritabanı ───────────────────────────────────────

    public function saveDatabase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'db_host'     => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+$/'],
            'db_port'     => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_$-]+$/'],
            'db_username' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'db_password' => ['nullable', 'string'],
            'create_db'   => ['nullable', 'boolean'],
            'db_root_username' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'db_root_password' => ['nullable', 'string'],
        ], [
            'db_host.required'     => 'Veritabanı host zorunludur.',
            'db_host.regex'        => 'Veritabanı host alanı yalnızca harf, rakam, nokta, alt çizgi ve tire içerebilir.',
            'db_database.required' => 'Veritabanı adı zorunludur.',
            'db_database.regex'    => 'Veritabanı adı yalnızca harf, rakam, alt çizgi, tire ve dolar işareti içerebilir.',
            'db_username.required' => 'Kullanıcı adı zorunludur.',
            'db_username.regex'    => 'Kullanıcı adı yalnızca harf, rakam, nokta, alt çizgi ve tire içerebilir.',
            'db_root_username.regex' => 'Root kullanıcı adı yalnızca harf, rakam, nokta, alt çizgi ve tire içerebilir.',
        ]);

        $createDb = (bool) ($validated['create_db'] ?? false);

        if ($createDb) {
            if (empty($validated['db_root_username'] ?? null)) {
                return back()
                    ->withInput()
                    ->withErrors(['db_root_username' => 'Veritabanı oluşturmak için root kullanıcı adı zorunludur.']);
            }

            try {
                $rootPdo = new \PDO(
                    "mysql:host={$validated['db_host']};port={$validated['db_port']}",
                    $validated['db_root_username'],
                    $validated['db_root_password'] ?? '',
                    [
                        \PDO::ATTR_TIMEOUT => 5,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    ]
                );

                $dbName = $validated['db_database'];
                $quotedDatabase = $this->quoteMysqlIdentifier($dbName);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS {$quotedDatabase} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                $appUser = $validated['db_username'];
                if ($appUser !== $validated['db_root_username']) {
                    $pwd = (string) ($validated['db_password'] ?? '');
                    $escapedUser = $this->escapeMysqlUserName($appUser);
                    // CREATE USER IF NOT EXISTS (MySQL 8+)
                    $rootPdo->exec("CREATE USER IF NOT EXISTS '{$escapedUser}'@'%' IDENTIFIED BY " . $rootPdo->quote($pwd));
                    $rootPdo->exec("GRANT ALL PRIVILEGES ON {$quotedDatabase}.* TO '{$escapedUser}'@'%'");
                    $rootPdo->exec('FLUSH PRIVILEGES');
                }

                unset($rootPdo);
            } catch (\Throwable $e) {
                report($e);

                return back()
                    ->withInput()
                    ->withErrors(['db_root_username' => 'Veritabanı oluşturma işlemi başarısız oldu. Girdiğiniz bilgileri kontrol edip tekrar deneyin.']);
            }
        }

        // Bağlantı testi (veritabanı var/yok fark etmez; create_db işaretliyse önce oluşturulur)
        try {
            $pdo = new \PDO(
                "mysql:host={$validated['db_host']};port={$validated['db_port']};dbname={$validated['db_database']}",
                $validated['db_username'],
                $validated['db_password'] ?? '',
                [
                    \PDO::ATTR_TIMEOUT => 5,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]
            );
            unset($pdo);
        } catch (\PDOException $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['db_host' => 'Veritabanı bağlantısı kurulamadı. Host, port ve kimlik bilgilerini kontrol edin.']);
        }

        // Root bilgilerini session'a yazmayalım
        unset($validated['db_root_username'], $validated['db_root_password']);

        session(['setup.database' => $validated, 'setup.step_2_done' => true]);

        return redirect()->route('setup.step', 3);
    }

    // ─── Adım 3: DO Spaces ────────────────────────────────────────

    public function saveSpaces(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enable_spaces'   => ['nullable', 'boolean'],
            'spaces_key'      => [$request->boolean('enable_spaces') ? 'required' : 'nullable', 'string'],
            'spaces_secret'   => [$request->boolean('enable_spaces') ? 'required' : 'nullable', 'string'],
            'spaces_bucket'   => [$request->boolean('enable_spaces') ? 'required' : 'nullable', 'string', 'max:63'],
            'spaces_region'   => [$request->boolean('enable_spaces') ? 'required' : 'nullable', 'string'],
            'spaces_endpoint' => [$request->boolean('enable_spaces') ? 'required' : 'nullable', 'url'],
        ], [
            'spaces_key.required'      => 'Spaces Access Key zorunludur.',
            'spaces_secret.required'   => 'Spaces Secret Key zorunludur.',
            'spaces_bucket.required'   => 'Bucket adı zorunludur.',
            'spaces_region.required'   => 'Region zorunludur.',
            'spaces_endpoint.required' => 'Endpoint URL zorunludur.',
            'spaces_endpoint.url'      => 'Geçerli bir URL girin.',
        ]);

        if (! $request->boolean('enable_spaces')) {
            session([
                'setup.spaces' => ['enabled' => false],
                'setup.step_3_done' => true,
            ]);

            return redirect()->route('setup.step', 4);
        }

        // Spaces bağlantı testi
        try {
            $client = new \Aws\S3\S3Client([
                'version'     => 'latest',
                'region'      => $validated['spaces_region'],
                'endpoint'    => $validated['spaces_endpoint'],
                'credentials' => [
                    'key'    => $validated['spaces_key'],
                    'secret' => $validated['spaces_secret'],
                ],
            ]);

            // Bucket'a erişim testi — hafif liste sorgusu
            $client->listObjectsV2([
                'Bucket'  => $validated['spaces_bucket'],
                'MaxKeys' => 1,
            ]);
        } catch (\Exception $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['spaces_key' => 'Spaces bağlantısı kurulamadı. Erişim bilgilerini ve bucket tanımını kontrol edin.']);
        }

        session([
            'setup.spaces' => [
                ...$validated,
                'enabled' => true,
            ],
            'setup.step_3_done' => true,
        ]);

        return redirect()->route('setup.step', 4);
    }

    // ─── Adım 4: Admin + Tamamla ──────────────────────────────────

    public function saveAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_name'              => ['required', 'string', 'max:100'],
            'admin_email'             => ['required', 'email'],
            'admin_password'          => ['required', 'string', 'min:8', 'confirmed'],
            'run_migrations'          => ['boolean'],
        ], [
            'admin_name.required'     => 'Ad zorunludur.',
            'admin_email.required'    => 'E-posta zorunludur.',
            'admin_email.email'       => 'Geçerli bir e-posta girin.',
            'admin_password.required' => 'Şifre zorunludur.',
            'admin_password.min'      => 'Şifre en az 8 karakter olmalıdır.',
            'admin_password.confirmed'=> 'Şifreler eşleşmiyor.',
        ]);

        session(['setup.admin' => $validated, 'setup.step_4_done' => true]);

        // .env dosyasını yaz
        $this->writeEnv();

        // Migration + seed çalıştır
        if ($request->boolean('run_migrations', true)) {
            try {
                Artisan::call('config:clear');
                Artisan::call('migrate', ['--force' => true]);
                $this->createAdminUser($validated);
            } catch (\Exception $e) {
                report($e);

                return redirect()
                    ->route('setup.step', 4)
                    ->withInput()
                    ->with('error', 'Kurulum adımları tamamlanamadı. Sunucu günlüklerini kontrol edin.');
            }
        }

        // ── Çift kilit mekanizması ──────────────────────────────────
        // 1. Lock dosyası
        file_put_contents(
            storage_path('app/.setup_complete'),
            json_encode([
                'installed_at' => now()->toIso8601String(),
                'installed_by' => $validated['admin_email'],
                'app_url'      => session('setup.site.app_url'),
            ], JSON_PRETTY_PRINT)
        );

        // 2. .env dosyasına APP_INSTALLED=true bayrağı ekle
        $this->appendEnvFlag();

        return redirect()->route('setup.complete');
    }

    // ─── Tamamlandı ekranı ────────────────────────────────────────

    public function complete(): View
    {
        // Session'ı temizle
        session()->forget('setup');

        return view('setup.complete');
    }

    // ─── .env Yaz ─────────────────────────────────────────────────

    private function writeEnv(): void
    {
        $site     = session('setup.site');
        $db       = session('setup.database');
        $spaces   = session('setup.spaces', ['enabled' => false]);
        $useSpaces = (bool) ($spaces['enabled'] ?? false);
        $filesystemDisk = $useSpaces ? 'spaces' : 'local';
        $spacesKey = $useSpaces ? $spaces['spaces_key'] : '';
        $spacesSecret = $useSpaces ? $spaces['spaces_secret'] : '';
        $spacesEndpoint = $useSpaces ? $spaces['spaces_endpoint'] : '';
        $spacesRegion = $useSpaces ? $spaces['spaces_region'] : '';
        $spacesBucket = $useSpaces ? $spaces['spaces_bucket'] : '';
        $spacesUrl = $useSpaces
            ? "https://{$spaces['spaces_bucket']}.{$spaces['spaces_region']}.digitaloceanspaces.com"
            : '';

        $appKey = 'base64:' . base64_encode(random_bytes(32));

        $content = <<<ENV
APP_NAME={$this->escapeEnvValue($site['app_name'])}
APP_ENV=production
APP_KEY={$this->escapeEnvValue($appKey)}
APP_DEBUG=false
APP_URL={$this->escapeEnvValue($site['app_url'])}
APP_TIMEZONE={$this->escapeEnvValue($site['app_timezone'])}
APP_LOCALE=tr

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$this->escapeEnvValue($db['db_host'])}
DB_PORT={$db['db_port']}
DB_DATABASE={$this->escapeEnvValue($db['db_database'])}
DB_USERNAME={$this->escapeEnvValue($db['db_username'])}
DB_PASSWORD={$this->escapeEnvValue((string) ($db['db_password'] ?? ''))}

CACHE_STORE=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=480

REDIS_HOST=redis
REDIS_CLIENT=predis
REDIS_PASSWORD=redissecret
REDIS_PORT=6379

FILESYSTEM_DISK={$this->escapeEnvValue($filesystemDisk)}
DO_SPACES_KEY={$this->escapeEnvValue($spacesKey)}
DO_SPACES_SECRET={$this->escapeEnvValue($spacesSecret)}
DO_SPACES_ENDPOINT={$this->escapeEnvValue($spacesEndpoint)}
DO_SPACES_REGION={$this->escapeEnvValue($spacesRegion)}
DO_SPACES_BUCKET={$this->escapeEnvValue($spacesBucket)}
DO_SPACES_URL={$this->escapeEnvValue($spacesUrl)}

ARTWORK_DOWNLOAD_TTL=15

MAIL_MAILER=log
MAIL_FROM_ADDRESS={$this->escapeEnvValue('portal@example.com')}
MAIL_FROM_NAME={$this->escapeEnvValue($site['app_name'])}
ENV;

        file_put_contents(base_path('.env'), $content);
    }

    // ─── .env bayrağı ekle ────────────────────────────────────────

    private function appendEnvFlag(): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $current = file_get_contents($envPath);

        // Zaten varsa güncelle, yoksa ekle
        if (str_contains($current, 'APP_INSTALLED=')) {
            $current = preg_replace('/APP_INSTALLED=.*/', 'APP_INSTALLED=true', $current);
        } else {
            $current .= "
APP_INSTALLED=true
";
        }

        file_put_contents($envPath, $current);
    }

    // ─── Admin kullanıcı oluştur ──────────────────────────────────

    private function createAdminUser(array $data): void
    {
        DB::table('users')->insert([
            'name'       => $data['admin_name'],
            'email'      => $data['admin_email'],
            'password'   => Hash::make($data['admin_password']),
            'role'       => 'admin',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function quoteMysqlIdentifier(string $value): string
    {
        return '`' . str_replace('`', '``', $value) . '`';
    }

    private function escapeMysqlUserName(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    private function escapeEnvValue(?string $value): string
    {
        $normalized = str_replace(["\r\n", "\r", "\n"], '\n', (string) ($value ?? ''));

        return '"' . addcslashes($normalized, "\\\"$") . '"';
    }
}
