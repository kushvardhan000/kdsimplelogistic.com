@props([
    'variant' => 'default',
    'dot' => false,
])

@php
    $variants = [
        'default' => 'bg-zinc-100 text-zinc-700 ring-zinc-600/10 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-500/20',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-500/20',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/10 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-500/20',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/10 dark:bg-red-950/40 dark:text-red-400 dark:ring-red-500/20',
        'info' => 'bg-brand-50 text-brand-700 ring-brand-600/10 dark:bg-brand-950/40 dark:text-brand-400 dark:ring-brand-500/20',
    ];

    $dots = [
        'default' => 'bg-zinc-400',
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger' => 'bg-red-500',
        'info' => 'bg-brand-500',
    ];
@endphp

<span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $variants[$variant] ?? $variants['default'] }}">
    @if($dot)
        <span class="h-1.5 w-1.5 rounded-full {{ $dots[$variant] ?? $dots['default'] }}"></span>
    @endif
    {{ $slot }}
</span>
