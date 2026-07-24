<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TransportLog;
use App\Models\ActivityLog;
use App\Models\FuelStation;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObserverLedgerActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_transport_log_with_diesel_advance_logs_station_debit_activity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $fuelStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $this->actingAs($user);

        $log = TransportLog::create([
            'date' => '2026-07-23',
            'vehicle_no' => 'MH 01 AB 1234A',
            'company' => 'Test Company',
            'transport_name' => 'Test Transport',
            'destination' => 'Mumbai',
            'km' => 100,
            'weight' => 50,
            'logsheet_no' => 'LS001',
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
            'diesel_advance' => 500,
            'cash_advance' => 0,
            'payment' => 0,
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $debit = ActivityLog::where('table_name', 'station_debits')
            ->where('action', 'create')
            ->first();

        $this->assertNotNull($debit, 'Activity log entry for station debit should exist');
        $this->assertNotNull($debit->changes, 'Changes payload should not be null');
        $this->assertTrue(
            collect($debit->changes)->contains(fn ($change) => $change['field'] === 'amount' && $change['new'] == 500),
            'Changes payload should contain the amount field with value 500'
        );
    }
}
