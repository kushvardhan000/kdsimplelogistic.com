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
        return [
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
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['linked_fuel_station_id', 'linked_driver_id'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
