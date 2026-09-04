<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\StoreAccountRequest;
use App\Models\Account;
use App\Services\AccountLedgerService;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        private AccountLedgerService $ledger
    ) {
        $this->authorizeResource(Account::class, 'account');
    }

    public function index(Request $request): View
    {
        $query = Account::query()
            ->with(['fuelStation', 'driver', 'branch', 'transactions'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderByDesc('created_at');

        $perPage = ($request->filled('type') && (string) $request->string('type') === 'fuel_station') ? 200 : 15;
        $accounts = $query->paginate($perPage)->withQueryString();

        if ($request->filled('type')) {
            $type = (string) $request->string('type');
            $branches = \App\Models\Branch::all();
            $drivers = \App\Models\Driver::all();
            $fuelStations = \App\Models\FuelStation::all();

            $accounts->load(['transactions' => fn ($q) => $q->latest()->limit(1)]);

            return view('accounts.type-index', compact('accounts', 'type', 'branches', 'drivers', 'fuelStations'));
        }

        $grouped = $accounts->getCollection()->groupBy('type');

        $typeStats = [];
        foreach (['fuel_station', 'motor_parts_shop', 'staff', 'company_expense'] as $typeKey) {
            $typeAccounts = Account::where('type', $typeKey)->get();
            $typeStats[$typeKey] = [
                'count' => $typeAccounts->count(),
                'balance' => $typeAccounts->sum('current_balance'),
                'activity' => \App\Models\AccountTransaction::whereHas('account', fn ($q) => $q->where('type', $typeKey))
                    ->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year)
                    ->count(),
            ];
        }

        $recentTransactions = \App\Models\AccountTransaction::with('account')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('accounts.index', compact('accounts', 'grouped', 'typeStats', 'recentTransactions'));
    }

    public function create(): View
    {
        $branches = \App\Models\Branch::all();
        $drivers = \App\Models\Driver::all();
        $fuelStations = \App\Models\FuelStation::all();

        return view('accounts.create', compact('branches', 'drivers', 'fuelStations'));
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = DB::transaction(function () use ($request) {
            return Account::create(array_merge(
                $request->validated(),
                [
                    'opening_balance' => $request->input('opening_balance', 0),
                    'current_balance' => $request->input('opening_balance', 0),
                    'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]
            ));
        });

        ActivityLogger::crud(
            action: 'create',
            module: 'accounts',
            recordId: $account->id,
            summary: $account->name,
            user: $request->user(),
            request: $request
        );

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function show(Account $account): View|\Illuminate\Http\RedirectResponse
    {
        if ($account->type === 'fuel_station') {
            return redirect()->route('accounts.index', ['type' => 'fuel_station', 'selected' => $account->id]);
        }

        $summary = $this->ledger->getBalanceSummary($account);
        $filters = $this->buildFilters(request());
        $transactions = $this->ledger->getFilteredTransactions($account, $filters);
        $branches = \App\Models\Branch::all();

        return view('accounts.show', compact('account', 'summary', 'transactions', 'filters', 'branches'));
    }

    public function edit(Account $account): View
    {
        $branches = \App\Models\Branch::all();
        $drivers = \App\Models\Driver::all();
        $fuelStations = \App\Models\FuelStation::all();

        return view('accounts.edit', compact('account', 'branches', 'drivers', 'fuelStations'));
    }

    public function searchTransportLogs(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = \App\Models\TransportLog::query()
            ->select('id', 'vehicle_no', 'logsheet_no', 'date', 'diesel_advance', 'fuel_station_id', 'branch_id')
            ->where(function ($q) use ($request) {
                $term = $request->string('q');
                $q->where('vehicle_no', 'like', '%' . $term . '%')
                    ->orWhere('logsheet_no', 'like', '%' . $term . '%')
                    ->orWhereDate('date', $term);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $results = $query->map(function ($log) {
            $paid = \App\Models\AccountTransaction::where('reference_type', \App\Models\TransportLog::class)
                ->where('reference_id', $log->id)
                ->where('direction', 'credit')
                ->whereNull('deleted_at')
                ->sum('amount');

            return [
                'id' => $log->id,
                'vehicle_no' => $log->vehicle_no,
                'logsheet_no' => $log->logsheet_no,
                'date' => $log->date?->format('Y-m-d'),
                'diesel_advance' => (float) $log->diesel_advance,
                'paid_amount' => round((float) $paid, 2),
                'remaining_due' => round(max(0, (float) $log->diesel_advance - (float) $paid), 2),
            ];
        });

        return response()->json($results);
    }

    public function pumpFlow(Account $account, Request $request): View
    {
        $summary = $this->ledger->getBalanceSummary($account);

        $branchId = $request->filled('branch_id') ? $request->integer('branch_id') : null;

        $transactions = \App\Models\AccountTransaction::where('account_id', $account->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $branches = \App\Models\AccountTransaction::where('account_id', $account->id)
            ->whereNotNull('branch_id')
            ->with('branch')
            ->get()
            ->map(fn ($t) => $t->branch)
            ->filter()
            ->unique('id')
            ->values();

        return view('accounts._pump-flow', compact('account', 'summary', 'transactions', 'branches', 'branchId'));
    }

    public function update(StoreAccountRequest $request, Account $account): RedirectResponse
    {
        $account->update(array_merge(
            $request->validated(),
            [
                'updated_by' => $request->user()->id,
            ]
        ));

        ActivityLogger::crud(
            action: 'update',
            module: 'accounts',
            recordId: $account->id,
            summary: $account->name,
            user: $request->user(),
            request: $request
        );

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $request = request();

        ActivityLogger::crud(
            action: 'delete',
            module: 'accounts',
            recordId: $account->id,
            summary: $account->name,
            user: $request->user(),
            request: $request
        );

        $account->delete();

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Account deleted successfully.');
    }

    private function buildFilters(Request $request): array
    {
        $filters = [
            'date_from' => $request->filled('date_from') ? $request->string('date_from')->__toString() : null,
            'date_to' => $request->filled('date_to') ? $request->string('date_to')->__toString() : null,
            'direction' => $request->filled('direction') ? $request->string('direction')->__toString() : null,
            'payment_mode' => $request->filled('payment_mode') ? $request->string('payment_mode')->__toString() : null,
            'payment_plan' => $request->filled('payment_plan') ? $request->string('payment_plan')->__toString() : null,
            'day' => $request->filled('day') ? $request->integer('day') : null,
            'month' => $request->filled('month') ? $request->integer('month') : null,
            'year' => $request->filled('year') ? $request->integer('year') : null,
            'sort' => $request->filled('sort') ? $request->string('sort')->__toString() : 'transaction_date',
            'direction_sort' => $request->filled('direction_sort') ? $request->string('direction_sort')->__toString() : 'desc',
        ];

        return $filters;
    }
}
