<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionsController extends Controller
{
    /** Tüm ekranlar ve her ekranın aksiyonları */
    public static array $screens = [
        'dashboard' => [
            'label'   => 'Dashboard',
            'actions' => ['view' => 'Görüntüle'],
        ],
        'orders' => [
            'label'   => 'Siparişler',
            'actions' => [
                'view'   => 'Görüntüle',
                'create' => 'Oluştur',
                'edit'   => 'Düzenle',
                'delete' => 'Sil',
            ],
        ],
        'suppliers' => [
            'label'   => 'Tedarikçiler',
            'actions' => [
                'view'   => 'Görüntüle',
                'create' => 'Oluştur',
                'edit'   => 'Düzenle',
            ],
        ],
        'users' => [
            'label'   => 'Kullanıcılar',
            'actions' => [
                'view'   => 'Görüntüle',
                'create' => 'Oluştur',
                'edit'   => 'Düzenle',
            ],
        ],
        'reports' => [
            'label'   => 'Raporlar',
            'actions' => ['view' => 'Görüntüle'],
        ],
        'gallery' => [
            'label'   => 'Artwork Galerisi',
            'actions' => [
                'view'   => 'Görüntüle',
                'upload' => 'Yükle',
                'delete' => 'Sil',
                'manage' => 'Kategori & Etiket Yönetimi',
            ],
        ],
        'logs' => [
            'label'   => 'Sistem Logları',
            'actions' => ['view' => 'Görüntüle'],
        ],
        'settings' => [
            'label'   => 'Ayarlar',
            'actions' => [
                'view' => 'Görüntüle',
                'edit' => 'Düzenle',
            ],
        ],
        'formats' => [
            'label'   => 'Dosya Formatları',
            'actions' => [
                'view'   => 'Görüntüle',
                'manage' => 'Ekle / Düzenle / Sil',
            ],
        ],
    ];

    public function index(): View
    {
        $users = User::whereIn('role', [
            UserRole::PURCHASING->value,
            UserRole::GRAPHIC->value,
        ])->orderBy('name')->get();

        return view('admin.permissions.index', [
            'users'   => $users,
            'screens' => self::$screens,
        ]);
    }

    public function show(User $user): View
    {
        abort_if($user->isAdmin() || $user->isSupplier(), 403);

        $effectivePermissions = $user->permissions
            ?? User::defaultPermissionsForRole($user->role);

        return view('admin.permissions.show', [
            'user'                 => $user,
            'screens'              => self::$screens,
            'effectivePermissions' => $effectivePermissions,
            'isCustom'             => ! is_null($user->permissions),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin() || $user->isSupplier(), 403);

        $built = [];
        foreach (self::$screens as $screenKey => $screen) {
            foreach ($screen['actions'] as $actionKey => $actionLabel) {
                $built[$screenKey][$actionKey] = $request->boolean("permissions.{$screenKey}.{$actionKey}");
            }
        }

        $user->update(['permissions' => $built]);

        return redirect()
            ->route('admin.permissions.show', $user)
            ->with('success', $user->name . ' kullanıcısının yetkileri güncellendi.');
    }

    public function reset(User $user): RedirectResponse
    {
        abort_if($user->isAdmin() || $user->isSupplier(), 403);

        $user->update(['permissions' => null]);

        return redirect()
            ->route('admin.permissions.show', $user)
            ->with('success', $user->name . ' yetkileri role varsayılanına sıfırlandı.');
    }
}
