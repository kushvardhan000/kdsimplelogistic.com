@extends('layouts.app')

@section('title', 'Accounts Hub · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Accounts' => '#']" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Accounts Hub</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage ledgers across all account types.</p>
        </div>
        <a href="{{ route('accounts.create') }}" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">
            + New Account
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $types = [
                ['key' => 'fuel_station', 'label' => 'Fuel Station', 'icon' => 'fuel_station', 'color' => 'blue'],
                ['key' => 'motor_parts_shop', 'label' => 'Motor Parts Shop', 'icon' => 'motor_parts_shop', 'color' => 'orange'],
                ['key' => 'staff', 'label' => 'Staff Salary', 'icon' => 'staff', 'color' => 'violet'],
                ['key' => 'company_expense', 'label' => 'Company Expense', 'icon' => 'company_expense', 'color' => 'rose'],
            ];
        @endphp

        @foreach($types as $type)
            @php
                $count = $typeStats[$type['key']]['count'] ?? 0;
                $balance = $typeStats[$type['key']]['balance'] ?? 0;
                $activity = $typeStats[$type['key']]['activity'] ?? 0;
            @endphp
            <a href="{{ route('accounts.index', ['type' => $type['key']]) }}"
               class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-5 shadow-premium-sm transition-all hover:shadow-premium dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-accounts.account-type-icon :type="$type['icon']" size="lg" />
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $type['label'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $count }} account{{ $count === 1 ? '' : 's' }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 space-y-1">
                    <p class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">
                        {{ number_format($balance, 2) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $activity }} transaction{{ $activity === 1 ? '' : 's' }} this month
                    </p>
                </div>
                <div class="absolute inset-x-0 bottom-0 h-1 {{ $type['color'] === 'blue' ? 'bg-blue-500' : ($type['color'] === 'orange' ? 'bg-orange-500' : ($type['color'] === 'violet' ? 'bg-violet-500' : 'bg-rose-500')) }} opacity-0 transition-opacity group-hover:opacity-100"></div>
            </a>
        @endforeach
    </div>

    <div class="mt-8">
        @if($grouped->isEmpty() && $recentTransactions->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-10 text-center shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No accounts yet. Create your first account to get started.</p>
                <a href="{{ route('accounts.create') }}" class="mt-4 inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">
                    + New Account
                </a>
            </div>
        @else
            <div class="flex items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-800">
                <h2 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50">Recent Transactions Across All Accounts</h2>
                <a href="{{ route('accounts.index') }}" class="text-xs font-medium text-brand-600 dark:text-brand-500 hover:underline">View all</a>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                            <th class="px-4 py-3">Account</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Direction</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse($recentTransactions as $txn)
                            @php
                                $typeLabels = [
                                    'fuel_station' => 'Fuel Station',
                                    'motor_parts_shop' => 'Motor Parts',
                                    'staff' => 'Staff',
                                    'company_expense' => 'Company Expense',
                                ];
                            @endphp
                            <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                                <td class="px-4 py-3">
                                    <a href="{{ route('accounts.show', $txn->account) }}" class="font-medium text-brand-600 hover:underline dark:text-brand-500">
                                        {{ $txn->account->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <x-accounts.account-type-icon :type="$txn->account->type" size="sm" />
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $typeLabels[$txn->account->type] ?? $txn->account->type }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($txn->direction === 'credit')
                                        <x-ui.badge variant="success" dot>Credit</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="danger" dot>Debit</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-medium {{ $txn->direction === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $txn->direction === 'credit' ? '+' : '-' }}{{ number_format($txn->amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                    {{ $txn->transaction_date?->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                                    {{ number_format($txn->running_balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
