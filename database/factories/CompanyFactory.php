<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' ' . fake()->randomElement(['Logistics', 'Transport', 'Freight', 'Cargo', 'Express']),
            'slug' => fake()->unique()->slug(3),
            'gstin' => fake()->optional()->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}'),
            'contact_info' => fake()->optional()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
