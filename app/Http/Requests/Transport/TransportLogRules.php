<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

trait TransportLogRules
{
    protected function transportLogRules(): array
    {
        $decimal = ['nullable', 'numeric', 'min:0', 'max:9999999999.99'];

        return [
            'date' => ['required', 'date'],
            'vehicle_no' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'transport_name' => ['required', 'string', 'max:255'],
            'logsheet_no' => ['nullable', 'string', 'max:100'],
            'destination' => ['required', 'string', 'max:255'],
            'km' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'weight' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'to_bb_sale' => $decimal,
            'paid_sale' => $decimal,
            'to_pay' => $decimal,
            'freight' => $decimal,
            'loading' => $decimal,
            'unloading' => $decimal,
            'dd' => $decimal,
            'tempu_expense' => $decimal,
            'commission' => $decimal,
            'diesel_advance' => $decimal,
            'cash_advance' => $decimal,
            'payment' => $decimal,
            'fuel_station_id' => ['nullable', 'integer', 'exists:fuel_stations,id'],
            'fuel_station_name' => ['nullable', 'string', 'max:255'],
            'fuel_station_balance' => $decimal,
            'clearing_date' => ['nullable', 'date', 'after_or_equal:date'],
            'detail' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'mileage' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'dtg_office_expense' => $decimal,
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['vehicle_no' => trim((string) $this->input('vehicle_no', ''))]);
    }
}
