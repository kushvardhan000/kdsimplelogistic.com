@props([
    'transactions' => [],
    'account' => null,
    'sort' => 'transaction_date',
    'directionSort' => 'desc',
])

@php
    $queryParams = request()->query();
    unset($queryParams['page']);
    $baseQuery = http_build_query($queryParams);
    $sortUrl = fn ($field) => request()->url() . '?' . http_build_query(array_merge($queryParams, ['sort' => $field, 'direction_sort' => request('sort') === $field && request('direction_sort') === 'asc' ? 'desc' : 'asc']));
@endphp

<div class="space-y-4">
    @if($transactions->count() > 0)
        <div class="hidden lg:block rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50/60 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400">
                        <th class="px-3 py-2 font-medium">Date</th>
                        <th class="px-3 py-2 font-medium">Direction</th>
                        <th class="px-3 py-2 font-medium text-right">Amount</th>
                        <th class="px-3 py-2 font-medium">Mode</th>
                        <th class="px-3 py-2 font-medium">Plan</th>
                        <th class="px-3 py-2 font-medium text-right">Balance</th>
                        <th class="px-3 py-2 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach($transactions as $txn)
                        <tr class="transition-colors hover:bg-zinc-50/70 dark:hover:bg-zinc-800/30">
                            <td class="px-3 py-2 text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap tabular-nums">
                                {{ $txn->transaction_date?->format('Y-m-d') }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if($txn->direction === 'credit')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-500/20">
                                        <span class="h-1 w-1 rounded-full bg-emerald-500"></span>
                                        Credit
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-950/40 dark:text-red-400 dark:ring-red-500/20">
                                        <span class="h-1 w-1 rounded-full bg-red-500"></span>
                                        Debit
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right text-sm font-medium tabular-nums whitespace-nowrap {{ $txn->direction === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $txn->direction === 'credit' ? '+' : '-' }}{{ number_format($txn->amount, 2) }}
                            </td>
                            <td class="px-3 py-2 text-xs capitalize whitespace-nowrap">
                                {{ $txn->payment_mode ?? '—' }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if($txn->payment_plan === 'emi' && $txn->installment_no && $txn->installment_total)
                                    <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-600/10 dark:bg-brand-950/40 dark:text-brand-400 dark:ring-brand-500/20">
                                        EMI {{ $txn->installment_no }}/{{ $txn->installment_total }}
                                    </span>
                                @else
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 capitalize">
                                        {{ $txn->payment_plan ?? '—' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right text-sm font-medium text-zinc-900 dark:text-zinc-100 whitespace-nowrap tabular-nums">
                                {{ number_format($txn->running_balance, 2) }}
                            </td>
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('accounts.transactions.edit', ['account' => $account, 'transaction' => $txn]) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" title="Edit">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('accounts.transactions.destroy', ['account' => $account, 'transaction' => $txn]) }}" class="inline" onsubmit="return confirm('Delete this transaction?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-red-950/40 dark:hover:text-red-400" title="Delete">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="lg:hidden space-y-2">
            @foreach($transactions as $txn)
                <div class="rounded-lg border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if($txn->direction === 'credit')
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/10 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-500/20">
                                    <span class="h-1 w-1 rounded-full bg-emerald-500"></span>
                                    Credit
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-950/40 dark:text-red-400 dark:ring-red-500/20">
                                    <span class="h-1 w-1 rounded-full bg-red-500"></span>
                                    Debit
                                </span>
                            @endif
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $txn->transaction_date?->format('Y-m-d') }}
                            </span>
                        </div>
                        <span class="text-sm font-semibold {{ $txn->direction === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $txn->direction === 'credit' ? '+' : '-' }}{{ number_format($txn->amount, 2) }}
                        </span>
                    </div>

                    <div class="mt-2 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-3">
                            <span class="text-zinc-500 dark:text-zinc-400">
                                Mode: <span class="capitalize">{{ $txn->payment_mode ?? '—' }}</span>
                            </span>
                            <span class="text-zinc-500 dark:text-zinc-400">
                                Plan: 
                                @if($txn->payment_plan === 'emi' && $txn->installment_no && $txn->installment_total)
                                    EMI {{ $txn->installment_no }}/{{ $txn->installment_total }}
                                @else
                                    {{ ucfirst($txn->payment_plan ?? '—') }}
                                @endif
                            </span>
                        </div>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100 tabular-nums">
                            Bal: {{ number_format($txn->running_balance, 2) }}
                        </span>
                    </div>

                    @if($txn->description)
                        <p class="mt-1.5 truncate text-xs text-zinc-600 dark:text-zinc-300" title="{{ $txn->description }}">
                            {{ $txn->description }}
                        </p>
                    @endif

                    <div class="mt-2 flex items-center justify-end gap-2 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                        <a href="{{ route('accounts.transactions.edit', ['account' => $account, 'transaction' => $txn]) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" title="Edit">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </a>
                        <form method="POST" action="{{ route('accounts.transactions.destroy', ['account' => $account, 'transaction' => $txn]) }}" class="inline" onsubmit="return confirm('Delete this transaction?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-red-950/40 dark:hover:text-red-400" title="Delete">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <svg class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">No transactions recorded yet.</p>
        </div>
    @endif
</div>
