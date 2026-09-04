<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Models\FuelStation;
use App\Models\TransportLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TraceCodeAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        return $user;
    }

    public function test_trace_code_is_generated_uniquely_on_every_new_transport_log(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $log1 = TransportLog::create([
            'date' => '2026-08-01',
            'vehicle_no' => 'MH 01 AB 1234A',
            'company' => 'Test Company',
            'transport_name' => 'Test Transport',
            'logsheet_no' => 'LS001',
            'destination' => 'Mumbai',
            'km' => 100,
            'weight' => 50,
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
            'fuel_station_name' => $fuelStation->name,
            'fuel_station_balance' => 0,
            'balance_vehicle_payment' => 1000,
            'branch_id' => $branch->id,
            'fuel_station_id' => $fuelStation->id,
            'created_by' => User::factory()->create()->id,
            'updated_by' => User::factory()->create()->id,
        ]);

        $log2 = TransportLog::create([
            'date' => '2026-08-02',
            'vehicle_no' => 'MH 02 CD 5678B',
            'company' => 'Test Company 2',
            'transport_name' => 'Test Transport 2',
            'logsheet_no' => 'LS002',
            'destination' => 'Delhi',
            'km' => 200,
            'weight' => 80,
            'to_bb_sale' => 2000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
            'diesel_advance' => 800,
            'cash_advance' => 0,
            'payment' => 0,
            'fuel_station_name' => $fuelStation->name,
            'fuel_station_balance' => 0,
            'balance_vehicle_payment' => 2000,
            'branch_id' => $branch->id,
            'fuel_station_id' => $fuelStation->id,
            'created_by' => User::factory()->create()->id,
            'updated_by' => User::factory()->create()->id,
        ]);

        $this->assertNotNull($log1->trace_code);
        $this->assertNotNull($log2->trace_code);
        $this->assertEquals('TL-' . str_pad((string) $log1->id, 6, '0', STR_PAD_LEFT), $log1->trace_code);
        $this->assertEquals('TL-' . str_pad((string) $log2->id, 6, '0', STR_PAD_LEFT), $log2->trace_code);
        $this->assertNotEquals($log1->trace_code, $log2->trace_code);

        $response = $this->get(route('trace.show', $log1->trace_code));
        $response->assertOk();
        $response->assertSee($log1->trace_code);
    }

    public function test_trace_page_flags_manually_corrupted_fuel_paid_amount_as_discrepancy(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 1000,
            'fuel_payment_status' => 'paid',
        ]);

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 500,
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        TransportLog::where('id', $log->id)->update(['fuel_paid_amount' => 9999]);

        $response = $this->get(route('trace.show', $log->trace_code));
        $response->assertOk();
        $response->assertSee('Discrepancy Found');
    }

    public function test_integrity_command_reports_zero_issues_on_fresh_database(): void
    {
        $this->actingAsSuperAdmin();

        $fuelStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
        ]);

        $exitCode = Artisan::call('accounts:verify-integrity');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('All integrity checks passed', $output);
    }
}
