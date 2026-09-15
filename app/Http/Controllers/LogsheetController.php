<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Logsheet;
use App\Models\LogsheetImport;
use App\Models\LogsheetRawRow;
use App\Services\LogsheetImportService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LogsheetController extends Controller
{
    public function __construct(
        private LogsheetImportService $importService
    ) {}

    public function index(Request $request): View
    {
        $query = Logsheet::query()->with(['lastImport', 'clearer']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        $logsheets = $query->orderByDesc('date')->paginate(15)->withQueryString();

        return view('logsheets.index', [
            'logsheets' => $logsheets,
            'dateFrom' => $request->input('date_from'),
            'dateTo' => $request->input('date_to'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $file = $request->file('file');
        $summary = $this->importService->import($file);

        return back()->with('success', 'Imported ' . ($summary['rows_imported'] ?? 0) . ' rows into ' . ($summary['consolidated'] ?? 0) . ' consolidated log sheets. Invalid rows: ' . ($summary['invalid'] ?? 0) . '.');
    }

    public function show(Logsheet $logsheet): View
    {
        $rawRows = LogsheetRawRow::where('log_sheet_no', $logsheet->log_sheet_no)
            ->orderBy('row_number_in_file')
            ->get();

        return view('logsheets.show', [
            'logsheet' => $logsheet,
            'rawRows' => $rawRows,
        ]);
    }

    public function clearLogsheet(Request $request): RedirectResponse
    {
        $logSheetNo = ltrim($request->input('log_sheet_no'), '0');
        $logSheetNo = $logSheetNo === '' ? $request->input('log_sheet_no') : $logSheetNo;
        $request->merge(['log_sheet_no' => $logSheetNo]);

        $request->validate([
            'log_sheet_no' => ['required', 'string', 'exists:logsheets,log_sheet_no'],
        ]);

        $logsheet = Logsheet::where('log_sheet_no', $logSheetNo)->first();

        if ($logsheet->status === 'cleared') {
            return back()->with('info', 'This log sheet is already cleared.');
        }

        $user = Auth::user();
        DB::transaction(function () use ($logsheet, $user, $request) {
            $logsheet->update([
                'status' => 'cleared',
                'cleared_at' => now(),
                'cleared_by' => $user?->id,
            ]);

            $logsheet->clearings()->create([
                'cleared_by' => $user?->id,
                'cleared_at' => now(),
                'invoice_no_reference' => $request->input('invoice_no_reference'),
                'notes' => $request->input('notes'),
            ]);
        });

        return back()->with('success', 'Log sheet cleared successfully.');
    }
}
