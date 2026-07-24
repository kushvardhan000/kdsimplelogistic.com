@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $base = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-150 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-zinc-950 disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' => 'bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200 shadow-premium-sm',
        'secondary' => 'bg-white text-zinc-900 border border-zinc-200 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-100 dark:border-zinc-800 dark:hover:bg-zinc-800/50',
        'ghost' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800 shadow-premium-sm',
        'brand' => 'bg-brand-600 text-white hover:bg-brand-700 shadow-premium-sm',
    ];

    $sizes = [
        'sm' => 'h-8 px-3 text-xs gap-1.5',
        'md' => 'h-10 px-4 text-sm gap-2',
        'lg' => 'h-11 px-6 text-sm gap-2',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
