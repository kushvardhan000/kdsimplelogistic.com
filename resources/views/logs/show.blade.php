@extends('layouts.app')

@section('title', 'Transport Log · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Transport Logs' => route('transport-logs.index'), 'Detail' => '#']" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Transport Log #{{ $transportLog->id }}</h1>
                @if($transportLog->trace_code)
                    <code class="rounded bg-zinc-100 px-2 py-1 text-xs font-mono text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $transportLog->trace_code }}</code>
                    <button
                        x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText('{{ $transportLog->trace_code }}'); copied = true; setTimeout(() => copied = false, 1500)"
                        class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50"
                        title="Copy trace code"
                    >
                        <svg x-show="!copied" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 17.25v3.75a1.5 1.5 0 01-1.5 1.5h-9a1.5 1.5 0 01-1.5-1.5v-9a1.5 1.5 0 011.5-1.5h3.75m6.75 6.75h-9a1.5 1.5 0 01-1.5-1.5v-9a1.5 1.5 0 011.5-1.5h9a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5z"/></svg>
                        <svg x-show="copied" x-cloak class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                    </button>
                @endif
                @if($transportLog->statusBadgeVariant() === 'success')
                    <x-ui.badge variant="success" dot>Cleared</x-ui.badge>
                @else
                    <x-ui.badge variant="warning" dot>Pending</x-ui.badge>
                @endif
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $transportLog->vehicle_no }} · {{ $transportLog->date->format('Y-m-d') }}</p>
        </div>
        <div class="flex items-center gap-3">
            @can('view', $transportLog)
                <a href="{{ route('transport-logs.export.single', $transportLog) }}" class="inline-flex h-8 items-center justify-center rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                    Export to Excel
                </a>
            @endcan
            @can('update', $transportLog)
                <a href="{{ route('transport-logs.edit', $transportLog) }}" class="inline-flex h-8 items-center justify-center rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                    Edit
                </a>
            @endcan
            @can('delete', $transportLog)
                <x-ui.button
                    type="button"
                    variant="danger"
                    size="sm"
                    x-data
                    @click="$dispatch('open-modal', 'delete-transport-log')"
                >Delete</x-ui.button>
            @endcan
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            'Vehicle No' => $transportLog->vehicle_no,
            'Company' => $transportLog->company,
            'Transport Name' => $transportLog->transport_name,
            'Logsheet No' => $transportLog->logsheet_no,
            'Destination' => $transportLog->destination,
            'KM' => $transportLog->km,
            'Weight' => $transportLog->weight,
            'To BB Sale' => $transportLog->to_bb_sale,
            'Paid Sale' => $transportLog->paid_sale,
            'To Pay' => $transportLog->to_pay,
            'Total Sale' => $transportLog->total_sale,
            'Freight' => $transportLog->freight,
            'Loading' => $transportLog->loading,
            'Unloading' => $transportLog->unloading,
            'DD' => $transportLog->dd,
            'Tempu Expense' => $transportLog->tempu_expense,
            'Commission' => $transportLog->commission,
            'Total Expense' => $transportLog->total_expense,
            'Profit' => $transportLog->profit,
            'Diesel Advance' => $transportLog->diesel_advance,
            'Cash Advance' => $transportLog->cash_advance,
            'Total Advance' => $transportLog->total_advance,
            'Payment' => $transportLog->payment,
            'Fuel Station' => $transportLog->fuel_station_id
                ? ['html' => '<a href="' . route('accounts.index', ['type' => 'fuel_station', 'selected' => $transportLog->fuel_station_id]) . '" class="text-brand-600 hover:underline dark:text-brand-400">' . e($transportLog->fuel_station_name) . ' →</a>']
                : $transportLog->fuel_station_name,
            'Fuel Station Balance' => $transportLog->fuel_station_balance,
            'Balance Vehicle Payment' => $transportLog->balance_vehicle_payment,
            'Clearing Date' => $transportLog->clearing_date?->format('Y-m-d'),
            'Mileage' => $transportLog->mileage,
            'DTG Office Expense' => $transportLog->dtg_office_expense,
            'Detail' => $transportLog->detail,
            'Remarks' => $transportLog->remarks,
        ] as $label => $value)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $label }}</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    @if($label === 'Profit')
                        <span @class([
                            'text-emerald-600 dark:text-emerald-400' => ($value ?? 0) > 0,
                            'text-red-600 dark:text-red-400' => ($value ?? 0) < 0,
                            'text-zinc-600 dark:text-zinc-400' => ($value ?? 0) === 0,
                        ])>{{ number_format($value ?? 0, 2) }}</span>
                    @elseif(is_array($value) && isset($value['html']))
                        {!! $value['html'] !!}
                    @else
                        {{ $value ?? '—' }}
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    @php
        $breakdowns = [
            'Total Sale' => [
                ['To BB Sale', 'to_bb_sale'],
                ['Paid Sale', 'paid_sale'],
                ['To Pay', 'to_pay'],
                'total_sale',
            ],
            'Total Expense' => [
                ['Freight', 'freight'],
                ['Loading', 'loading'],
                ['Unloading', 'unloading'],
                ['DD', 'dd'],
                ['Tempu Expense', 'tempu_expense'],
                ['Commission', 'commission'],
                ['DTG Office Expense', 'dtg_office_expense'],
                'total_expense',
            ],
            'Profit' => [
                ['Total Sale', 'total_sale'],
                ['Total Expense', 'total_expense'],
                'profit',
            ],
            'Total Advance' => [
                ['Diesel Advance', 'diesel_advance'],
                ['Cash Advance', 'cash_advance'],
                'total_advance',
            ],
            'Balance Vehicle Payment' => [
                ['Total Sale', 'total_sale'],
                ['Payment', 'payment'],
                'balance_vehicle_payment',
            ],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($breakdowns as $label => $items)
            @php
                $totalField = null;
                $addends = [];
                foreach ($items as $item) {
                    if (is_array($item)) {
                        $addends[] = $item;
                    } else {
                        $totalField = $item;
                    }
                }
            @endphp
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $label }}</p>
                <div class="mt-3 space-y-2">
                    @foreach($addends as [$name, $field])
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $name }}</span>
                            <span class="text-zinc-900 dark:text-zinc-100 text-right tabular-nums">{{ number_format($transportLog->$field ?? 0, 2) }}</span>
                        </div>
                    @endforeach
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 mt-2 flex items-center justify-between text-sm font-semibold">
                        <span class="text-zinc-900 dark:text-zinc-100">{{ $label }}</span>
                        <span class="text-right tabular-nums">
                            @if($label === 'Profit')
                                <span @class([
                                    'text-emerald-600 dark:text-emerald-400' => ($transportLog->$totalField ?? 0) > 0,
                                    'text-red-600 dark:text-red-400' => ($transportLog->$totalField ?? 0) < 0,
                                    'text-zinc-600 dark:text-zinc-400' => ($transportLog->$totalField ?? 0) === 0,
                                ])>{{ number_format($transportLog->$totalField ?? 0, 2) }}</span>
                            @else
                                {{ number_format($transportLog->$totalField ?? 0, 2) }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @can('delete', $transportLog)
        <x-ui.modal id="delete-transport-log" title="Delete Transport Log" size="sm">
            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                Are you sure you want to delete transport log <span class="font-semibold text-zinc-900 dark:text-zinc-100">#{{ $transportLog->id }}</span>
                for vehicle <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $transportLog->vehicle_no }}</span>?
                This action cannot be undone.
            </p>

            <x-slot:footer>
                <x-ui.button type="button" variant="secondary" size="sm" @click="$dispatch('close-modal', 'delete-transport-log')">Cancel</x-ui.button>
                <form method="POST" action="{{ route('transport-logs.destroy', $transportLog) }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">Delete Log</x-ui.button>
                </form>
            </x-slot:footer>
        </x-ui.modal>
    @endcan

    @if($transportLog->fuel_station_id && (float) $transportLog->diesel_advance > 0)
        @php
            $statusVariant = match($fuelPaymentStatus) {
                'paid' => 'success',
                'partial' => 'warning',
                'overpaid' => 'danger',
                default => 'zinc',
            };
            $statusLabel = $fuelPaymentStatus ? ucfirst($fuelPaymentStatus) : 'Unpaid';
            $advance = (float) $transportLog->diesel_advance;
            $paid = (float) $transportLog->fuel_paid_amount;
            $remaining = max(0, $advance - $paid);
            $progressPercent = $advance > 0 ? min(100, round(($paid / $advance) * 100)) : 0;
        @endphp
        <div class="mt-8">
            <div class="flex items-center justify-between border-b border-zinc-200 pb-4 dark:border-zinc-800">
                <div>
                    <h2 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50">Payment History for This Log</h2>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Diesel advance of {{ number_format($advance, 2) }} for {{ $transportLog->fuel_station_name ?? 'Fuel Station #' . $transportLog->fuel_station_id }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui.badge :variant="$statusVariant" dot>{{ $statusLabel }}</x-ui.badge>
                    @can('create', \App\Models\AccountTransaction::class)
                        <button type="button" @click="$dispatch('open-modal', 'record-payment-modal-{{ $transportLog->id }}')" class="inline-flex h-8 items-center justify-center rounded-lg bg-brand-600 px-3 text-xs font-medium text-white shadow-premium-sm hover:bg-brand-700">
                            Record Payment
                        </button>
                    @endcan
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Diesel Advance</p>
                    <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($advance, 2) }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Paid Amount</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($paid, 2) }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Remaining Due</p>
                    <p class="mt-1 text-lg font-semibold {{ $remaining > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($remaining, 2) }}</p>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 mb-1">
                    <span>Payment Progress</span>
                    <span>{{ $progressPercent }}%</span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full rounded-full bg-emerald-500 dark:bg-emerald-400 transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-3">Transactions</h3>
                @if($fuelPaymentHistory->count() > 0)
                    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3">Payment Mode</th>
                                    <th class="px-4 py-3">Plan</th>
                                    <th class="px-4 py-3">Recorded By</th>
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
                                        <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300 whitespace-nowrap">
                                            {{ $txn->creator?->name ?? 'System' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="rounded-xl border border-zinc-200 bg-white p-10 text-center shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No payments recorded yet for this log.</p>
                    </div>
                @endif
            </div>
        </div>

        @if($transportLog->fuel_station_id && $fuelStationAccount ?? false)
            <x-accounts.transaction-form-modal
                id="record-payment-modal-{{ $transportLog->id }}"
                title="Record Payment — Log #{{ $transportLog->id }}"
                :account="$fuelStationAccount"
                :currentBalance="$fuelStationAccount->current_balance"
                :action="route('accounts.transactions.store', $fuelStationAccount)"
                :branches="\App\Models\Branch::all()"
            />
        @endif
    @endif
@endsection
