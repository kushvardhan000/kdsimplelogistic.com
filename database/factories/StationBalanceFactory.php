<?php

namespace Database\Factories;

use App\Models\FuelStation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StationBalance>
 */
class StationBalanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fuel_station_id' => FuelStation::factory(),
            'total_amount' => fake()->randomFloat(2, -5000, 50000),
        ];
    }

    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => fake()->randomFloat(2, 1000, 50000),
        ]);
    }

    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_amount' => fake()->randomFloat(2, -5000, -100),
        ]);
    }
}
