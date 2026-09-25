@extends('layouts.app')

@section('title', 'All Log Sheets · Transport')

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
@endphp

<x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Logsheet Imports' => route('logsheets.index'), 'All Log Sheets' => '#']" />

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">All Log Sheets</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Consolidated view of all imported log sheets across all imports.</p>
        </div>
        <a href="{{ route('logsheets.index') }}" class="inline-flex items-center gap-2 text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            ← Back to Imports
        </a>
    </div>

    <form method="GET" action="{{ route('logsheets.records') }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 sm:grid-cols-4 items-end">
            <div>
                <label for="log_sheet_no" class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Log Sheet No</label>
                <input id="log_sheet_no" type="search" name="log_sheet_no" value="{{ request('log_sheet_no') }}" placeholder="Search log sheet no..." class="{{ $filterInputClass }}">
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
        </div>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-brand-700">Apply Filters</button>
            <a href="{{ route('logsheets.records') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-300 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Clear</a>
            <input type="hidden" name="sort" value="{{ $sortField }}">
            <input type="hidden" name="direction" value="{{ $sortDirection }}">
        </div>
    </form>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 border-b border-zinc-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-800">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Consolidated Logsheets</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Showing {{ $logsheets->count() }} consolidated entries</p>
            </div>
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
                                Import Period <span>{{ $sortIndicator('date') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-right whitespace-nowrap">
                            <a href="{{ $sortUrl('total_actual_amount') }}" class="inline-flex items-center justify-end gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Total Amount <span>{{ $sortIndicator('total_actual_amount') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-center whitespace-nowrap">
                            <a href="{{ $sortUrl('status') }}" class="inline-flex items-center justify-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                                Status <span>{{ $sortIndicator('status') }}</span>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-center whitespace-nowrap">Out of Range</th>
                        <th class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($logsheets as $logsheet)
                        <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30 @if($logsheet->fully_out_of_requested_range) bg-amber-50 dark:bg-amber-900/20 @endif">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <a href="{{ route('logsheets.show', $logsheet) }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $logsheet->log_sheet_no }}</a>
                            </td>
                            <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                {{ $logsheet->lastImport->date_from?->format('j M Y') ?? '—' }} &rarr; {{ $logsheet->lastImport->date_to?->format('j M Y') ?? '—' }}
                            </td>
                            <td class="px-6 py-3 text-right font-mono whitespace-nowrap {{ $logsheet->total_actual_amount > 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                                &#8377;{{ number_format($logsheet->total_actual_amount, 2) }}
                            </td>
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
                            <td class="px-6 py-3 text-center whitespace-nowrap">
                                @if($logsheet->fully_out_of_requested_range)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        Yes
                                    </span>
                                @else
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400">
                                        <svg class="h-3 w-3 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-3 text-xs">
                                    <a href="{{ route('logsheets.show', $logsheet) }}" class="text-brand-600 hover:underline dark:text-brand-500 min-h-[44px] min-w-[44px] flex items-center justify-center px-3">View</a>
                                    <form method="POST" action="{{ route('logsheets.destroy', $logsheet) }}" class="inline" onsubmit="return confirm('Delete this logsheet and all related records?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 min-h-[44px] min-w-[44px] flex items-center justify-center px-3">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No logsheets imported yet. Upload an Excel file from the <a href="{{ route('logsheets.index') }}" class="text-brand-600 hover:underline dark:text-brand-400">Imports page</a> to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Cards (mobile) -->
        <div class="sm:hidden divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse($logsheets as $logsheet)
                <div class="p-4 @if($logsheet->fully_out_of_requested_range) bg-amber-50 dark:bg-amber-900/20 @endif">
                    <div class="flex flex-col gap-1 mb-3">
                        <a href="{{ route('logsheets.show', $logsheet) }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-300">{{ $logsheet->log_sheet_no }}</a>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $logsheet->lastImport->date_from?->format('j M Y') ?? '—' }} &rarr; {{ $logsheet->lastImport->date_to?->format('j M Y') ?? '—' }}</span>
                        @if($logsheet->fully_out_of_requested_range)
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Fully Out of Range
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100 {{ $logsheet->total_actual_amount > 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                            &#8377;{{ number_format($logsheet->total_actual_amount, 2) }}
                        </span>
                        <div class="flex flex-col gap-2 sm:flex-row">
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
                            <div class="flex gap-2">
                                <a href="{{ route('logsheets.show', $logsheet) }}" class="text-brand-600 hover:underline dark:text-brand-500 min-h-[44px] min-w-[44px] flex items-center justify-center px-3">View</a>
                                <form method="POST" action="{{ route('logsheets.destroy', $logsheet) }}" class="inline" onsubmit="return confirm('Delete this logsheet and all related records?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 min-h-[44px] min-w-[44px] flex items-center justify-center px-3">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No logsheets imported yet. Upload an Excel file from the <a href="{{ route('logsheets.index') }}" class="text-brand-600 hover:underline dark:text-brand-400">Imports page</a> to get started.</p>
                </div>
            @endforelse
        </div>

        <div class="flex items-center justify-between border-t border-zinc-200 px-6 py-3 dark:border-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $logsheets->firstItem() ?? 0 }}-{{ $logsheets->lastItem() ?? 0 }} of {{ $logsheets->total() }}</p>
            {{ $logsheets->links() }}
        </div>
    </div>
</div>
@endsection