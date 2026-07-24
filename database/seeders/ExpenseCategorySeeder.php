<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Freight', 'Loading', 'Unloading', 'DD', 'Tempu Expense',
            'Commission', 'DTG Office Expense',
        ];

        foreach ($categories as $category) {
            ExpenseCategory::factory()->create([
                'name' => $category,
                'slug' => strtolower(str_replace(' ', '-', $category)),
            ]);
        }
    }
}
