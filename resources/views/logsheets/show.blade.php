@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Logsheet Detail</h1>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $logsheet->log_sheet_no }}</div>
        </div>
        <span class="rounded-full @if($logsheet->status === 'cleared') bg-emerald-100 text-emerald-800 @else bg-amber-100 text-amber-800 @endif px-3 py-1 font-semibold text-xs">
            {{ ucfirst($logsheet->status) }}
        </span>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="font-semibold">Date:</span> {{ $logsheet->date?->format('Y-m-d') }}</div>
            <div><span class="font-semibold">Vehicle:</span> {{ $logsheet->vehicle_no }}</div>
            <div><span class="font-semibold">Tprt Code:</span> {{ $logsheet->tprt_code }}</div>
            <div><span class="font-semibold">Tprt Name:</span> {{ $logsheet->tprt_name }}</div>
            <div><span class="font-semibold">Destination:</span> {{ $logsheet->destination }}</div>
            <div><span class="font-semibold">SAP Invoice No:</span> {{ $logsheet->sap_invoice_no }}</div>
            <div><span class="font-semibold">Posting:</span> {{ $logsheet->posting_date?->format('Y-m-d') }}</div>
            <div><span class="font-semibold">Bill:</span> {{ $logsheet->bill_date?->format('Y-m-d') }}</div>
            <div><span class="font-semibold">Vendor Inv No:</span> {{ $logsheet->vendor_inv_no }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b px-4 py-3 font-semibold">Raw Consignment Rows</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-950">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Row</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Invoice No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Inv-Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Payer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Payer Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Town</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold">Volume</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($rawRows as $raw)
                        <tr>
                            <td class="px-4 py-3">{{ $raw->row_number_in_file }}</td>
                            <td class="px-4 py-3">{{ data_get($raw->raw_data, 'invoice_no') }}</td>
                            <td class="px-4 py-3">{{ data_get($raw->raw_data, 'inv_date') }}</td>
                            <td class="px-4 py-3">{{ data_get($raw->raw_data, 'payer') }}</td>
                            <td class="px-4 py-3">{{ data_get($raw->raw_data, 'payer_name') }}</td>
                            <td class="px-4 py-3">{{ data_get($raw->raw_data, 'town') }}</td>
                            <td class="px-4 py-3">{{ data_get($raw->raw_data, 'volume') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-500">No raw rows available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
