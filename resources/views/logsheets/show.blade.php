@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Logsheet Detail</h1>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $logsheet->log_sheet_no }}
                @if($logsheet->fully_out_of_requested_range)
                    <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                        Fully Out of Range
                    </span>
                @endif
            </div>
        </div>
        <span class="rounded-full @if($logsheet->status === 'cleared') bg-emerald-100 text-emerald-800 @else bg-amber-100 text-amber-800 @endif px-3 py-1 font-semibold text-xs">
            {{ ucfirst($logsheet->status) }}
        </span>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <h3 class="mb-4 font-semibold text-zinc-900 dark:text-zinc-100">Consolidated Summary</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4 text-sm">
            <div><span class="font-semibold">Date:</span> {{ $logsheet->date?->format('Y-m-d') }}</div>
            <div><span class="font-semibold">Vehicle:</span> {{ $logsheet->vehicle_no }}</div>
            <div><span class="font-semibold">TPRT Code:</span> {{ $logsheet->tprt_code }}</div>
            <div><span class="font-semibold">TPRT Name:</span> {{ $logsheet->tprt_name }}</div>
            <div><span class="font-semibold">Destination:</span> {{ $logsheet->destination }}</div>
            <div><span class="font-semibold">SAP Invoice No:</span> {{ $logsheet->sap_invoice_no }}</div>
            <div><span class="font-semibold">Posting:</span> {{ $logsheet->posting_date?->format('Y-m-d') }}</div>
            <div><span class="font-semibold">Bill:</span> {{ $logsheet->bill_date?->format('Y-m-d') }}</div>
            <div><span class="font-semibold">Vendor Inv No:</span> {{ $logsheet->vendor_inv_no }}</div>
            <div><span class="font-semibold">Gross Wt:</span> {{ number_format($logsheet->total_gross_wt, 3) }}</div>
            <div><span class="font-semibold">Booked:</span> {{ number_format($logsheet->total_booked_amount, 2) }}</div>
            <div><span class="font-semibold">Actual:</span> {{ number_format($logsheet->total_actual_amount, 2) }}</div>
            <div><span class="font-semibold">Diff:</span> {{ number_format($logsheet->total_diff, 2) }}</div>
            <div><span class="font-semibold">Consignments:</span> {{ $logsheet->consignment_count }}</div>
            <div><span class="font-semibold">Cleared At:</span> {{ $logsheet->cleared_at?->format('Y-m-d H:i') ?? '—' }}</div>
            <div><span class="font-semibold">Cleared By:</span> {{ $logsheet->clearer?->name ?? '—' }}</div>
            <div><span class="font-semibold">Last Import:</span> {{ $logsheet->lastImport?->original_filename ?? '—' }}</div>
        </div>
    </div>

    @if($logsheet->details->count() > 0)
    @php
        // Compute union of all extra_fields keys across all detail rows for this logsheet
        $extraFieldKeys = [];
        foreach ($logsheet->details as $detail) {
            if (!empty($detail->extra_fields) && is_array($detail->extra_fields)) {
                foreach (array_keys($detail->extra_fields) as $key) {
                    if (!in_array($key, $extraFieldKeys, true)) {
                        $extraFieldKeys[] = $key;
                    }
                }
            }
        }
    @endphp
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b px-4 py-3 font-semibold">Consignment Details ({{ $logsheet->details->count() }} rows)</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-950">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Invoice No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Inv Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Payer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Payer Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Town</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Town 2</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Gross Wt</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Diff</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Volume</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">TPRT Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">TPRT Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Container</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Destination</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">SAP Inv No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Posting</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Bill</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Vendor Inv</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Route</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Gross Wt 2</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Booked Amt</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Actual Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Actual Amt</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Diff</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Cust Group</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Packs</th>
                        @foreach($extraFieldKeys as $key)
                            <th class="px-4 py-3 text-left text-xs font-semibold">{{ $key }}</th>
                        @endforeach
                        <th class="px-4 py-3 text-center text-xs font-semibold">Cleared</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($logsheet->details as $detail)
                        <tr class="@if($detail->cleared) bg-emerald-50 dark:bg-emerald-900/20 @endif">
                            <td class="px-4 py-2">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2">{{ $detail->date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2">{{ $detail->invoice_no }}</td>
                            <td class="px-4 py-2">{{ $detail->inv_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2">{{ $detail->payer }}</td>
                            <td class="px-4 py-2">{{ $detail->payer_name }}</td>
                            <td class="px-4 py-2">{{ $detail->town }}</td>
                            <td class="px-4 py-2">{{ $detail->town_2 }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($detail->gross_wt ?? 0, 3) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($detail->difference ?? 0, 3) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($detail->amount ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($detail->volume ?? 0, 3) }}</td>
                            <td class="px-4 py-2">{{ $detail->tprt_code }}</td>
                            <td class="px-4 py-2">{{ $detail->tprt_name }}</td>
                            <td class="px-4 py-2">{{ $detail->container_id }}</td>
                            <td class="px-4 py-2">{{ $detail->destination }}</td>
                            <td class="px-4 py-2">{{ $detail->sap_invoice_no }}</td>
                            <td class="px-4 py-2">{{ $detail->posting_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2">{{ $detail->bill_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2">{{ $detail->vendor_inv_no }}</td>
                            <td class="px-4 py-2">{{ $detail->route }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($detail->gross_weight_2 ?? 0, 3) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($detail->booked_amount ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($detail->actual_rate ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($detail->actual_amount ?? 0, 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono {{ $detail->diff > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($detail->diff ?? 0, 2) }}</td>
                            <td class="px-4 py-2">{{ $detail->time }}</td>
                            <td class="px-4 py-2">{{ $detail->cust_group }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ $detail->no_of_packs }}</td>
                            @foreach($extraFieldKeys as $key)
                                <td class="px-4 py-2">{{ $detail->extra_fields[$key] ?? '—' }}</td>
                            @endforeach
                            <td class="px-4 py-2 text-center">
                                @if($detail->cleared)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="h-1 w-1 rounded-full bg-emerald-600"></span>
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                        <span class="h-1 w-1 rounded-full bg-amber-600"></span>
                                        No
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b px-4 py-3 font-semibold">Raw Imported Rows ({{ $rawRows->count() }} rows)</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-950">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Row #</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Log Sheet No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Invoice No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Inv Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Payer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Payer Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Town</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Town 2</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Gross Wt</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Diff</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Volume</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">TPRT Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">TPRT Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Container</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Destination</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">SAP Inv No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Posting</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Bill</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Vendor Inv</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Route</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Booked Amt</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Actual Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Actual Amt</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Diff</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Cust Group</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold">Packs</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold">Valid</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($rawRows as $raw)
                        @php
                            // Helper: safely get numeric value from raw_data JSON (strings -> float, blank -> 0)
                            $n = fn($key, $default = 0) => (float) (data_get($raw->raw_data, $key) ?? $default);
                        @endphp
                        <tr class="@if(!$raw->is_valid) bg-red-50 dark:bg-red-900/20 @endif">
                            <td class="px-4 py-2">{{ $raw->row_number_in_file }}</td>
                            <td class="px-4 py-2">{{ $raw->log_sheet_no }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'date') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'invoice_no') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'inv_date') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'payer') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'payer_name') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'town') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'town_2') }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($n('gross_wt'), 3) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($n('diff'), 3) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($n('amount'), 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($n('volume'), 3) }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'tprt_code') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'tprt_name') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'container_id') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'destination') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'sap_invoice_no') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'posting_date') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'bill_date') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'vendor_inv_no') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'route') }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($n('booked_amount'), 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($n('actual_rate'), 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($n('actual_amount'), 2) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($n('diff'), 2) }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'time') }}</td>
                            <td class="px-4 py-2">{{ data_get($raw->raw_data, 'cust_group') }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ $n('no_of_packs') ?: '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($raw->is_valid)
                                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500" title="Valid"></span>
                                @else
                                    <span class="inline-flex h-2 w-2 rounded-full bg-red-500" title="Invalid"></span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-red-600 dark:text-red-400">{{ $raw->validation_error }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="32" class="px-4 py-8 text-center text-zinc-500">No raw rows available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection