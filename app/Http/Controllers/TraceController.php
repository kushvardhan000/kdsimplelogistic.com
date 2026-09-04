<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\TransportLog;
use App\Services\AccountLedgerService;
use App\Services\FuelSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TraceController extends Controller
{
    public function __construct(
        private AccountLedgerService $ledger,
        private FuelSettlementService $fuelSettlement
    ) {
    }

    public function show(Request $request, string $traceCode): View
    {
        $log = TransportLog::where('trace_code', $traceCode)->firstOrFail();

        $log->load('creator', 'editor', 'vehicle', 'company', 'carrier', 'branch', 'fuelStation');

        $fuelStationAccount = null;
        $fuelPaymentHistory = collect();
        $fuelRemainingDue = 0;
        $fuelPaymentStatus = 'unpaid';

        if ($log->fuel_station_id) {
            $fuelStationAccount = Account::where('type', 'fuel_station')
                ->where('linked_fuel_station_id', $log->fuel_station_id)
                ->first();

            if ($fuelStationAccount) {
                $fuelPaymentHistory = $this->fuelSettlement->getPaymentHistoryForLog($log);
                $fuelRemainingDue = $this->fuelSettlement->getRemainingDue($log);
                $fuelPaymentStatus = $log->fuel_payment_status ?? 'unpaid';
            }
        }

        $isConsistent = $this->checkConsistency($log, $fuelStationAccount);

        return view('trace.show', compact(
            'log',
            'fuelStationAccount',
            'fuelPaymentHistory',
            'fuelRemainingDue',
            'fuelPaymentStatus',
            'isConsistent'
        ));
    }

    private function checkConsistency(TransportLog $log, ?Account $fuelStationAccount): bool
    {
        if (! $fuelStationAccount) {
            return true;
        }

        $computedPaid = \App\Models\AccountTransaction::where('reference_type', TransportLog::class)
            ->where('reference_id', $log->id)
            ->where('direction', 'credit')
            ->whereNull('deleted_at')
            ->sum('amount');

        $computedPaid = round((float) $computedPaid, 2);
        $storedPaid = round((float) $log->fuel_paid_amount, 2);

        if ($computedPaid !== $storedPaid) {
            return false;
        }

        $summary = $this->ledger->getBalanceSummary($fuelStationAccount);
        $expectedBalance = round((float) $summary['current_balance'], 2);
        $storedBalance = round((float) $fuelStationAccount->current_balance, 2);

        if ($expectedBalance !== $storedBalance) {
            return false;
        }

        return true;
    }
}
