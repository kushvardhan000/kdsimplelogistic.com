@props([
    'id' => 'transaction-modal',
    'title' => 'Add Transaction',
    'account' => null,
    'currentBalance' => 0,
    'transaction' => null,
    'action' => null,
    'branches' => [],
    'paymentModes' => [],
    'paymentPlans' => [],
])

@php
    $isEdit = $transaction !== null;
    $balance = (float) $currentBalance;
    $isFuelStation = $account && $account->type === 'fuel_station';
@endphp

<div
    x-data="{
        open: false,

        isEdit: @js($isEdit),

        direction: @js($isEdit ? $transaction->direction : 'debit'),
        amount: @js($isEdit ? (string) $transaction->amount : ''),
        paymentPlan: @js($isEdit ? ($transaction->payment_plan ?? 'full') : 'full'),

        currentBalance: @js($balance),
        submitting: false,

        @if($isFuelStation && !$isEdit)
        transportLogSearch: '',
        transportLogResults: [],
        selectedTransportLog: null,
        searchingTransportLogs: false,

        dieselAdvance: 0,
        paidAmount: 0,
        remainingDue: 0,

        searchDropdownTop: 0,
        searchDropdownLeft: 0,
        searchDropdownWidth: 0,

        searchTimeout: null,
        _searchReposition: null,
        @endif

        get newBalance() {
            const amt = Number.parseFloat(this.amount) || 0;

            if (this.direction === 'debit') {
                return Math.round((this.currentBalance + amt) * 100) / 100;
            }

            return Math.round(Math.max(0, this.currentBalance - amt) * 100) / 100;
        },

        init() {
            this.$watch('direction', () => {
                this.$refs.form?.resetValidation?.();
            });

            this.$watch('amount', () => {
                this.$refs.form?.resetValidation?.();
            });

            this.$watch('paymentPlan', () => {
                this.$refs.form?.resetValidation?.();
            });

            @if($isFuelStation && !$isEdit)

            this.$watch('transportLogSearch', (value) => {
                clearTimeout(this.searchTimeout);

                if (!value || value.trim().length < 2) {
                    this.transportLogResults = [];
                    return;
                }

                this.searchTimeout = setTimeout(() => {
                    this.searchTransportLogs(value.trim());
                }, 300);
            });

            this._searchReposition = () => {
                if (this.transportLogResults.length > 0) {
                    this.positionSearchDropdown();
                }
            };

            this.$watch('open', (value) => {
                if (value) {
                    window.addEventListener(
                        'scroll',
                        this._searchReposition,
                        true
                    );

                    window.addEventListener(
                        'resize',
                        this._searchReposition
                    );

                    if (this.transportLogResults.length > 0) {
                        this.$nextTick(() => {
                            this.positionSearchDropdown();
                        });
                    }
                } else {
                    window.removeEventListener(
                        'scroll',
                        this._searchReposition,
                        true
                    );

                    window.removeEventListener(
                        'resize',
                        this._searchReposition
                    );
                }
            });

            @endif
        },

        resetForm() {
            this.submitting = false;

            this.direction = 'debit';
            this.amount = '';
            this.paymentPlan = 'full';

            @if($isFuelStation && !$isEdit)
            this.clearTransportLog();
            @endif

            this.$nextTick(() => {
                this.$refs.form?.resetValidation?.();
            });
        },

        closeModal() {
            this.open = false;
            this.submitting = false;

            @if($isFuelStation && !$isEdit)
            this.transportLogResults = [];
            this.searchingTransportLogs = false;
            @endif
        },

        @if($isFuelStation && !$isEdit)

        async searchTransportLogs(query) {
            this.searchingTransportLogs = true;

            try {
                const response = await fetch(
                    `/accounts/transport-logs/search?q=${encodeURIComponent(query)}`,
                    {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to search transport logs.');
                }

                const data = await response.json();

                this.transportLogResults = Array.isArray(data)
                    ? data
                    : [];

                await this.$nextTick();

                if (this.transportLogResults.length > 0) {
                    this.positionSearchDropdown();
                }
            } catch (error) {
                console.error('Transport log search error:', error);

                this.transportLogResults = [];
            } finally {
                this.searchingTransportLogs = false;
            }
        },

        positionSearchDropdown() {
            const input = this.$refs.searchInput;

            if (!input) {
                return;
            }

            const rect = input.getBoundingClientRect();

            this.searchDropdownTop = rect.bottom;
            this.searchDropdownLeft = rect.left;
            this.searchDropdownWidth = rect.width;
        },

        selectTransportLog(log) {
            this.selectedTransportLog = log;

            this.transportLogSearch =
                `#${log.id} - ${log.vehicle_no} (${log.date})`;

            this.transportLogResults = [];

            this.dieselAdvance = Number(log.diesel_advance) || 0;
            this.paidAmount = Number(log.paid_amount) || 0;
            this.remainingDue = Number(log.remaining_due) || 0;

            if (
                this.amount === '' ||
                this.amount === '0' ||
                Number(this.amount) === 0
            ) {
                this.amount = String(this.remainingDue);
            }

            this.direction = 'credit';
        },

        clearTransportLog() {
            this.selectedTransportLog = null;
            this.transportLogSearch = '';
            this.transportLogResults = [];

            this.dieselAdvance = 0;
            this.paidAmount = 0;
            this.remainingDue = 0;

            this.amount = '';
        },

        @endif

        formatCurrency(value) {
            const num = Number.parseFloat(value) || 0;

            return num.toFixed(2);
        }
    }"

    x-on:open-modal.window="
        if ($event.detail === @js($id)) {
            open = true;

            if (!isEdit) {
                resetForm();
            }
        }
    "

    x-on:close-modal.window="
        if ($event.detail === @js($id)) {
            closeModal();
        }
    "

    x-show="open"
    x-cloak

    class="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-4"

    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition.opacity
        class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm"
        @click="closeModal()"
    ></div>

    {{-- Modal --}}
    <div
        x-show="open"
        x-transition
        @click.stop
        class="relative flex w-full max-w-lg flex-col rounded-none border border-zinc-200 bg-white shadow-premium-lg dark:border-zinc-800 dark:bg-zinc-900 sm:max-h-[90vh] sm:rounded-2xl"
    >
        {{-- Header --}}
        <div
            class="flex shrink-0 items-center justify-between border-b border-zinc-200 px-4 py-4 sm:px-6 dark:border-zinc-800"
        >
            <h3
                id="{{ $id }}-title"
                class="text-base font-semibold text-zinc-950 dark:text-zinc-50"
            >
                {{ $title }}
            </h3>

            <button
                type="button"
                @click="closeModal()"
                :disabled="submitting"
                class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                aria-label="Close"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <form
            x-ref="form"
            method="POST"
            action="{{ $action ?? route('accounts.transactions.store', $account) }}"
            enctype="multipart/form-data"
            class="space-y-4 overflow-y-auto px-4 py-5 sm:px-6"
            @submit="submitting = true"
        >
            @csrf

            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">

                {{-- Direction --}}
                <div class="sm:col-span-2">
                    <label
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Direction
                    </label>

                    <div
                        class="mt-1.5 flex overflow-hidden rounded-lg border border-zinc-300 dark:border-zinc-700"
                    >
                        <button
                            type="button"
                            @click="direction = 'debit'"
                            :class="
                                direction === 'debit'
                                    ? 'bg-red-600 text-white hover:bg-red-700'
                                    : 'bg-white text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'
                            "
                            class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors"
                        >
                            Debit (+ owed)
                        </button>

                        <button
                            type="button"
                            @click="direction = 'credit'"
                            :class="
                                direction === 'credit'
                                    ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                                    : 'bg-white text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'
                            "
                            class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors"
                        >
                            Credit (paid out)
                        </button>
                    </div>

                    <input
                        type="hidden"
                        name="direction"
                        :value="direction"
                    >

                    @error('direction')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Branch --}}
                <div>
                    <label
                        for="{{ $id }}-branch_id"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Branch
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="branch_id"
                        id="{{ $id }}-branch_id"
                        required
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                    >
                        <option value="">Select branch</option>

                        @foreach($branches as $branch)
                            <option
                                value="{{ $branch->id }}"
                                @selected(
                                    old(
                                        'branch_id',
                                        $isEdit ? $transaction->branch_id : ''
                                    ) == $branch->id
                                )
                            >
                                {{ $branch->name }} ({{ $branch->code }})
                            </option>
                        @endforeach
                    </select>

                    @error('branch_id')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Transport Log Search --}}
                @if($isFuelStation && !$isEdit)
                    <div class="sm:col-span-2">
                        <label
                            for="{{ $id }}-transport-log-search"
                            class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Link to Transport Log
                        </label>

                        <div class="relative mt-1.5">
                            <input
                                type="text"
                                id="{{ $id }}-transport-log-search"
                                x-model="transportLogSearch"
                                x-ref="searchInput"
                                placeholder="Search by vehicle no, logsheet no, or date..."
                                autocomplete="off"
                                class="block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                            >

                            {{-- Loading --}}
                            <div
                                x-show="searchingTransportLogs"
                                x-cloak
                                class="absolute inset-y-0 right-0 flex items-center pr-3"
                            >
                                <svg
                                    class="h-5 w-5 animate-spin text-zinc-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>

                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                            </div>

                            {{-- Search Results --}}
                            <div
                                x-show="transportLogResults.length > 0"
                                x-cloak
                                x-transition
                                class="fixed z-[60] max-h-60 overflow-auto rounded-lg border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
                                :style="{
                                    top: searchDropdownTop + 'px',
                                    left: searchDropdownLeft + 'px',
                                    width: searchDropdownWidth + 'px'
                                }"
                            >
                                <template
                                    x-for="log in transportLogResults"
                                    :key="log.id"
                                >
                                    <button
                                        type="button"
                                        @click="selectTransportLog(log)"
                                        class="flex w-full flex-col items-start px-4 py-3 text-left transition-colors hover:bg-zinc-50 focus:bg-zinc-50 focus:outline-none dark:hover:bg-zinc-800 dark:focus:bg-zinc-800"
                                    >
                                        <span
                                            class="text-sm font-medium text-zinc-900 dark:text-zinc-100"
                                            x-text="`#${log.id} - ${log.vehicle_no}`"
                                        ></span>

                                        <span
                                            class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400"
                                            x-text="`${log.date} | Logsheet: ${log.logsheet_no || 'N/A'} | Advance: ${formatCurrency(log.diesel_advance)}`"
                                        ></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Selected Transport Log --}}
                        <div
                            x-show="selectedTransportLog"
                            x-cloak
                            x-transition
                            class="mt-3 rounded-lg border border-brand-200 bg-brand-50 p-3 dark:border-brand-800 dark:bg-brand-950/30"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-brand-900 dark:text-brand-100">
                                        Transport Log #

                                        <span
                                            x-text="selectedTransportLog?.id"
                                        ></span>
                                    </p>

                                    <p class="text-xs text-brand-700 dark:text-brand-300">
                                        Vehicle:

                                        <span
                                            x-text="selectedTransportLog?.vehicle_no"
                                        ></span>
                                    </p>

                                    <p
                                        x-show="selectedTransportLog?.driver_name"
                                        class="text-xs text-brand-700 dark:text-brand-300"
                                    >
                                        Driver:

                                        <span
                                            x-text="selectedTransportLog?.driver_name"
                                        ></span>
                                    </p>

                                    <p
                                        x-show="selectedTransportLog?.destination"
                                        class="text-xs text-brand-700 dark:text-brand-300"
                                    >
                                        Destination:

                                        <span
                                            x-text="selectedTransportLog?.destination"
                                        ></span>
                                    </p>

                                    <p class="text-xs text-brand-700 dark:text-brand-300">
                                        Diesel Advance:

                                        <span
                                            x-text="formatCurrency(dieselAdvance)"
                                        ></span>
                                    </p>

                                    <p class="text-xs text-brand-700 dark:text-brand-300">
                                        Already Paid:

                                        <span
                                            x-text="formatCurrency(paidAmount)"
                                        ></span>
                                    </p>

                                    <p class="text-xs font-semibold text-brand-900 dark:text-brand-100">
                                        Remaining Due:

                                        <span
                                            x-text="formatCurrency(remainingDue)"
                                        ></span>
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    @click="clearTransportLog()"
                                    class="shrink-0 rounded-md p-1 text-brand-600 transition-colors hover:bg-brand-100 hover:text-brand-800 dark:text-brand-400 dark:hover:bg-brand-900/50 dark:hover:text-brand-200"
                                    aria-label="Clear transport log"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <input
                                type="hidden"
                                name="reference_type"
                                value="{{ \App\Models\TransportLog::class }}"
                            >

                            <input
                                type="hidden"
                                name="reference_id"
                                :value="selectedTransportLog?.id || ''"
                            >
                        </div>
                    </div>
                @endif

                {{-- Amount --}}
                <div class="sm:col-span-2">
                    <label
                        for="{{ $id }}-amount"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Amount
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="amount"
                        id="{{ $id }}-amount"
                        x-model="amount"
                        required
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                    >

                    @error('amount')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- New Balance --}}
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        New Balance Preview
                    </label>

                    <div
                        class="mt-1.5 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/50"
                    >
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            Current:
                        </span>

                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ number_format($balance, 2) }}
                        </span>

                        <span class="mx-2 text-zinc-300 dark:text-zinc-600">
                            →
                        </span>

                        <span
                            class="text-sm font-semibold"
                            :class="
                                direction === 'debit'
                                    ? 'text-red-600 dark:text-red-400'
                                    : 'text-emerald-600 dark:text-emerald-400'
                            "
                            x-text="newBalance.toFixed(2)"
                        ></span>
                    </div>
                </div>

                {{-- Payment Mode --}}
                <div>
                    <label
                        for="{{ $id }}-payment-mode"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Payment Mode
                    </label>

                    <select
                        name="payment_mode"
                        id="{{ $id }}-payment-mode"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                    >
                        <option value="">Select mode</option>

                        @foreach($paymentModes as $mode)
                            <option
                                value="{{ $mode->value }}"
                                @selected(
                                    old(
                                        'payment_mode',
                                        $isEdit ? $transaction->payment_mode : ''
                                    ) === $mode->value
                                )
                            >
                                {{ $mode->label }}
                            </option>
                        @endforeach
                    </select>

                    @error('payment_mode')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Payment Plan --}}
                <div>
                    <label
                        for="{{ $id }}-payment-plan"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Payment Plan
                    </label>

                    <select
                        name="payment_plan"
                        id="{{ $id }}-payment-plan"
                        x-model="paymentPlan"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                    >
                        <option value="">Select plan</option>

                        @foreach($paymentPlans as $plan)
                            <option
                                value="{{ $plan->value }}"
                                @selected(
                                    old(
                                        'payment_plan',
                                        $isEdit ? ($transaction->payment_plan ?? 'full') : 'full'
                                    ) === $plan->value
                                )
                            >
                                {{ $plan->label }}
                            </option>
                        @endforeach
                    </select>

                    @error('payment_plan')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- EMI Fields --}}
                <div
                    x-show="paymentPlan === 'emi'"
                    x-cloak
                    x-transition
                    class="grid gap-4 sm:col-span-2 sm:grid-cols-2"
                >
                    <div>
                        <label
                            for="{{ $id }}-installment-no"
                            class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Installment No.
                        </label>

                        <input
                            type="number"
                            name="installment_no"
                            id="{{ $id }}-installment-no"
                            value="{{ old('installment_no', $isEdit ? $transaction->installment_no : '') }}"
                            min="1"
                            class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                        >

                        @error('installment_no')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="{{ $id }}-installment-total"
                            class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                        >
                            Total Installments
                        </label>

                        <input
                            type="number"
                            name="installment_total"
                            id="{{ $id }}-installment-total"
                            value="{{ old('installment_total', $isEdit ? $transaction->installment_total : '') }}"
                            min="1"
                            class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                        >

                        @error('installment_total')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Transaction Date --}}
                <div class="sm:col-span-2">
                    <label
                        for="{{ $id }}-transaction-date"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Transaction Date
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="transaction_date"
                        id="{{ $id }}-transaction-date"
                        value="{{ old(
                            'transaction_date',
                            $isEdit
                                ? $transaction->transaction_date->format('Y-m-d')
                                : now()->format('Y-m-d')
                        ) }}"
                        required
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                    >

                    @error('transaction_date')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="sm:col-span-2">
                    <label
                        for="{{ $id }}-description"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Description
                    </label>

                    <textarea
                        name="description"
                        id="{{ $id }}-description"
                        rows="2"
                        class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm"
                    >{{ old('description', $isEdit ? $transaction->description : '') }}</textarea>

                    @error('description')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Attachment --}}
                <div class="sm:col-span-2">
                    <label
                        for="{{ $id }}-attachment"
                        class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
                    >
                        Attachment
                    </label>

                    <input
                        type="file"
                        name="attachment"
                        id="{{ $id }}-attachment"
                        class="mt-1.5 block w-full text-sm text-zinc-500 dark:text-zinc-400 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-50 file:px-4 file:py-2 file:text-xs file:font-medium file:text-zinc-700 hover:file:bg-zinc-100 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700"
                    >

                    @if($isEdit && $transaction->attachment_path)
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Current:
                            {{ basename($transaction->attachment_path) }}
                        </p>
                    @endif

                    @error('attachment')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>

            {{-- Footer --}}
            <div
                class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800"
            >
                <button
                    type="button"
                    @click="closeModal()"
                    :disabled="submitting"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 transition-colors hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    :disabled="submitting"
                    class="inline-flex h-9 items-center justify-center rounded-lg bg-zinc-900 px-4 text-sm font-medium text-white shadow-premium-sm transition-colors hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200"
                >
                    <svg
                        x-show="submitting"
                        x-cloak
                        class="mr-2 h-4 w-4 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>

                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>

                    <span x-show="!submitting">
                        {{ $isEdit ? 'Update Transaction' : 'Add Transaction' }}
                    </span>

                    <span x-show="submitting" x-cloak>
                        {{ $isEdit ? 'Updating...' : 'Adding...' }}
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
