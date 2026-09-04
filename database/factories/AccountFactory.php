<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\FuelStation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'motor_parts_shop',
            'name' => fake()->company(),
            'linked_fuel_station_id' => null,
            'linked_driver_id' => null,
            'branch_id' => Branch::factory(),
            'contact_info' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'metadata' => null,
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    public function fuelStation(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'fuel_station',
                'linked_fuel_station_id' => FuelStation::factory(),
            ];
        });
    }

    public function motorPartsShop(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'motor_parts_shop',
        ]);
    }

    public function staff(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'staff',
                'linked_driver_id' => Driver::factory(),
            ];
        });
    }

    public function companyExpense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'company_expense',
        ]);
    }
}
