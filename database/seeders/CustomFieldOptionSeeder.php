<?php

namespace Database\Seeders;

use App\Models\CustomFieldOption;
use Illuminate\Database\Seeder;

class CustomFieldOptionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $options = [
            ['payment_mode', 'Cash', 'cash', 10],
            ['payment_mode', 'Bank Transfer', 'bank_transfer', 20],
            ['payment_mode', 'UPI', 'upi', 30],
            ['payment_mode', 'Cheque', 'cheque', 40],
            ['payment_mode', 'Other', 'other', 50],
            ['payment_plan', 'Full', 'full', 10],
            ['payment_plan', 'EMI', 'emi', 20],
            ['payment_plan', 'Partial', 'partial', 30],
        ];

        foreach ($options as [$fieldKey, $label, $value, $sortOrder]) {
            CustomFieldOption::query()->firstOrCreate(
                ['field_key' => $fieldKey, 'value' => $value],
                [
                    'label' => $label,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
