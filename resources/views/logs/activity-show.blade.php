@extends('layouts.app')

@section('title', 'Activity Log · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Activity Logs' => route('activity-logs.index'), 'Detail' => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Activity Log #{{ $activityLog->id }}</h1>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            'User' => $activityLog->user?->name ?? 'System',
            'Role' => $activityLog->role ?? '—',
            'Action' => $activityLog->action,
            'Module' => $activityLog->table_name,
            'Record ID' => $activityLog->record_id,
            'Record Summary' => $activityLog->record_summary,
            'Time' => $activityLog->created_at?->format('Y-m-d H:i'),
            'IP Address' => $activityLog->ip_address ?? '—',
            'User Agent' => $activityLog->user_agent ?? '—',
            'Method' => $activityLog->method ?? '—',
            'URL' => $activityLog->url ?? '—',
            'Status' => $activityLog->success ? 'Success' : 'Failed',
            'Description' => $activityLog->description,
        ] as $label => $value)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ $label }}</p>
                <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100 break-all">{{ $value ?? '—' }}</p>
            </div>
        @endforeach

        @if($activityLog->changes && count($activityLog->changes) > 0)
            <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900 sm:col-span-2 lg:col-span-3">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Changes</p>
                <div class="mt-3 space-y-2">
                    @foreach($activityLog->changes as $change)
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $change['field'] ?? 'N/A' }}</span>
                            <span class="text-zinc-400">:</span>
                            <span class="text-zinc-500 dark:text-zinc-400 line-through">{{ $change['old'] ?? 'null' }}</span>
                            <span class="text-zinc-400">→</span>
                            <span class="text-zinc-900 dark:text-zinc-100">{{ $change['new'] ?? 'null' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
