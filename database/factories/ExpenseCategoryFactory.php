<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    protected static array $names = [
        'Freight', 'Loading', 'Unloading', 'DD', 'Tempu Expense',
        'Commission', 'DTG Office Expense',
    ];

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(self::$names);

        return [
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'is_active' => true,
        ];
    }
}
