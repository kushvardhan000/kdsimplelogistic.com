@props([
    'headers' => [],
    'responsive' => true,
])

<div @class([
    'w-full overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900',
    'relative' => $responsive,
])>
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-zinc-200 bg-zinc-50/70 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                @foreach($headers as $header)
                    <th scope="col" class="px-6 py-3.5">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 text-sm text-zinc-600 dark:divide-zinc-800 dark:text-zinc-300">
            {{ $slot }}
        </tbody>
    </table>
</div>
