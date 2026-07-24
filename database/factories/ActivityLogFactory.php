<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['store', 'update', 'delete']),
            'table_name' => fake()->randomElement(['transport_logs', 'users']),
            'subject_type' => fake()->randomElement(['transport_logs', 'users']),
            'record_id' => fake()->numberBetween(1, 100),
            'description' => fake()->sentence(),
            'created_at' => fake()->dateTimeBetween('-2 months', 'now'),
        ];
    }
}
