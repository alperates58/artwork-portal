<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request): View
    {
        $orders = PurchaseOrder::query()
            ->with('supplier:id,name')
            ->withListMetrics()
            ->when($request->supplier_id, fn ($query) => $query->where('supplier_id', $request->supplier_id))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->when($request->search, fn ($query) => $query->search($request->search))
            ->orderByDesc('order_date')
            ->paginate(25)
            ->withQueryString();

        $suppliers = Supplier::active()->orderBy('name')->pluck('name', 'id');

        return view('orders.index', compact('orders', 'suppliers'));
    }

    public function show(PurchaseOrder $order): View
    {
        $order->load([
            'supplier',
            'createdBy',
            'lines.artwork.activeRevision.uploadedBy',
            'lines.artwork.revisions' => fn ($query) => $query->orderByDesc('revision_no'),
        ]);

        $this->audit->log('order.view', $order);

        return view('orders.show', compact('order'));
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);

        $suppliers = Supplier::active()->orderBy('name')->pluck('name', 'id');

        return view('orders.create', compact('suppliers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_no' => ['required', 'string', 'max:50', 'unique:purchase_orders'],
            'order_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after:order_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_no' => ['required', 'string', 'max:20'],
            'lines.*.product_code' => ['required', 'string', 'max:100'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit' => ['nullable', 'string', 'max:20'],
        ]);

        $order = PurchaseOrder::create([
            ...$validated,
            'status' => 'active',
            'created_by' => auth()->id(),
            'shipment_status' => 'pending',
        ]);

        foreach ($validated['lines'] as $line) {
            $order->lines()->create([
                ...$line,
                'artwork_status' => 'pending',
            ]);
        }

        $this->audit->log('order.create', $order, ['order_no' => $order->order_no]);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Sipariş başarıyla oluşturuldu.');
    }

    public function edit(PurchaseOrder $order): View
    {
        $this->authorize('update', $order);

        $order->load([
            'supplier:id,name,code',
            'lines.artwork.activeRevision',
        ]);

        $suppliers = Supplier::active()->orderBy('name')->pluck('name', 'id');

        return view('orders.edit', compact('order', 'suppliers'));
    }

    public function update(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,active,completed,cancelled'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->update($validated);

        $this->audit->log('order.update', $order);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Sipariş güncellendi.');
    }

    public function destroy(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $request->validate([
            'confirmation_text' => ['required', 'string'],
        ], [
            'confirmation_text.required' => 'Silme işlemi için sipariş numarasını yazmalısınız.',
        ]);

        if ($request->string('confirmation_text')->toString() !== $order->order_no) {
            return back()->with('error', 'Onay metni sipariş numarasıyla eşleşmiyor.');
        }

        DB::transaction(function () use ($order) {
            $this->audit->log('order.delete', $order, ['order_no' => $order->order_no]);
            $order->delete();
        });

        return redirect()
            ->route('orders.index')
            ->with('success', 'Sipariş ve bağlı satırlar silindi.');
    }
}
