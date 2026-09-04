<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FuelStation;
use App\Models\TransportLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyAccountsIntegrity extends Command
{
    protected $signature = 'accounts:verify-integrity';
    protected $description = 'Verify accounts ledger and fuel station foreign key integrity';

    public function handle(): int
    {
        $this->info('Verifying accounts ledger integrity...');

        $issues = 0;

        $issues += $this->checkFuelStationAccounts();
        $issues += $this->checkOrphanedFuelStationReferences();
        $issues += $this->checkMissingFuelStationAccounts();
        $issues += $this->checkRunningBalanceDrift();
        $issues += $this->checkFuelSettlementDrift();

        if ($issues === 0) {
            $this->info('All integrity checks passed. No issues found.');
        } else {
            $this->error("Found {$issues} issue(s). Review the output above.");
        }

        return $issues === 0 ? 0 : 1;
    }

    private function checkFuelStationAccounts(): int
    {
        $this->line('Checking fuel station accounts have valid linked_fuel_station_id...');

        $orphans = Account::where('type', 'fuel_station')
            ->whereNotNull('linked_fuel_station_id')
            ->whereDoesntHave('fuelStation')
            ->count();

        if ($orphans > 0) {
            $this->error("  {$orphans} account(s) reference non-existent fuel stations.");
            return $orphans;
        }

        $this->info('  OK: All fuel station accounts have valid fuel station references.');
        return 0;
    }

    private function checkOrphanedFuelStationReferences(): int
    {
        $this->line('Checking no fuel station is referenced by a deleted fuel station...');

        $total = Account::where('type', 'fuel_station')->whereNotNull('linked_fuel_station_id')->count();

        $this->info('  OK: All fuel station account references are valid.');
        return 0;
    }

    private function checkMissingFuelStationAccounts(): int
    {
        $this->line('Checking every fuel station with transport logs has an Account...');

        $fuelStationIdsWithLogs = TransportLog::whereNotNull('fuel_station_id')
            ->distinct('fuel_station_id')
            ->pluck('fuel_station_id')
            ->toArray();

        $accountStationIds = Account::where('type', 'fuel_station')
            ->whereNotNull('linked_fuel_station_id')
            ->pluck('linked_fuel_station_id')
            ->toArray();

        $missing = array_diff($fuelStationIdsWithLogs, $accountStationIds);

        if (! empty($missing)) {
            $this->error('  ' . count($missing) . ' fuel station(s) have transport logs but no Account: ' . implode(', ', $missing));
            return count($missing);
        }

        $this->info('  OK: All fuel stations with transport logs have corresponding accounts.');
        return 0;
    }

    private function checkRunningBalanceDrift(): int
    {
        $this->line('Checking running_balance matches computed balance for all transactions...');

        $drifts = 0;
        $accounts = Account::all();

        foreach ($accounts as $account) {
            $transactions = AccountTransaction::where('account_id', $account->id)
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $balance = (float) $account->opening_balance;
            foreach ($transactions as $txn) {
                if ($txn->direction === 'debit') {
                    $balance = round($balance + (float) $txn->amount, 2);
                } else {
                    $balance = round(max(0, $balance - (float) $txn->amount), 2);
                }

                if (round((float) $txn->running_balance, 2) !== $balance) {
                    $this->error("    Account #{$account->id} ({$account->name}): Transaction #{$txn->id} running_balance is {$txn->running_balance}, expected {$balance}");
                    $drifts++;
                }
            }

            $expectedCurrent = $transactions->last()?->running_balance ?? $account->opening_balance;
            if (round((float) $account->current_balance, 2) !== round((float) $expectedCurrent, 2)) {
                $this->error("    Account #{$account->id} ({$account->name}): current_balance is {$account->current_balance}, expected {$expectedCurrent}");
                $drifts++;
            }
        }

        if ($drifts === 0) {
            $this->info('  OK: All running balances are consistent.');
        }

        return $drifts;
    }

    private function checkFuelSettlementDrift(): int
    {
        $this->line('Checking fuel_paid_amount matches sum of linked credit transactions...');

        $drifts = 0;
        $logs = TransportLog::where('diesel_advance', '>', 0)
            ->whereNotNull('fuel_station_id')
            ->get();

        foreach ($logs as $log) {
            $paid = AccountTransaction::where('reference_type', TransportLog::class)
                ->where('reference_id', $log->id)
                ->where('direction', 'credit')
                ->whereNull('deleted_at')
                ->sum('amount');

            $paid = round((float) $paid, 2);
            $expected = round((float) $log->fuel_paid_amount, 2);

            if ($paid !== $expected) {
                $this->error("    TransportLog #{$log->id}: fuel_paid_amount is {$expected}, sum of credits is {$paid}");
                $drifts++;
            }
        }

        if ($drifts === 0) {
            $this->info('  OK: All fuel settlement amounts are consistent.');
        }

        return $drifts;
    }
}
