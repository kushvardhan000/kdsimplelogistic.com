<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Driver>
 */
class DriverFactory extends Factory
{
    public function definition(): array
    {
        $licenseNo = fake()->bothify('??########?');

        return [
            'name' => fake()->name(),
            'license_no' => fake()->unique()->regexify('[A-Z]{2}[0-9]{10}'),
            'phone' => fake()->optional()->phoneNumber(),
            'vehicle_id' => Vehicle::factory(),
            'is_active' => true,
            'branch_id' => Branch::factory(),
        ];
    }
}
