<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FuelStation;
use App\Models\TransportLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelStationComboboxTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        return $user;
    }

    private function validPayload(int $fuelStationId = null, int $km = 100): array
    {
        $branch = Branch::first() ?? Branch::factory()->create();
        $vehicle = Vehicle::factory()->create();

        return [
            'date' => '2026-08-15',
            'vehicle_no' => $vehicle->vehicle_no,
            'company' => 'Test Co',
            'transport_name' => 'Test Transport',
            'destination' => 'Mumbai',
            'km' => $km,
            'weight' => 1000,
            'to_bb_sale' => 1000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
            'diesel_advance' => 0,
            'cash_advance' => 0,
            'payment' => 0,
            'fuel_station_id' => $fuelStationId,
            'fuel_station_name' => $fuelStationId ? FuelStation::find($fuelStationId)?->name : 'Free-text',
            'branch_id' => $branch->id,
        ];
    }

    public function test_create_transport_log_with_valid_fuel_station_id_succeeds(): void
    {
        $this->actingAsSuperAdmin();
        $station = FuelStation::factory()->create(['name' => 'BPCL Pump', 'is_active' => true]);

        $payload = $this->validPayload($station->id);
        $payload['diesel_advance'] = 500;
        $payload['fuel_station_name'] = $station->name;

        $response = $this->post(route('transport-logs.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $log = TransportLog::where('vehicle_no', $payload['vehicle_no'])->first();
        $this->assertNotNull($log);
        $this->assertEquals($station->id, $log->fuel_station_id);
        $this->assertEquals('BPCL Pump', $log->fuel_station_name);
    }

    public function test_create_transport_log_with_invalid_fuel_station_id_returns_validation_error(): void
    {
        $this->actingAsSuperAdmin();
        $invalidId = 999999;

        $payload = $this->validPayload($invalidId);
        $payload['fuel_station_name'] = 'Non-existent Pump';

        $response = $this->post(route('transport-logs.store'), $payload);
        $response->assertSessionHasErrors('fuel_station_id');
        $this->assertDatabaseMissing('transport_logs', ['vehicle_no' => $payload['vehicle_no']]);
    }

    public function test_create_transport_log_with_no_fuel_station_succeeds(): void
    {
        $this->actingAsSuperAdmin();

        $payload = $this->validPayload(null);
        $response = $this->post(route('transport-logs.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_create_form_renders_with_fuel_stations_list(): void
    {
        $this->actingAsSuperAdmin();
        $active = FuelStation::factory()->create(['name' => 'Active Pump', 'is_active' => true]);
        $inactive = FuelStation::factory()->create(['name' => 'Inactive Pump', 'is_active' => false]);

        $response = $this->get(route('transport-logs.create'));
        $response->assertOk();
        $response->assertSee('Active Pump');
        $response->assertDontSee('Inactive Pump');
    }
}
