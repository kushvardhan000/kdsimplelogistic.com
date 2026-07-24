<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\FuelStation;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransportLog>
 */
class TransportLogFactory extends Factory
{
    protected static array $destinations = [
        'Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Ahmedabad', 'Chennai',
        'Kolkata', 'Pune', 'Jaipur', 'Lucknow', 'Indore', 'Nagpur',
    ];

    public function definition(): array
    {
        $toBbSale = fake()->randomFloat(2, 1000, 60000);
        $paidSale = fake()->randomFloat(2, 1000, 60000);
        $toPay = fake()->randomFloat(2, 0, 8000);
        $totalSale = round($toBbSale + $paidSale + $toPay, 2);

        $freight = fake()->randomFloat(2, 2000, 50000);
        $loading = fake()->randomFloat(2, 0, 4000);
        $unloading = fake()->randomFloat(2, 0, 4000);
        $dd = fake()->randomFloat(2, 0, 1500);
        $tempuExpense = fake()->randomFloat(2, 0, 3000);
        $commission = fake()->randomFloat(2, 0, 2500);
        $dtgOfficeExpense = fake()->randomFloat(2, 0, 1500);
        $totalExpense = round($freight + $loading + $unloading + $dd + $tempuExpense + $commission + $dtgOfficeExpense, 2);
        $profit = round($totalSale - $totalExpense, 2);

        $dieselAdvance = fake()->randomFloat(2, 0, 12000);
        $cashAdvance = fake()->randomFloat(2, 0, 6000);
        $totalAdvance = round($dieselAdvance + $cashAdvance, 2);

        $payment = fake()->randomFloat(2, 0, 20000);
        $balanceVehiclePayment = round($totalSale - $payment, 2);

        $companyName = fake()->randomElement([
            'Tata Motors', 'Mahindra Logistics', 'ABB Transport', 'Reliance Freight',
            'Adani Ports', 'Blue Dart', 'VRL Logistics', 'TCI Express',
            'Safexpress', 'Jindal Steel', 'Ultratech Cement', 'Ambuja Cements',
        ]);

        $stateCode = fake()->randomElement(['MH', 'GJ', 'RJ', 'DL', 'KA', 'TN', 'UP', 'HR', 'PB', 'MP', 'BR', 'AP']);
        $districtCode = str_pad(fake()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT);
        $series = fake()->bothify('??');
        $number = fake()->unique()->numberBetween(1000, 9999);
        $optionalAlpha = fake()->optional()->randomElement(['A', 'B', 'C', 'D']);
        $vehicleNo = "{$stateCode} {$districtCode} {$series}{$number}{$optionalAlpha}";

        $fuelStationName = fake()->randomElement([
            'Indian Oil - Andheri', 'Bharat Petroleum - Thane', 'HP Petrol - Nashik',
            'Reliance Jio-BP - Pune', 'Indian Oil - Solapur', 'Nayara - Aurangabad',
        ]);

        return [
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'vehicle_no' => $vehicleNo,
            'company' => $companyName,
            'transport_name' => $companyName,
            'logsheet_no' => (string) fake()->unique()->numberBetween(1000, 9999),
            'destination' => fake()->randomElement(self::$destinations),
            'km' => fake()->randomFloat(2, 50, 2000),
            'weight' => fake()->randomFloat(2, 1, 50),
            'to_bb_sale' => $toBbSale,
            'paid_sale' => $paidSale,
            'to_pay' => $toPay,
            'total_sale' => $totalSale,
            'freight' => $freight,
            'loading' => $loading,
            'unloading' => $unloading,
            'dd' => $dd,
            'tempu_expense' => $tempuExpense,
            'commission' => $commission,
            'total_expense' => $totalExpense,
            'profit' => $profit,
            'diesel_advance' => $dieselAdvance,
            'cash_advance' => $cashAdvance,
            'total_advance' => $totalAdvance,
            'payment' => $payment,
            'fuel_station_name' => $fuelStationName,
            'fuel_station_balance' => fake()->randomFloat(2, 0, 10000),
            'balance_vehicle_payment' => $balanceVehiclePayment,
            'clearing_date' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d') : null,
            'detail' => fake()->sentence(),
            'remarks' => fake()->optional()->sentence(),
            'mileage' => fake()->randomFloat(2, 5, 30),
            'dtg_office_expense' => $dtgOfficeExpense,
            'created_by' => null,
            'updated_by' => null,
            'vehicle_id' => Vehicle::factory(),
            'company_id' => Company::factory(),
            'carrier_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'fuel_station_id' => FuelStation::factory(),
        ];
    }

    public function loss(): static
    {
        return $this->state(function (array $attributes) {
            $totalSale = $attributes['total_sale'];
            $totalExpense = round($totalSale * fake()->randomFloat(2, 1.01, 1.5), 2);
            $profit = round($totalSale - $totalExpense, 2);

            return [
                'total_expense' => $totalExpense,
                'profit' => $profit,
            ];
        });
    }

    public function breakEven(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_sale' => $attributes['total_expense'],
            'profit' => 0.00,
        ]);
    }

    public function overpaid(): static
    {
        return $this->state(function (array $attributes) {
            $payment = round($attributes['total_sale'] * fake()->randomFloat(2, 1.01, 1.3), 2);
            $balanceVehiclePayment = round($attributes['total_sale'] - $payment, 2);

            return [
                'payment' => $payment,
                'balance_vehicle_payment' => $balanceVehiclePayment,
            ];
        });
    }
}
