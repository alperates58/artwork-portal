<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isPurchasing(), 403);

        $suppliers = Supplier::query()
            ->select(['id', 'name', 'code', 'email', 'phone', 'is_active'])
            ->withCount(['users', 'purchaseOrders'])
            ->when($request->search, fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            }))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isPurchasing(), 403);

        return view('admin.suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isPurchasing(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'code' => ['required', 'string', 'max:50', 'unique:suppliers'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        Supplier::create($validated);

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Tedarikçi oluşturuldu.');
    }

    public function show(Supplier $supplier): View
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isPurchasing(), 403);

        $supplier->load([
            'allUsers',
            'mikroAccounts' => fn ($query) => $query->orderBy('id'),
            'purchaseOrders' => fn ($query) => $query->withCount('lines')->orderByDesc('order_date')->limit(10),
        ]);

        return view('admin.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'code' => ['required', 'string', 'max:50', "unique:suppliers,code,{$supplier->id}"],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Tedarikçi güncellendi.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate([
            'confirmation' => ['required', 'accepted'],
        ]);

        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Sipariş bağlı olduğu için tedarikçi silinemez.');
        }

        DB::transaction(function () use ($supplier) {
            $supplier->delete();
        });

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Tedarikçi arşivlendi.');
    }
}
