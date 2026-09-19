@extends('layouts.app')

@section('title', 'Logsheet Imports · Transport')

@section('content')
@php
    $filterInputClass = 'w-full rounded-md border border-zinc-300 px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100';
    $queryParams = request()->query();
    unset($queryParams['page']);
    $sortUrl = fn ($field) => request()->url() . '?' . http_build_query(array_merge($queryParams, [
        'sort' => $field,
        'direction' => $sortField === $field && $sortDirection === 'asc' ? 'desc' : 'asc',
    ]));
    $sortIndicator = fn ($field) => $sortField === $field ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕';
    $uploadedFile = old('file');
    $uploadLabel = is_object($uploadedFile) && method_exists($uploadedFile, 'getClientOriginalName')
        ? $uploadedFile->getClientOriginalName()
        : ($logsheets->first()?->lastImport?->original_filename ?? 'Choose a file');
@endphp

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
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
            <div>
                <label for="log_sheet_no" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Log Sheet No</label>
                <input id="log_sheet_no" type="search" name="log_sheet_no" value="{{ request('log_sheet_no') }}" placeholder="Search log sheet no..." class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="transport" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Transport</label>
                <input id="transport" type="search" name="transport" value="{{ request('transport') }}" placeholder="TPRT code or name..." class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="town" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Town</label>
                <input id="town" type="search" name="town" value="{{ request('town') }}" placeholder="Search town..." class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="destination" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Location / Destination</label>
                <input id="destination" type="search" name="destination" value="{{ request('destination') }}" placeholder="Search location..." class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="vehicle_no" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Vehicle No</label>
                <input id="vehicle_no" type="search" name="vehicle_no" value="{{ request('vehicle_no') }}" placeholder="Search vehicle..." class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="sap_invoice_no" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">SAP Invoice No</label>
                <input id="sap_invoice_no" type="search" name="sap_invoice_no" value="{{ request('sap_invoice_no') }}" placeholder="Search SAP invoice..." class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="vendor_inv_no" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Vendor Invoice No</label>
                <input id="vendor_inv_no" type="search" name="vendor_inv_no" value="{{ request('vendor_inv_no') }}" placeholder="Search vendor invoice..." class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="status" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Status</label>
                <select id="status" name="status" class="{{ $filterInputClass }}">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="cleared" {{ request('status') === 'cleared' ? 'selected' : '' }}>Cleared</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">From Date</label>
                <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="date_to" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">To Date</label>
                <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="posting_date_from" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Posting From</label>
                <input id="posting_date_from" type="date" name="posting_date_from" value="{{ request('posting_date_from') }}" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="posting_date_to" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Posting To</label>
                <input id="posting_date_to" type="date" name="posting_date_to" value="{{ request('posting_date_to') }}" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="bill_date_from" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Bill From</label>
                <input id="bill_date_from" type="date" name="bill_date_from" value="{{ request('bill_date_from') }}" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="bill_date_to" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Bill To</label>
                <input id="bill_date_to" type="date" name="bill_date_to" value="{{ request('bill_date_to') }}" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="min_gross_wt" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Min Gross Weight</label>
                <input id="min_gross_wt" type="number" step="0.001" min="0" name="min_gross_wt" value="{{ request('min_gross_wt') }}" placeholder="0.000" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="max_gross_wt" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Max Gross Weight</label>
                <input id="max_gross_wt" type="number" step="0.001" min="0" name="max_gross_wt" value="{{ request('max_gross_wt') }}" placeholder="0.000" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="min_amount" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Min Actual Amount</label>
                <input id="min_amount" type="number" step="0.01" min="0" name="min_amount" value="{{ request('min_amount') }}" placeholder="0.00" class="{{ $filterInputClass }}">
            </div>
            <div>
                <label for="max_amount" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Max Actual Amount</label>
                <input id="max_amount" type="number" step="0.01" min="0" name="max_amount" value="{{ request('max_amount') }}" placeholder="0.00" class="{{ $filterInputClass }}">
            </div>
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-brand-700">Apply Filters</button>
            <a href="{{ route('logsheets.index') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-300 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Clear</a>
            <input type="hidden" name="sort" value="{{ $sortField }}">
            <input type="hidden" name="direction" value="{{ $sortDirection }}">
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
                        {{ old('file', $logsheets->first()?->lastImport?->original_filename ?? 'Choose a file') }}
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
            <table class="w-full min-w-max text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('log_sheet_no') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Log Sheet No <span>{{ $sortIndicator('log_sheet_no') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('date') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Date <span>{{ $sortIndicator('date') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('vehicle_no') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Vehicle <span>{{ $sortIndicator('vehicle_no') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('tprt_code') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                TPRT Code <span>{{ $sortIndicator('tprt_code') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('tprt_name') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                TPRT Name <span>{{ $sortIndicator('tprt_name') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('town') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Town <span>{{ $sortIndicator('town') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('destination') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Destination <span>{{ $sortIndicator('destination') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('sap_invoice_no') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                SAP Invoice No <span>{{ $sortIndicator('sap_invoice_no') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('posting_date') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Posting Date <span>{{ $sortIndicator('posting_date') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('bill_date') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Bill Date <span>{{ $sortIndicator('bill_date') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('vendor_inv_no') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Vendor Inv No <span>{{ $sortIndicator('vendor_inv_no') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-right whitespace-nowrap">
                            <a href="{{ $sortUrl('total_gross_wt') }}" class="inline-flex items-center justify-end gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Gross Wt <span>{{ $sortIndicator('total_gross_wt') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-right whitespace-nowrap">
                            <a href="{{ $sortUrl('total_booked_amount') }}" class="inline-flex items-center justify-end gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Booked Amt <span>{{ $sortIndicator('total_booked_amount') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-right whitespace-nowrap">
                            <a href="{{ $sortUrl('total_actual_amount') }}" class="inline-flex items-center justify-end gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Actual Amt <span>{{ $sortIndicator('total_actual_amount') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-right whitespace-nowrap">
                            <a href="{{ $sortUrl('total_diff') }}" class="inline-flex items-center justify-end gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Diff <span>{{ $sortIndicator('total_diff') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-center whitespace-nowrap">
                            <a href="{{ $sortUrl('consignment_count') }}" class="inline-flex items-center justify-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Consignments <span>{{ $sortIndicator('consignment_count') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-center whitespace-nowrap">
                            <a href="{{ $sortUrl('status') }}" class="inline-flex items-center justify-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Status <span>{{ $sortIndicator('status') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">
                            <a href="{{ $sortUrl('cleared_at') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Cleared At <span>{{ $sortIndicator('cleared_at') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left whitespace-nowrap">Cleared By</th>
                        <th class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($logsheets as $logsheet)
                        <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                            <td class="px-6 py-3 whitespace-nowrap">
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
                            <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $logsheet->date?->format('Y-m-d') }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">{{ $logsheet->vehicle_no }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">{{ $logsheet->tprt_code }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">{{ $logsheet->tprt_name }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                @php
                                    $towns = $logsheet->details->flatMap(fn ($detail) => [$detail->town, $detail->town_2])->filter()->unique()->join(', ');
                                @endphp
                                {{ $towns ?: '—' }}
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">{{ $logsheet->destination }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">{{ $logsheet->sap_invoice_no }}</td>
                            <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $logsheet->posting_date?->format('Y-m-d') }}</td>
                            <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $logsheet->bill_date?->format('Y-m-d') }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">{{ $logsheet->vendor_inv_no }}</td>
                            <td class="px-6 py-3 text-right font-mono whitespace-nowrap">{{ number_format($logsheet->total_gross_wt, 3) }}</td>
                            <td class="px-6 py-3 text-right font-mono whitespace-nowrap">{{ number_format($logsheet->total_booked_amount, 2) }}</td>
                            <td class="px-6 py-3 text-right font-mono {{ $logsheet->total_diff > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }} whitespace-nowrap">{{ number_format($logsheet->total_actual_amount, 2) }}</td>
                            <td class="px-6 py-3 text-right font-mono {{ $logsheet->total_diff > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }} whitespace-nowrap">{{ number_format($logsheet->total_diff, 2) }}</td>
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
                            <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">{{ $logsheet->cleared_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-6 py-3 whitespace-nowrap">{{ $logsheet->clearer?->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
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
                            <td colspan="20" class="px-6 py-16 text-center">
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