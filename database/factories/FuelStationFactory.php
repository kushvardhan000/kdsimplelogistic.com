<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FuelStation>
 */
class FuelStationFactory extends Factory
{
    protected static array $brands = [
        'Indian Oil', 'Bharat Petroleum', 'HP Petrol', 'Reliance Jio-BP',
        'Nayara', 'Shell', 'Essar', 'Hindustan Petroleum',
    ];

    public function definition(): array
    {
        $brand = fake()->randomElement(self::$brands);
        $city = fake()->city();

        return [
            'name' => $brand . ' - ' . $city,
            'slug' => strtolower(str_replace([' ', '-'], '-', $brand)) . '-' . strtolower(str_replace(' ', '-', $city)),
            'contact_info' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'is_active' => true,
            'branch_id' => Branch::factory(),
        ];
    }
}
