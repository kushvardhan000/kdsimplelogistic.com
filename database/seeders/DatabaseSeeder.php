<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Driver;
use App\Models\ExpenseCategory;
use App\Models\FuelStation;
use App\Models\StationBalance;
use App\Models\StationDebit;
use App\Models\TransportLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::factory()->count(3)->create();
        $companies = Company::factory()->count(12)->create();
        $vehicles = Vehicle::factory()->count(15)->recycle($branches)->create();
        $drivers = Driver::factory()->count(15)->recycle($branches)->recycle($vehicles)->create();
        ExpenseCategory::factory()->count(7)->create();
        $fuelStations = FuelStation::factory()->count(8)->recycle($branches)->create();

        foreach ($fuelStations as $station) {
            StationBalance::create([
                'fuel_station_id' => $station->id,
                'total_amount' => 0,
            ]);
        }

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@sls.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
            'branch_id' => $branches->first()->id,
        ]);

        $admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@sls.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'branch_id' => $branches->first()->id,
        ]);

        $admin2 = User::create([
            'name' => 'Admin Two',
            'email' => 'admin2@sls.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'branch_id' => $branches->first()->id,
        ]);

        $admins = [$admin1->id, $admin2->id];

        TransportLog::factory()
            ->count(60)
            ->recycle($branches)
            ->recycle($companies)
            ->recycle($vehicles)
            ->recycle($fuelStations)
            ->create()
            ->each(function (TransportLog $log) use ($admins) {
                $creator = $admins[array_rand($admins)];
                $log->created_by = $creator;
                $log->updated_by = $creator;
                $log->save();

                if ($log->diesel_advance > 0 && $log->fuel_station_id) {
                    StationDebit::create([
                        'fuel_station_id' => $log->fuel_station_id,
                        'branch_id' => $log->branch_id,
                        'amount' => $log->diesel_advance,
                        'reference_type' => 'transport_log',
                        'reference_id' => $log->id,
                        'notes' => 'Diesel advance for ' . $log->date,
                        'created_by' => $creator,
                        'updated_by' => $creator,
                        'date' => $log->date,
                    ]);
                }
            });

        ActivityLog::factory()
            ->count(30)
            ->create([
                'user_id' => $admins[array_rand($admins)],
            ]);

        $this->call(AccountSeeder::class);
    }
}
