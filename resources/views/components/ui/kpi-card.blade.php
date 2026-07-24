@props([
    'title',
    'value',
    'change' => null,
    'trend' => 'up' // up, down, neutral
])

<div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm transition-all hover:shadow-premium-md dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex items-center justify-between">
        <span class="text-xs font-medium tracking-tight text-zinc-500 dark:text-zinc-400">{{ $title }}</span>
        @if(isset($icon))
            <div class="text-zinc-400 dark:text-zinc-500">
                {{ $icon }}
            </div>
        @endif
    </div>
    
    <div class="mt-2 flex items-baseline gap-2">
        <span class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ $value }}</span>
        
         @if($change)
             <span class="inline-flex items-center gap-0.5 rounded px-1.5 py-0.5 text-xs font-medium {{ $trend === 'up' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : ($trend === 'down' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300') }}">
                 {{ $change }}
             </span>
         @endif
    </div>
    
    @if(isset($chart))
        <div class="mt-4 h-10 w-full opacity-80">
            {{ $chart }}
        </div>
    @endif
</div>