@extends('layouts.app')

@section('title', 'Import Details · Transport')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Import Details</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">View consolidated log sheets and invalid rows for this import.</p>
        </div>
        <a href="{{ route('logsheets.index') }}" class="inline-flex items-center gap-2 text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            ← Back to Imports
        </a>
    </div>

    <!-- Header Card -->
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $import->original_filename }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    Period: {{ $import->date_from?->format('Y-m-d') }} to {{ $import->date_to?->format('Y-m-d') }}
                </p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Uploaded by: {{ $import->uploader?->name ?? '—' }} · {{ $import->created_at?->format('Y-m-d H:i') }}
                </p>
            </div>
            <div class="text-right sm:text-left">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total Amount</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">₹{{ number_format($import->total_amount, 2) }}</p>
            </div>
            <div class="text-right sm:text-left">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Booked</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">₹{{ number_format($import->total_booked_amount, 2) }}</p>
            </div>
            <div class="text-right sm:text-left">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Diff</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight {{ $import->total_diff > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">₹{{ number_format($import->total_diff, 2) }}</p>
            </div>
            <div class="text-right sm:text-left">
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Gross Wt</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ number_format($import->total_gross_wt, 3) }}</p>
            </div>
        </div>
        <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-800 grid gap-4 sm:grid-cols-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Log Sheets</p>
                <p class="mt-1 text-xl font-semibold text-zinc-950 dark:text-zinc-50">{{ $totalCount }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Cleared</p>
                <p class="mt-1 text-xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $clearedCount }} of {{ $totalCount }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Out-of-Range Rows</p>
                <p class="mt-1 text-xl font-semibold text-amber-600 dark:text-amber-400">{{ $import->out_of_range_rows }}</p>
            </div>
        </div>
    </div>

    <!-- Log Sheets Table -->
    <div class="rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Log Sheets in this Import ({{ $logsheets->count() }})</h3>
        </div>
        @if($logsheets->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                            <th class="px-6 py-3 text-left whitespace-nowrap">Log Sheet No</th>
                            <th class="px-6 py-3 text-right whitespace-nowrap">Amount</th>
                            <th class="px-6 py-3 text-right whitespace-nowrap">Booked</th>
                            <th class="px-6 py-3 text-right whitespace-nowrap">Diff</th>
                            <th class="px-6 py-3 text-right whitespace-nowrap">Gross Wt</th>
                            <th class="px-6 py-3 text-center whitespace-nowrap">Consignments</th>
                            <th class="px-6 py-3 text-center whitespace-nowrap">Status</th>
                            <th class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach($logsheets as $logsheet)
                            <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <a href="{{ route('logsheets.show', $logsheet) }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $logsheet->log_sheet_no }}</a>
                                </td>
                                <td class="px-6 py-3 text-right font-mono whitespace-nowrap">{{ number_format($logsheet->total_actual_amount, 2) }}</td>
                                <td class="px-6 py-3 text-right font-mono whitespace-nowrap">{{ number_format($logsheet->total_booked_amount, 2) }}</td>
                                <td class="px-6 py-3 text-right font-mono {{ $logsheet->total_diff > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }} whitespace-nowrap">{{ number_format($logsheet->total_diff, 2) }}</td>
                                <td class="px-6 py-3 text-right font-mono whitespace-nowrap">{{ number_format($logsheet->total_gross_wt, 3) }}</td>
                                <td class="px-6 py-3 text-center font-mono whitespace-nowrap">{{ $logsheet->consignment_count }}</td>
                                <td class="px-6 py-3 text-center whitespace-nowrap">
                                    @if($logsheet->status === 'cleared')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            Cleared
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('logsheets.show', $logsheet) }}" class="text-brand-600 hover:underline dark:text-brand-500">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No log sheets in this import.</p>
            </div>
        @endif
    </div>

    <!-- Invalid Rows Section -->
    <div class="rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Invalid Rows ({{ $invalidRows->count() }})</h3>
            @if($invalidRows->count() > 10)
                <button type="button" class="inline-flex items-center gap-1 text-sm text-brand-600 hover:underline dark:text-brand-400" x-data="{ show: false }" @click="show = !show">
                    <span x-show="!show">Show all ({{ $invalidRows->count() }} rows)</span>
                    <span x-show="show">Show less</span>
                    <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': show }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            @endif
        </div>
        @if($invalidRows->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                            <th class="px-6 py-3 text-left whitespace-nowrap">Row #</th>
                            <th class="px-6 py-3 text-left whitespace-nowrap">Log Sheet No</th>
                            <th class="px-6 py-3 text-left whitespace-nowrap">Error</th>
                            <th class="px-6 py-3 text-left whitespace-nowrap">Raw Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach($invalidRows as $index => $row)
                            <tr class="bg-red-50 dark:bg-red-900/20" x-show="$index < 10 || show" x-data="{ show: @js($invalidRows->count() <= 10) }">
                                <td class="px-6 py-3 whitespace-nowrap">{{ $row->row_number_in_file }}</td>
                                <td class="px-6 py-3 whitespace-nowrap">{{ $row->log_sheet_no ?? '—' }}</td>
                                <td class="px-6 py-3 text-red-600 dark:text-red-400 whitespace-nowrap">{{ $row->validation_error }}</td>
                                <td class="px-6 py-3 max-w-xs">
                                    <pre class="text-xs text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap overflow-auto max-h-24">{{ json_encode($row->raw_data, JSON_PRETTY_PRINT) }}</pre>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-emerald-300 dark:text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No invalid rows in this import.</p>
            </div>
        @endif
    </div>
</div>
@endsection