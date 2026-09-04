@extends('layouts.app')

@section('title', 'Trace ' . $log->trace_code . ' · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Trace Lookup' => '#']" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Transport Log Trace</h1>
                @if($isConsistent)
                    <x-ui.badge variant="success" dot>Consistent</x-ui.badge>
                @else
                    <x-ui.badge variant="danger" dot>Discrepancy Found</x-ui.badge>
                @endif
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $log->vehicle_no }} · {{ $log->date->format('Y-m-d') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <button
                x-data="{ copied: false }"
                @click="navigator.clipboard.writeText('{{ $log->trace_code }}'); copied = true; setTimeout(() => copied = false, 1500)"
                class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50"
                title="Copy trace code"
            >
                <svg x-show="!copied" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 17.25v3.75a1.5 1.5 0 01-1.5 1.5h-9a1.5 1.5 0 01-1.5-1.5v-9a1.5 1.5 0 011.5-1.5h3.75m6.75 6.75h-9a1.5 1.5 0 01-1.5-1.5v-9a1.5 1.5 0 011.5-1.5h9a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5z"/></svg>
                <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6 6 9-13.5"/></svg>
                <span x-text="copied ? 'Copied!' : 'Copy Trace Code'"></span>
            </button>
            <a href="{{ route('transport-logs.show', $log) }}" class="inline-flex h-8 items-center justify-center rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                View Full Log
            </a>
            <a href="{{ route('transport-logs.edit', $log) }}" class="inline-flex h-8 items-center justify-center rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                Edit Log
            </a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Trace Code</p>
            <p class="mt-1 text-sm font-mono font-medium text-zinc-900 dark:text-zinc-100">{{ $log->trace_code }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Log Date</p>
            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->date->format('Y-m-d') }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Status</p>
            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->statusBadge() }}</p>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50 mb-4">Log Details</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Vehicle</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->vehicle->registration_no ?? $log->vehicle_no }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Company</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->company->name ?? $log->company }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Carrier</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->carrier->name ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Branch</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->branch->name ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Created By</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->creator->name ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Last Updated By</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->editor->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50 mb-4">Financials</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Sale</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($log->total_sale, 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Expense</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($log->total_expense, 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Profit</p>
                <p class="mt-1 text-sm font-medium {{ $log->profitColorClass() }}">{{ number_format($log->profit, 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Diesel Advance</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($log->diesel_advance, 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Fuel Paid Amount</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($log->fuel_paid_amount, 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Balance Vehicle Payment</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($log->balance_vehicle_payment, 2) }}</p>
            </div>
        </div>
    </div>

    @if($fuelStationAccount)
        <div class="mt-8">
            <h2 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50 mb-4">Fuel Station Reconciliation</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Station Name</p>
                    <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $fuelStationAccount->name }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Current Balance</p>
                    <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($fuelStationAccount->current_balance, 2) }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Paid for This Log</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($log->fuel_paid_amount, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50 mb-4">Payment History</h2>
            @if($fuelPaymentHistory->count() > 0)
                <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3">Payment Mode</th>
                                <th class="px-4 py-3">Plan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach($fuelPaymentHistory as $txn)
                                <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                                    <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                        {{ $txn->transaction_date?->format('Y-m-d') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                        +{{ number_format($txn->amount, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs capitalize whitespace-nowrap">
                                        {{ $txn->payment_mode ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs capitalize whitespace-nowrap">
                                        {{ $txn->payment_plan ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-xl border border-zinc-200 bg-white p-10 text-center shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No payments recorded yet for this log.</p>
                </div>
            @endif
        </div>
    @endif
@endsection
