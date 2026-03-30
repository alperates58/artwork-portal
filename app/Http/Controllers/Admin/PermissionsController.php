<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
                'view'          => 'Görüntüle',
                'create'        => 'Oluştur',
                'edit'          => 'Düzenle',
                'delete'        => 'Sil',
                'plan_priority' => 'Planlama Önceliği Düzenle',
                'purchasing_date' => 'Satın Alma Tarihi Düzenle',
            ],
        ],
        'suppliers' => [
            'label'   => 'Tedarikçiler',
            'actions' => [
                'view'         => 'Görüntüle',
                'create'       => 'Oluştur',
                'edit'         => 'Düzenle',
                'bulk_import'  => 'Toplu Excel İçe Aktarma',
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
        'departments' => [
            'label'   => 'Departmanlar',
            'actions' => [
                'view'   => 'Görüntüle',
                'create' => 'Oluştur',
                'edit'   => 'Düzenle',
                'delete' => 'Sil',
            ],
        ],
        'reports' => [
            'label'   => 'Raporlar',
            'actions' => [
                'view'   => 'Görüntüle',
                'create' => 'Özel Rapor Oluştur',
                'save'   => 'Özel Rapor Kaydet',
            ],
        ],
        'artworks' => [
            'label'   => 'Artwork İşlemleri',
            'actions' => [
                'upload'  => 'Artwork Yükle',
                'approve' => 'Artwork Onayla / Reddet',
            ],
        ],
        'notes' => [
            'label'   => 'Notlar',
            'actions' => [
                'internal'      => 'İç Not Yaz (dahili)',
                'supplier_only' => 'Tedarikçi Notu Yaz',
            ],
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
        'backup' => [
            'label'   => 'Yedek & Veri Aktarımı',
            'actions' => [
                'export' => 'Dışa Aktar',
                'import' => 'İçe Aktar',
            ],
        ],
    ];

    public function index(): View
    {
        $supportsDepartments = Schema::hasTable('departments')
            && Schema::hasColumn('users', 'department_id');

        $users = User::query()
            ->when($supportsDepartments, fn ($query) => $query->with('department'))
            ->where('role', '!=', UserRole::SUPPLIER->value)
            ->where('role', '!=', UserRole::ADMIN->value)
            ->orderBy('name')
            ->get();

        if (! $supportsDepartments) {
            $users->each(fn (User $user) => $user->setRelation('department', null));
        }

        $departments = $supportsDepartments
            ? \App\Models\Department::orderBy('name')->get()
            : collect();

        return view('admin.permissions.index', [
            'users'       => $users,
            'departments' => $departments,
            'screens'     => self::$screens,
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
