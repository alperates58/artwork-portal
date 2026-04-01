<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\SupplierBulkImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasPermission('suppliers'), 403);

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

        $supplierIds = $suppliers->pluck('id');
        $recentOrdersBySupplier = \App\Models\PurchaseOrder::whereIn('supplier_id', $supplierIds)
            ->select(['id', 'supplier_id', 'order_no', 'order_date', 'status', 'shipment_status'])
            ->orderByDesc('order_date')
            ->get()
            ->groupBy('supplier_id')
            ->map(fn ($orders) => $orders->take(5));

        return view('admin.suppliers.index', compact('suppliers', 'recentOrdersBySupplier'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('suppliers', 'create'), 403);

        return view('admin.suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('suppliers', 'create'), 403);

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
        abort_unless(auth()->user()->hasPermission('suppliers'), 403);

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

    public function destroy(Request $request, Supplier $supplier, AuditLogService $audit): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate([
            'confirmation' => ['required', 'accepted'],
        ]);

        if ($supplier->purchaseOrders()->exists()) {
            return back()->with('error', 'Sipariş bağlı olduğu için tedarikçi silinemez.');
        }

        DB::transaction(function () use ($supplier, $audit) {
            $audit->log('supplier.delete', $supplier, [
                'supplier_name' => $supplier->name,
                'supplier_code' => $supplier->code,
                'bulk' => false,
            ]);
            $supplier->delete();
        });

        return redirect()
            ->route('admin.suppliers.index')
            ->with('success', 'Tedarikçi arşivlendi.');
    }

    public function bulkDestroy(Request $request, AuditLogService $audit): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'supplier_ids' => ['required', 'array', 'min:1'],
            'supplier_ids.*' => ['integer', 'distinct', 'exists:suppliers,id'],
        ], [
            'supplier_ids.required' => 'Lütfen silinecek en az bir tedarikçi seçin.',
            'supplier_ids.min' => 'Lütfen silinecek en az bir tedarikçi seçin.',
        ]);

        $suppliers = Supplier::query()
            ->withCount('purchaseOrders')
            ->whereIn('id', $validated['supplier_ids'])
            ->get();

        $blockedSuppliers = $suppliers->filter(fn (Supplier $supplier) => $supplier->purchase_orders_count > 0)->values();
        $deletableSuppliers = $suppliers->reject(fn (Supplier $supplier) => $supplier->purchase_orders_count > 0)->values();

        DB::transaction(function () use ($deletableSuppliers, $audit) {
            foreach ($deletableSuppliers as $supplier) {
                $audit->log('supplier.delete', $supplier, [
                    'supplier_name' => $supplier->name,
                    'supplier_code' => $supplier->code,
                    'bulk' => true,
                ]);

                $supplier->delete();
            }
        });

        $response = redirect()->route('admin.suppliers.index');

        if ($deletableSuppliers->isNotEmpty()) {
            $response->with('success', $deletableSuppliers->count() . ' tedarikçi arşivlendi.');
        }

        if ($blockedSuppliers->isNotEmpty()) {
            $blockedNames = $blockedSuppliers->pluck('name')->take(3)->implode(', ');
            $suffix = $blockedSuppliers->count() > 3 ? ' ve diğerleri' : '';

            $response->with(
                'error',
                'Sipariş bağlı olduğu için ' . $blockedSuppliers->count() . ' tedarikçi atlandı: ' . $blockedNames . $suffix . '.'
            );
        }

        if ($deletableSuppliers->isEmpty() && $blockedSuppliers->isEmpty()) {
            $response->with('error', 'Seçilen tedarikçiler bulunamadı.');
        }

        return $response;
    }

    public function importForm(): View
    {
        abort_unless(
            auth()->user()->hasPermission('suppliers', 'create') || auth()->user()->hasPermission('suppliers', 'bulk_import'),
            403
        );

        return view('admin.suppliers.import');
    }

    public function import(Request $request, SupplierBulkImportService $service): RedirectResponse
    {
        abort_unless(
            auth()->user()->hasPermission('suppliers', 'create') || auth()->user()->hasPermission('suppliers', 'bulk_import'),
            403
        );

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'file.required' => 'Lütfen bir Excel dosyası seçin.',
            'file.mimes'    => 'Sadece .xlsx veya .xls dosyası yüklenebilir.',
            'file.max'      => 'Dosya boyutu en fazla 5 MB olabilir.',
        ]);

        try {
            $result = $service->import($request->file('file'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Dosya işlenirken bir hata oluştu: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.suppliers.import.form')
            ->with('import_result', $result);
    }

    public function downloadTemplate(SupplierBulkImportService $service): void
    {
        abort_unless(auth()->user()->hasPermission('suppliers'), 403);

        $service->streamTemplate();
    }
}
