<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FuelStation;
use App\Models\TransportLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelStationDisplayRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->superAdmin()->create();
        $this->actingAs($this->admin);
    }

    public function test_legacy_log_with_only_fuel_station_name_displays_gracefully(): void
    {
        $branch = Branch::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $log = TransportLog::create([
            'date' => '2026-08-01',
            'vehicle_no' => $vehicle->vehicle_no,
            'company' => 'Legacy Co',
            'transport_name' => 'Legacy Transport',
            'destination' => 'Mumbai',
            'km' => 50,
            'weight' => 500,
            'diesel_advance' => 0,
            'cash_advance' => 0,
            'to_bb_sale' => 0,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
            'payment' => 0,
            'fuel_station_id' => null,
            'fuel_station_name' => 'Old Pump Name (no longer linked)',
            'branch_id' => $branch->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->get(route('transport-logs.show', $log));
        $response->assertOk();
        $response->assertSee('Old Pump Name (no longer linked)');

        $response = $this->get(route('transport-logs.edit', $log));
        $response->assertOk();
    }

    public function test_log_with_no_fuel_component_shows_no_payment_history_panel(): void
    {
        $branch = Branch::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $log = TransportLog::create([
            'date' => '2026-08-01',
            'vehicle_no' => $vehicle->vehicle_no,
            'company' => 'Test',
            'transport_name' => 'Test',
            'destination' => 'Mumbai',
            'km' => 50,
            'weight' => 500,
            'diesel_advance' => 0,
            'cash_advance' => 0,
            'fuel_station_id' => null,
            'fuel_station_name' => null,
            'to_bb_sale' => 0,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
            'payment' => 0,
            'branch_id' => $branch->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->get(route('transport-logs.show', $log));
        $response->assertOk();
        $response->assertDontSee('Payment History for This Log');
    }

    public function test_log_with_fuel_station_id_links_station_name_to_ledger(): void
    {
        $branch = Branch::factory()->create();
        $vehicle = Vehicle::factory()->create();
        $station = FuelStation::factory()->create(['name' => 'Linked Pump', 'is_active' => true]);

        $log = TransportLog::create([
            'date' => '2026-08-01',
            'vehicle_no' => $vehicle->vehicle_no,
            'company' => 'Test',
            'transport_name' => 'Test',
            'destination' => 'Mumbai',
            'km' => 50,
            'weight' => 500,
            'diesel_advance' => 1000,
            'cash_advance' => 0,
            'to_bb_sale' => 0,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
            'payment' => 0,
            'fuel_station_id' => $station->id,
            'fuel_station_name' => $station->name,
            'branch_id' => $branch->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $response = $this->get(route('transport-logs.show', $log));
        $response->assertOk();
        $response->assertSee('accounts?type=fuel_station');
        $response->assertSee('selected=' . $station->id);
    }
}
