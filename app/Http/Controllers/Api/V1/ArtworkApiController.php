<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Services\SpacesStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Faz 3 — Harici ERP sistemleri için REST API
 * Auth: Laravel Sanctum token (api middleware)
 *
 * Base URL: /api/v1/
 */
class ArtworkApiController extends Controller
{
    public function __construct(
        private SpacesStorageService $spaces
    ) {}

    /**
     * GET /api/v1/orders
     * Tedarikçiye ait siparişleri listele
     */
    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = PurchaseOrder::query()
            ->when($user->isSupplier(), fn ($q) => $q->where('supplier_id', $user->supplier_id))
            ->with(['supplier:id,name,code', 'lines:id,purchase_order_id,line_no,product_code,artwork_status'])
            ->orderByDesc('order_date')
            ->paginate(50);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'total'        => $orders->total(),
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/orders/{orderNo}
     * Sipariş detayı — aktif artwork bilgisi dahil
     */
    public function orderDetail(string $orderNo): JsonResponse
    {
        $matchingOrders = PurchaseOrder::where('order_no', $orderNo)
            ->with([
                'supplier:id,name,code',
                'lines.artwork.activeRevision',
            ])
            ->get();

        if ($matchingOrders->count() > 1) {
            return response()->json([
                'error' => 'Ayni siparis numarasi birden fazla tedarikci altinda bulundu. supplier_code ile filtrelenmis bir endpoint gerekir.',
            ], 409);
        }

        $order = $matchingOrders->firstOrFail();

        $this->authorize('view', $order);

        return response()->json([
            'data' => [
                'id'         => $order->id,
                'order_no'   => $order->order_no,
                'status'     => $order->status,
                'order_date' => $order->order_date,
                'due_date'   => $order->due_date,
                'supplier'   => $order->supplier->only('id', 'name', 'code'),
                'lines'      => $order->lines->map(fn ($line) => [
                    'id'             => $line->id,
                    'line_no'        => $line->line_no,
                    'product_code'   => $line->product_code,
                    'description'    => $line->description,
                    'quantity'       => $line->quantity,
                    'artwork_status' => $line->artwork_status,
                    'active_artwork' => $line->artwork?->activeRevision ? [
                        'revision_no'  => $line->artwork->activeRevision->revision_no,
                        'filename'     => $line->artwork->activeRevision->original_filename,
                        'file_size'    => $line->artwork->activeRevision->file_size,
                        'uploaded_at'  => $line->artwork->activeRevision->created_at,
                        'download_url' => route('artwork.download', $line->artwork->activeRevision),
                    ] : null,
                ]),
            ],
        ]);
    }

    /**
     * GET /api/v1/artworks/{revisionId}/download-url
     * Güvenli indirme URL'si al (presigned, 15 dakika geçerli)
     */
    public function downloadUrl(ArtworkRevision $revision): JsonResponse
    {
        $this->authorize('view', $revision->artwork);

        if (request()->user()->isSupplier() && ! $revision->is_active) {
            return response()->json(['error' => 'Sadece aktif revizyon indirilebilir.'], 403);
        }

        $url = $this->spaces->presignedUrl($revision->spaces_path);

        return response()->json([
            'download_url' => $url,
            'expires_at'   => now()->addMinutes(config('artwork.download_ttl', 15))->toIso8601String(),
            'filename'     => $revision->original_filename,
            'file_size'    => $revision->file_size,
        ]);
    }

    /**
     * POST /api/v1/orders (ERP push endpoint)
     * Harici sistemden sipariş verisi push'la
     */
    public function pushOrder(Request $request): JsonResponse
    {
        // Sadece admin/sistem token'ı bu endpoint'i kullanabilir
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'supplier_code'        => ['required', 'string'],
            'order_no'             => ['required', 'string'],
            'order_date'           => ['required', 'date'],
            'due_date'             => ['nullable', 'date'],
            'lines'                => ['required', 'array', 'min:1'],
            'lines.*.line_no'      => ['required', 'string'],
            'lines.*.product_code' => ['required', 'string'],
            'lines.*.description'  => ['required', 'string'],
            'lines.*.quantity'     => ['required', 'integer', 'min:1'],
        ]);

        $supplier = Supplier::where('code', $validated['supplier_code'])->firstOrFail();

        Validator::make($validated, [
            'order_no' => [
                'required',
                'string',
                Rule::unique('purchase_orders')->where(fn ($query) => $query->where('supplier_id', $supplier->id)),
            ],
        ])->validate();

        $order = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'order_no'    => $validated['order_no'],
            'status'      => 'active',
            'order_date'  => $validated['order_date'],
            'due_date'    => $validated['due_date'] ?? null,
            'created_by'  => $request->user()->id,
            'notes'       => 'API üzerinden oluşturuldu',
        ]);

        foreach ($validated['lines'] as $line) {
            $order->lines()->create([
                ...$line,
                'artwork_status' => 'pending',
            ]);
        }

        return response()->json(['data' => ['id' => $order->id, 'order_no' => $order->order_no]], 201);
    }
}
