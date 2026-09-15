@php
    $numericFields = ['km', 'weight', 'to_bb_sale', 'paid_sale', 'to_pay', 'total_sale', 'freight', 'loading', 'unloading', 'dd', 'tempu_expense', 'commission', 'total_expense', 'profit', 'diesel_advance', 'cash_advance', 'total_advance', 'payment', 'fuel_station_balance', 'balance_vehicle_payment', 'mileage', 'dtg_office_expense'];
    $num = fn ($field) => old($field, $log ? ($log->$field ?? (in_array($field, $numericFields) ? 0 : '')) : (in_array($field, $numericFields) ? 0 : ''));

    $sections = [
        'Vehicle Information' => ['vehicle_no', 'company', 'destination', 'km', 'weight', 'mileage', 'logsheet_no'],
        'Transport Information' => ['transport_name', 'date', 'clearing_date', 'fuel_station_id', 'fuel_station_name', 'fuel_station_balance'],
        'Sales' => ['to_bb_sale', 'paid_sale', 'to_pay', 'total_sale'],
        'Expenses' => ['freight', 'loading', 'unloading', 'dd', 'tempu_expense', 'commission', 'dtg_office_expense', 'total_expense'],
        'Advance' => ['diesel_advance', 'cash_advance', 'total_advance'],
        'Payment' => ['payment'],
    ];

    $computedTooltip = [
        'total_sale' => '= To BB Sale + Paid Sale + To Pay',
        'total_expense' => '= Freight + Loading + Unloading + DD + Tempu Expense + Commission + DTG Office Expense',
        'total_advance' => '= Diesel Advance + Cash Advance',
        'profit' => '= Total Sale - Total Expense',
        'balance_vehicle_payment' => '= Total Sale - Payment',
    ];

    $hasPayments = false;
    $totalPaid = 0;
    $paymentCount = 0;
    $oldStationName = null;
    if ($log && $log->fuel_station_id) {
        $payments = \App\Models\AccountTransaction::where('reference_type', \App\Models\TransportLog::class)
            ->where('reference_id', $log->id)
            ->where('direction', 'credit')
            ->whereNull('deleted_at')
            ->get();
        $paymentCount = $payments->count();
        $totalPaid = $payments->sum('amount');
        $hasPayments = $paymentCount > 0;
        $oldStation = \App\Models\Account::where('type', 'fuel_station')
            ->where('linked_fuel_station_id', $log->fuel_station_id)
            ->first();
        $oldStationName = $oldStation?->name ?? ('Fuel Station #' . $log->fuel_station_id);
    }

    $fuelStationsJson = $fuelStations->map(fn($s) => ['value' => $s['id'], 'label' => $s['name'], 'branch' => $s['branch'], 'balance' => $s['current_balance']])->values()->toJson();
    $selectedFuelStationId = $log && $log->fuel_station_id ? (int) $log->fuel_station_id : null;
    $createFormFields = [
        ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 255],
        ['name' => 'branch_id', 'label' => 'Branch', 'type' => 'select', 'required' => false, 'options' => $branches->map(fn($b) => ['value' => $b->id, 'label' => $b->name . ' (' . $b->code . ')'])->toArray()],
        ['name' => 'contact_info', 'label' => 'Contact Info', 'type' => 'text', 'required' => false, 'maxlength' => 255],
        ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'required' => false],
        ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => true],
    ];
    $createFormFieldsJson = json_encode($createFormFields);
@endphp

<form method="POST" action="{{ isset($formAction) ? $formAction : route('transport-logs.store') }}" id="transport-form" x-data="transportForm" x-init="recalc()">
    @isset($formMethod) @method($formMethod) @endisset
    @csrf

    @if($log && $hasPayments)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">Warning: Fuel Station Reassignment Will Reverse Payments</h3>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    Changing the fuel station for this log will reverse {{ $paymentCount }} existing payment(s) totaling ₹{{ number_format($totalPaid, 2) }} currently recorded against <strong>{{ $oldStationName }}</strong>.
                    You will need to re-record these payments against the new station if applicable.
                </p>
                <label class="mt-2 flex items-center gap-2 text-sm text-amber-800 dark:text-amber-200">
                    <input type="checkbox" name="confirm_reassignment" value="1" class="rounded border-amber-300 text-amber-600 focus:ring-amber-500" required>
                    <span>I understand the payments will be reversed and I may need to re-record them.</span>
                </label>
            </div>
        </div>
    </div>
    @endif

<div class="space-y-8">
    @foreach($sections as $label => $fields)
        <fieldset class="space-y-4">
            <legend class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $label }}</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($fields as $field)
                    @if($field === 'fuel_station_id')
                        <div class="sm:col-span-2 lg:col-span-1">
                            <x-ui.creatable-select
                                name="fuel_station_id"
                                label="Fuel Station"
                                :options="$fuelStationsJson"
                                :selected="$selectedFuelStationId"
                                create-url="{{ route('entities.fuel-stations.store') }}"
                                fallback-url="{{ route('accounts.create', ['type' => 'fuel_station']) }}"
                                create-modal-id="add-fuel-station-modal"
                                create-modal-title="Add New Fuel Station"
                                :create-form-fields="$createFormFieldsJson"
                                sync-name="fuel_station_name"
                                placeholder="Search fuel station..."
                                :error="$errors->has('fuel_station_id')"
                            />
                            @error('fuel_station_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        @php
                            $type = match(true) {
                                in_array($field, ['date', 'clearing_date']) => 'date',
                                in_array($field, ['vehicle_no', 'company', 'destination', 'transport_name', 'fuel_station_name', 'logsheet_no']) => 'text',
                                default => 'number',
                            };
                            $readonly = in_array($field, ['total_sale', 'total_expense', 'total_advance', 'balance_vehicle_payment']);
                            $isRequired = in_array($field, ['date', 'vehicle_no', 'company', 'transport_name', 'destination', 'km', 'weight']);
                        @endphp
                        <x-ui.input
                            name="{{ $field }}"
                            label="{{ \Illuminate\Support\Str::headline($field) }}"
                            type="{{ $type }}"
                            :value="$num($field)"
                            :required="$isRequired"
                            :readonly="$readonly"
                            step="0.01"
                            @input="recalc()"
                            title="{{ $computedTooltip[$field] ?? '' }}"
                        />
                    @endif
                @endforeach
            </div>

    @if($label === 'Transport Information' && isset($log) && $log->fuel_station_id && (float) $log->diesel_advance > 0)
        @php
            $fuelSettlementService = app(\App\Services\FuelSettlementService::class);
            $fuelPaymentHistory = $fuelSettlementService->getPaymentHistoryForLog($log);
            $fuelPaymentStatus = $log->fuel_payment_status;
            $statusLabel = $fuelPaymentStatus ? ucfirst($fuelPaymentStatus) : 'Unpaid';
            $statusTextClass = match($fuelPaymentStatus) {
                'paid' => 'text-emerald-600 dark:text-emerald-400',
                'partial' => 'text-amber-600 dark:text-amber-400',
                'overpaid' => 'text-red-600 dark:text-red-400',
                default => 'text-zinc-500 dark:text-zinc-400',
            };
            $advance = (float) $log->diesel_advance;
            $paid = (float) $log->fuel_paid_amount;
            $remaining = max(0, $advance - $paid);
            $dueClass = $remaining > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400';
            $fuelStationAccount = \App\Models\Account::where('type', 'fuel_station')
                ->where('linked_fuel_station_id', $log->fuel_station_id)
                ->first();
        @endphp
        
        <div class="mt-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Fuel Settlement</p>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium {{ $statusTextClass }}">{{ $statusLabel }}</span>
                    @if($fuelStationAccount)
                        <a href="{{ route('accounts.show', $fuelStationAccount) }}" class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                            View full ledger →
                        </a>
                    @endif
                </div>
            </div>
            <div class="mt-2 space-y-1">
                <div class="flex justify-between text-xs">
                    <span class="text-zinc-600 dark:text-zinc-400">Advance</span>
                    <span class="tabular-nums">{{ number_format($advance, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-zinc-600 dark:text-zinc-400">Paid</span>
                    <span class="tabular-nums text-emerald-600 dark:text-emerald-400">+{{ number_format($paid, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-zinc-600 dark:text-zinc-400">Due</span>
                    <span class="tabular-nums {{ $dueClass }}">{{ number_format($remaining, 2) }}</span>
                </div>
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-1 flex justify-between text-sm font-semibold">
                    <span class="text-zinc-900 dark:text-zinc-100">Remaining Due</span>
                    <span class="tabular-nums {{ $dueClass }}">{{ number_format($remaining, 2) }}</span>
                </div>
            </div>
            @if($fuelPaymentHistory->count() > 0)
                <div class="mt-2 max-h-28 space-y-1.5 overflow-y-auto border-t border-zinc-200 pt-2 dark:border-zinc-700">
                    @foreach($fuelPaymentHistory as $txn)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $txn->transaction_date?->format('Y-m-d') }}</span>
                            <span class="tabular-nums text-emerald-600 dark:text-emerald-400">+{{ number_format($txn->amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
        </fieldset>
    @endforeach

        <fieldset class="space-y-4">
            <legend class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Profit Summary</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.input
                    name="profit"
                    label="Profit"
                    type="number"
                    :value="$num('profit')"
                    readonly
                    step="0.01"
                    x-ref="profit"
                    title="{{ $computedTooltip['profit'] }}"
                />
                <x-ui.input
                    name="balance_vehicle_payment"
                    label="Balance Vehicle Payment"
                    type="number"
                    :value="$num('balance_vehicle_payment')"
                    readonly
                    step="0.01"
                    title="{{ $computedTooltip['balance_vehicle_payment'] }}"
                />
            </div>
            <div class="mt-2">
                <span x-data="{
                    get profitClass() {
                        const el = document.getElementById('transport-form')?.elements['profit'];
                        const val = el ? parseFloat(el.value || 0) : 0;
                        if (val > 0) return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400';
                        if (val < 0) return 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400';
                        return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
                    },
                    get profitLabel() {
                        const el = document.getElementById('transport-form')?.elements['profit'];
                        const val = el ? parseFloat(el.value || 0) : 0;
                        if (val > 0) return 'Profit';
                        if (val < 0) return 'Loss';
                        return 'Break-even';
                    }
                }" x-bind:class="profitClass" class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" x-text="profitLabel"></span>
            </div>
        </fieldset>

        <fieldset class="space-y-4">
            <legend class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Live Breakdown</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Sale</p>
                    <div class="mt-2 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">To BB Sale</span>
                            <span class="tabular-nums" x-text="fmt('to_bb_sale')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Paid Sale</span>
                            <span class="tabular-nums" x-text="fmt('paid_sale')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">To Pay</span>
                            <span class="tabular-nums" x-text="fmt('to_pay')"></span>
                        </div>
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-1 flex justify-between text-sm font-semibold">
                            <span class="text-zinc-900 dark:text-zinc-100">Total Sale</span>
                            <span class="tabular-nums text-zinc-900 dark:text-zinc-100" x-text="fmt('total_sale')"></span>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Expense</p>
                    <div class="mt-2 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Freight</span>
                            <span class="tabular-nums" x-text="fmt('freight')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Loading</span>
                            <span class="tabular-nums" x-text="fmt('loading')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Unloading</span>
                            <span class="tabular-nums" x-text="fmt('unloading')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">DD</span>
                            <span class="tabular-nums" x-text="fmt('dd')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Tempu Expense</span>
                            <span class="tabular-nums" x-text="fmt('tempu_expense')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Commission</span>
                            <span class="tabular-nums" x-text="fmt('commission')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">DTG Office Expense</span>
                            <span class="tabular-nums" x-text="fmt('dtg_office_expense')"></span>
                        </div>
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-1 flex justify-between text-sm font-semibold">
                            <span class="text-zinc-900 dark:text-zinc-100">Total Expense</span>
                            <span class="tabular-nums text-zinc-900 dark:text-zinc-100" x-text="fmt('total_expense')"></span>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Profit</p>
                    <div class="mt-2 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Total Sale</span>
                            <span class="tabular-nums" x-text="fmt('total_sale')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Total Expense</span>
                            <span class="tabular-nums" x-text="fmt('total_expense')"></span>
                        </div>
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-1 flex justify-between text-sm font-semibold">
                            <span class="text-zinc-900 dark:text-zinc-100">Profit</span>
                            <span class="tabular-nums text-zinc-900 dark:text-zinc-100" x-text="fmt('profit')"></span>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Total Advance</p>
                    <div class="mt-2 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Diesel Advance</span>
                            <span class="tabular-nums" x-text="fmt('diesel_advance')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Cash Advance</span>
                            <span class="tabular-nums" x-text="fmt('cash_advance')"></span>
                        </div>
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-1 flex justify-between text-sm font-semibold">
                            <span class="text-zinc-900 dark:text-zinc-100">Total Advance</span>
                            <span class="tabular-nums text-zinc-900 dark:text-zinc-100" x-text="fmt('total_advance')"></span>
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Balance Vehicle Payment</p>
                    <div class="mt-2 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Total Sale</span>
                            <span class="tabular-nums" x-text="fmt('total_sale')"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-zinc-600 dark:text-zinc-400">Payment</span>
                            <span class="tabular-nums" x-text="fmt('payment')"></span>
                        </div>
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-1 flex justify-between text-sm font-semibold">
                            <span class="text-zinc-900 dark:text-zinc-100">Balance Vehicle Payment</span>
                            <span class="tabular-nums text-zinc-900 dark:text-zinc-100" x-text="fmt('balance_vehicle_payment')"></span>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>

        <fieldset class="space-y-4">
            <legend class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Remarks</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Detail</label>
                    <textarea name="detail" rows="3" class="block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">{{ old('detail', $log?->detail) }}</textarea>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Remarks</label>
                    <textarea name="remarks" rows="3" class="block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">{{ old('remarks', $log?->remarks) }}</textarea>
                </div>
            </div>
        </fieldset>

        <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-800">
            <a href="{{ route('transport-logs.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg px-4 text-sm font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                Cancel
            </a>
            <x-ui.button type="submit" variant="brand" size="md">
                {{ $log ? 'Update Log' : 'Save Log' }}
            </x-ui.button>
        </div>
    </div>
</form>
