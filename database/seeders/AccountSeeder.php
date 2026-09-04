<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Driver;
use App\Models\FuelStation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $branches = DB::table('branches')->get();
        $fuelStations = DB::table('fuel_stations')->get();
        $drivers = DB::table('drivers')->get();
        $users = DB::table('users')->get()->pluck('id');
        $creator = $users->random();

        if ($fuelStations->isEmpty() || $drivers->isEmpty() || $branches->isEmpty()) {
            return;
        }

        $this->command->info('Seeding accounts and transactions...');

        $accounts = [];

        foreach ($fuelStations->take(4) as $station) {
            $branch = $branches->random();
            $account = Account::create([
                'type' => 'fuel_station',
                'name' => $station->name,
                'linked_fuel_station_id' => $station->id,
                'linked_driver_id' => null,
                'branch_id' => $branch->id,
                'contact_info' => fake()->optional()->phoneNumber(),
                'address' => fake()->optional()->address(),
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'metadata' => null,
                'created_by' => $creator,
                'updated_by' => $creator,
            ]);
            $accounts[] = ['model' => $account, 'branch_id' => $branch->id];
        }

        for ($i = 0; $i < 3; $i++) {
            $branch = $branches->random();
            $account = Account::create([
                'type' => 'motor_parts_shop',
                'name' => fake()->company() . ' Motors',
                'linked_fuel_station_id' => null,
                'linked_driver_id' => null,
                'branch_id' => $branch->id,
                'contact_info' => fake()->optional()->phoneNumber(),
                'address' => fake()->optional()->address(),
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'metadata' => null,
                'created_by' => $creator,
                'updated_by' => $creator,
            ]);
            $accounts[] = ['model' => $account, 'branch_id' => $branch->id];
        }

        foreach ($drivers->take(5) as $driver) {
            $branch = $branches->random();
            $account = Account::create([
                'type' => 'staff',
                'name' => $driver->name . ' (Staff Account)',
                'linked_fuel_station_id' => null,
                'linked_driver_id' => $driver->id,
                'branch_id' => $branch->id,
                'contact_info' => fake()->optional()->phoneNumber(),
                'address' => null,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'metadata' => null,
                'created_by' => $creator,
                'updated_by' => $creator,
            ]);
            $accounts[] = ['model' => $account, 'branch_id' => $branch->id];
        }

        $expenseAccounts = [
            ['name' => 'Office Rent', 'branch_id' => $branches->random()->id],
            ['name' => 'Miscellaneous', 'branch_id' => $branches->random()->id],
        ];

        foreach ($expenseAccounts as $expense) {
            $account = Account::create([
                'type' => 'company_expense',
                'name' => $expense['name'],
                'linked_fuel_station_id' => null,
                'linked_driver_id' => null,
                'branch_id' => $expense['branch_id'],
                'contact_info' => null,
                'address' => null,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
                'metadata' => null,
                'created_by' => $creator,
                'updated_by' => $creator,
            ]);
            $accounts[] = ['model' => $account, 'branch_id' => $expense['branch_id']];
        }

        foreach ($accounts as $accountData) {
            $account = $accountData['model'];
            $branchId = $accountData['branch_id'];

            $txnCount = fake()->numberBetween(5, 10);
            $balance = (float) $account->opening_balance;

            for ($i = 0; $i < $txnCount; $i++) {
                $direction = fake()->randomElement(['debit', 'credit']);
                $amount = match ($account->type) {
                    'fuel_station' => fake()->randomFloat(2, 2000, 45000),
                    'motor_parts_shop' => fake()->randomFloat(2, 1000, 25000),
                    'staff' => fake()->randomFloat(2, 500, 15000),
                    'company_expense' => fake()->randomFloat(2, 5000, 80000),
                };

                if ($direction === 'debit') {
                    $balance += $amount;
                } else {
                    $balance = max(0, $balance - $amount);
                }

                $paymentMode = fake()->randomElement(['cash', 'bank_transfer', 'upi', 'cheque', 'other']);
                $paymentPlan = fake()->randomElement(['full', 'emi', 'partial']);

                AccountTransaction::create([
                    'account_id' => $account->id,
                    'branch_id' => $branchId,
                    'direction' => $direction,
                    'amount' => $amount,
                    'payment_mode' => $paymentMode,
                    'payment_plan' => $paymentPlan,
                    'installment_no' => $paymentPlan === 'emi' ? fake()->numberBetween(1, 12) : null,
                    'installment_total' => $paymentPlan === 'emi' ? fake()->numberBetween(12, 24) : null,
                    'reference_type' => fake()->optional()->randomElement(['transport_log', 'invoice', 'manual']),
                    'reference_id' => fake()->optional()->numberBetween(1, 1000),
                    'description' => fake()->optional()->sentence(),
                    'attachment_path' => fake()->optional()->word() . '.' . fake()->fileExtension(),
                    'transaction_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                    'running_balance' => round($balance, 2),
                    'created_by' => $creator,
                    'updated_by' => $creator,
                ]);
            }

            $account->update(['current_balance' => round($balance, 2)]);
        }
    }
}
