@extends('layouts.app')

@section('title', 'Logsheet Imports · Transport')

@section('content')
<x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Logsheet Imports' => '#']" />

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Logsheet Imports</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Upload and consolidate transport logsheet Excel files.</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total Imports</p>
            <p class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ $totalImports }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Pending</p>
            <p class="mt-1 text-2xl font-semibold tracking-tight text-amber-600 dark:text-amber-400">{{ $pendingCount }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Cleared</p>
            <p class="mt-1 text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">{{ $clearedCount }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Total Gross Weight</p>
            <p class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ number_format($totalGrossWt, 3) }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('logsheets.index') }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 sm:grid-cols-3 items-end">
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-brand-700">Filter</button>
                <a href="{{ route('logsheets.index') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-300 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Clear</a>
            </div>
        </div>
    </form>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="mb-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">Upload Excel File</h2>
        <form method="POST" action="{{ route('logsheets.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
            @csrf
            <div class="flex flex-col items-center justify-center gap-4 sm:flex-row sm:items-center">
                <div class="flex h-12 items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 px-6 py-3 dark:border-zinc-700">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-600 hover:text-brand-600 dark:text-zinc-400 dark:hover:text-brand-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        {{ old('file', $logsheets->lastImport?->original_filename ?? 'Choose a file') }}
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="sr-only">
                    </label>
                </div>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-emerald-600 px-6 text-sm font-medium text-white shadow-sm hover:bg-emerald-700">Upload & Import</button>
            </div>
            <p class="text-xs text-zinc-500">Accepted formats: .xlsx, .xls, .csv. The file is parsed and consolidated by Log Sheet No, with duplicate detection and validation.</p>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 border-b border-zinc-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Consolidated Logsheets</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Showing {{ $logsheets->count() }} consolidated entries</p>
            </div>
            <form method="POST" action="{{ route('logsheets.clear') }}" class="flex gap-2">
                @csrf
                <input type="text" name="log_sheet_no" value="{{ old('log_sheet_no') }}" placeholder="Enter Log Sheet No → Clear Payment" class="w-64 rounded-md border border-zinc-300 px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-amber-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-amber-700">Clear Payment</button>
            </form>
        </div>
        @if(session('success'))
            <div class="mx-6 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400">
                {{ session('success') }}
            </div>
        @elseif(session('info'))
            <div class="mx-6 mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
                {{ session('info') }}
            </div>
        @elseif(session('error'))
            <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mx-6 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                        <th class="px-6 py-3 text-left">Log Sheet No</th>
                        <th class="px-6 py-3 text-left">Date</th>
                        <th class="px-6 py-3 text-left">Vehicle</th>
                        <th class="px-6 py-3 text-left">Destination</th>
                        <th class="px-6 py-3 text-right">Gross Wt</th>
                        <th class="px-6 py-3 text-right">Booked</th>
                        <th class="px-6 py-3 text-right">Actual</th>
                        <th class="px-6 py-3 text-right">Diff</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-left">Cleared</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($logsheets as $logsheet)
                        <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                            <td class="px-6 py-3">
                                <a href="{{ route('logsheets.show', $logsheet) }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $logsheet->log_sheet_no }}</a>
                                @if($logsheet->lastImport && $logsheet->lastImport->file_path)
                                    <br>
                                    <a href="{{ route('logsheets.download', $logsheet->last_import_id) }}" class="inline-flex items-center gap-1 text-xs text-zinc-500 hover:text-brand-600 dark:text-zinc-400 dark:hover:text-brand-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        {{ $logsheet->lastImport->original_filename }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">{{ $logsheet->date?->format('Y-m-d') }}</td>
                            <td class="px-6 py-3">{{ $logsheet->vehicle_no }}</td>
                            <td class="px-6 py-3">{{ $logsheet->destination }}</td>
                            <td class="px-6 py-3 text-right font-mono">{{ number_format($logsheet->total_gross_wt, 3) }}</td>
                            <td class="px-6 py-3 text-right font-mono">{{ number_format($logsheet->total_booked_amount, 2) }}</td>
                            <td class="px-6 py-3 text-right font-mono {{ $logsheet->total_diff > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($logsheet->total_actual_amount, 2) }}</td>
                            <td class="px-6 py-3 text-right font-mono {{ $logsheet->total_diff > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">{{ number_format($logsheet->total_diff, 2) }}</td>
                            <td class="px-6 py-3 text-center">
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
                            <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400">{{ $logsheet->cleared_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-6 py-3 text-right">
                                <div class="inline-flex items-center justify-end gap-3 text-xs">
                                    <a href="{{ route('logsheets.show', $logsheet) }}" class="text-brand-600 hover:underline dark:text-brand-500">View</a>
                                    <form method="POST" action="{{ route('logsheets.destroy', $logsheet) }}" class="inline" onsubmit="return confirm('Delete this logsheet and all related records?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-16 text-center">
                                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No logsheets imported yet. Upload an Excel file to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between border-t border-zinc-200 px-6 py-3 dark:border-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $logsheets->firstItem() ?? 0 }}-{{ $logsheets->lastItem() ?? 0 }} of {{ $logsheets->total() }}</p>
            {{ $logsheets->links() }}
        </div>
    </div>
</div>
@endsection
