<?php

namespace App\Http\Requests\Transport;

use App\Models\TransportLog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransportLogRequest extends FormRequest
{
    use TransportLogRules;

    public function authorize(): bool
    {
        return $this->user()->isActive()
            && $this->user()->can('update', $this->route('transport_log'));
    }

    public function rules(): array
    {
        return $this->transportLogRules();
    }
}
