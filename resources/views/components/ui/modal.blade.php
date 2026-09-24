@props([
    'id' => 'modal',
    'title' => null,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
    ];
@endphp

<template x-teleport="body">
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="if ($event.detail === '{{ $id }}') open = true"
        x-on:close-modal.window="if ($event.detail === '{{ $id }}') open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm" @click="open = false"></div>

    <div
        x-show="open"
        x-transition
        class="relative w-full {{ $sizes[$size] ?? $sizes['md'] }} rounded-2xl border border-zinc-200 bg-white shadow-premium-lg dark:border-zinc-800 dark:bg-zinc-900"
    >
        @if($title)
            <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                <h3 class="text-base font-semibold text-zinc-950 dark:text-zinc-50">{{ $title }}</h3>
                <button type="button" @click="open = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <div class="px-6 py-5">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="flex justify-end gap-2 border-t border-zinc-200 px-6 py-4 dark:border-zinc-800">
                {{ $footer }}
            </div>
        @endisset
    </div>
    </div>
</template>
