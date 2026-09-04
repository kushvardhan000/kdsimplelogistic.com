@props([
    'type',
    'size' => 'md',
])

@php
    $config = [
        'fuel_station' => [
            'color' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400',
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
        ],
        'motor_parts_shop' => [
            'color' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400',
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        ],
        'staff' => [
            'color' => 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400',
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        ],
        'company_expense' => [
            'color' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400',
            'icon' => '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
        ],
    ];

    $sizes = [
        'sm' => 'h-6 w-6 rounded-md',
        'md' => 'h-8 w-8 rounded-lg',
        'lg' => 'h-10 w-10 rounded-xl',
    ];

    $item = $config[$type] ?? $config['company_expense'];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<span class="inline-flex items-center justify-center {{ $sizeClass }} {{ $item['color'] }}">
    {!! $item['icon'] !!}
</span>
