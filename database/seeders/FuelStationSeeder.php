<?php

namespace Database\Seeders;

use App\Models\FuelStation;
use Illuminate\Database\Seeder;

class FuelStationSeeder extends Seeder
{
    public function run(): void
    {
        FuelStation::factory()
            ->count(8)
            ->create();
    }
}
