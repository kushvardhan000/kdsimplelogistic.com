<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    protected static array $types = ['Truck', 'Trailer', 'Tanker', 'Container', 'LCV', ' HCV'];

    public function definition(): array
    {
        $stateCode = fake()->randomElement(['MH', 'GJ', 'RJ', 'DL', 'KA', 'TN', 'UP', 'HR', 'PB', 'MP', 'BR', 'AP']);
        $districtCode = str_pad(fake()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT);
        $series = fake()->bothify('??');
        $number = fake()->unique()->numberBetween(1000, 9999);
        $optionalAlpha = fake()->optional()->randomElement(['A', 'B', 'C', 'D']);

        $vehicleNo = "{$stateCode}-{$districtCode}-{$series}-{$number}{$optionalAlpha}";

        return [
            'vehicle_no' => $vehicleNo,
            'owner_name' => fake()->name(),
            'type' => fake()->randomElement(self::$types),
            'capacity_kg' => fake()->randomFloat(2, 500, 50000),
            'mileage_baseline' => fake()->randomFloat(2, 3, 18),
            'is_active' => true,
            'branch_id' => Branch::factory(),
        ];
    }
}
