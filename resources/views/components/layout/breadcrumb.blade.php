@props([
    'items' => [],
])

@if(count($items) > 0)
    <nav class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400" aria-label="Breadcrumb">
        @foreach($items as $label => $url)
            @if($loop->last)
                <span class="font-medium text-zinc-900 dark:text-zinc-100" aria-current="page">{{ $label }}</span>
            @else
                <a href="{{ $url }}" class="transition-colors hover:text-zinc-900 dark:hover:text-zinc-100">{{ $label }}</a>
                <svg class="h-4 w-4 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            @endif
        @endforeach
    </nav>
@endif
