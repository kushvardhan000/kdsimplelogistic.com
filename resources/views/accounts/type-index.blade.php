@extends('layouts.app')

@section('title', ucwords(str_replace('_', ' ', $type)) . ' Accounts · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Accounts' => route('accounts.index'), ucwords(str_replace('_', ' ', $type)) => '#']" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <div>
            <div class="flex items-center gap-3">
                <x-accounts.account-type-icon :type="$type" size="lg" />
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ ucwords(str_replace('_', ' ', $type)) }} Accounts</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $accounts->total() }} account{{ $accounts->total() === 1 ? '' : 's' }}</p>
                </div>
            </div>
        </div>
        <a href="{{ route('accounts.create', ['type' => $type]) }}" class="inline-flex h-9 items-center justify-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-brand-700">
            + New Account
        </a>
    </div>

    @if($type === 'fuel_station')
        <div x-data="fuelStationFlow()">
            {{-- Step 1: pump grid (server-rendered, visible immediately on load) --}}
            <div x-show="!selectedId">
                @if($accounts->total() > 6)
                    <div class="mb-4 max-w-md">
                        <input type="text" x-model="search" placeholder="Search pumps..." class="block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($accounts as $account)
                        @php
                            $balanceClass = $account->current_balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                            $dotClass = $account->current_balance >= 0 ? 'bg-emerald-500' : 'bg-red-500';
                        @endphp
                        <button type="button"
                                data-pump-card
                                data-pump-id="{{ $account->id }}"
                                data-pump-name="{{ $account->name }}"
                                x-show="pumpMatches($el)"
                                @click="selectAccount({{ $account->id }}, $el.dataset.pumpName)"
                                :class="selectedId === {{ $account->id }} ? 'ring-2 ring-brand-500 border-brand-300 dark:border-brand-700' : 'border-zinc-200 dark:border-zinc-800 hover:border-brand-300 dark:hover:border-brand-700'"
                                class="text-left rounded-2xl border bg-white p-5 shadow-premium-sm transition-all dark:bg-zinc-900">
                            <div class="flex items-center justify-between gap-3">
                                <span class="min-w-0 truncate text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $account->name }}</span>
                                <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full {{ $dotClass }}"></span>
                            </div>
                            <p class="mt-3 text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Current Balance</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums {{ $balanceClass }}">
                                {{ number_format($account->current_balance, 2) }}
                            </p>
                        </button>
                    @empty
                        <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-zinc-200 bg-white p-10 text-center shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No fuel station pumps found.</p>
                        </div>
                    @endforelse
                </div>

                <div x-show="noMatches()" class="rounded-2xl border border-zinc-200 bg-white p-10 text-center shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No matching pumps.</p>
                </div>

                <div class="mt-6">
                    @if($accounts->hasPages()){{ $accounts->links() }}@endif
                </div>
            </div>

            {{-- Transaction modals are rendered here so they remain available when a pump is selected --}}
            @foreach($accounts as $account)
                <x-accounts.transaction-form-modal
                    :id="'transaction-modal-' . $account->id"
                    :title="'Add Transaction — ' . $account->name"
                    :account="$account"
                    :currentBalance="$account->current_balance"
                    :action="route('accounts.transactions.store', $account)"
                    :branches="$branches"
                />
            @endforeach

            {{-- Step 2 & 3: balance summary + optional branch filter + transactions --}}
            <div x-show="selectedId" x-cloak>
                <div class="flex items-center justify-between gap-3">
                    <button type="button" @click="backToList()" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-600 hover:text-brand-600 dark:text-zinc-400 dark:hover:text-brand-400">
                        <span aria-hidden="true">←</span> Back to all pumps
                    </button>
                    <div class="flex items-center gap-2">
                        <a :href="'{{ route('accounts.edit', ['account' => '__ID__']) }}'.replace('__ID__', selectedId)"
                           class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                           title="Edit account">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </a>
                        <form method="POST" :action="'{{ route('accounts.destroy', ['account' => '__ID__']) }}'.replace('__ID__', selectedId)" class="inline" onsubmit="return confirm('Delete this account?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-red-950/40 dark:hover:text-red-400" title="Delete account">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.108 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

                <h2 class="mt-4 text-xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50" x-text="selectedName"></h2>

                <div x-show="loading" class="mt-8 flex items-center justify-center py-16">
                    <svg class="h-6 w-6 animate-spin text-brand-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <div x-ref="fragmentContainer" x-show="!loading" x-html="fragment" class="mt-6"></div>
            </div>
        </div>

        <script>
            function fuelStationFlow() {
                return {
                    selectedId: null,
                    selectedName: '',
                    search: '',
                    fragment: '',
                    loading: false,
                    pumpMatches(el) {
                        if (!this.search) return true;
                        const name = (el.dataset.pumpName || '').toLowerCase();
                        return name.includes(this.search.toLowerCase());
                    },
                    noMatches() {
                        if (!this.search) return false;
                        const cards = this.$root ? this.$root.querySelectorAll('[data-pump-card]') : [];
                        let visible = 0;
                        cards.forEach(c => { if (c.style.display !== 'none') visible++; });
                        return visible === 0;
                    },
                    selectAccount(id, name) {
                        this.selectedName = name || '';
                        this.selectedId = id;
                        this.loadFragment(id);
                        this.updateUrl();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },
                    backToList() {
                        this.selectedId = null;
                        this.fragment = '';
                        this.selectedName = '';
                        const url = new URL(window.location.href);
                        url.searchParams.delete('selected');
                        window.history.pushState({}, '', url);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },
                    loadFragment(id, params = {}) {
                        this.loading = true;
                        this.fragment = '';
                        const qs = new URLSearchParams(params);
                        fetch(`/accounts/${id}/pump-flow?${qs.toString()}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(r => r.text())
                        .then(html => {
                            this.fragment = html;
                            this.loading = false;
                            this.$nextTick(() => this.bindFragmentEvents(id));
                        })
                        .catch(() => {
                            this.fragment = '<p class="text-sm text-red-600 dark:text-red-400">Failed to load pump details.</p>';
                            this.loading = false;
                        });
                    },
                    bindFragmentEvents(id) {
                        const container = this.$refs.fragmentContainer;
                        if (!container) return;
                        const select = container.querySelector('#pump-branch-select');
                        if (select) {
                            select.addEventListener('change', (e) => {
                                this.loadFragment(id, { branch_id: e.target.value });
                            });
                        }
                        container.querySelectorAll('[data-page]').forEach(btn => {
                            btn.addEventListener('click', (e) => {
                                e.preventDefault();
                                const page = btn.getAttribute('data-page');
                                const branch = select ? select.value : '';
                                this.loadFragment(id, { branch_id: branch, page: page });
                            });
                        });
                    },
                    updateUrl() {
                        const url = new URL(window.location.href);
                        url.searchParams.set('selected', this.selectedId);
                        window.history.pushState({}, '', url);
                    },
                    init() {
                        const params = new URLSearchParams(window.location.search);
                        const sel = params.get('selected');
                        if (sel) {
                            const card = this.$root.querySelector('[data-pump-id="' + sel + '"]');
                            const name = card ? card.getAttribute('data-pump-name') : '';
                            this.selectAccount(parseInt(sel, 10), name);
                        }
                    }
                }
            }
        </script>
    @else
        <form method="GET" action="{{ route('accounts.index') }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900 mb-4">
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
                <div class="sm:col-span-2 lg:col-span-1">
                    <label for="search" class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Search</label>
                    <input type="text" name="search" id="search" placeholder="Search accounts..." value="{{ request('search') }}" class="block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                </div>
                <div>
                    <label for="is_active" class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Status</label>
                    <select name="is_active" id="is_active" class="block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="">All</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-2 flex items-end gap-2">
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                        Filter
                    </button>
                    <a href="{{ route('accounts.index', ['type' => $type]) }}" class="inline-flex h-9 items-center justify-center rounded-lg px-4 text-sm font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                        Clear
                    </a>
                </div>
            </div>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($accounts as $account)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-premium-sm transition-all hover:shadow-premium-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <x-accounts.account-type-icon :type="$account->type" size="md" />
                            <div>
                                <a href="{{ route('accounts.show', $account) }}" class="font-medium text-zinc-900 hover:text-brand-600 dark:text-zinc-100 dark:hover:text-brand-400">
                                    {{ $account->name }}
                                </a>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    @if($account->fuelStation)
                                        {{ $account->fuelStation->name }}
                                    @elseif($account->driver)
                                        {{ $account->driver->name }}
                                    @else
                                        No linked entity
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if($account->is_active)
                            <x-ui.badge variant="success" dot>Active</x-ui.badge>
                        @else
                            <x-ui.badge variant="danger" dot>Inactive</x-ui.badge>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Current Balance</span>
                            <p class="mt-0.5 text-lg font-semibold {{ $account->current_balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($account->current_balance, 2) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Last Activity</span>
                            <p class="mt-0.5 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $account->transactions->last()?->transaction_date?->format('Y-m-d') ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button type="button" @click="$dispatch('open-modal', 'transaction-modal-{{ $account->id }}')" class="inline-flex h-8 items-center justify-center rounded-lg bg-brand-600 px-3 text-xs font-medium text-white shadow-premium-sm hover:bg-brand-700">
                            Add Transaction
                        </button>
                        <a href="{{ route('accounts.edit', $account) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" title="Edit account">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                        </a>
                        <form method="POST" action="{{ route('accounts.destroy', $account) }}" class="inline" onsubmit="return confirm('Delete this account?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:text-zinc-400 dark:hover:bg-red-950/40 dark:hover:text-red-400" title="Delete account">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.108 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        </form>
                        <a href="{{ route('accounts.show', $account) }}" class="inline-flex h-8 items-center justify-center rounded-lg border border-zinc-200 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                            View Ledger
                        </a>
                    </div>
                </div>

                <x-accounts.transaction-form-modal
                    :id="'transaction-modal-' . $account->id"
                    :title="'Add Transaction — ' . $account->name"
                    :account="$account"
                    :currentBalance="$account->current_balance"
                    :action="route('accounts.transactions.store', $account)"
                    :branches="$branches"
                />
            @empty
                <div class="sm:col-span-2 lg:col-span-3 rounded-xl border border-zinc-200 bg-white p-10 text-center shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <svg class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No accounts found for this type.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $accounts->links() }}
        </div>
    @endif
@endsection
