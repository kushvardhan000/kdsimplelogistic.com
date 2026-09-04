@extends('layouts.app')

@section('title', 'Edit Account · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Accounts' => route('accounts.index'), 'Edit ' . $account->name => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Edit Account</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Update details for {{ $account->name }}.</p>
    </div>

    <form method="POST" action="{{ route('accounts.update', $account) }}" class="mt-6 space-y-6" x-data='{ selectedType: @json(old("type", $account->type)) }'>
        @csrf
        @method('PUT')

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
                        <option value="fuel_station" {{ old('type', $account->type) === 'fuel_station' ? 'selected' : '' }}>Fuel Station</option>
                        <option value="motor_parts_shop" {{ old('type', $account->type) === 'motor_parts_shop' ? 'selected' : '' }}>Motor Parts Shop</option>
                        <option value="staff" {{ old('type', $account->type) === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="company_expense" {{ old('type', $account->type) === 'company_expense' ? 'selected' : '' }}>Company Expense</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $account->name) }}" required class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    @error('name')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="selectedType === 'fuel_station'" x-cloak x-transition>
                    <label for="linked_fuel_station_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Linked Fuel Station</label>
                    <select name="linked_fuel_station_id" id="linked_fuel_station_id" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="">Select fuel station</option>
                        @foreach($fuelStations as $station)
                            <option value="{{ $station->id }}" {{ old('linked_fuel_station_id', $account->linked_fuel_station_id) == $station->id ? 'selected' : '' }}>{{ $station->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="selectedType === 'staff'" x-cloak x-transition>
                    <label for="linked_driver_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Linked Driver</label>
                    <select name="linked_driver_id" id="linked_driver_id" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="">Select driver</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('linked_driver_id', $account->linked_driver_id) == $driver->id ? 'selected' : '' }}>{{ $driver->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="branch_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Branch <span class="text-red-500">*</span></label>
                    <select name="branch_id" id="branch_id" required class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                        <option value="">Select branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $account->branch_id) == $branch->id ? 'selected' : '' }}>{{ $branch->name }} ({{ $branch->code }})</option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contact_info" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Contact Info</label>
                    <input type="text" name="contact_info" id="contact_info" value="{{ old('contact_info', $account->contact_info) }}" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Address</label>
                    <textarea name="address" id="address" rows="2" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">{{ old('address', $account->address) }}</textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900">
                    <label for="is_active" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Active</label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('accounts.show', $account) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                Cancel
            </a>
            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-zinc-900 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200">
                Update Account
            </button>
        </div>
    </form>
@endsection
