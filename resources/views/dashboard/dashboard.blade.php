@extends('layouts.app')

@section('title', 'Dashboard · Transport')

@php
    $currency = fn ($value) => '₹ ' . number_format((float) $value, 2);
@endphp

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => '#']" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Dashboard</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Welcome back, {{ auth()->user()?->name }}. Here is your transport overview.</p>
        </div>
        @can('create', App\Models\TransportLog::class)
            <a href="{{ route('transport-logs.create') }}" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">
                New Transport Log
            </a>
        @endcan
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.kpi-card title="Total Transport Entries" :value="$totalEntries" />
        <x-ui.kpi-card title="Today's Entries" :value="$todayEntries" />
        <x-ui.kpi-card title="Today's Profit" :value="$currency($todayProfit)" />
        <x-ui.kpi-card title="Monthly Profit" :value="$currency($monthlyProfit)" />
        <x-ui.kpi-card title="Pending Vehicle Payments" :value="$currency($pendingVehiclePayments)" />
        <x-ui.kpi-card title="Pending Fuel Station Balance" :value="$currency($pendingFuelBalance)" />
        <x-ui.kpi-card title="Pending Fuel Settlements" :value="$pendingFuelSettlementsCount . ' logs · ' . $currency($pendingFuelSettlementsDue)" />
        <x-ui.kpi-card title="Total Advances" :value="$currency($totalAdvances)" />
        <x-ui.kpi-card title="Total Expenses" :value="$currency($totalExpenses)" />

        @if(auth()->user()?->isSuperAdmin())
            <x-ui.kpi-card title="Total Fuel Station Dues" :value="$currency($totalFuelStationDues ?? 0)" />
            <x-ui.kpi-card title="Total Motor Parts Dues" :value="$currency($totalMotorPartsDues ?? 0)" />
            <x-ui.kpi-card title="Monthly Staff Salary Paid" :value="$currency($monthlyStaffSalaryPaid ?? 0)" />
            <x-ui.kpi-card title="Monthly Company Expenses" :value="$currency($monthlyCompanyExpenses ?? 0)" />
        @endif
    </div>

    <div class="grid min-w-0 gap-6 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50">Recent Transport Logs</h3>
                <a href="{{ route('transport-logs.index') }}" class="text-xs font-medium text-brand-600 dark:text-brand-500 hover:underline">View all</a>
            </div>

            <div class="w-full overflow-x-auto rounded-xl">
    <div class="min-w-max">
            <x-ui.table :headers="['Date', 'Vehicle', 'Company', 'Profit', '']">
                @forelse($recentLogs as $log)
                    <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                        <td class="px-6 py-3.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $log->date->format('Y-m-d') }}</td>
                        <td class="px-6 py-3.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $log->vehicle_no }}</td>
                        <td class="px-6 py-3.5">{{ $log->company }}</td>
                        <td class="px-6 py-3.5 font-medium {{ $log->profitColorClass() }}">{{ $currency($log->profit) }}</td>
                        <td class="px-6 py-3.5 text-right">
                            <a href="{{ route('transport-logs.show', $log) }}" class="text-brand-600 hover:underline dark:text-brand-500">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No records yet.</td>
                    </tr>
                @endforelse
            </x-ui.table>
              </div>
</div>

            @if(auth()->user()?->isSuperAdmin())
            <div>
                <h3 class="mb-4 text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50">Recent Activity</h3>
                <x-ui.table :headers="['Time', 'User', 'Action', 'Description']">
                @forelse($recentActivity as $activity)
                    <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                        <td class="px-6 py-3.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $activity->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-3.5">{{ $activity->user?->name ?? 'System' }}</td>
                        <td class="px-6 py-3.5 uppercase text-xs font-medium">{{ $activity->action }}</td>
                        <td class="px-6 py-3.5 text-sm">{{ $activity->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No records yet.</td>
                    </tr>
                @endforelse
                </x-ui.table>
            </div>
            @endif
        </div>

        @if(auth()->user()?->isSuperAdmin())
        <div class="min-w-0 space-y-4">
            <h3 class="text-base font-medium tracking-tight text-zinc-950 dark:text-zinc-50">Recent Admin Actions</h3>
            <div class="space-y-3">
                @forelse($recentAdminActions as $action)
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $action->user?->name ?? 'System' }}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $action->created_at?->format('Y-m-d H:i') }}</span>
                        </div>
                        <p class="mt-1 text-xs uppercase font-medium text-brand-600 dark:text-brand-500">{{ $action->action }}</p>
                        <p class="mt-0.5 text-sm text-zinc-600 dark:text-zinc-300">{{ $action->description }}</p>
                    </div>
                @empty
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                        No records yet.
                    </div>
                @endforelse
            </div>

            @can('view-activity-logs')
                <a href="{{ route('activity-logs.index') }}" class="block rounded-xl border border-zinc-200 bg-white p-4 text-center text-sm font-medium text-brand-600 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-brand-500 dark:hover:bg-zinc-800/50">
                    View Full Activity Log
                </a>
            @endcan
        </div>
        @endif
    </div>
@endsection
