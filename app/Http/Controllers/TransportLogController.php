<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transport\StoreTransportLogRequest;
use App\Http\Requests\Transport\UpdateTransportLogRequest;
use App\Models\StationDebit;
use App\Models\TransportLog;
use App\Exports\TransportLogsExport;
use App\Services\ActivityLogger;
use App\Services\AccountLedgerService;
use App\Services\TransportLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\View\View;

class TransportLogController extends Controller
{
    private const COMPUTED_FIELDS = [
        'total_sale',
        'total_expense',
        'profit',
        'total_advance',
        'balance_vehicle_payment',
    ];

    public function __construct(
        private TransportLogService $totals,
        private AccountLedgerService $ledger
    ) {
        $this->authorizeResource(TransportLog::class, 'transport_log');
    }

    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $search = $request->string('search');

        if ($search !== '' && TransportLog::where('trace_code', $search)->exists()) {
            return redirect()->route('trace.show', $search);
        }

        $query = TransportLog::with(['creator', 'editor', 'vehicle', 'company', 'carrier', 'branch', 'fuelStation']);

        $query = $this->applyTransportLogFilters($query, $request);

        $allowedSorts = ['created_at', 'date', 'vehicle_no', 'company', 'total_sale', 'total_expense', 'profit'];
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        if (! in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $logs = $query->orderBy($sort, $direction)->paginate(15)->withQueryString();

        return view('logs.index', compact('logs'));
    }

    public function create(): View
    {
        return view('logs.create', $this->formViewData(null));
    }

    private function formViewData(?TransportLog $log): array
    {
        $fuelStations = \App\Models\FuelStation::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($station) {
                $account = \App\Models\Account::where('type', 'fuel_station')
                    ->where('linked_fuel_station_id', $station->id)
                    ->first();

                return [
                    'id' => $station->id,
                    'name' => $station->name,
                    'branch' => $station->branch?->name,
                    'current_balance' => $account ? (float) $account->current_balance : 0.0,
                ];
            })
            ->values();

        return [
            'log' => $log,
            'fuelStations' => $fuelStations,
        ];
    }

    public function store(StoreTransportLogRequest $request): RedirectResponse
    {
        try {
            $validated = DB::transaction(function () use ($request) {
                $validated = $request->validated();

                foreach (self::COMPUTED_FIELDS as $field) {
                    unset($validated[$field]);
                }

                $validated = $this->totals->computeTotals($validated);
                $validated['created_by'] = $request->user()->id;
                $validated['updated_by'] = $request->user()->id;

                return TransportLog::create($validated);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'system' => 'Failed to create transport log. Please try again.',
            ]);
        }

        return redirect()
            ->route('transport-logs.show', $validated)
            ->with('success', 'Transport log created successfully.');
    }

    public function show(TransportLog $transportLog): View
    {
        $transportLog->load('creator', 'editor', 'vehicle', 'company', 'carrier', 'branch', 'fuelStation');

        $fuelSettlementService = app(\App\Services\FuelSettlementService::class);
        $fuelPaymentHistory = [];
        $fuelRemainingDue = 0;
        $fuelPaymentStatus = 'unpaid';
        $fuelStationAccount = null;

        if ($transportLog->fuel_station_id) {
            $fuelStationAccount = \App\Models\Account::where('type', 'fuel_station')
                ->where('linked_fuel_station_id', $transportLog->fuel_station_id)
                ->first();

            if ($fuelStationAccount) {
                $fuelPaymentHistory = $fuelSettlementService->getPaymentHistoryForLog($transportLog);
                $fuelRemainingDue = $fuelSettlementService->getRemainingDue($transportLog);
                $fuelPaymentStatus = $transportLog->fuel_payment_status ?? 'unpaid';
            }
        }

        return view('logs.show', compact('transportLog', 'fuelStationAccount', 'fuelPaymentHistory', 'fuelRemainingDue', 'fuelPaymentStatus'));
    }

    public function exportSingle(TransportLog $transportLog, Request $request)
    {
        $this->authorize('view', $transportLog);

        $query = TransportLog::where('id', $transportLog->id);

        ActivityLogger::export('transport_logs', $transportLog->id, $transportLog->vehicle_no, $request->user(), $request);

        return Excel::download(
            new TransportLogsExport($query),
            'transport-log-'.$transportLog->id.'-'.$transportLog->vehicle_no.'.xlsx'
        );
    }

    public function exportMonthly(Request $request)
    {
        $this->authorize('viewAny', TransportLog::class);

        $month = $request->integer('month');
        $year = $request->integer('year');

        $query = $this->applyTransportLogFilters(
            TransportLog::query()
                ->when($month, fn ($q) => $q->whereMonth('date', $month))
                ->when($year, fn ($q) => $q->whereYear('date', $year)),
            $request
        );

        $filename = 'transport-logs-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT).'-'.$year.'.xlsx';

        ActivityLogger::export('transport_logs', null, "Monthly export: {$month}/{$year}", $request->user(), $request);

        return Excel::download(new TransportLogsExport($query), $filename);
    }

    public function exportYearly(Request $request)
    {
        $this->authorize('viewAny', TransportLog::class);

        $year = $request->integer('year');

        $query = $this->applyTransportLogFilters(
            TransportLog::query()
                ->when($year, fn ($q) => $q->whereYear('date', $year)),
            $request
        );

        ActivityLogger::export('transport_logs', null, "Yearly export: {$year}", $request->user(), $request);

        return Excel::download(new TransportLogsExport($query), 'transport-logs-'.$year.'.xlsx');
    }

    public function exportRange(Request $request)
    {
        $this->authorize('viewAny', TransportLog::class);

        $query = $this->applyTransportLogFilters(TransportLog::query(), $request);

        $dateFrom = $request->filled('date_from') ? $request->string('date_from') : 'all';
        $dateTo = $request->filled('date_to') ? $request->string('date_to') : 'all';

        $filename = 'transport-logs-'.$dateFrom.'-to-'.$dateTo.'.xlsx';

        ActivityLogger::export('transport_logs', null, "Range export: {$dateFrom} to {$dateTo}", $request->user(), $request);

        return Excel::download(new TransportLogsExport($query), $filename);
    }

    public function edit(TransportLog $transportLog): View
    {
        $data = $this->formViewData($transportLog->load('creator', 'editor', 'vehicle', 'company', 'carrier', 'branch', 'fuelStation'));
        $data['transportLog'] = $data['log'];
        return view('logs.edit', $data);
    }

    public function update(UpdateTransportLogRequest $request, TransportLog $transportLog): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $transportLog) {
                $validated = $request->validated();

                foreach (self::COMPUTED_FIELDS as $field) {
                    unset($validated[$field]);
                }

                $validated = $this->totals->computeTotals($validated);
                $validated['updated_by'] = $request->user()->id;

                $transportLog->update($validated);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'system' => 'Failed to update transport log. Please try again.',
            ]);
        }

        return redirect()
            ->route('transport-logs.show', $transportLog)
            ->with('success', 'Transport log updated successfully.');
    }

    public function destroy(TransportLog $transportLog): RedirectResponse
    {
        try {
            DB::transaction(function () use ($transportLog) {
                $transportLog->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'system' => 'Failed to delete transport log. Please try again.',
            ]);
        }

        return redirect()
            ->route('transport-logs.index')
            ->with('success', 'Transport log deleted successfully.');
    }

    private function applyTransportLogFilters(\Illuminate\Database\Eloquent\Builder $query, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_no', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('transport_name', 'like', "%{$search}%")
                  ->orWhere('trace_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('company')) {
            $query->where('company', 'like', '%' . $request->string('company') . '%');
        }

        if ($request->filled('status')) {
            $status = (string) $request->string('status');

            if ($status === 'profit') {
                $query->where('profit', '>', 0);
            } elseif ($status === 'loss') {
                $query->where('profit', '<', 0);
            } elseif ($status === 'breakeven') {
                $query->where('profit', '=', 0);
            }
        }

        if ($request->filled('created_date')) {
            try {
                $query->whereDate('created_at', $request->date('created_date'));
            } catch (\Throwable $e) {
                $query->whereRaw('0 = 1');
            }
        }

        if ($request->filled('clearing_date')) {
            try {
                $query->whereDate('clearing_date', $request->date('clearing_date'));
            } catch (\Throwable $e) {
                $query->whereRaw('0 = 1');
            }
        }

        return $query;
    }
}
