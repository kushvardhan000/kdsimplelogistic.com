@props([
    'id' => 'transaction-modal',
    'title' => 'Add Transaction',
    'account' => null,
    'currentBalance' => 0,
    'transaction' => null,
    'action' => null,
    'branches' => [],
])

@php
    $isEdit = $transaction !== null;
    $balance = (float) $currentBalance;
    $isFuelStation = $account && $account->type === 'fuel_station';
@endphp

<div
    x-data='{
        open: false,
        direction: @json($isEdit ? $transaction->direction : "debit"),
        amount: @json($isEdit ? $transaction->amount : ""),
        paymentPlan: @json($isEdit ? $transaction->payment_plan : "full"),
        currentBalance: {{ $balance }},
        submitting: false,
        @if($isFuelStation && !$isEdit)
        transportLogSearch: "",
        transportLogResults: [],
        selectedTransportLog: null,
        searchingTransportLogs: false,
        dieselAdvance: 0,
        paidAmount: 0,
        remainingDue: 0,
        @endif
        get newBalance() {
            const amt = parseFloat(this.amount) || 0;
            if (this.direction === "debit") {
                return Math.round((this.currentBalance + amt) * 100) / 100;
            }
            return Math.round(Math.max(0, this.currentBalance - amt) * 100) / 100;
        },
        init() {
            this.$watch("direction", () => this.$refs.form?.resetValidation?.());
            this.$watch("amount", () => this.$refs.form?.resetValidation?.());
            this.$watch("paymentPlan", () => this.$refs.form?.resetValidation?.());
            @if($isFuelStation && !$isEdit)
            let searchTimeout;
            this.$watch("transportLogSearch", (value) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (value.length >= 2) {
                        this.searchTransportLogs(value);
                    } else {
                        this.transportLogResults = [];
                    }
                }, 300);
            });
            @endif
        },
        @if($isFuelStation && !$isEdit)
        searchTransportLogs(query) {
            this.searchingTransportLogs = true;
            fetch(`/accounts/transport-logs/search?q=${encodeURIComponent(query)}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
            .then(response => response.json())
            .then(data => {
                this.transportLogResults = data;
                this.searchingTransportLogs = false;
            })
            .catch(() => {
                this.transportLogResults = [];
                this.searchingTransportLogs = false;
            });
        },
        selectTransportLog(log) {
            this.selectedTransportLog = log;
            this.transportLogSearch = `#${log.id} - ${log.vehicle_no} (${log.date})`;
            this.transportLogResults = [];
            this.dieselAdvance = log.diesel_advance;
            this.paidAmount = log.paid_amount;
            this.remainingDue = log.remaining_due;
            if (this.amount === "" || this.amount === "0") {
                this.amount = String(log.remaining_due);
            }
            if (this.direction === "debit") {
                this.direction = "credit";
            }
        },
        clearTransportLog() {
            this.selectedTransportLog = null;
            this.transportLogSearch = "";
            this.dieselAdvance = 0;
            this.paidAmount = 0;
            this.remainingDue = 0;
            this.amount = "";
        },
        @endif
        formatCurrency(value) {
            const num = parseFloat(value) || 0;
            return num.toFixed(2);
        }
    }'
    x-on:open-modal.window="if ($event.detail === '{{ $id }}') { open = true; if (!{{ $isEdit ? 'true' : 'false' }}) { $refs.form?.reset(); direction = 'debit'; amount = ''; paymentPlan = 'full'; @if($isFuelStation && !$isEdit) clearTransportLog(); @endif } }"
    x-on:close-modal.window="if ($event.detail === '{{ $id }}') open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-4"
    role="dialog"
    aria-modal="true"
>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm" @click="open = false"></div>

    <div x-show="open" x-transition class="relative w-full max-w-lg rounded-none sm:rounded-2xl border border-zinc-200 bg-white shadow-premium-lg dark:border-zinc-800 dark:bg-zinc-900 sm:max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 sm:px-6 dark:border-zinc-800">
            <h3 class="text-base font-semibold text-zinc-950 dark:text-zinc-50">{{ $title }}</h3>
            <button type="button" @click="open = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" aria-label="Close">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form
            x-ref="form"
            method="POST"
            action="{{ $action ?? route('accounts.transactions.store', $account) }}"
            enctype="multipart/form-data"
            class="px-4 py-5 sm:px-6 space-y-4"
            @submit="submitting = true"
        >
            @if($isEdit)
                @method('PUT')
            @endif
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Direction</label>
                    <div class="mt-1.5 flex rounded-lg border border-zinc-300 dark:border-zinc-700 overflow-hidden">
                        <button type="button" x-on:click="direction = 'debit'" :class="direction === 'debit' ? 'bg-red-600 text-white' : 'bg-white text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'" class="flex-1 px-4 py-2 text-sm font-medium transition-colors hover:bg-red-700 dark:hover:bg-red-800">
                            Debit (+ owed)
                        </button>
                        <button type="button" x-on:click="direction = 'credit'" :class="direction === 'credit' ? 'bg-emerald-600 text-white' : 'bg-white text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'" class="flex-1 px-4 py-2 text-sm font-medium transition-colors hover:bg-emerald-700 dark:hover:bg-emerald-800">
                            Credit (paid out)
                        </button>
                    </div>
                    <input type="hidden" name="direction" :value="direction">
                    @error('direction')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="branch_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Branch <span class="text-red-500">*</span></label>
                    <select name="branch_id" id="branch_id" required class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="">Select branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                @if($isFuelStation && !$isEdit)
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Link to Transport Log</label>
                    <div class="mt-1.5 relative">
                        <input type="text"
                               x-model="transportLogSearch"
                               placeholder="Search by vehicle no, logsheet no, or date..."
                               autocomplete="off"
                               class="block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">

                        <div x-show="searchingTransportLogs" class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="h-5 w-5 animate-spin text-zinc-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>

                        <div x-show="transportLogResults.length > 0"
                             x-transition
                             class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                            <template x-for="log in transportLogResults" :key="log.id">
                                <button type="button"
                                        @click="selectTransportLog(log)"
                                        class="flex w-full flex-col items-start px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800 focus:bg-zinc-50 dark:focus:bg-zinc-800 focus:outline-none">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100" x-text="`#${log.id} - ${log.vehicle_no}`"></span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400" x-text="`${log.date} | Logsheet: ${log.logsheet_no || 'N/A'} | Advance: ${log.diesel_advance}`"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div x-show="selectedTransportLog" x-transition class="mt-3 rounded-lg border border-brand-200 bg-brand-50 p-3 dark:border-brand-800 dark:bg-brand-950/30">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-brand-900 dark:text-brand-100">
                                    Transport Log #<span x-text="selectedTransportLog?.id"></span>
                                </p>
                                <p class="text-xs text-brand-700 dark:text-brand-300">
                                    Vehicle: <span x-text="selectedTransportLog?.vehicle_no"></span>
                                </p>
                                <p class="text-xs text-brand-700 dark:text-brand-300" x-show="selectedTransportLog?.driver_name">
                                    Driver: <span x-text="selectedTransportLog?.driver_name"></span>
                                </p>
                                <p class="text-xs text-brand-700 dark:text-brand-300" x-show="selectedTransportLog?.destination">
                                    Destination: <span x-text="selectedTransportLog?.destination"></span>
                                </p>
                                <p class="text-xs text-brand-700 dark:text-brand-300">
                                    Diesel Advance: <span x-text="formatCurrency(dieselAdvance)"></span>
                                </p>
                                <p class="text-xs text-brand-700 dark:text-brand-300">
                                    Already Paid: <span x-text="formatCurrency(paidAmount)"></span>
                                </p>
                                <p class="text-xs font-semibold text-brand-900 dark:text-brand-100">
                                    Remaining Due: <span x-text="formatCurrency(remainingDue)"></span>
                                </p>
                            </div>
                            <button type="button" @click="clearTransportLog()" class="text-brand-600 hover:text-brand-800 dark:text-brand-400 dark:hover:text-brand-200">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <input type="hidden" name="reference_type" value="{{ \App\Models\TransportLog::class }}">
                        <input type="hidden" name="reference_id" :value="selectedTransportLog?.id || ''">
                    </div>
                </div>
                @endif

                <div class="sm:col-span-2">
                    <label for="amount" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Amount <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="amount" id="amount" x-model="amount" required class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    @error('amount')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">New Balance Preview</label>
                    <div class="mt-1.5 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/50">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Current: </span>
                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($balance, 2) }}</span>
                        <span class="mx-2 text-zinc-300 dark:text-zinc-600">→</span>
                        <span class="text-sm font-semibold" :class="direction === 'debit' ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'" x-text="newBalance.toFixed(2)"></span>
                    </div>
                </div>

                <div>
                    <label for="payment_mode" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Payment Mode</label>
                    <select name="payment_mode" id="payment_mode" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="">Select mode</option>
                        <option value="cash" {{ ($isEdit && $transaction->payment_mode === 'cash') ? 'selected' : '' }}>Cash</option>
                        <option value="bank_transfer" {{ ($isEdit && $transaction->payment_mode === 'bank_transfer') ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="upi" {{ ($isEdit && $transaction->payment_mode === 'upi') ? 'selected' : '' }}>UPI</option>
                        <option value="cheque" {{ ($isEdit && $transaction->payment_mode === 'cheque') ? 'selected' : '' }}>Cheque</option>
                        <option value="other" {{ ($isEdit && $transaction->payment_mode === 'other') ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label for="payment_plan" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Payment Plan</label>
                    <select name="payment_plan" id="payment_plan" x-model="paymentPlan" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="full">Full</option>
                        <option value="emi" {{ ($isEdit && $transaction->payment_plan === 'emi') ? 'selected' : '' }}>EMI</option>
                        <option value="partial" {{ ($isEdit && $transaction->payment_plan === 'partial') ? 'selected' : '' }}>Partial</option>
                    </select>
                </div>

                <div x-show="paymentPlan === 'emi'" x-cloak x-transition class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="installment_no" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Installment No.</label>
                        <input type="number" name="installment_no" id="installment_no" value="{{ $isEdit ? $transaction->installment_no : '' }}" min="1" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    </div>
                    <div>
                        <label for="installment_total" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Total Installments</label>
                        <input type="number" name="installment_total" id="installment_total" value="{{ $isEdit ? $transaction->installment_total : '' }}" min="1" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label for="transaction_date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Transaction Date <span class="text-red-500">*</span></label>
                    <input type="date" name="transaction_date" id="transaction_date" value="{{ $isEdit ? $transaction->transaction_date->format('Y-m-d') : old('transaction_date', now()->format('Y-m-d')) }}" required class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    @error('transaction_date')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                    <textarea name="description" id="description" rows="2" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">{{ $isEdit ? $transaction->description : '' }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label for="attachment" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Attachment</label>
                    <input type="file" name="attachment" id="attachment" class="mt-1.5 block w-full text-sm text-zinc-500 dark:text-zinc-400 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-50 file:px-4 file:py-2 file:text-xs file:font-medium file:text-zinc-700 hover:file:bg-zinc-100 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700">
                    @if($isEdit && $transaction->attachment_path)
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Current: {{ basename($transaction->attachment_path) }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <button type="button" @click="open = false" :disabled="submitting" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-50 disabled:cursor-not-allowed dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                    Cancel
                </button>
                <button type="submit" :disabled="submitting" class="inline-flex h-9 items-center justify-center rounded-lg bg-zinc-900 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-zinc-800 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $isEdit ? 'Update Transaction' : 'Add Transaction' }}
                </button>
            </div>
        </form>
    </div>
</div>
