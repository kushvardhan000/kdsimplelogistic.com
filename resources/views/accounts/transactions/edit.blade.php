@extends('layouts.app')

@section('title', 'Edit Transaction · Transport')

@section('content')
    <x-layout.breadcrumb :items="['Dashboard' => route('dashboard'), 'Accounts' => route('accounts.index'), $account->name => route('accounts.show', $account), 'Edit Transaction' => '#']" />

    <div class="border-b border-zinc-200 pb-5 dark:border-zinc-800">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Edit Transaction</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Update transaction for {{ $account->name }}.</p>
    </div>

    @php
    $paymentModes = \App\Models\CustomFieldOption::forField('payment_mode')->active()->ordered()->get();
    $paymentPlans = \App\Models\CustomFieldOption::forField('payment_plan')->active()->ordered()->get();
@endphp

<form method="POST" action="{{ route('accounts.transactions.update', [$account, $transaction]) }}" class="mt-6 space-y-6" x-data='{ direction: @json(old("direction", $transaction->direction ?? "debit")), paymentPlan: @json(old("payment_plan", $transaction->payment_plan ?? "full")) }' enctype="multipart/form-data">
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
            <h3 class="text-base font-medium text-zinc-900 dark:text-zinc-100">Transaction Details</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Modify the transaction amount, direction, and payment details.</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
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
                    <input type="hidden" name="direction" x-model="direction">
                </div>

                <div class="sm:col-span-2">
                    <label for="amount" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Amount <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', $transaction->amount) }}" required class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    @error('amount')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

<div>
    <label for="payment_mode" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Payment Mode</label>
    <select name="payment_mode" id="payment_mode" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
        <option value="">Select mode</option>
        @foreach($paymentModes as $mode)
            <option value="{{ $mode->value }}" {{ old('payment_mode', $transaction->payment_mode) === $mode->value ? 'selected' : '' }}>{{ $mode->label }}</option>
        @endforeach
    </select>
</div>

<div>
    <label for="payment_plan" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Payment Plan</label>
    <select name="payment_plan" id="payment_plan" x-model="paymentPlan" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
        <option value="">Select plan</option>
        @foreach($paymentPlans as $plan)
            <option value="{{ $plan->value }}" {{ old('payment_plan', $transaction->payment_plan) === $plan->value ? 'selected' : '' }}>{{ $plan->label }}</option>
        @endforeach
    </select>
</div>

                <div x-show="paymentPlan === 'emi'" x-cloak x-transition class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="installment_no" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Installment No.</label>
                        <input type="number" name="installment_no" id="installment_no" value="{{ old('installment_no', $transaction->installment_no) }}" min="1" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    </div>
                    <div>
                        <label for="installment_total" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Total Installments</label>
                        <input type="number" name="installment_total" id="installment_total" value="{{ old('installment_total', $transaction->installment_total) }}" min="1" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label for="transaction_date" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Transaction Date <span class="text-red-500">*</span></label>
                    <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">
                    @error('transaction_date')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                    <textarea name="description" id="description" rows="2" class="mt-1.5 block w-full rounded-lg border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 sm:text-sm">{{ old('description', $transaction->description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label for="attachment" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Attachment</label>
                    <input type="file" name="attachment" id="attachment" class="mt-1.5 block w-full text-sm text-zinc-500 dark:text-zinc-400 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-50 file:px-4 file:py-2 file:text-xs file:font-medium file:text-zinc-700 hover:file:bg-zinc-100 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700">
                    @if($transaction->attachment_path)
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Current: {{ basename($transaction->attachment_path) }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('accounts.show', $account) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-zinc-200 px-4 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/50">
                Cancel
            </a>
            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-zinc-900 px-4 text-sm font-medium text-white shadow-premium-sm hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200">
                Update Transaction
            </button>
        </div>
    </form>
@endsection
