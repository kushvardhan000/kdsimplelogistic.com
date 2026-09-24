<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $account = $this->route('account');
        $aadharUnique = Rule::unique('accounts', 'aadhar_no')->ignore($account->id ?? null);

        $rules = [
            'type' => ['required', 'string', 'in:fuel_station,motor_parts_shop,staff,company_expense'],
            'name' => ['required', 'string', 'max:255'],
            'linked_fuel_station_id' => ['nullable', 'integer', 'exists:fuel_stations,id'],
            'linked_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'contact_info' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'aadhar_no' => ['nullable', 'string', 'max:20', $aadharUnique],
            'driving_license_no' => ['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9\-]{8,20}$/i'],
            'bank_account_no' => ['nullable', 'string', 'max:255'],
            'bank_ifsc_code' => ['nullable', 'string', 'max:11', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/i'],
            'bank_name' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->input('type') === 'staff') {
            $rules['aadhar_no'] = ['required', 'string', 'max:20', 'regex:/^\d{12}$/', $aadharUnique];

            $drivingRules = ['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9\-]{8,20}$/i'];

            $isDriver = $this->boolean('is_driver') || $this->filled('linked_driver_id');
            if ($isDriver) {
                $drivingRules[] = 'required';
            }

            $rules['driving_license_no'] = $drivingRules;
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('aadhar_no')) {
            $this->merge(['aadhar_no' => preg_replace('/\s+/', '', (string) $this->input('aadhar_no'))]);
        }

        foreach (['linked_fuel_station_id', 'linked_driver_id', 'aadhar_no', 'driving_license_no', 'bank_account_no', 'bank_ifsc_code', 'bank_name'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->input('is_driver') === null) {
            $this->merge(['is_driver' => false]);
        }

        if ($this->filled('bank_ifsc_code')) {
            $this->merge(['bank_ifsc_code' => strtoupper(trim($this->input('bank_ifsc_code')))]);
        }
    }
}
