<?php

namespace App\Http\Controllers;

use App\Models\ArtworkGallery;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $user = $request->user();
        $results = [];

        $roleValue = $user->role->value ?? '';

        // Orders — accessible to admin, purchasing, graphic
        if (in_array($roleValue, ['admin', 'purchasing', 'graphic'])) {
            $orders = PurchaseOrder::with('supplier:id,name')
                ->where(function ($query) use ($q) {
                    $query->where('order_no', 'like', "%{$q}%")
                          ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$q}%"));
                })
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'order_no', 'status', 'supplier_id', 'order_date']);

            foreach ($orders as $order) {
                $results[] = [
                    'type'     => 'order',
                    'label'    => $order->order_no,
                    'sub'      => $order->supplier?->name ?? '—',
                    'badge'    => $order->status_label,
                    'url'      => route('orders.show', $order),
                ];
            }
        }

        // Suppliers — admin & purchasing
        if (in_array($roleValue, ['admin', 'purchasing'])) {
            $suppliers = Supplier::where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
                ->limit(4)
                ->get(['id', 'name', 'code', 'is_active']);

            foreach ($suppliers as $supplier) {
                $results[] = [
                    'type'  => 'supplier',
                    'label' => $supplier->name,
                    'sub'   => $supplier->code ?? '',
                    'badge' => $supplier->is_active ? 'Aktif' : 'Pasif',
                    'url'   => route('admin.suppliers.show', $supplier),
                ];
            }
        }

        // Artwork Gallery — admin, purchasing, graphic
        if (in_array($roleValue, ['admin', 'purchasing', 'graphic'])) {
            $artworks = ArtworkGallery::where('name', 'like', "%{$q}%")
                ->orWhere('stock_code', 'like', "%{$q}%")
                ->limit(4)
                ->get(['id', 'name', 'stock_code', 'file_type']);

            foreach ($artworks as $artwork) {
                $results[] = [
                    'type'  => 'artwork',
                    'label' => $artwork->name,
                    'sub'   => $artwork->stock_code ?? '',
                    'badge' => strtoupper($artwork->file_type ?? ''),
                    'url'   => route('admin.artwork-gallery.index') . '?q=' . urlencode($artwork->name),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }
}
