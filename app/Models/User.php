<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'supplier_id',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role'          => UserRole::class,
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
            'password'      => 'hashed',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function uploadedRevisions(): HasMany
    {
        return $this->hasMany(ArtworkRevision::class, 'uploaded_by');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isSupplier(): bool
    {
        return $this->role === UserRole::SUPPLIER;
    }

    public function isGraphic(): bool
    {
        return $this->role === UserRole::GRAPHIC;
    }

    public function isPurchasing(): bool
    {
        return $this->role === UserRole::PURCHASING;
    }

    public function isInternal(): bool
    {
        return $this->role->isInternal();
    }

    public function canUploadArtwork(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::GRAPHIC]);
    }

    public function canManageOrders(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::PURCHASING]);
    }

    /**
     * Tedarikçi kullanıcı belirtilen siparişe erişebilir mi?
     */
    public function canAccessOrder(PurchaseOrder $order): bool
    {
        if ($this->isSupplier()) {
            return $order->supplier_id === $this->supplier_id;
        }

        return true; // admin, grafik, satın alma hepsini görebilir
    }
}
