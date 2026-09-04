<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\StoreAccountTransactionRequest;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Services\AccountLedgerService;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountTransactionController extends Controller
{
    public function __construct(
        private AccountLedgerService $ledger
    ) {
        $this->authorizeResource(AccountTransaction::class, 'transaction');
    }

    public function store(StoreAccountTransactionRequest $request, Account $account): RedirectResponse
    {
        try {
            $transaction = DB::transaction(function () use ($request, $account) {
                $data = $request->validated();
                $data['created_by'] = $request->user()->id;
                $data['updated_by'] = $request->user()->id;

                return $this->ledger->createTransaction($account, $data);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'system' => 'Failed to create transaction. Please try again.',
            ]);
        }

        ActivityLogger::crud(
            action: 'create',
            module: 'account_transactions',
            recordId: $transaction->id,
            summary: $transaction->direction . ' ' . $transaction->amount . ' for ' . $account->name,
            user: $request->user(),
            request: $request
        );

        return redirect()
            ->route('accounts.show', $account)
            ->with('success', 'Transaction created successfully.');
    }

    public function edit(Account $account, AccountTransaction $transaction): View
    {
        return view('accounts.transactions.edit', compact('account', 'transaction'));
    }

    public function update(StoreAccountTransactionRequest $request, Account $account, AccountTransaction $transaction): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $account, $transaction) {
                $oldDirection = $transaction->direction;
                $oldAmount = (float) $transaction->amount;

                $this->ledger->reverseTransaction($transaction);

                $data = $request->validated();
                $data['created_by'] = $transaction->created_by;
                $data['updated_by'] = $request->user()->id;

                $this->ledger->createTransaction($account, $data);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'system' => 'Failed to update transaction. Please try again.',
            ]);
        }

        ActivityLogger::crud(
            action: 'update',
            module: 'account_transactions',
            recordId: $transaction->id,
            summary: $transaction->direction . ' ' . $transaction->amount . ' for ' . $account->name,
            user: $request->user(),
            request: $request
        );

        return redirect()
            ->route('accounts.show', $account)
            ->with('success', 'Transaction updated successfully.');
    }

    public function destroy(Request $request, Account $account, AccountTransaction $transaction): RedirectResponse
    {
        try {
            $this->ledger->deleteTransaction($transaction);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'system' => 'Failed to delete transaction. Please try again.',
            ]);
        }

        ActivityLogger::crud(
            action: 'delete',
            module: 'account_transactions',
            recordId: $transaction->id,
            summary: $transaction->direction . ' ' . $transaction->amount . ' for ' . $account->name,
            user: $request->user(),
            request: $request
        );

        return redirect()
            ->route('accounts.show', $account)
            ->with('success', 'Transaction deleted successfully.');
    }
}
