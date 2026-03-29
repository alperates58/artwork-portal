<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPARTMENTS = [
        [
            'name' => 'Satın Alma',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
                'suppliers' => ['view' => true, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => false, 'manage' => false],
            ],
        ],
        [
            'name' => 'Planlama',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => true, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => false, 'manage' => false],
            ],
        ],
        [
            'name' => 'Grafik',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => false, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => true, 'delete' => true, 'manage' => true],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => true, 'manage' => false],
            ],
        ],
        [
            'name' => 'Pazarlama',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => true, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => false, 'manage' => false],
            ],
        ],
        [
            'name' => 'Kalite',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => true, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => false, 'manage' => false],
            ],
        ],
        [
            'name' => 'Satış',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => true, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => true, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => false, 'manage' => false],
            ],
        ],
        [
            'name' => 'Üretim',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => false, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => false, 'manage' => false],
            ],
        ],
        [
            'name' => 'Yönetim',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => true, 'create' => false, 'edit' => false],
                'users'     => ['view' => true, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => true],
                'settings'  => ['view' => true, 'edit' => false],
                'formats'   => ['view' => true, 'manage' => false],
            ],
        ],
        [
            'name' => 'Ar-Ge',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => false, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => false, 'manage' => false],
            ],
        ],
        [
            'name' => 'Dijital Ekip',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => false, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => true, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => true, 'manage' => false],
            ],
        ],
        [
            'name' => 'BT',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => true, 'create' => false, 'edit' => false],
                'users'     => ['view' => true, 'create' => true, 'edit' => true],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => true, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => true],
                'settings'  => ['view' => true, 'edit' => true],
                'formats'   => ['view' => true, 'manage' => true],
            ],
        ],
        [
            'name' => 'Depo & Lojistik',
            'permissions' => [
                'dashboard' => ['view' => true],
                'orders'    => ['view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                'suppliers' => ['view' => true, 'create' => false, 'edit' => false],
                'users'     => ['view' => false, 'create' => false, 'edit' => false],
                'reports'   => ['view' => true],
                'gallery'   => ['view' => false, 'upload' => false, 'delete' => false, 'manage' => false],
                'logs'      => ['view' => false],
                'settings'  => ['view' => false, 'edit' => false],
                'formats'   => ['view' => false, 'manage' => false],
            ],
        ],
    ];

    public function up(): void
    {
        $now = now();
        foreach (self::DEPARTMENTS as $dept) {
            DB::table('departments')->insertOrIgnore([
                'name'        => $dept['name'],
                'permissions' => json_encode($dept['permissions']),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down(): void
    {
        $names = array_column(self::DEPARTMENTS, 'name');
        DB::table('departments')->whereIn('name', $names)->delete();
    }
};
