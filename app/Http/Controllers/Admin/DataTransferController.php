<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DataTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DataTransferController extends Controller
{
    public function __construct(private DataTransferService $dataTransfer) {}

    public function index(): View
    {
        $this->checkAccess();

        return view('admin.data-transfer.index', $this->dataTransfer->buildExportOptions());
    }

    public function export(Request $request): Response|RedirectResponse
    {
        $this->checkAccess();

        $selection = $this->dataTransfer->validateSelection($request->all());

        if ($selection === []) {
            return back()->withErrors(['fields' => 'Lütfen dışa aktarılacak en az bir veri alanı seçin.']);
        }

        try {
            $result = $this->dataTransfer->export(
                selection: $selection,
                includeMedia: $request->boolean('include_media'),
                onlyNew: false,
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'fields' => $exception->getMessage() ?: 'Dışa aktarım paketi oluşturulurken bir hata oluştu.',
            ]);
        }

        $exportedCount = collect($result['stats'])->sum(fn (array $sectionStats) => (int) ($sectionStats['count'] ?? 0));

        if ($exportedCount === 0) {
            return redirect()
                ->route('admin.data-transfer.index')
                ->with('warning', 'Seçilen alanlar için dışa aktarılacak kayıt bulunamadı.');
        }

        return response($result['xml'], 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->checkAccess();

        $request->validate([
            'xml_file' => ['required', 'file', 'mimetypes:application/xml,text/xml,text/plain', 'max:51200'],
        ]);

        try {
            $result = $this->dataTransfer->import($request->file('xml_file'));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'xml_file' => 'İçe aktarma sırasında bir hata oluştu. Son migrationların çalıştığından emin olun ve tekrar deneyin.',
            ]);
        }

        if (! $result['ok']) {
            return back()->withErrors(['xml_file' => $result['message']]);
        }

        return back()->with('success', $result['message']);
    }

    public function destroyImported(): RedirectResponse
    {
        $this->checkAccess();

        $this->dataTransfer->destroyImported();

        return back()->with('success', 'İçe aktarılan tüm veriler silindi.');
    }

    private function checkAccess(): void
    {
        abort_if(! auth()->user()->isAdmin(), 403);
    }
}
