<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\FuelStation;
use App\Models\TransportLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PumpDetailTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CustomFieldOptionSeeder::class);
        $this->admin = User::factory()->superAdmin()->create();
        $this->actingAs($this->admin);
    }

    public function test_adding_transaction_from_pump_detail_with_linked_transport_log_updates_settlement(): void
    {
        $station = FuelStation::factory()->create(['name' => 'Test Pump', 'is_active' => true]);
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $station->id]);
        $vehicle = Vehicle::factory()->create(['vehicle_no' => 'MH01AB1234']);
        $driver = Driver::factory()->create(['name' => 'Test Driver', 'vehicle_id' => $vehicle->id]);
        $log = TransportLog::factory()->create([
            'vehicle_id' => $vehicle->id,
            'vehicle_no' => 'MH01AB1234',
            'destination' => 'Mumbai',
            'diesel_advance' => 5000,
            'fuel_station_id' => $station->id,
            'fuel_station_name' => $station->name,
            'branch_id' => $branch->id,
        ]);

        $response = $this->post(route('accounts.transactions.store', $account), [
            'account_id' => $account->id,
            'direction' => 'credit',
            'amount' => 2000,
            'branch_id' => $branch->id,
            'transaction_date' => now()->format('Y-m-d'),
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'description' => 'Partial payment from pump detail',
            'payment_plan' => 'partial',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $log->refresh();
        $this->assertEquals(2000, (float) $log->fuel_paid_amount);
        $this->assertEquals('partial', $log->fuel_payment_status);

        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $account->id,
            'direction' => 'credit',
            'amount' => 2000,
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
        ]);
    }

    public function test_adding_transaction_without_linked_log_does_not_touch_transport_logs(): void
    {
        $account = Account::factory()->fuelStation()->create();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.transactions.store', $account), [
            'account_id' => $account->id,
            'direction' => 'credit',
            'amount' => 1500,
            'branch_id' => $branch->id,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => 'Lump sum advance',
            'payment_plan' => 'full',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $account->id,
            'direction' => 'credit',
            'amount' => 1500,
        ]);

        $logsWithSettlement = TransportLog::where('fuel_payment_status', '!=', null)->count();
        $this->assertEquals(0, $logsWithSettlement, 'No transport log should have its settlement modified when transaction has no linked log.');
    }

    public function test_transport_log_search_returns_destination_and_driver(): void
    {
        $station = FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $station->id]);
        $vehicle = Vehicle::factory()->create(['vehicle_no' => 'MH01AB1234']);
        $driver = Driver::factory()->create(['name' => 'John Driver', 'vehicle_id' => $vehicle->id]);
        $log = TransportLog::factory()->create([
            'vehicle_id' => $vehicle->id,
            'vehicle_no' => 'MH01AB1234',
            'destination' => 'Delhi',
            'diesel_advance' => 3000,
            'fuel_station_id' => $station->id,
            'branch_id' => $branch->id,
        ]);

        $response = $this->get(route('accounts.transport-logs.search', ['q' => 'MH01AB1234']));
        $response->assertOk();
        $response->assertJson([
            [
                'id' => $log->id,
                'vehicle_no' => 'MH01AB1234',
                'destination' => 'Delhi',
                'driver_name' => 'John Driver',
                'diesel_advance' => 3000,
                'paid_amount' => 0,
                'remaining_due' => 3000,
            ]
        ]);
    }

    public function test_pump_detail_page_shows_add_transaction_button_and_modal(): void
    {
        $station = FuelStation::factory()->create(['name' => 'Test Pump', 'is_active' => true]);
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $station->id]);

        $response = $this->get(route('accounts.index', ['type' => 'fuel_station']));
        $response->assertOk();
        $response->assertSee('transaction-modal-' . $account->id);

        $fragmentResponse = $this->get(route('accounts.pump-flow', $account));
        $fragmentResponse->assertOk();
        $fragmentResponse->assertSee('+ Add Transaction');
    }

    public function test_completing_payment_from_pump_detail_marks_log_as_paid(): void
    {
        $station = FuelStation::factory()->create(['name' => 'Test Pump', 'is_active' => true]);
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $station->id]);
        $vehicle = Vehicle::factory()->create(['vehicle_no' => 'MH01AB1234']);
        $log = TransportLog::factory()->create([
            'vehicle_id' => $vehicle->id,
            'vehicle_no' => 'MH01AB1234',
            'destination' => 'Mumbai',
            'diesel_advance' => 5000,
            'fuel_station_id' => $station->id,
            'fuel_station_name' => $station->name,
            'branch_id' => $branch->id,
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'account_id' => $account->id,
            'direction' => 'credit',
            'amount' => 5000,
            'branch_id' => $branch->id,
            'transaction_date' => now()->format('Y-m-d'),
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'description' => 'Full payment',
            'payment_plan' => 'full',
        ]);

        $log->refresh();
        $this->assertEquals('paid', $log->fuel_payment_status);
    }
}
