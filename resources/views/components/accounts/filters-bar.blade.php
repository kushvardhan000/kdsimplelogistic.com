@props([
    'action' => request()->url(),
    'preserve' => [],
])

@php
    $preserveKeys = is_array($preserve) ? $preserve : [$preserve];
    $query = request()->query();
    $query['page'] = null;
    $baseQuery = http_build_query($query);
@endphp

<div
    x-data='{
        quickRange: @json(request("quick_range") ?? ""),
        direction: @json(request("direction") ?? ""),
        paymentMode: @json(request("payment_mode") ?? ""),
        search: @json(request("search") ?? ""),
        debounceTimer: null,
        init() {
            this.$watch("quickRange", (value) => {
                if (value) {
                    this.applyQuickRange(value);
                }
            });
            this.$watch("direction", () => this.dispatchChange());
            this.$watch("paymentMode", () => this.dispatchChange());
            this.$watch("search", (value) => {
                clearTimeout(this.debounceTimer);
                this.debounceTimer = setTimeout(() => {
                    this.dispatchChange();
                }, 350);
            });
        },
        applyQuickRange(range) {
            const today = new Date();
            const format = (d) => d.toISOString().split("T")[0];
            let from = "", to = format(today);

            if (range === "today") {
                from = to;
            } else if (range === "this_week") {
                const day = today.getDay() || 7;
                const diff = today.getDate() - day + 1;
                from = format(new Date(today.setDate(diff)));
            } else if (range === "this_month") {
                from = format(new Date(today.getFullYear(), today.getMonth(), 1));
            } else if (range === "this_year") {
                from = format(new Date(today.getFullYear(), 0, 1));
            }

            const form = document.getElementById("filters-form");
            if (form) {
                const fromEl = form.elements["date_from"];
                const toEl = form.elements["date_to"];
                if (fromEl) fromEl.value = from;
                if (toEl) toEl.value = to;
                this.dispatchChange();
            }
        },
        dispatchChange() {
            const form = document.getElementById("filters-form");
            if (form) {
                const params = Object.fromEntries(new FormData(form));
                window.dispatchEvent(new CustomEvent("ledger-filters-changed", {
                    detail: { url: form.action + "?" + new URLSearchParams(params).toString() }
                }));
            }
        },
        clear() {
            const form = document.getElementById("filters-form");
            if (form) {
                const fromEl = form.elements["date_from"];
                const toEl = form.elements["date_to"];
                if (fromEl) fromEl.value = "";
                if (toEl) toEl.value = "";
                this.quickRange = "";
                this.direction = "";
                this.paymentMode = "";
                this.search = "";
                this.dispatchChange();
            }
        }
    }'
    class="rounded-xl border border-zinc-200 bg-white shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900"
>
    <form id="filters-form" method="GET" action="{{ $action }}" class="flex flex-wrap items-center gap-2 p-2">
        @foreach($preserveKeys as $key)
            @if(request()->has($key))
                <input type="hidden" name="{{ $key }}" value="{{ request($key) }}">
            @endif
        @endforeach

        <div class="flex items-center gap-1">
            <span class="text-xxs font-medium text-zinc-400 dark:text-zinc-500 mr-1">Quick:</span>
            <button type="button" x-on:click.prevent="quickRange = 'today'" :class="quickRange === 'today' ? 'bg-brand-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'" class="inline-flex h-7 items-center justify-center rounded-md px-2.5 text-xs font-medium transition-all">Today</button>
            <button type="button" x-on:click.prevent="quickRange = 'this_week'" :class="quickRange === 'this_week' ? 'bg-brand-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'" class="inline-flex h-7 items-center justify-center rounded-md px-2.5 text-xs font-medium transition-all">Week</button>
            <button type="button" x-on:click.prevent="quickRange = 'this_month'" :class="quickRange === 'this_month' ? 'bg-brand-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'" class="inline-flex h-7 items-center justify-center rounded-md px-2.5 text-xs font-medium transition-all">Month</button>
            <button type="button" x-on:click.prevent="quickRange = 'this_year'" :class="quickRange === 'this_year' ? 'bg-brand-600 text-white shadow-sm' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700'" class="inline-flex h-7 items-center justify-center rounded-md px-2.5 text-xs font-medium transition-all">Year</button>
        </div>

        <span class="h-4 w-px bg-zinc-200 dark:bg-zinc-700 mx-1"></span>

        <input
            type="text"
            name="search"
            x-model="search"
            placeholder="Search description..."
            value="{{ request('search') }}"
            class="h-7 w-40 rounded-md border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 text-xs"
        >

        <select name="direction" x-model="direction" class="h-7 rounded-md border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 text-xs">
            <option value="">All Directions</option>
            <option value="debit" {{ request('direction') === 'debit' ? 'selected' : '' }}>Debit</option>
            <option value="credit" {{ request('direction') === 'credit' ? 'selected' : '' }}>Credit</option>
        </select>

        <select name="payment_mode" x-model="paymentMode" class="h-7 rounded-md border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 text-xs">
            <option value="">All Modes</option>
            <option value="cash" {{ request('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
            <option value="bank_transfer" {{ request('payment_mode') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
            <option value="upi" {{ request('payment_mode') === 'upi' ? 'selected' : '' }}>UPI</option>
            <option value="cheque" {{ request('payment_mode') === 'cheque' ? 'selected' : '' }}>Cheque</option>
            <option value="other" {{ request('payment_mode') === 'other' ? 'selected' : '' }}>Other</option>
        </select>

        <div class="flex items-center gap-1">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="h-7 w-28 rounded-md border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 text-xs">
            <span class="text-xxs text-zinc-400">to</span>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="h-7 w-28 rounded-md border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 text-xs">
        </div>

        <button type="button" x-on:click="clear()" class="ml-auto inline-flex h-7 items-center justify-center rounded-md px-2.5 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
            Clear
        </button>
    </form>
</div>
