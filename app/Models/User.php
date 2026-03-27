<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saved(function (self $user) {
            $user->syncPrimarySupplierMapping();
        });
    }

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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierMappings(): HasMany
    {
        return $this->hasMany(SupplierUser::class);
    }

    public function mappedSuppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_users')
            ->withPivot('title', 'is_primary', 'can_download', 'can_approve')
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function uploadedRevisions(): HasMany
    {
        return $this->hasMany(ArtworkRevision::class, 'uploaded_by');
    }

    public function uploadedGalleryItems(): HasMany
    {
        return $this->hasMany(ArtworkGallery::class, 'uploaded_by');
    }

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
        return in_array($this->role, [UserRole::ADMIN, UserRole::GRAPHIC], true);
    }

    public function canManageOrders(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::PURCHASING], true);
    }

    public function canAccessOrder(PurchaseOrder $order): bool
    {
        if (! $this->isSupplier()) {
            return true;
        }

        return $this->accessibleSupplierIds()->contains($order->supplier_id);
    }

    public function canDownloadForSupplier(int $supplierId): bool
    {
        if (! $this->isSupplier()) {
            return true;
        }

        if (! $this->accessibleSupplierIds()->contains($supplierId)) {
            return false;
        }

        $mapping = $this->relationLoaded('supplierMappings')
            ? $this->supplierMappings->firstWhere('supplier_id', $supplierId)
            : $this->supplierMappings()->where('supplier_id', $supplierId)->first();

        if (! $mapping) {
            return false;
        }

        return (bool) $mapping->can_download;
    }

    public function accessibleSupplierIds(): Collection
    {
        if ($this->isSupplier()) {
            $mappedIds = $this->relationLoaded('supplierMappings')
                ? $this->supplierMappings->pluck('supplier_id')
                : $this->supplierMappings()->pluck('supplier_id');

            return $mappedIds
                ->filter()
                ->unique()
                ->values();
        }

        $mappedIds = $this->relationLoaded('supplierMappings')
            ? $this->supplierMappings->pluck('supplier_id')
            : $this->supplierMappings()->pluck('supplier_id');

        return $mappedIds
            ->push($this->supplier_id)
            ->filter()
            ->unique()
            ->values();
    }

    public function syncPrimarySupplierMapping(): bool
    {
        $this->unsetRelation('supplierMappings');
        $this->unsetRelation('mappedSuppliers');

        if (! $this->isSupplier()) {
            $deleted = $this->supplierMappings()->delete();

            return $deleted > 0;
        }

        if (! $this->supplier_id) {
            return false;
        }

        $mapping = $this->supplierMappings()
            ->where('supplier_id', $this->supplier_id)
            ->first();

        $created = false;

        if (! $mapping) {
            $mapping = $this->supplierMappings()->create([
                'supplier_id' => $this->supplier_id,
                'title' => null,
                'is_primary' => true,
                'can_download' => true,
                'can_approve' => false,
            ]);
            $created = true;
        }

        $updatedPrimary = 0;

        if (! $mapping->is_primary) {
            $mapping->update(['is_primary' => true]);
            $updatedPrimary++;
        }

        $demoted = $this->supplierMappings()
            ->where('supplier_id', '!=', $this->supplier_id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);

        $this->unsetRelation('supplierMappings');
        $this->unsetRelation('mappedSuppliers');

        return $created || $updatedPrimary > 0 || $demoted > 0;
    }
}
