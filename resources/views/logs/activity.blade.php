@extends('layouts.app')

@section('title', 'Activity Logs Â· Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Activity Logs' => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Activity Logs</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Audit trail of all administrative actions and system events.</p>
    </div>

    <form method="GET" action="{{ route('activity-logs.index') }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900 mb-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end">
            <x-ui.input name="action" type="text" placeholder="Filter by action..." :value="request('action')" />
            <x-ui.input name="user" type="text" placeholder="Filter by user..." :value="request('user')" />
            <x-ui.select name="role" label="Role">
                <option value="">All Roles</option>
                <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </x-ui.select>
            <x-ui.input name="module" type="text" placeholder="Filter by module..." :value="request('module')" />
            <x-ui.input name="description" type="text" placeholder="Filter by description..." :value="request('description')" />
            <x-ui.input name="record_id" type="number" label="Record ID" :value="request('record_id')" />
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end mt-3">
            <x-ui.input name="date_from" type="date" label="From" :value="request('date_from')" />
            <x-ui.input name="date_to" type="date" label="To" :value="request('date_to')" />
            <x-ui.select name="success" label="Status">
                <option value="">All Statuses</option>
                <option value="1" {{ request('success') === '1' ? 'selected' : '' }}>Success</option>
                <option value="0" {{ request('success') === '0' ? 'selected' : '' }}>Failed</option>
            </x-ui.select>
        </div>
        <div class="flex items-center gap-2 mt-3">
            <x-ui.button type="submit" variant="secondary" size="sm">Search</x-ui.button>
            <a href="{{ route('activity-logs.index') }}" class="inline-flex h-8 items-center justify-center rounded-lg px-3 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">Clear</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-left text-sm min-w-[640px]">
            <thead>
                <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                    <th class="px-6 py-3.5">Time</th>
                    <th class="px-6 py-3.5">User</th>
                    <th class="px-6 py-3.5">Role</th>
                    <th class="px-6 py-3.5">Action</th>
                    <th class="px-6 py-3.5">Module</th>
                    <th class="px-6 py-3.5">Description</th>
                    <th class="px-6 py-3.5">Status</th>
                    <th class="px-6 py-3.5">Changes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse($logs as $log)
                    <tr class="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-800/30">
                        <td class="px-6 py-4 text-xs text-zinc-500 dark:text-zinc-400">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-6 py-4">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-6 py-4 text-xs uppercase font-medium">{{ $log->role ?? '—' }}</td>
                        <td class="px-6 py-4 uppercase text-xs font-medium">{{ $log->action }}</td>
                        <td class="px-6 py-4 text-xs">{{ $log->table_name }}</td>
                        <td class="px-6 py-4 text-sm max-w-xs truncate">{{ $log->description }}</td>
                        <td class="px-6 py-4">
                            @if($log->success)
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-500/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Success
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-950/40 dark:text-red-400 dark:ring-red-500/20">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Failed
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($log->changes)
                                <a href="{{ route('activity-logs.show', $log) }}" class="text-xs font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400">
                                    View changes
                                </a>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No activity log records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
@endsection
