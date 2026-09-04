<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccountTransaction>
 */
class AccountTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'branch_id' => Branch::factory(),
            'direction' => fake()->randomElement(['debit', 'credit']),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'payment_mode' => fake()->randomElement(['cash', 'bank_transfer', 'upi', 'cheque', 'other']),
            'payment_plan' => fake()->randomElement(['full', 'emi', 'partial']),
            'installment_no' => fake()->optional()->numberBetween(1, 12),
            'installment_total' => fake()->optional()->numberBetween(1, 24),
            'reference_type' => fake()->optional()->randomElement(['transport_log', 'invoice', 'manual']),
            'reference_id' => fake()->optional()->numberBetween(1, 1000),
            'description' => fake()->optional()->sentence(),
            'attachment_path' => fake()->optional()->word() . '.' . fake()->fileExtension(),
            'transaction_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'running_balance' => 0,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'credit',
        ]);
    }

    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'debit',
        ]);
    }

    public function emi(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_plan' => 'emi',
                'installment_no' => fake()->numberBetween(1, 12),
                'installment_total' => fake()->numberBetween(12, 24),
            ];
        });
    }
}
