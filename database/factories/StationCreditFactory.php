<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\FuelStation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StationCredit>
 */
class StationCreditFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fuel_station_id' => FuelStation::factory(),
            'branch_id' => Branch::factory(),
            'amount' => fake()->randomFloat(2, 500, 50000),
            'reference_type' => fake()->optional()->randomElement(['transport_log', 'invoice', 'manual']),
            'reference_id' => fake()->optional()->numberBetween(1, 1000),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
        ];
    }
}
