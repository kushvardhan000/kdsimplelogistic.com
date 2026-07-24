@extends('layouts.app')

@section('title', 'Transport Log · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Transport Logs' => route('transport-logs.index'), 'Detail' => '#']" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Transport Log #{{ $transportLog->id }}</h1>
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
            'Fuel Station' => $transportLog->fuel_station_name,
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
@endsection
