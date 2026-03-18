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
        AuditLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id'   => $model?->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'payload'    => $payload ?: null,
            'created_at' => now(),
        ]);
    }

    /**
     * Giriş logu
     */
    public function logLogin(int $userId): void
    {
        AuditLog::create([
            'user_id'    => $userId,
            'action'     => 'user.login',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Çıkış logu
     */
    public function logLogout(int $userId): void
    {
        AuditLog::create([
            'user_id'    => $userId,
            'action'     => 'user.logout',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
