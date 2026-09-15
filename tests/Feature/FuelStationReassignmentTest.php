<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Models\FuelStation;
use App\Models\TransportLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelStationReassignmentTest extends TestCase
{
    use RefreshDatabase;

    private ?User $superAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CustomFieldOptionSeeder::class);
    }

    private function actingAsSuperAdmin(): User
    {
        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($this->superAdmin);
        return $this->superAdmin;
    }

    public function test_reassigning_station_with_no_prior_payments_correctly_moves_debit(): void
    {
        $this->actingAsSuperAdmin();

        $oldStation = FuelStation::factory()->create();
        $newStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $log = TransportLog::factory()->create([
            'fuel_station_id' => $oldStation->id,
            'diesel_advance' => 5000,
            'branch_id' => $branch->id,
        ]);

        $oldAccount = Account::where('linked_fuel_station_id', $oldStation->id)->first();
        $newAccount = Account::where('linked_fuel_station_id', $newStation->id)->first();

        $this->assertNotNull($oldAccount);
        $this->assertNull($newAccount);
        $this->assertEquals(5000, (float) $oldAccount->current_balance);

        $log->update(['fuel_station_id' => $newStation->id]);

        $oldAccount->refresh();
        $newAccount = Account::where('linked_fuel_station_id', $newStation->id)->first();

        $this->assertEquals(0, (float) $oldAccount->current_balance);
        $this->assertNotNull($newAccount);
        $this->assertEquals(5000, (float) $newAccount->current_balance);

        $this->assertDatabaseHas('account_transactions', [
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'direction' => 'debit',
            'deleted_at' => null,
            'account_id' => $newAccount->id,
        ]);
    }

    public function test_reassigning_station_with_prior_payments_reverses_them_and_resets_status(): void
    {
        $this->actingAsSuperAdmin();

        $oldStation = FuelStation::factory()->create();
        $newStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $log = TransportLog::factory()->create([
            'fuel_station_id' => $oldStation->id,
            'diesel_advance' => 5000,
            'branch_id' => $branch->id,
        ]);

        $oldAccount = Account::where('linked_fuel_station_id', $oldStation->id)->first();

        AccountTransaction::create([
            'account_id' => $oldAccount->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 2000,
            'payment_mode' => 'cash',
            'payment_plan' => 'full',
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'description' => 'Payment 1',
            'transaction_date' => now()->format('Y-m-d'),
            'running_balance' => 0,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        app(\App\Services\AccountLedgerService::class)->recalculateRunningBalances($oldAccount);
        $log->refresh();

        $this->assertEquals('partial', $log->fuel_payment_status);
        $this->assertEquals(2000, (float) $log->fuel_paid_amount);

        $log->update(['fuel_station_id' => $newStation->id]);

        $oldAccount->refresh();
        $newAccount = Account::where('linked_fuel_station_id', $newStation->id)->first();

        // Old account should have zero balance (debit and credits soft-deleted)
        $this->assertEquals(0, (float) $oldAccount->current_balance);
        // New account should have only the new debit (diesel advance)
        $this->assertNotNull($newAccount);
        $this->assertEquals(5000, (float) $newAccount->current_balance);

        $log->refresh();
        // Log's settlement should be reset
        $this->assertEquals(0, (float) $log->fuel_paid_amount);
        $this->assertEquals('unpaid', $log->fuel_payment_status);

        // Credit transactions should be soft-deleted on old account
        $this->assertSoftDeleted('account_transactions', [
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'direction' => 'credit',
            'account_id' => $oldAccount->id,
        ]);

        // No credit transactions should exist on new account (admin must re-record)
        $this->assertDatabaseMissing('account_transactions', [
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'direction' => 'credit',
            'deleted_at' => null,
            'account_id' => $newAccount->id,
        ]);
    }

    public function test_clearing_fuel_station_id_reverses_debit_and_resets_status(): void
    {
        $this->actingAsSuperAdmin();

        $oldStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $log = TransportLog::factory()->create([
            'fuel_station_id' => $oldStation->id,
            'diesel_advance' => 5000,
            'branch_id' => $branch->id,
        ]);

        $oldAccount = Account::where('linked_fuel_station_id', $oldStation->id)->first();
        $this->assertEquals(5000, (float) $oldAccount->current_balance);

        $log->update(['fuel_station_id' => null]);

        $oldAccount->refresh();
        $this->assertEquals(0, (float) $oldAccount->current_balance);

        $log->refresh();
        $this->assertNull($log->fuel_payment_status);
        $this->assertEquals(0, (float) $log->fuel_paid_amount);
    }

    public function test_changing_both_station_and_advance_amount_nets_correctly(): void
    {
        $this->actingAsSuperAdmin();

        $oldStation = FuelStation::factory()->create();
        $newStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $log = TransportLog::factory()->create([
            'fuel_station_id' => $oldStation->id,
            'diesel_advance' => 5000,
            'branch_id' => $branch->id,
        ]);

        $oldAccount = Account::where('linked_fuel_station_id', $oldStation->id)->first();
        $this->assertEquals(5000, (float) $oldAccount->current_balance);

        $log->update([
            'fuel_station_id' => $newStation->id,
            'diesel_advance' => 3000,
        ]);

        $oldAccount->refresh();
        $newAccount = Account::where('linked_fuel_station_id', $newStation->id)->first();

        $this->assertEquals(0, (float) $oldAccount->current_balance);
        $this->assertNotNull($newAccount);
        $this->assertEquals(3000, (float) $newAccount->current_balance);
    }

    public function test_old_and_new_station_balances_are_both_correct_after_reassignment(): void
    {
        $this->actingAsSuperAdmin();

        $oldStation = FuelStation::factory()->create();
        $newStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $log1 = TransportLog::factory()->create([
            'fuel_station_id' => $oldStation->id,
            'diesel_advance' => 3000,
            'branch_id' => $branch->id,
        ]);

        $log2 = TransportLog::factory()->create([
            'fuel_station_id' => $oldStation->id,
            'diesel_advance' => 2000,
            'branch_id' => $branch->id,
        ]);

        $oldAccount = Account::where('linked_fuel_station_id', $oldStation->id)->first();
        $this->assertEquals(5000, (float) $oldAccount->current_balance);

        $log1->update(['fuel_station_id' => $newStation->id]);

        $oldAccount->refresh();
        $newAccount = Account::where('linked_fuel_station_id', $newStation->id)->first();

        $this->assertEquals(2000, (float) $oldAccount->current_balance);
        $this->assertNotNull($newAccount);
        $this->assertEquals(3000, (float) $newAccount->current_balance);
    }
}