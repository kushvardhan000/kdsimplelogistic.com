<?php

namespace App\Http\Requests\Account;

use App\Models\CustomFieldOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isActive() && $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        $paymentModeValues = CustomFieldOption::forField('payment_mode')
            ->active()
            ->pluck('value')
            ->toArray();

        $paymentPlanValues = CustomFieldOption::forField('payment_plan')
            ->active()
            ->pluck('value')
            ->toArray();

        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'direction' => ['required', 'string', 'in:debit,credit'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_mode' => ['nullable', 'string', Rule::in($paymentModeValues)],
            'payment_plan' => ['nullable', 'string', Rule::in($paymentPlanValues)],
            'installment_no' => ['nullable', 'integer', 'min:1'],
            'installment_total' => ['nullable', 'integer', 'min:1'],
            'reference_type' => ['nullable', 'string', 'max:255'],
            'reference_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'attachment_path' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('installment_no', ['required', 'integer', 'min:1'], function ($input) {
            return $input->payment_plan === 'emi';
        });

        $validator->sometimes('installment_total', ['required', 'integer', 'min:1'], function ($input) {
            return $input->payment_plan === 'emi';
        });
    }
}
