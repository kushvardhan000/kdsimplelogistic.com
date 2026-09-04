<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\StationDebit;
use App\Models\TransportLog;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransportLogObserver
{
    public function saving(TransportLog $log): void
    {
        $attributes = $log->getAttributes();
        $attributes = $log->computeTotals($attributes);
        $log->forceFill($attributes);

        if ($log->diesel_advance > 0 && $log->fuel_station_id) {
            $creatorId = Auth::id() ?? ($log->created_by ?? null);

            $existing = StationDebit::where('reference_type', 'transport_log')
                ->where('reference_id', $log->id)
                ->whereNull('deleted_at')
                ->first();

            $old = $existing ? $existing->getAttributes() : null;

            $debit = StationDebit::updateOrCreate(
                [
                    'reference_type' => 'transport_log',
                    'reference_id' => $log->id,
                    'deleted_at' => null,
                ],
                [
                    'fuel_station_id' => $log->fuel_station_id,
                    'branch_id' => $log->branch_id,
                    'amount' => $log->diesel_advance,
                    'notes' => 'Diesel advance for ' . $log->date,
                    'created_by' => $creatorId,
                    'updated_by' => $creatorId,
                    'date' => $log->date,
                ]
            );

            $new = $debit->getAttributes();
            $changes = $this->buildDebitChanges($old, $new);
            $this->logDebitActivity($log, $debit, $changes, $existing ? 'updated' : 'created');
        } else {
            $existing = StationDebit::where('reference_type', 'transport_log')
                ->where('reference_id', $log->id)
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                $old = $existing->getAttributes();
                $existing->update([
                    'deleted_at' => now(),
                    'updated_by' => Auth::id() ?? $log->updated_by,
                ]);
                $new = $existing->getAttributes();
                $changes = $this->buildDebitChanges($old, $new);
                $this->logDebitActivity($log, $existing, $changes, 'reversed');
            }
        }

        if ($log->exists) {
            $this->mirrorToAccountTransactions($log);
        }
    }

    public function created(TransportLog $log): void
    {
        if (empty($log->trace_code)) {
            $log->forceFill(['trace_code' => $this->generateTraceCode($log->id)])->saveQuietly();
        }

        $this->mirrorToAccountTransactions($log);
        $this->recalculateSettlement($log);
    }

    public function updated(TransportLog $log): void
    {
        $this->mirrorToAccountTransactions($log);
        $this->recalculateSettlement($log);
    }

    public function deleted(TransportLog $log): void
    {
        if ($log->diesel_advance > 0 && $log->fuel_station_id) {
            $existing = StationDebit::where('reference_type', 'transport_log')
                ->where('reference_id', $log->id)
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                $old = $existing->getAttributes();
                $existing->update([
                    'deleted_at' => now(),
                    'updated_by' => Auth::id() ?? $log->updated_by,
                ]);
                $new = $existing->getAttributes();
                $changes = $this->buildDebitChanges($old, $new);
                $this->logDebitActivity($log, $existing, $changes, 'reversed');
            }
        }

        $this->mirrorToAccountTransactions($log, 'delete');
    }

    public function restored(TransportLog $log): void
    {
        $this->recalculateSettlement($log);
    }

    public function saved(TransportLog $log): void
    {
        $this->clearDashboardCache();
    }

    protected function mirrorToAccountTransactions(TransportLog $log, string $verb = 'sync'): void
    {
        if (! $log->fuel_station_id) {
            return;
        }

        $account = Account::where('type', 'fuel_station')
            ->where('linked_fuel_station_id', $log->fuel_station_id)
            ->first();

        if (! $account) {
            $account = Account::create([
                'type' => 'fuel_station',
                'name' => $log->fuelStation->name ?? ('Fuel Station #' . $log->fuel_station_id),
                'linked_fuel_station_id' => $log->fuel_station_id,
                'branch_id' => $log->branch_id,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'created_by' => Auth::id() ?? ($log->created_by ?? null),
                'updated_by' => Auth::id() ?? ($log->updated_by ?? null),
            ]);
        }

        $userId = Auth::id() ?? ($log->updated_by ?? $log->created_by ?? null);
        $existing = AccountTransaction::where('reference_type', TransportLog::class)
            ->where('reference_id', $log->id)
            ->whereNull('deleted_at')
            ->first();

        if ($verb === 'delete' || $log->diesel_advance <= 0) {
            if ($existing) {
                DB::transaction(function () use ($existing, $account, $userId) {
                    $lockedAccount = Account::whereKey($account->id)->lockForUpdate()->firstOrFail();
                    $existing->update(['deleted_at' => now(), 'updated_by' => $userId]);
                    $this->recalculateRunningBalances($lockedAccount);
                });
            }
            return;
        }

        DB::transaction(function () use ($log, $account, $existing, $userId) {
            $lockedAccount = Account::whereKey($account->id)->lockForUpdate()->firstOrFail();
            $amount = (float) $log->diesel_advance;

            if ($existing) {
                $oldAmount = (float) $existing->amount;
                if ($oldAmount !== $amount) {
                    $existing->update([
                        'amount' => $amount,
                        'description' => 'Diesel advance for ' . $log->date,
                        'updated_by' => $userId,
                    ]);
                    $this->recalculateRunningBalances($lockedAccount);
                }
            } else {
                AccountTransaction::create([
                    'account_id' => $lockedAccount->id,
                    'branch_id' => $log->branch_id,
                    'direction' => 'debit',
                    'amount' => $amount,
                    'payment_mode' => null,
                    'payment_plan' => null,
                    'reference_type' => TransportLog::class,
                    'reference_id' => $log->id,
                    'description' => 'Diesel advance for ' . $log->date,
                    'transaction_date' => $log->date,
                    'running_balance' => 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                $this->recalculateRunningBalances($lockedAccount);
            }
        });
    }

    protected function recalculateRunningBalances(Account $account): void
    {
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
            $txn->update(['running_balance' => $balance]);
        }

        $lastTxn = AccountTransaction::where('account_id', $account->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->first();

        $account->update([
            'current_balance' => $lastTxn ? $lastTxn->running_balance : $account->opening_balance,
        ]);
    }

    protected function recalculateSettlement(TransportLog $log): void
    {
        app(\App\Services\FuelSettlementService::class)->recalculateForLog($log);
    }

    protected function buildDebitChanges(?array $old, array $new): array
    {
        $excluded = ['id', 'created_at', 'updated_at', 'created_by', 'updated_by'];
        $changes = [];

        if ($old === null) {
            foreach ($new as $key => $value) {
                if (in_array($key, $excluded, true) || $value === null) {
                    continue;
                }

                $changes[] = [
                    'field' => $key,
                    'old' => null,
                    'new' => $value,
                ];
            }

            return $changes;
        }

        foreach ($old as $key => $oldValue) {
            if (in_array($key, $excluded, true)) {
                continue;
            }

            $newValue = $new[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[] = [
                    'field' => $key,
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    protected function logDebitActivity(TransportLog $log, StationDebit $debit, array $changes, string $verb): void
    {
        $userId = Auth::id() ?? ($log->updated_by ?? $log->created_by ?? null);

        $fields = collect($changes)->pluck('field')->join(', ');
        $description = match ($verb) {
            'created' => "Created station debit #{$debit->id} for transport log #{$log->id}: {$fields}",
            'updated' => "Updated station debit #{$debit->id} for transport log #{$log->id}: {$fields}",
            'reversed' => "Reversed station debit #{$debit->id} for transport log #{$log->id}: {$fields}",
            default => "Modified station debit #{$debit->id} for transport log #{$log->id}: {$fields}",
        };

        ActivityLogger::log(
            action: $verb === 'created' ? 'create' : ($verb === 'reversed' ? 'delete' : 'update'),
            module: 'station_debits',
            recordId: $debit->id,
            description: $description,
            recordSummary: $log->vehicle_no,
            changes: count($changes) > 0 ? $changes : null,
            user: Auth::user()
        );
    }

    private function generateTraceCode(int $id): string
    {
        return 'TL-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    private function clearDashboardCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('dashboard.aggregates');
        \Illuminate\Support\Facades\Cache::forget('dashboard.totalFuelStationDues');
        \Illuminate\Support\Facades\Cache::forget('dashboard.totalMotorPartsDues');
        \Illuminate\Support\Facades\Cache::forget('dashboard.monthlyStaffSalaryPaid');
        \Illuminate\Support\Facades\Cache::forget('dashboard.monthlyCompanyExpenses');
    }
}
