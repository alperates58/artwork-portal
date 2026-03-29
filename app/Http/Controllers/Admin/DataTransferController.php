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
                onlyNew: $request->boolean('only_new', true),
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'fields' => $exception->getMessage() ?: 'Dışa aktarım paketi oluşturulurken bir hata oluştu.',
            ]);
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

        $result = $this->dataTransfer->import($request->file('xml_file'));

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
