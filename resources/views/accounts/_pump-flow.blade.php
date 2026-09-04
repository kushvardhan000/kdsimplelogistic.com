<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Current Balance</p>
            <p class="mt-2 text-3xl font-bold tabular-nums {{ $summary['current_balance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                {{ number_format((float) $summary['current_balance'], 2) }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Credited</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
                +{{ number_format((float) $summary['total_credited'], 2) }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Debited</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-red-600 dark:text-red-400">
                -{{ number_format((float) $summary['total_debited'], 2) }}
            </p>
        </div>
    </div>

    @if($branches->count() > 1)
        <div class="max-w-xs">
            <label for="pump-branch-select" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Branch</label>
            <select id="pump-branch-select"
                    class="block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                <option value="">All branches</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected($branchId === $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3 font-medium">Date</th>
                    <th class="px-4 py-3 text-right font-medium">Amount</th>
                    <th class="px-4 py-3 font-medium">Direction</th>
                    <th class="px-4 py-3 text-right font-medium">Balance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse($transactions as $txn)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                        <td class="px-4 py-3 tabular-nums text-zinc-700 dark:text-zinc-300">{{ $txn->transaction_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-medium {{ $txn->direction === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $txn->direction === 'credit' ? '+' : '-' }}{{ number_format((float) $txn->amount, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $txn->direction === 'credit' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400' }}">
                                {{ ucfirst($txn->direction) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) $txn->running_balance, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center">
                            <div class="rounded-xl border border-zinc-200 bg-white p-8 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <p class="mt-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">No transactions yet for this pump</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Record one to get started.</p>
                                <button type="button" @click="$dispatch('open-modal', 'transaction-modal-{{ $account->id }}')" class="mt-4 inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">
                                    + Record Transaction
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transactions->total() > 0)
        <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} of {{ $transactions->total() }}
            </p>
            <div class="flex items-center gap-2">
                @if($transactions->previousPageUrl())
                    <a href="#" data-page="{{ $transactions->currentPage() - 1 }}"
                       class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                        ← Previous
                    </a>
                @endif
                @if($transactions->nextPageUrl())
                    <a href="#" data-page="{{ $transactions->currentPage() + 1 }}"
                       class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">
                        Next →
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
