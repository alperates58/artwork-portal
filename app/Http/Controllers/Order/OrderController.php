<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OrderNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use App\Services\NotificationService;
use App\Services\PortalSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private AuditLogService $audit,
        private DashboardCacheService $dashboardCache,
        private NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $orders = PurchaseOrder::query()
            ->with('supplier:id,name')
            ->withListMetrics()
            ->when($request->supplier_id, fn ($query) => $query->where('supplier_id', $request->supplier_id))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->when($request->artwork_status, fn ($query) => $query->whereHas('lines', fn ($q) => $q->where('artwork_status', $request->artwork_status)))
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
            'lines.manualArtworkCompletedBy:id,name',
            'lines.lineNotes.user:id,name',
            'lines.lineNotes.replies.user:id,name',
            'lines.artwork.activeRevision.uploadedBy',
            'lines.artwork.revisions' => fn ($query) => $query->orderByDesc('revision_no'),
            'lines.artwork.revisions.uploadedBy:id,name',
            'orderNotes.user:id,name',
            'orderNotes.replies.user:id,name',
        ]);

        $this->audit->log('order.view', $order);

        $timeline = collect();

        $timeline->push([
            'at' => $order->created_at,
            'icon' => 'plus',
            'color' => 'violet',
            'title' => 'Sipariş oluşturuldu',
            'sub' => $order->createdBy?->name ?? '—',
        ]);

        foreach ($order->lines as $line) {
            foreach ($line->artwork?->revisions ?? [] as $revision) {
                $timeline->push([
                    'at' => $revision->created_at,
                    'icon' => 'upload',
                    'color' => 'blue',
                    'title' => "Revizyon #{$revision->revision_no} yüklendi",
                    'sub' => ($revision->uploadedBy?->name ?? '—') . ' · ' . ($line->description ?? $line->product_code ?? "Satır #{$line->id}"),
                ]);
            }
        }

        foreach ($order->orderNotes as $note) {
            $timeline->push([
                'at' => $note->created_at,
                'icon' => 'note',
                'color' => 'amber',
                'title' => 'Not eklendi',
                'sub' => $note->user?->name ?? '—',
                'body' => mb_strimwidth($note->body, 0, 120, '…'),
            ]);

            foreach ($note->replies as $reply) {
                $timeline->push([
                    'at' => $reply->created_at,
                    'icon' => 'reply',
                    'color' => 'amber',
                    'title' => 'Not yanıtlandı',
                    'sub' => $reply->user?->name ?? '—',
                    'body' => mb_strimwidth($reply->body, 0, 120, '…'),
                ]);
            }
        }

        foreach ($order->lines as $line) {
            foreach ($line->lineNotes as $note) {
                $timeline->push([
                    'at' => $note->created_at,
                    'icon' => 'note',
                    'color' => 'amber',
                    'title' => 'Satır açıklaması eklendi',
                    'sub' => ($note->user?->name ?? '—') . ' · ' . ($line->product_code ?? "Satır #{$line->id}"),
                    'body' => mb_strimwidth($note->body, 0, 120, '…'),
                ]);

                foreach ($note->replies as $reply) {
                    $timeline->push([
                        'at' => $reply->created_at,
                        'icon' => 'reply',
                        'color' => 'amber',
                        'title' => 'Satır açıklaması yanıtlandı',
                        'sub' => ($reply->user?->name ?? '—') . ' · ' . ($line->product_code ?? "Satır #{$line->id}"),
                        'body' => mb_strimwidth($reply->body, 0, 120, '…'),
                    ]);
                }
            }
        }

        $manualArtworkLogs = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'model_type', 'model_id', 'payload', 'created_at'])
            ->with('user:id,name')
            ->where('model_type', PurchaseOrderLine::class)
            ->whereIn('model_id', $order->lines->pluck('id'))
            ->where('action', 'order_line.manual_artwork.complete')
            ->orderByDesc('created_at')
            ->get();

        foreach ($manualArtworkLogs as $log) {
            $payload = $log->payload ?? [];

            $timeline->push([
                'at' => $log->created_at,
                'icon' => 'mail',
                'color' => 'emerald',
                'title' => 'Satır manuel gönderildi olarak işaretlendi',
                'sub' => ($log->user?->name ?? '—') . ' · ' . ($payload['product_code'] ?? ('Satır #' . ($payload['line_no'] ?? $log->model_id))),
                'body' => $payload['note'] ?? null,
            ]);
        }

        $timeline = $timeline->sortByDesc('at')->values();

        return view('orders.show', compact('order', 'timeline'));
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
            'order_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('purchase_orders')->where(
                    fn ($query) => $query->where('supplier_id', $request->integer('supplier_id'))
                ),
            ],
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
        $this->dashboardCache->forgetMetrics();

        $this->notifications->notifyDepartment(
            null,
            'order_created',
            "Yeni sipariş: {$order->order_no}",
            auth()->user()->name . ' tarafından ' . count($validated['lines']) . ' satırlı sipariş oluşturuldu.',
            route('orders.show', $order),
        );

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
        $this->dashboardCache->forgetMetrics();

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Sipariş güncellendi.');
    }

    public function storeNote(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'purchase_order_line_id' => ['nullable', 'integer', 'exists:purchase_order_lines,id'],
            'parent_id' => ['nullable', 'integer', 'exists:order_notes,id'],
        ]);

        $lineId = array_key_exists('purchase_order_line_id', $validated) && $validated['purchase_order_line_id'] !== null
            ? (int) $validated['purchase_order_line_id']
            : null;
        $parentId = array_key_exists('parent_id', $validated) && $validated['parent_id'] !== null
            ? (int) $validated['parent_id']
            : null;
        $parentNote = null;

        if ($lineId !== null) {
            abort_if(! $order->lines()->whereKey($lineId)->exists(), 404);
        }

        if ($parentId !== null) {
            $selectedNote = OrderNote::query()
                ->select(['id', 'purchase_order_id', 'purchase_order_line_id', 'parent_id'])
                ->findOrFail($parentId);

            abort_if($selectedNote->purchase_order_id !== $order->id, 404);

            $parentNote = $selectedNote->parent_id
                ? OrderNote::query()
                    ->select(['id', 'purchase_order_id', 'purchase_order_line_id', 'parent_id'])
                    ->findOrFail($selectedNote->parent_id)
                : $selectedNote;

            if ($lineId === null) {
                $lineId = $parentNote->purchase_order_line_id;
            }

            abort_if((int) $parentNote->purchase_order_line_id !== (int) $lineId, 422, 'Yanıt satırı eşleşmiyor.');
            $parentId = $parentNote->id;
        }

        $note = $order->allOrderNotes()->create([
            'purchase_order_line_id' => $lineId,
            'parent_id' => $parentId,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        $this->audit->log('order.note.create', $note, [
            'order_no' => $order->order_no,
            'line_id' => $lineId,
            'parent_id' => $parentId,
        ]);

        return back()->with('success', $parentNote
            ? 'Yanıt eklendi.'
            : ($lineId ? 'Satır açıklaması eklendi.' : 'Not eklendi.'));
    }

    public function updateNote(Request $request, PurchaseOrder $order, OrderNote $note): RedirectResponse
    {
        abort_if($note->purchase_order_id !== $order->id, 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $note->update([
            'body' => $validated['body'],
        ]);

        $this->audit->log('order.note.update', $note, [
            'order_no' => $order->order_no,
            'line_id' => $note->purchase_order_line_id,
            'parent_id' => $note->parent_id,
        ]);

        return back()->with('success', $note->parent_id ? 'Yanıt güncellendi.' : 'Açıklama güncellendi.');
    }

    public function destroy(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $portalConfig = app(PortalSettings::class)->portalConfig();
        abort_if(! ($portalConfig['order_deletion_enabled'] ?? true), 403, 'Sipariş silme işlevi sistem ayarlarından devre dışı bırakılmıştır.');

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
            $this->dashboardCache->forgetMetricsAfterCommit();
        });

        return redirect()
            ->route('orders.index')
            ->with('success', 'Sipariş ve bağlı satırlar silindi.');
    }
}
