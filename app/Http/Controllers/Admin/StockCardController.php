<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArtworkCategory;
use App\Models\StockCard;
use App\Services\ArtworkCategoryService;
use App\Services\ArtworkRevisionNumberService;
use App\Services\AuditLogService;
use App\Services\StockCardBulkImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockCardController extends Controller
{
    public function __construct(
        private ArtworkCategoryService $categories,
        private ArtworkRevisionNumberService $revisionNumbers,
        private AuditLogService $audit,
    ) {}

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasPermission('stock_cards'), 403);

        $stockCards = StockCard::query()
            ->select(['id', 'stock_code', 'stock_name', 'category_id', 'created_at'])
            ->with(['category:id,name'])
            ->withCount('galleryItems')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('stock_code', 'like', '%' . $search . '%')
                        ->orWhere('stock_name', 'like', '%' . $search . '%');
                });
            })
            ->when($request->category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->orderBy('stock_code')
            ->simplePaginate(50)
            ->withQueryString();

        $categories = ArtworkCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.stock-cards.index', compact('stockCards', 'categories'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('stock_cards', 'create'), 403);

        $categories = ArtworkCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.stock-cards.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('stock_cards', 'create'), 403);

        $request->merge([
            'stock_code' => $this->normalizeStockCode((string) $request->input('stock_code')),
        ]);

        $validated = $request->validate([
            'stock_code' => ['required', 'string', 'max:100', 'unique:stock_cards,stock_code'],
            'stock_name' => ['required', 'string', 'max:200'],
            'category_name' => ['required', 'string', 'max:120'],
        ]);

        $stockCard = DB::transaction(function () use ($validated) {
            $category = $this->categories->findOrCreate($validated['category_name']);

            $stockCard = StockCard::create([
                'stock_code' => $this->normalizeStockCode($validated['stock_code']),
                'stock_name' => trim($validated['stock_name']),
                'category_id' => $category->id,
            ]);

            $this->syncGalleryItems($stockCard);

            return $stockCard->load('category');
        });

        $this->audit->log('stock_card.create', $stockCard, [
            'stock_code' => $stockCard->stock_code,
            'stock_name' => $stockCard->stock_name,
            'category' => $stockCard->category?->name,
        ]);

        return redirect()
            ->route('admin.stock-cards.index')
            ->with('success', 'Stok kartı oluşturuldu.');
    }

    public function edit(StockCard $stockCard): View
    {
        abort_unless(auth()->user()->hasPermission('stock_cards', 'edit'), 403);

        $stockCard->load(['category:id,name'])->loadCount('galleryItems');
        $categories = ArtworkCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.stock-cards.edit', compact('stockCard', 'categories'));
    }

    public function update(Request $request, StockCard $stockCard): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('stock_cards', 'edit'), 403);

        $request->merge([
            'stock_code' => $this->normalizeStockCode((string) $request->input('stock_code')),
        ]);

        $validated = $request->validate([
            'stock_code' => ['required', 'string', 'max:100', 'unique:stock_cards,stock_code,' . $stockCard->id],
            'stock_name' => ['required', 'string', 'max:200'],
            'category_name' => ['required', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($validated, $stockCard) {
            $category = $this->categories->findOrCreate($validated['category_name']);

            $stockCard->update([
                'stock_code' => $this->normalizeStockCode($validated['stock_code']),
                'stock_name' => trim($validated['stock_name']),
                'category_id' => $category->id,
            ]);

            $this->syncGalleryItems($stockCard->fresh());
        });

        $this->audit->log('stock_card.update', $stockCard->fresh('category'), [
            'stock_code' => $stockCard->stock_code,
            'stock_name' => $stockCard->stock_name,
            'category' => $stockCard->category?->name,
        ]);

        return redirect()
            ->route('admin.stock-cards.edit', $stockCard)
            ->with('success', 'Stok kartı güncellendi.');
    }

    public function destroy(StockCard $stockCard): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('stock_cards', 'delete'), 403);

        if ($stockCard->galleryItems()->exists()) {
            return back()->with('error', 'Galeri kaydı bağlı olduğu için stok kartı silinemez.');
        }

        $this->audit->log('stock_card.delete', $stockCard, [
            'stock_code' => $stockCard->stock_code,
            'stock_name' => $stockCard->stock_name,
        ]);

        $stockCard->delete();

        return redirect()
            ->route('admin.stock-cards.index')
            ->with('success', 'Stok kartı silindi.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('stock_cards', 'delete'), 403);

        $validated = $request->validate([
            'stock_card_ids' => ['required', 'array', 'min:1'],
            'stock_card_ids.*' => ['integer', 'distinct', 'exists:stock_cards,id'],
        ], [
            'stock_card_ids.required' => 'Lütfen silinecek en az bir stok kartı seçin.',
            'stock_card_ids.min' => 'Lütfen silinecek en az bir stok kartı seçin.',
        ]);

        $stockCards = StockCard::query()
            ->withCount('galleryItems')
            ->whereIn('id', $validated['stock_card_ids'])
            ->get();

        $blockedStockCards = $stockCards->filter(fn (StockCard $stockCard) => $stockCard->gallery_items_count > 0)->values();
        $deletableStockCards = $stockCards->reject(fn (StockCard $stockCard) => $stockCard->gallery_items_count > 0)->values();

        DB::transaction(function () use ($deletableStockCards) {
            foreach ($deletableStockCards as $stockCard) {
                $this->audit->log('stock_card.delete', $stockCard, [
                    'stock_code' => $stockCard->stock_code,
                    'stock_name' => $stockCard->stock_name,
                    'bulk' => true,
                ]);

                $stockCard->delete();
            }
        });

        $response = redirect()->route('admin.stock-cards.index');

        if ($deletableStockCards->isNotEmpty()) {
            $response->with('success', $deletableStockCards->count() . ' stok kartı silindi.');
        }

        if ($blockedStockCards->isNotEmpty()) {
            $blockedCodes = $blockedStockCards->pluck('stock_code')->take(3)->implode(', ');
            $suffix = $blockedStockCards->count() > 3 ? ' ve diğerleri' : '';

            $response->with(
                'error',
                'Galeri kaydı bağlı olduğu için ' . $blockedStockCards->count() . ' stok kartı atlandı: ' . $blockedCodes . $suffix . '.'
            );
        }

        if ($deletableStockCards->isEmpty() && $blockedStockCards->isEmpty()) {
            $response->with('error', 'Seçilen stok kartları bulunamadı.');
        }

        return $response;
    }

    public function importForm(): View
    {
        abort_unless(
            auth()->user()->hasPermission('stock_cards', 'create') || auth()->user()->hasPermission('stock_cards', 'bulk_import'),
            403
        );

        return view('admin.stock-cards.import');
    }

    public function import(Request $request, StockCardBulkImportService $service): RedirectResponse
    {
        abort_unless(
            auth()->user()->hasPermission('stock_cards', 'create') || auth()->user()->hasPermission('stock_cards', 'bulk_import'),
            403
        );

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'file.required' => 'Lütfen bir Excel dosyası seçin.',
            'file.mimes' => 'Sadece .xlsx veya .xls dosyası yüklenebilir.',
            'file.max' => 'Dosya boyutu en fazla 5 MB olabilir.',
        ]);

        try {
            $result = $service->import($request->file('file'));
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            return back()->with('error', 'Dosya işlenirken hata oluştu: ' . $exception->getMessage());
        }

        $this->audit->log('stock_card.import', null, [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'errors' => $result['error_count'],
        ]);

        return redirect()
            ->route('admin.stock-cards.import.form')
            ->with('import_result', $result);
    }

    public function downloadTemplate(StockCardBulkImportService $service): void
    {
        abort_unless(auth()->user()->hasPermission('stock_cards'), 403);

        $service->streamTemplate();
    }

    public function lookup(Request $request): JsonResponse
    {
        abort_if(
            ! auth()->user()->canUploadArtwork()
            && ! auth()->user()->hasPermission('stock_cards'),
            403
        );

        $stockCode = $this->normalizeStockCode((string) $request->query('stock_code'));

        if ($stockCode === '') {
            return response()->json(['message' => 'Stok kodu gereklidir.'], 422);
        }

        $stockCard = StockCard::query()
            ->with('category:id,name')
            ->where('stock_code', $stockCode)
            ->first();

        if (! $stockCard) {
            return response()->json(['message' => 'Stok kartı bulunamadı.'], 404);
        }

        return response()->json([
            'id' => $stockCard->id,
            'stock_code' => $stockCard->stock_code,
            'stock_name' => $stockCard->stock_name,
            'category_id' => $stockCard->category_id,
            'category_name' => $stockCard->category?->display_name,
            'latest_gallery_revision_no' => $this->revisionNumbers->maxGalleryRevisionNo($stockCard->stock_code),
            'next_upload_revision_no' => $this->revisionNumbers->nextUploadRevisionNo(stockCode: $stockCard->stock_code),
        ]);
    }

    private function normalizeStockCode(string $value): string
    {
        return mb_strtoupper(trim($value));
    }

    private function syncGalleryItems(StockCard $stockCard): void
    {
        DB::table('artwork_gallery')
            ->where('stock_code', $stockCard->stock_code)
            ->update([
                'stock_card_id' => $stockCard->id,
                'category_id' => $stockCard->category_id,
                'updated_at' => now(),
            ]);
    }
}
