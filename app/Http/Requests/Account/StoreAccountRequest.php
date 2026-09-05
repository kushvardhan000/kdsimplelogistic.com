<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isActive() && $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
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
            'aadhar_no' => ['nullable', 'string', 'max:20', 'unique:accounts,aadhar_no'],
            'driving_license_no' => ['nullable', 'string', 'max:50', 'regex:/^[A-Z0-9\-]{8,20}$/i'],
        ];

        if ($this->input('type') === 'staff') {
            $rules['aadhar_no'] = ['required', 'string', 'max:20', 'regex:/^\d{12}$/', 'unique:accounts,aadhar_no'];

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
        foreach (['linked_fuel_station_id', 'linked_driver_id', 'aadhar_no', 'driving_license_no'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->input('is_driver') === null) {
            $this->merge(['is_driver' => false]);
        }
    }
}
