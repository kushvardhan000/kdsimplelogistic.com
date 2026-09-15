@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Logsheet Imports</h1>
        </div>
        <div class="w-full max-w-xl">
            <form method="GET" action="{{ route('logsheets.index') }}" class="flex gap-2">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input rounded-md border-zinc-300">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input rounded-md border-zinc-300">
                <button type="submit" class="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white">Filter</button>
            </form>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <form method="POST" action="{{ route('logsheets.store') }}" enctype="multipart/form-data" class="flex gap-3">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="rounded-md border-zinc-300">
            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white">Upload Logsheet Excel</button>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <div class="font-semibold text-zinc-900 dark:text-zinc-100">Consolidated Logsheets</div>
            <form method="POST" action="{{ route('logsheets.clear') }}" class="flex gap-2">
                @csrf
                <input type="text" name="log_sheet_no" placeholder="Enter Log Sheet No → Clear Payment" class="rounded-md border-zinc-300">
                <button type="submit" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white">Clear Payment</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                <thead class="bg-zinc-50 dark:bg-zinc-950">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Log Sheet No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Vehicle</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Destination</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Total Gross Weight</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Booked Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Actual Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Diff</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Cleared Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($logsheets as $logsheet)
                        <tr>
                            <td class="px-4 py-3"><a href="{{ route('logsheets.show', $logsheet) }}" class="font-semibold text-brand-700 dark:text-brand-300">{{ $logsheet->log_sheet_no }}</a></td>
                            <td class="px-4 py-3">{{ $logsheet->date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ $logsheet->vehicle_no }}</td>
                            <td class="px-4 py-3">{{ $logsheet->destination }}</td>
                            <td class="px-4 py-3">{{ number_format($logsheet->total_gross_wt, 3) }}</td>
                            <td class="px-4 py-3">{{ number_format($logsheet->total_booked_amount, 2) }}</td>
                            <td class="px-4 py-3">{{ number_format($logsheet->total_actual_amount, 2) }}</td>
                            <td class="px-4 py-3">{{ number_format($logsheet->total_diff, 2) }}</td>
                            <td class="px-4 py-3">
                                @if($logsheet->status === 'cleared')
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-800">Cleared</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">Pending</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $logsheet->cleared_at?->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-8 text-center text-zinc-500">No logsheets imported yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $logsheets->links() }}
        </div>
    </div>
</div>
@endsection
