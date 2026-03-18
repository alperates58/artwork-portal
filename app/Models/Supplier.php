<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'email', 'phone', 'address', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // Pivot üzerinden tedarikçiye bağlı kullanıcılar
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'supplier_users')
                    ->withPivot('title', 'is_primary', 'can_download', 'can_approve')
                    ->withTimestamps()
                    ->where('users.is_active', true);
    }

    public function allUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'supplier_users')
                    ->withPivot('title', 'is_primary', 'can_download', 'can_approve')
                    ->withTimestamps();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getActiveOrdersCountAttribute(): int
    {
        return $this->purchaseOrders()->whereIn('status', ['active', 'draft'])->count();
    }
}
