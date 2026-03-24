<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'supplier_id', 'last_login_at', 'is_active'])
            ->with('supplier')
            ->when($request->role, fn ($query) => $query->where('role', $request->role))
            ->when($request->search, fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $suppliers = Supplier::active()->orderBy('name')->pluck('name', 'id');
        $roles = UserRole::cases();

        return view('admin.users.create', compact('suppliers', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'supplier_id' => [
                Rule::requiredIf(fn () => $request->role === UserRole::SUPPLIER->value),
                'nullable',
                'exists:suppliers,id',
            ],
            'is_active' => ['boolean'],
        ]);

        $user = User::create($validated);
        $this->syncSupplierMapping($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Kullanıcı oluşturuldu.');
    }

    public function edit(User $user): View
    {
        $suppliers = Supplier::active()->orderBy('name')->pluck('name', 'id');
        $roles = UserRole::cases();

        return view('admin.users.edit', compact('user', 'suppliers', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'supplier_id' => [
                Rule::requiredIf(fn () => $request->role === UserRole::SUPPLIER->value),
                'nullable',
                'exists:suppliers,id',
            ],
            'is_active' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        if (($validated['role'] ?? null) !== UserRole::SUPPLIER) {
            $validated['supplier_id'] = null;
        }

        $user->update($validated);
        $this->syncSupplierMapping($user->fresh());

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Kullanıcı güncellendi.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'Kendi hesabınızı pasife alamazsınız.');

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'aktif' : 'pasif';

        return back()->with('success', "Kullanıcı {$status} yapıldı.");
    }

    private function syncSupplierMapping(User $user): void
    {
        if (! $user->isSupplier() || ! $user->supplier_id) {
            $user->supplierMappings()->delete();

            return;
        }

        $existingTitle = $user->supplierMappings()
            ->where('supplier_id', $user->supplier_id)
            ->value('title');

        $user->supplierMappings()->where('supplier_id', '!=', $user->supplier_id)->update([
            'is_primary' => false,
        ]);

        $user->supplierMappings()->updateOrCreate(
            ['supplier_id' => $user->supplier_id],
            [
                'title' => $existingTitle,
                'is_primary' => true,
                'can_download' => true,
                'can_approve' => false,
            ]
        );
    }
}
