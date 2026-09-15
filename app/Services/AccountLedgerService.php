<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountLedgerService
{
    public function createTransaction(Account $account, array $data): AccountTransaction
    {
        $amount = (float) $data['amount'];

        return DB::transaction(function () use ($account, $data, $amount) {
            $lockedAccount = Account::whereKey($account->id)->lockForUpdate()->firstOrFail();

            $transaction = AccountTransaction::create([
                'account_id' => $lockedAccount->id,
                'branch_id' => $data['branch_id'] ?? $lockedAccount->branch_id,
                'direction' => $data['direction'],
                'amount' => $amount,
                'payment_mode' => $data['payment_mode'] ?? null,
                'payment_plan' => $data['payment_plan'] ?? null,
                'installment_no' => $data['installment_no'] ?? null,
                'installment_total' => $data['installment_total'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'attachment_path' => $data['attachment_path'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'running_balance' => 0,
                'created_by' => $data['created_by'] ?? null,
                'updated_by' => $data['updated_by'] ?? null,
            ]);

            $this->recalculateRunningBalances($lockedAccount);

            \Illuminate\Support\Facades\Cache::forget('dashboard.aggregates');
            \Illuminate\Support\Facades\Cache::forget('dashboard.totalFuelStationDues');
            \Illuminate\Support\Facades\Cache::forget('dashboard.totalMotorPartsDues');
            \Illuminate\Support\Facades\Cache::forget('dashboard.monthlyStaffSalaryPaid');
            \Illuminate\Support\Facades\Cache::forget('dashboard.monthlyCompanyExpenses');

            return $transaction->fresh();
        });
    }

    public function reverseTransaction(AccountTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $account = Account::whereKey($transaction->account_id)->lockForUpdate()->firstOrFail();
            $transaction->delete();

            $this->recalculateRunningBalances($account);
        });

        \Illuminate\Support\Facades\Cache::forget('dashboard.aggregates');
        \Illuminate\Support\Facades\Cache::forget('dashboard.totalFuelStationDues');
        \Illuminate\Support\Facades\Cache::forget('dashboard.totalMotorPartsDues');
        \Illuminate\Support\Facades\Cache::forget('dashboard.monthlyStaffSalaryPaid');
        \Illuminate\Support\Facades\Cache::forget('dashboard.monthlyCompanyExpenses');
    }

    public function deleteTransaction(AccountTransaction $transaction): void
    {
        $this->reverseTransaction($transaction);
    }

    public function getBalanceSummary(Account $account): array
    {
        $transactions = AccountTransaction::where('account_id', $account->id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $totalCredited = $transactions->where('direction', 'credit')->sum('amount');
        $totalDebited = $transactions->where('direction', 'debit')->sum('amount');
        $lastTransaction = $transactions->sortByDesc('transaction_date')->sortByDesc('id')->first();

        $balance = (float) $account->opening_balance;
        foreach ($transactions as $txn) {
            if ($txn->direction === 'debit') {
                $balance = round($balance + (float) $txn->amount, 2);
            } else {
                $balance = round(max(0, $balance - (float) $txn->amount), 2);
            }
        }

        return [
            'total_credited' => round((float) $totalCredited, 2),
            'total_debited' => round((float) $totalDebited, 2),
            'current_balance' => $balance,
            'last_transaction_date' => $lastTransaction?->transaction_date,
            'opening_balance' => round((float) $account->opening_balance, 2),
        ];
    }

    public function getFilteredTransactions(Account $account, array $filters)
    {
        $query = AccountTransaction::where('account_id', $account->id)
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '<=', $date))
            ->when($filters['direction'] ?? null, fn ($q, $direction) => $q->where('direction', $direction))
            ->when($filters['payment_mode'] ?? null, fn ($q, $mode) => $q->where('payment_mode', $mode))
            ->when($filters['payment_plan'] ?? null, fn ($q, $plan) => $q->where('payment_plan', $plan))
            ->when($filters['day'] ?? null, fn ($q, $day) => $q->whereDay('transaction_date', $day))
            ->when($filters['month'] ?? null, fn ($q, $month) => $q->whereMonth('transaction_date', $month))
            ->when($filters['year'] ?? null, fn ($q, $year) => $q->whereYear('transaction_date', $year));

        $allowedSorts = ['transaction_date', 'amount', 'running_balance'];
        $sort = $filters['sort'] ?? 'transaction_date';
        $direction = $filters['direction_sort'] ?? 'desc';

        if (! in_array($sort, $allowedSorts)) {
            $sort = 'transaction_date';
        }
        if (! in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction)->orderByDesc('id');

        return $query->paginate(15)->withQueryString();
    }

    public function recalculateRunningBalances(Account $account): void
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
}
