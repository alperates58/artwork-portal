<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomReport;
use App\Models\Supplier;
use App\Services\ReportQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportFactoryController extends Controller
{
    public function __construct(private ReportQueryService $queryService) {}

    public function index()
    {
        $this->gate();
        $reports = CustomReport::with('createdBy')
            ->where(fn($q) => $q->where('created_by', auth()->id())->orWhere('is_shared', true))
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.reports.factory.index', compact('reports'));
    }

    public function create()
    {
        $this->gate();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        return view('admin.reports.factory.builder', ['report' => null, 'suppliers' => $suppliers]);
    }

    public function store(Request $request)
    {
        $this->gate();
        $v = $request->validate([
            'name'       => 'required|string|max:200',
            'dimensions' => 'required|json',
            'metrics'    => 'required|json',
            'chart_type' => 'required|in:bar,line,pie,doughnut',
            'filters'    => 'nullable|json',
        ]);

        $report = CustomReport::create([
            'created_by' => auth()->id(),
            'name'       => $v['name'],
            'dimensions' => json_decode($v['dimensions'], true),
            'metrics'    => json_decode($v['metrics'], true),
            'chart_type' => $v['chart_type'],
            'filters'    => isset($v['filters']) ? json_decode($v['filters'], true) : null,
        ]);

        return redirect()->route('admin.reports.factory.show', $report)
            ->with('success', '"' . $report->name . '" raporu kaydedildi.');
    }

    public function show(CustomReport $customReport)
    {
        $this->gateReport($customReport);
        $data      = $this->queryService->run($customReport->dimensions, $customReport->metrics, $customReport->filters ?? []);
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        return view('admin.reports.factory.show', compact('customReport', 'data', 'suppliers'));
    }

    public function update(Request $request, CustomReport $customReport)
    {
        $this->gateReport($customReport);
        $v = $request->validate([
            'name'       => 'required|string|max:200',
            'dimensions' => 'required|json',
            'metrics'    => 'required|json',
            'chart_type' => 'required|in:bar,line,pie,doughnut',
            'filters'    => 'nullable|json',
        ]);

        $customReport->update([
            'name'       => $v['name'],
            'dimensions' => json_decode($v['dimensions'], true),
            'metrics'    => json_decode($v['metrics'], true),
            'chart_type' => $v['chart_type'],
            'filters'    => isset($v['filters']) ? json_decode($v['filters'], true) : null,
        ]);

        return redirect()->route('admin.reports.factory.show', $customReport)
            ->with('success', 'Rapor güncellendi.');
    }

    public function destroy(CustomReport $customReport)
    {
        $this->gateReport($customReport);
        $customReport->delete();
        return redirect()->route('admin.reports.factory.index')
            ->with('success', 'Rapor silindi.');
    }

    public function preview(Request $request): JsonResponse
    {
        $this->gate();

        $allowed_dims    = ['supplier', 'month', 'year', 'quarter', 'order_status', 'artwork_status'];
        $allowed_metrics = ['order_count', 'line_count', 'pending_artwork', 'uploaded_artwork', 'revision_count', 'avg_days_to_upload'];

        $dimensions = array_values(array_filter(
            (array) $request->input('dimensions', []),
            fn($d) => in_array($d, $allowed_dims)
        ));
        $metrics = array_values(array_filter(
            (array) $request->input('metrics', []),
            fn($m) => in_array($m, $allowed_metrics)
        ));

        if (empty($dimensions) || empty($metrics)) {
            return response()->json(['error' => 'En az bir boyut ve bir metrik seçin.'], 422);
        }

        $filters = (array) $request->input('filters', []);
        $data    = $this->queryService->run($dimensions, $metrics, $filters);

        return response()->json($data);
    }

    private function gate(): void
    {
        abort_unless(auth()->user()->hasPermission('reports'), 403);
    }

    private function gateReport(CustomReport $report): void
    {
        $this->gate();
        abort_unless(
            $report->created_by === auth()->id() || auth()->user()->isAdmin() || $report->is_shared,
            403
        );
    }
}
