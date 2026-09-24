@extends('layouts.app')

@section('title', 'New Account · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Accounts' => route('accounts.index'), 'New Account' => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">New Account</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Create a new ledger account.</p>
    </div>

    <form method="POST" action="{{ route('accounts.store') }}" class="mt-6 space-y-6" x-data='{ selectedType: @json(old("type", "fuel_station")), isDriver: @json(old("is_driver", false)), linkedDriverId: @json(old("linked_driver_id", "")) }'>
        @csrf

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/30">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">Please fix the following errors:</p>
                <ul class="mt-2 list-disc list-inside text-sm text-red-700 dark:text-red-300">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-premium-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="text-base font-medium text-zinc-900 dark:text-zinc-100">Account Details</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Basic information for this account.</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="type" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Type <span class="text-red-500">*</span></label>
                    <select name="type" id="type" x-model="selectedType" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="fuel_station">Fuel Station</option>
                        <option value="motor_parts_shop">Motor Parts Shop</option>
                        <option value="staff">Staff</option>
                        <option value="company_expense">Company Expense</option>
                    </select>
                    @error('type')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    @error('name')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                @php
        $fuelStationsJson = $fuelStations->map(fn($s) => ['value' => $s->id, 'label' => $s->name])->values()->toJson();
        $branchesJson = $branches->map(fn($b) => ['value' => $b->id, 'label' => $b->name . ' (' . $b->code . ')'])->values()->toJson();
        $createFuelStationFields = [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            ['name' => 'branch_id', 'label' => 'Branch', 'type' => 'select', 'required' => false, 'options' => $branches->map(fn($b) => ['value' => $b->id, 'label' => $b->name . ' (' . $b->code . ')'])->toArray()],
            ['name' => 'contact_info', 'label' => 'Contact Info', 'type' => 'text', 'required' => false, 'maxlength' => 255],
            ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'required' => false],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => true],
        ];
        $createBranchFields = [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true, 'maxlength' => 255],
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true, 'maxlength' => 20, 'help' => 'Short alphanumeric code (e.g. DEL, MUM)'],
            ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'required' => false],
        ];
        $createFuelStationFieldsJson = json_encode($createFuelStationFields);
        $createBranchFieldsJson = json_encode($createBranchFields);
    @endphp
                <div x-show="selectedType === 'fuel_station'" x-cloak x-transition>
                    <x-ui.creatable-select
                        name="linked_fuel_station_id"
                        label="Linked Fuel Station"
                        :options="$fuelStationsJson"
                        :selected="old('linked_fuel_station_id')"
                        create-url="{{ route('entities.fuel-stations.store') }}"
                        fallback-url="{{ route('accounts.create', ['type' => 'fuel_station']) }}"
                        create-modal-id="add-fuel-station-modal"
                        create-modal-title="Add new fuel station"
                        :create-form-fields="$createFuelStationFieldsJson"
                        placeholder="Select fuel station..."
                    />
                </div>

                <div x-show="selectedType === 'staff'" x-cloak x-transition>
                    <label for="aadhar_no" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Aadhar No. <span class="text-red-500">*</span></label>
                    <input type="text" name="aadhar_no" id="aadhar_no" value="{{ old('aadhar_no') }}" maxlength="14" placeholder="XXXX XXXX XXXX" inputmode="numeric" x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 12).replace(/(\d{4})(?=\d)/g, '$1 ')" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">12-digit Aadhar number (e.g. 1234 5678 9012)</p>
                    @error('aadhar_no')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="selectedType === 'staff'" x-cloak x-transition>
                    <label for="linked_driver_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Linked Driver</label>
                    <select name="linked_driver_id" id="linked_driver_id" x-model="linkedDriverId" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="">Select driver</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('linked_driver_id') == $driver->id ? 'selected' : '' }}>{{ $driver->name }} ({{ $driver->license_no }})</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="selectedType === 'staff'" x-cloak x-transition class="sm:col-span-2">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_driver" id="is_driver" value="1" x-model="isDriver" {{ old('is_driver') ? 'checked' : '' }} class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900">
                        <label for="is_driver" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">This staff member is a driver</label>
                    </div>
                </div>

                <div x-show="selectedType === 'staff' && (isDriver || linkedDriverId)" x-cloak x-transition>
                    <label for="driving_license_no" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Driving License No. <span class="text-red-500">*</span></label>
                    <input type="text" name="driving_license_no" id="driving_license_no" value="{{ old('driving_license_no') }}" placeholder="e.g. DL1420110012345" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Alphanumeric, 8–20 characters</p>
                    @error('driving_license_no')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div x-data='{ bankAccountNo: @json(old("bank_account_no")), bankIfscCode: @json(old("bank_ifsc_code")), bankName: @json(old("bank_name")), get bankHint() { const filled = [this.bankAccountNo, this.bankIfscCode, this.bankName].filter(v => v && v.trim()).length; return filled > 0 && filled < 3; } }' x-show="$parent.selectedType === 'fuel_station' || $parent.selectedType === 'staff'" x-cloak x-transition class="sm:col-span-2">
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Bank Details</h4>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 mb-3">Optional. Provide bank details for fuel station payouts or staff salary transfers.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="bank_account_no" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Account Number</label>
                                <input type="text" name="bank_account_no" id="bank_account_no" x-model="bankAccountNo" placeholder="e.g. 123456789012" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                                @error('bank_account_no')
                                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="bank_ifsc_code" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">IFSC Code</label>
                                <input type="text" name="bank_ifsc_code" id="bank_ifsc_code" x-model="bankIfscCode" maxlength="11" placeholder="e.g. SBIN0001234" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Format: 4 letters + 0 + 6 alphanumeric (e.g. SBIN0001234)</p>
                                @error('bank_ifsc_code')
                                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="bank_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Bank Name</label>
                                <input type="text" name="bank_name" id="bank_name" x-model="bankName" placeholder="e.g. State Bank of India" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                                @error('bank_name')
                                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <p x-show="bankHint" x-cloak class="mt-3 text-xs text-blue-600 dark:text-blue-400">
                            You've entered some bank details. For complete records, consider filling in all three fields (account number, IFSC, and bank name).
                        </p>
                    </div>
                </div>

                <div>
                    <x-ui.creatable-select
                        name="branch_id"
                        label="Branch"
                        :options="$branchesJson"
                        :selected="old('branch_id')"
                        create-url="{{ route('entities.branches.store') }}"
                        fallback-url="{{ route('accounts.create') }}"
                        create-modal-id="add-branch-modal"
                        create-modal-title="Add new branch"
                        :create-form-fields="$createBranchFieldsJson"
                        placeholder="Select branch..."
                        required
                    />
                    @error('branch_id')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contact_info" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Contact Info</label>
                    <input type="text" name="contact_info" id="contact_info" value="{{ old('contact_info') }}" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Address</label>
                    <textarea name="address" id="address" rows="2" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">{{ old('address') }}</textarea>
                </div>

                <div>
                    <label for="opening_balance" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Opening Balance</label>
                    <input type="number" step="0.01" name="opening_balance" id="opening_balance" value="{{ old('opening_balance', 0) }}" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900">
                    <label for="is_active" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Active</label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('accounts.index') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                Cancel
            </a>
            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-zinc-900 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200">
                Create Account
            </button>
        </div>
    </form>
@endsection
