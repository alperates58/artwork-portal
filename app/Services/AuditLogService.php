<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * Aktivite kaydı oluştur
     *
     * @param string     $action   'artwork.download', 'order.view', 'user.login' vs.
     * @param Model|null $model    İlgili kayıt (opsiyonel)
     * @param array      $payload  Ek bilgi
     */
    public function log(string $action, ?Model $model = null, array $payload = []): void
    {
        $this->write(Auth::id(), $action, $model, $payload);
    }

    public function logForUser(int $userId, string $action, ?Model $model = null, array $payload = []): void
    {
        $this->write($userId, $action, $model, $payload);
    }

    /**
     * Giriş logu
     */
    public function logLogin(int $userId): void
    {
        $this->write($userId, 'user.login');
    }

    /**
     * Çıkış logu
     */
    public function logLogout(int $userId): void
    {
        $this->write($userId, 'user.logout');
    }

    private function write(?int $userId, string $action, ?Model $model = null, array $payload = []): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload' => $payload ?: null,
            'created_at' => now(),
        ]);
    }
}
