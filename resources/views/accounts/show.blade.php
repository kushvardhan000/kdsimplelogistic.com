@extends('layouts.app')

@section('title', $account->name . ' · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Accounts' => route('accounts.index'), $account->name => '#']" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div class="flex items-center gap-4">
            <x-accounts.account-type-icon :type="$account->type" size="lg" />
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ $account->name }}</h1>
                <div class="mt-1 flex items-center gap-2">
                    <x-ui.badge :variant="$account->is_active ? 'success' : 'danger'" dot>
                        {{ $account->is_active ? 'Active' : 'Inactive' }}
                    </x-ui.badge>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucwords(str_replace('_', ' ', $account->type)) }}</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('accounts.edit', $account) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                Edit
            </a>
            <button type="button" @click="$dispatch('open-modal', 'transaction-modal')" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">
                + Add Transaction
            </button>
        </div>
    </div>

    @if($account->contact_info || $account->address || ($account->type === 'staff' && ($account->aadhar_no || $account->driving_license_no)))
        <div class="mt-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap gap-4 text-sm">
                @if($account->contact_info)
                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                        <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ $account->contact_info }}
                    </div>
                @endif
                @if($account->address)
                    <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                        <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $account->address }}
                    </div>
                @endif
                @if($account->type === 'staff')
                    @if($account->aadhar_no)
                        @php
                            $aadhar = preg_replace('/\s/', '', $account->aadhar_no);
                            $masked = 'XXXX XXXX ' . substr($aadhar, -4);
                        @endphp
                        <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                            <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-9 0V4m9 2v2m-9 2v2"/></svg>
                            Aadhar: <span class="font-mono">{{ $masked }}</span>
                        </div>
                    @endif
                    @if($account->driving_license_no)
                        <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-300">
                            <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            License: {{ $account->driving_license_no }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif

    <div class="mt-6">
        <x-accounts.balance-summary-card
            :currentBalance="$summary['current_balance']"
            :totalCredited="$summary['total_credited']"
            :totalDebited="$summary['total_debited']"
            :lastTransactionDate="$summary['last_transaction_date']"
            :openingBalance="$summary['opening_balance']"
        />
    </div>

    <div class="mt-6">
        <x-accounts.filters-bar :action="request()->url()" preserve="type" />
    </div>

    <script>
        document.addEventListener('ledger-filters-changed', (e) => {
            window.location.href = e.detail.url;
        });
    </script>

    <div class="mt-6">
        <x-accounts.ledger-table
            :transactions="$transactions"
            :account="$account"
            :sort="$filters['sort'] ?? 'transaction_date'"
            :direction-sort="$filters['direction_sort'] ?? 'desc'"
        />
    </div>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>

    <x-accounts.transaction-form-modal
        id="transaction-modal"
        title="Add Transaction"
        :account="$account"
        :currentBalance="$account->current_balance"
        :action="route('accounts.transactions.store', $account)"
        :branches="$branches"
    />
@endsection
