@props([
    'currentBalance' => 0,
    'totalCredited' => 0,
    'totalDebited' => 0,
    'lastTransactionDate' => null,
    'openingBalance' => 0,
])

@php
    $net = round((float) $totalCredited - (float) $totalDebited, 2);
    $isCreditHeavy = $net >= 0;
    $balanceColor = $isCreditHeavy ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
    $trendIcon = $isCreditHeavy
        ? '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>'
        : '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>';
@endphp

<div class="flex flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 border-b border-zinc-200 dark:border-zinc-800">
    <div class="flex items-baseline gap-2">
        <span class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Balance</span>
        <span class="text-xl font-semibold tracking-tight {{ $balanceColor }}">
            {{ number_format((float) $currentBalance, 2) }}
        </span>
        <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium {{ $isCreditHeavy ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400' }}">
            {!! $trendIcon !!}
            {{ $isCreditHeavy ? '+' : '' }}{{ number_format($net, 2) }}
        </span>
    </div>

    <div class="flex items-center gap-4 text-xs">
        <div class="flex items-center gap-1.5">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            <span class="text-zinc-500 dark:text-zinc-400">Credited</span>
            <span class="font-medium text-emerald-600 dark:text-emerald-400 tabular-nums">+{{ number_format((float) $totalCredited, 2) }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
            <span class="text-zinc-500 dark:text-zinc-400">Debited</span>
            <span class="font-medium text-red-600 dark:text-red-400 tabular-nums">-{{ number_format((float) $totalDebited, 2) }}</span>
        </div>
    </div>

    <div class="flex items-center gap-2 ml-auto">
        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
            <div class="h-full rounded-full bg-emerald-500 dark:bg-emerald-400 transition-all duration-500"
                 style="width: {{ $totalCredited + $totalDebited > 0 ? max(2, round((float) $totalCredited / ($totalCredited + $totalDebited) * 100)) : 50 }}%">
            </div>
        </div>
        <span class="text-xxs text-zinc-500 dark:text-zinc-400 tabular-nums">
            {{ $totalCredited + $totalDebited > 0 ? round((float) $totalCredited / ($totalCredited + $totalDebited) * 100) : 50 }}%
        </span>
    </div>
</div>
