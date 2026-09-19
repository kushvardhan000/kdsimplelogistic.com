<?php

namespace App\Http\Controllers;

use App\Models\Logsheet;
use App\Models\LogsheetDetail;
use App\Models\LogsheetImport;
use App\Models\LogsheetRawRow;
use App\Services\LogsheetImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LogsheetController extends Controller
{
    public function __construct(
        private LogsheetImportService $importService
    ) {}

    public function index(Request $request): View
    {
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'posting_date_from' => ['nullable', 'date_format:Y-m-d'],
            'posting_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:posting_date_from'],
            'bill_date_from' => ['nullable', 'date_format:Y-m-d'],
            'bill_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:bill_date_from'],
            'min_gross_wt' => ['nullable', 'numeric', 'min:0'],
            'max_gross_wt' => ['nullable', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:pending,cleared'],
        ]);

        $query = Logsheet::query()
            ->with(['details' => fn ($details) => $details->orderBy('id')]);

        $logSheetNo = trim((string) $request->input('log_sheet_no', ''));
        if ($logSheetNo !== '') {
            $query->where('log_sheet_no', 'like', '%'.$logSheetNo.'%');
        }

        $transport = trim((string) $request->input('transport', ''));
        if ($transport !== '') {
            $search = '%'.$transport.'%';
            $query->where(function ($q) use ($search) {
                $q->where('tprt_code', 'like', $search)
                    ->orWhere('tprt_name', 'like', $search)
                    ->orWhereHas('details', function ($q) use ($search) {
                        $q->where('tprt_code', 'like', $search)
                            ->orWhere('tprt_name', 'like', $search);
                    });
            });
        }

        $town = trim((string) $request->input('town', ''));
        if ($town !== '') {
            $search = '%'.$town.'%';
            $query->whereHas('details', function ($q) use ($search) {
                $q->where('town', 'like', $search)
                    ->orWhere('town_2', 'like', $search);
            });
        }

        $location = trim((string) ($request->input('destination') ?? $request->input('location') ?? ''));
        if ($location !== '') {
            $search = '%'.$location.'%';
            $query->where(function ($q) use ($search) {
                $q->where('destination', 'like', $search)
                    ->orWhereHas('details', function ($q) use ($search) {
                        $q->where('destination', 'like', $search)
                            ->orWhere('route', 'like', $search);
                    });
            });
        }

        $vehicleNo = trim((string) $request->input('vehicle_no', ''));
        if ($vehicleNo !== '') {
            $query->where('vehicle_no', 'like', '%'.$vehicleNo.'%');
        }

        $sapInvoiceNo = trim((string) $request->input('sap_invoice_no', ''));
        if ($sapInvoiceNo !== '') {
            $query->where('sap_invoice_no', 'like', '%'.$sapInvoiceNo.'%');
        }

        $vendorInvoiceNo = trim((string) $request->input('vendor_inv_no', ''));
        if ($vendorInvoiceNo !== '') {
            $query->where('vendor_inv_no', 'like', '%'.$vendorInvoiceNo.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        if ($request->filled('posting_date_from')) {
            $query->whereDate('posting_date', '>=', $request->input('posting_date_from'));
        }

        if ($request->filled('posting_date_to')) {
            $query->whereDate('posting_date', '<=', $request->input('posting_date_to'));
        }

        if ($request->filled('bill_date_from')) {
            $query->whereDate('bill_date', '>=', $request->input('bill_date_from'));
        }

        if ($request->filled('bill_date_to')) {
            $query->whereDate('bill_date', '<=', $request->input('bill_date_to'));
        }

        if ($request->filled('min_gross_wt') && is_numeric($request->input('min_gross_wt'))) {
            $query->where('total_gross_wt', '>=', $request->input('min_gross_wt'));
        }

        if ($request->filled('max_gross_wt') && is_numeric($request->input('max_gross_wt'))) {
            $query->where('total_gross_wt', '<=', $request->input('max_gross_wt'));
        }

        if ($request->filled('min_amount') && is_numeric($request->input('min_amount'))) {
            $query->where('total_actual_amount', '>=', $request->input('min_amount'));
        }

        if ($request->filled('max_amount') && is_numeric($request->input('max_amount'))) {
            $query->where('total_actual_amount', '<=', $request->input('max_amount'));
        }

        $sortField = strtolower(trim((string) $request->input('sort', 'date')));
        $sortDirection = strtolower(trim((string) $request->input('direction', 'desc')));
        $allowedSortFields = [
            'log_sheet_no', 'date', 'vehicle_no', 'tprt_code', 'tprt_name', 'town',
            'destination', 'sap_invoice_no', 'posting_date', 'bill_date', 'vendor_inv_no',
            'total_gross_wt', 'total_booked_amount', 'total_actual_amount', 'total_diff',
            'consignment_count', 'status', 'cleared_at', 'created_at',
        ];

        if (! in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'date';
        }

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        if ($sortField === 'town') {
            $townSortQuery = LogsheetDetail::query()
                ->selectRaw('logsheet_id, MIN(COALESCE(NULLIF(town, ""), NULLIF(town_2, ""))) AS town_sort')
                ->groupBy('logsheet_id');

            $query->leftJoinSub($townSortQuery, 'logsheet_town_sort', fn ($join) => $join->on('logsheet_town_sort.logsheet_id', '=', 'logsheets.id'));
            $query->orderBy('logsheet_town_sort.town_sort', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        $logsheets = $query->paginate(25)->withQueryString();

        $summaryQuery = clone $query;
        $summaryQuery->setEagerLoads([]);
        $summaryQuery->getQuery()->orders = [];
        $summaryQuery->getQuery()->limit = null;
        $summaryQuery->getQuery()->offset = null;

        return view('logsheets.index', [
            'logsheets' => $logsheets,
            'totalImports' => $summaryQuery->count(),
            'pendingCount' => (clone $summaryQuery)->where('status', 'pending')->count(),
            'clearedCount' => (clone $summaryQuery)->where('status', 'cleared')->count(),
            'totalGrossWt' => (clone $summaryQuery)->sum('total_gross_wt'),
            'totalBookedAmount' => (clone $summaryQuery)->sum('total_booked_amount'),
            'totalActualAmount' => (clone $summaryQuery)->sum('total_actual_amount'),
            'totalDiff' => (clone $summaryQuery)->sum('total_diff'),
            'filters' => $request->all(),
            'sortField' => $sortField,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $file = $request->file('file');
        $summary = $this->importService->import($file);

        return back()->with('success', 'Imported '.($summary['rows_imported'] ?? 0).' rows into '.($summary['consolidated'] ?? 0).' consolidated log sheets. Invalid rows: '.($summary['invalid'] ?? 0).'.');
    }

    public function show(Logsheet $logsheet): View
    {
        $logsheet->load(['details', 'lastImport', 'clearings.clearer']);

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

            $logsheet->details()->update(['cleared' => true]);
        });

        return back()->with('success', 'Log sheet cleared successfully.');
    }

    public function destroy(Logsheet $logsheet): RedirectResponse
    {
        $logsheet->delete();

        return back()->with('success', 'Logsheet deleted successfully.');
    }

    public function download(LogsheetImport $import): RedirectResponse
    {
        if (! $import->file_path) {
            return back()->with('error', 'No file found for this import.');
        }

        return Storage::disk('public')->download($import->file_path, $import->original_filename);
    }
}
