<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransportLogRequest extends FormRequest
{
    use TransportLogRules;

    public function authorize(): bool
    {
        return $this->user()->isActive() && $this->user()->can('create', TransportLog::class);
    }

    public function rules(): array
    {
        return $this->transportLogRules();
    }

    public function messages(): array
    {
        return [
            'vehicle_no.required' => 'The vehicle number is required.',
            'company.required' => 'The company name is required.',
            'transport_name.required' => 'The transport name is required.',
        ];
    }
}
