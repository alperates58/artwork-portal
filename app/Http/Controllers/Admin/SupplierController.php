<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $suppliers = Supplier::withCount(['users', 'purchaseOrders'])
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            }))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('admin.suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:200'],
            'code'      => ['required', 'string', 'max:50', 'unique:suppliers'],
            'email'     => ['nullable', 'email'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'notes'     => ['nullable', 'string'],
        ]);

        Supplier::create($validated);

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Tedarikçi oluşturuldu.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load([
            'allUsers',
            'purchaseOrders' => fn ($q) => $q->withCount('lines')->orderByDesc('order_date')->limit(10),
        ]);

        return view('admin.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:200'],
            'code'      => ['required', 'string', 'max:50', "unique:suppliers,code,{$supplier->id}"],
            'email'     => ['nullable', 'email'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'notes'     => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Tedarikçi güncellendi.');
    }
}
