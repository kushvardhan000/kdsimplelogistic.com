<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\StationDebit;
use App\Models\TransportLog;
use App\Services\AccountLedgerService;
use App\Services\ActivityLogger;
use App\Services\FuelSettlementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransportLogObserver
{
    private static array $pendingReassignments = [];

    public function updating(TransportLog $log): void
    {
        if (! $log->exists || ! $log->isDirty('fuel_station_id')) {
            return;
        }

        self::$pendingReassignments[$log->id] = $log->getOriginal('fuel_station_id');

        $oldStationId = $log->getOriginal('fuel_station_id');
        $newStationId = $log->fuel_station_id;

        $this->handleStationReassignment($log, $oldStationId, $newStationId);
    }

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
        $oldStationId = self::$pendingReassignments[$log->id] ?? null;
        unset(self::$pendingReassignments[$log->id]);

        if ($oldStationId === null || $oldStationId === $log->fuel_station_id) {
            $this->mirrorToAccountTransactions($log);
        }

        $this->recalculateSettlement($log);
    }

    public function deleted(TransportLog $log): void
    {
        // On soft delete, do NOT delete the mirrored debit transaction.
        // The debit and credit transactions are historical ledger entries that must be preserved.
        // Only the fuel settlement cached fields on the log are affected (handled by restored event).
        // StationDebit is also preserved for backward compatibility.
    }

    public function forceDeleted(TransportLog $log): void
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
            ->where('account_id', $account->id)
            ->whereNull('deleted_at')
            ->first();

        $ledgerService = app(AccountLedgerService::class);

        if ($verb === 'delete' || $log->diesel_advance <= 0) {
            if ($existing) {
                $ledgerService->reverseTransaction($existing);
            }
            return;
        }

        $amount = (float) $log->diesel_advance;

        if ($existing) {
            $oldAmount = (float) $existing->amount;
            if ($oldAmount !== $amount) {
                $lockedAccount = Account::whereKey($account->id)->lockForUpdate()->firstOrFail();
                $existing->update([
                    'amount' => $amount,
                    'description' => 'Diesel advance for ' . $log->date,
                    'updated_by' => $userId,
                ]);
                $ledgerService->recalculateRunningBalances($lockedAccount);
            }
        } else {
            $ledgerService->createTransaction($account, [
                'branch_id' => $log->branch_id,
                'direction' => 'debit',
                'amount' => $amount,
                'payment_mode' => null,
                'payment_plan' => null,
                'reference_type' => TransportLog::class,
                'reference_id' => $log->id,
                'description' => 'Diesel advance for ' . $log->date,
                'transaction_date' => $log->date,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    protected function handleStationReassignment(TransportLog $log, ?int $oldStationId = null, ?int $newStationId = null): void
    {
        $oldStationId = $oldStationId ?? ($log->_oldFuelStationId ?? $log->getOriginal('fuel_station_id'));
        $newStationId = $newStationId ?? $log->fuel_station_id;
        $amount = (float) ($log->diesel_advance ?? 0);
        $userId = Auth::id() ?? ($log->updated_by ?? $log->created_by ?? null);

        DB::transaction(function () use ($log, $oldStationId, $newStationId, $amount, $userId) {
            $ledgerService = app(AccountLedgerService::class);

            $oldAccount = $oldStationId
                ? Account::where('type', 'fuel_station')
                    ->where('linked_fuel_station_id', $oldStationId)
                    ->first()
                : null;

            if ($oldAccount) {
                $oldDebit = AccountTransaction::where('reference_type', TransportLog::class)
                    ->where('reference_id', $log->id)
                    ->where('direction', 'debit')
                    ->where('account_id', $oldAccount->id)
                    ->whereNull('deleted_at')
                    ->first();

                if ($oldDebit) {
                    $lockedOldAccount = Account::whereKey($oldAccount->id)->lockForUpdate()->firstOrFail();
                    $ledgerService->reverseTransaction($oldDebit);
                    $ledgerService->recalculateRunningBalances($lockedOldAccount);
                }

                $payments = AccountTransaction::where('reference_type', TransportLog::class)
                    ->where('reference_id', $log->id)
                    ->where('direction', 'credit')
                    ->where('account_id', $oldAccount->id)
                    ->whereNull('deleted_at')
                    ->get();

                if ($payments->isNotEmpty()) {
                    $paymentCount = $payments->count();
                    $totalPaid = round((float) $payments->sum('amount'), 2);

                    foreach ($payments as $payment) {
                        $lockedPaymentAccount = Account::whereKey($payment->account_id)->lockForUpdate()->firstOrFail();
                        $ledgerService->reverseTransaction($payment);
                        $ledgerService->recalculateRunningBalances($lockedPaymentAccount);
                    }

                    $oldStationName = $oldAccount->name ?? ('Fuel Station #' . $oldStationId);
                    ActivityLogger::log(
                        action: 'reassign_station',
                        module: 'transport_logs',
                        recordId: $log->id,
                        description: "Reassigning transport log #{$log->id} from fuel station #{$oldStationId} ({$oldStationName}) to fuel station #" . ($newStationId ?? 'cleared') . ". Reversed {$paymentCount} existing payment(s) totaling ₹" . number_format($totalPaid, 2) . " previously recorded against the old station. Those credits must be re-recorded on the new station if applicable.",
                        recordSummary: $log->vehicle_no,
                        changes: [
                            'fuel_station_id' => ['old' => $oldStationId, 'new' => $newStationId],
                            'reversed_payments' => $paymentCount,
                            'reversed_amount' => $totalPaid,
                        ],
                        user: Auth::user()
                    );
                }
            }

            $log->forceFill([
                'fuel_paid_amount' => 0,
                'fuel_payment_status' => null,
            ]);

            if ($newStationId) {
                $newAccount = Account::where('type', 'fuel_station')
                    ->where('linked_fuel_station_id', $newStationId)
                    ->first();

                if (! $newAccount) {
                    $newAccount = Account::create([
                        'type' => 'fuel_station',
                        'name' => $log->fuelStation->name ?? ('Fuel Station #' . $newStationId),
                        'linked_fuel_station_id' => $newStationId,
                        'branch_id' => $log->branch_id,
                        'opening_balance' => 0,
                        'current_balance' => 0,
                        'is_active' => true,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }

                if ($amount > 0) {
                    $ledgerService->createTransaction($newAccount, [
                        'branch_id' => $log->branch_id,
                        'direction' => 'debit',
                        'amount' => $amount,
                        'payment_mode' => null,
                        'payment_plan' => null,
                        'reference_type' => TransportLog::class,
                        'reference_id' => $log->id,
                        'description' => 'Diesel advance for ' . $log->date,
                        'transaction_date' => $log->date,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }

            app(FuelSettlementService::class)->recalculateForLog($log);
        });
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
