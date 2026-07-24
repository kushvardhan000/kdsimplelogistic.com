@extends('layouts.app')

@section('title', 'Transport Logs · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Transport Logs' => '#']" />

    @php
        $queryParams = request()->query();
        unset($queryParams['page']);
        $baseQuery = http_build_query($queryParams);
        $sortUrl = fn($field) => request()->url() . '?' . http_build_query(array_merge($queryParams, ['sort' => $field, 'direction' => request('sort') === $field && request('direction') === 'asc' ? 'desc' : 'asc']));
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Transport Logs</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Searchable history of all transport records.</p>
        </div>
        <div class="flex items-center gap-3">
            @can('create', App\Models\TransportLog::class)
                <a href="{{ route('transport-logs.create') }}" class="inline-flex h-8 items-center justify-center rounded-lg bg-brand-600 px-3 text-xs font-medium text-white shadow-premium-sm hover:bg-brand-700">
                    New Log
                </a>
            @endcan
            @can('viewAny', App\Models\TransportLog::class)
                <div x-data="{ exportOpen: false }" class="relative">
                    <button type="button" @click="exportOpen = !exportOpen" class="inline-flex h-8 items-center justify-center rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                        Export ▾
                    </button>
                    <div x-show="exportOpen" @click.away="exportOpen = false" class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="py-1">
                            <a href="{{ route('transport-logs.export.monthly', array_merge(request()->query(), ['month' => now()->month, 'year' => now()->year])) }}" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">This Month</a>
                            <a href="{{ route('transport-logs.export.yearly', array_merge(request()->query(), ['year' => now()->year])) }}" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">This Year</a>
                            <!-- <button @click.prevent="exportOpen = false" class="block w-full text-left px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">Custom Date Range</button> -->
                            <a href="{{ route('transport-logs.export.range', request()->query()) }}" class="block px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">Export Current View</a>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </div>

    <form method="GET" action="{{ route('transport-logs.index') }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900 mb-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
            <x-ui.input name="search" type="search" placeholder="Search vehicle / company..." :value="request('search')" />
            <x-ui.input name="company" type="text" placeholder="Filter by company..." :value="request('company')" />
            <x-ui.select name="status" label="Status">
                <option value="">All Statuses</option>
                <option value="profit" {{ request('status') === 'profit' ? 'selected' : '' }}>Profit</option>
                <option value="loss" {{ request('status') === 'loss' ? 'selected' : '' }}>Loss</option>
                <option value="breakeven" {{ request('status') === 'breakeven' ? 'selected' : '' }}>Break-even</option>
            </x-ui.select>
            <x-ui.input name="created_date" type="date" label="Created Date" :value="request('created_date')" />
            <x-ui.input name="clearing_date" type="date" label="Clearing Date" :value="request('clearing_date')" />
        </div>
        <div class="flex items-center gap-2 mt-3">
            <x-ui.button type="submit" variant="secondary" size="sm">Search</x-ui.button>
            <a href="{{ route('transport-logs.index') }}" class="inline-flex h-8 items-center justify-center rounded-lg px-3 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">Clear</a>
        </div>
        <input type="hidden" name="sort" value="{{ request('sort') }}">
        <input type="hidden" name="direction" value="{{ request('direction') }}">
    </form>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                    <th class="px-3 py-3">
                        <a href="{{ $sortUrl('date') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                            Date
                            @if(request('sort') === 'date')
                                <span class="text-brand-600 dark:text-brand-400">{{ request('direction') === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">↕</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-3 py-3">
                        <a href="{{ $sortUrl('vehicle_no') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                            Vehicle No
                            @if(request('sort') === 'vehicle_no')
                                <span class="text-brand-600 dark:text-brand-400">{{ request('direction') === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">↕</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-3 py-3">
                        <a href="{{ $sortUrl('company') }}" class="inline-flex items-center gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                            Company
                            @if(request('sort') === 'company')
                                <span class="text-brand-600 dark:text-brand-400">{{ request('direction') === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">↕</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-3 py-3 text-right">
                        <a href="{{ $sortUrl('total_sale') ?? '#' }}" class="inline-flex items-center justify-end gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                            Total Sale
                            @if(request('sort') === 'total_sale')
                                <span class="text-brand-600 dark:text-brand-400">{{ request('direction') === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">↕</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-3 py-3 text-right">
                        <a href="{{ $sortUrl('total_expense') ?? '#' }}" class="inline-flex items-center justify-end gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                            Total Expense
                            @if(request('sort') === 'total_expense')
                                <span class="text-brand-600 dark:text-brand-400">{{ request('direction') === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">↕</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-3 py-3 text-right">
                        <a href="{{ $sortUrl('profit') ?? '#' }}" class="inline-flex items-center justify-end gap-1 hover:text-zinc-900 dark:hover:text-zinc-100">
                            Profit
                            @if(request('sort') === 'profit')
                                <span class="text-brand-600 dark:text-brand-400">{{ request('direction') === 'asc' ? '▲' : '▼' }}</span>
                            @else
                                <span class="text-zinc-300 dark:text-zinc-600">↕</span>
                            @endif
                        </a>
                    </th>
                    <th class="px-3 py-3 text-center">Status</th>
                    <th class="px-3 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($logs as $log)
                    <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                        <td class="px-3 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $log->date->format('Y-m-d') }}</td>
                        <td class="px-3 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $log->vehicle_no }}</td>
                        <td class="px-3 py-3">{{ $log->company }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($log->total_sale, 2) }}</td>
                        <td class="px-3 py-3 text-right">{{ number_format($log->total_expense, 2) }}</td>
                        <td class="px-3 py-3 text-right font-medium {{ $log->profitColorClass() }}">{{ number_format($log->profit, 2) }}</td>
                        <td class="px-3 py-3 text-center">
                            @if($log->profit > 0)
                                <x-ui.badge variant="success" dot>Profit</x-ui.badge>
                            @elseif($log->profit < 0)
                                <x-ui.badge variant="danger" dot>Loss</x-ui.badge>
                            @else
                                <x-ui.badge variant="default" dot>Break-even</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-3 text-xs">
                                <a href="{{ route('transport-logs.show', $log) }}" class="text-brand-600 hover:underline dark:text-brand-500">View</a>
                                @can('update', $log)
                                    <a href="{{ route('transport-logs.edit', $log) }}" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200">Edit</a>
                                @endcan
                                @can('delete', $log)
                                    <form method="POST" action="{{ route('transport-logs.destroy', $log) }}" class="inline" onsubmit="return confirm('Delete this transport log?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No transport logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
@endsection
