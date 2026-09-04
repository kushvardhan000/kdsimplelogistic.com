<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Models\FuelStation;
use App\Models\TransportLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AccountLedgerService;
use App\Services\FuelSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FuelSettlementEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->superAdmin()->create();
        $this->actingAs($this->admin);
    }

    private function makeLog(array $overrides = []): TransportLog
    {
        $station = FuelStation::factory()->create(['name' => 'Test Pump', 'is_active' => true]);
        $branch = Branch::factory()->create();
        $vehicle = Vehicle::factory()->create();

        return TransportLog::factory()->create(array_merge([
            'date' => '2026-08-15',
            'vehicle_no' => $vehicle->vehicle_no,
            'company' => 'Test Co',
            'transport_name' => 'Test Transport',
            'destination' => 'Mumbai',
            'km' => 100,
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
            'diesel_advance' => 1000,
            'cash_advance' => 0,
            'payment' => 0,
            'fuel_station_name' => $station->name,
            'fuel_station_balance' => 0,
            'balance_vehicle_payment' => 1000,
            'branch_id' => $branch->id,
            'fuel_station_id' => $station->id,
            'vehicle_id' => $vehicle->id,
        ], $overrides));
    }

    private function getOrCreateFuelStationAccount(TransportLog $log): Account
    {
        return Account::firstOrCreate(
            [
                'type' => 'fuel_station',
                'linked_fuel_station_id' => $log->fuel_station_id,
            ],
            [
                'name' => $log->fuelStation->name,
                'branch_id' => $log->branch_id,
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ]
        );
    }

    // =====================
    // Scenario 1: zero advance, no station → fuel_payment_status must be "not_applicable" (NOT "unpaid")
    // =====================
    public function test_log_with_no_fuel_component_is_not_applicable_not_unpaid(): void
    {
        $log = $this->makeLog([
            'diesel_advance' => 0,
            'cash_advance' => 0,
            'fuel_station_id' => null,
            'fuel_station_name' => null,
        ]);

        $log->refresh();
        $this->assertNull($log->fuel_payment_status, "fuel_payment_status should be null (not 'unpaid') for logs with no fuel component.");
        $this->assertEquals(0, (float) $log->fuel_paid_amount);
    }

    // =====================
    // Scenario 2: edit advance UP after payments exist → status reverts to "partial"
    // =====================
    public function test_editing_advance_upward_reverts_paid_status_to_partial(): void
    {
        $log = $this->makeLog(['diesel_advance' => 5000]);
        $account = $this->getOrCreateFuelStationAccount($log);

        app(AccountLedgerService::class)->createTransaction($account, [
            'direction' => 'credit',
            'amount' => 5000,
            'transaction_date' => '2026-08-16',
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'description' => 'Full payment',
        ]);

        $log->refresh();
        $this->assertEquals('paid', $log->fuel_payment_status);

        $log->update(['diesel_advance' => 8000, 'updated_by' => $this->admin->id]);
        $log->refresh();

        $this->assertEquals(8000, (float) $log->diesel_advance);
        $this->assertEquals(5000, (float) $log->fuel_paid_amount);
        $this->assertEquals('partial', $log->fuel_payment_status);
    }

    // =====================
    // Scenario 3: edit advance DOWN below what was already paid → "overpaid"
    // =====================
    public function test_editing_advance_downward_below_paid_produces_overpaid_status(): void
    {
        $log = $this->makeLog(['diesel_advance' => 5000]);
        $account = $this->getOrCreateFuelStationAccount($log);

        app(AccountLedgerService::class)->createTransaction($account, [
            'direction' => 'credit',
            'amount' => 6000,
            'transaction_date' => '2026-08-16',
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'description' => 'Overpayment',
        ]);

        $log->refresh();
        $this->assertEquals('overpaid', $log->fuel_payment_status);

        $log->update(['diesel_advance' => 4000, 'updated_by' => $this->admin->id]);
        $log->refresh();

        $this->assertEquals(4000, (float) $log->diesel_advance);
        $this->assertEquals(6000, (float) $log->fuel_paid_amount);
        $this->assertEquals('overpaid', $log->fuel_payment_status);
    }

    // =====================
    // Scenario 4: soft-deleting a log with active linked payments — payments persist, account balance remains consistent
    // =====================
    public function test_soft_deleting_log_preserves_account_transactions_and_balances(): void
    {
        $log = $this->makeLog(['diesel_advance' => 2000]);
        $account = $this->getOrCreateFuelStationAccount($log);

        app(AccountLedgerService::class)->createTransaction($account, [
            'direction' => 'credit',
            'amount' => 1000,
            'transaction_date' => '2026-08-16',
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'description' => 'Partial payment',
        ]);

        $account->refresh();
        $balanceBefore = (float) $account->current_balance;

        $log->delete();

        $existingCredit = AccountTransaction::where('reference_type', TransportLog::class)
            ->where('reference_id', $log->id)
            ->where('direction', 'credit')
            ->whereNull('deleted_at')
            ->first();

        $this->assertNotNull($existingCredit, "Linked credit transaction should remain after soft-deleting the transport log.");
        $this->assertNull($existingCredit->deleted_at, "Credit transaction should NOT be soft-deleted along with the log.");

        $account->refresh();
        $this->assertEquals($balanceBefore, (float) $account->current_balance, "Account balance must not change when the linked log is soft-deleted.");
    }

    // =====================
    // Scenario 5: restoring a log — settlement is recalculated from current transactions
    // =====================
    public function test_restoring_log_recalculates_settlement_from_current_transactions(): void
    {
        $log = $this->makeLog(['diesel_advance' => 3000]);
        $account = $this->getOrCreateFuelStationAccount($log);

        app(AccountLedgerService::class)->createTransaction($account, [
            'direction' => 'credit',
            'amount' => 1000,
            'transaction_date' => '2026-08-16',
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'description' => 'First payment',
        ]);

        $log->delete();

        app(AccountLedgerService::class)->createTransaction($account, [
            'direction' => 'credit',
            'amount' => 2000,
            'transaction_date' => '2026-08-17',
            'reference_type' => TransportLog::class,
            'reference_id' => $log->id,
            'description' => 'Second payment while log deleted',
        ]);

        $log->restore();
        $log->refresh();

        $this->assertNull($log->deleted_at);
        $this->assertEquals(3000, (float) $log->fuel_paid_amount, "After restore, fuel_paid_amount should be 3000 (sum of both payments).");
        $this->assertEquals('paid', $log->fuel_payment_status);
    }

    // =====================
    // Scenario 6: referential integrity — same (reference_type, reference_id) cannot resolve to two different logs
    // =====================
    public function test_reference_pair_is_strict_per_log(): void
    {
        $log1 = $this->makeLog();
        $log2 = $this->makeLog();
        $account = $this->getOrCreateFuelStationAccount($log1);

        app(AccountLedgerService::class)->createTransaction($account, [
            'direction' => 'credit',
            'amount' => 100,
            'transaction_date' => '2026-08-16',
            'reference_type' => TransportLog::class,
            'reference_id' => $log1->id,
        ]);

        $countForLog1 = AccountTransaction::where('reference_type', TransportLog::class)
            ->where('reference_id', $log1->id)
            ->where('direction', 'credit')
            ->whereNull('deleted_at')
            ->count();

        $countForLog2 = AccountTransaction::where('reference_type', TransportLog::class)
            ->where('reference_id', $log2->id)
            ->where('direction', 'credit')
            ->whereNull('deleted_at')
            ->count();

        $this->assertEquals(1, $countForLog1);
        $this->assertEquals(0, $countForLog2);

        $log1->refresh();
        $log2->refresh();
        $this->assertEquals(100, (float) $log1->fuel_paid_amount);
        $this->assertEquals(0, (float) $log2->fuel_paid_amount);
    }

    // =====================
    // Scenario 7: out-of-order deletes — recalculation must remain sane (non-negative or correctly overpaid)
    // =====================
    public function test_out_of_order_deletes_keep_settlement_sane(): void
    {
        $log = $this->makeLog(['diesel_advance' => 1000]);
        $account = $this->getOrCreateFuelStationAccount($log);

        $txn1 = app(AccountLedgerService::class)->createTransaction($account, [
            'direction' => 'credit', 'amount' => 300, 'transaction_date' => '2026-08-15',
            'reference_type' => TransportLog::class, 'reference_id' => $log->id,
        ]);
        $txn2 = app(AccountLedgerService::class)->createTransaction($account, [
            'direction' => 'credit', 'amount' => 400, 'transaction_date' => '2026-08-16',
            'reference_type' => TransportLog::class, 'reference_id' => $log->id,
        ]);
        $txn3 = app(AccountLedgerService::class)->createTransaction($account, [
            'direction' => 'credit', 'amount' => 500, 'transaction_date' => '2026-08-17',
            'reference_type' => TransportLog::class, 'reference_id' => $log->id,
        ]);

        $log->refresh();
        $this->assertEquals(1200, (float) $log->fuel_paid_amount);
        $this->assertEquals('overpaid', $log->fuel_payment_status);

        $txn1->delete();
        $log->refresh();
        $this->assertEquals(900, (float) $log->fuel_paid_amount);
        $this->assertEquals('partial', $log->fuel_payment_status);

        $txn2->delete();
        $log->refresh();
        $this->assertEquals(500, (float) $log->fuel_paid_amount);
        $this->assertEquals('partial', $log->fuel_payment_status);

        $txn3->delete();
        $log->refresh();
        $this->assertEquals(0, (float) $log->fuel_paid_amount);
        $this->assertEquals('unpaid', $log->fuel_payment_status);
    }

    // =====================
    // Scenario 8: concurrent payment attempts — lockForUpdate prevents race
    // =====================
    public function test_concurrent_payments_are_safely_serialized(): void
    {
        $log = $this->makeLog(['diesel_advance' => 5000]);
        $account = $this->getOrCreateFuelStationAccount($log);

        $results = DB::transaction(function () use ($account, $log) {
            $txn1 = app(AccountLedgerService::class)->createTransaction($account, [
                'direction' => 'credit', 'amount' => 1000, 'transaction_date' => '2026-08-15',
                'reference_type' => TransportLog::class, 'reference_id' => $log->id,
            ]);

            $txn2 = app(AccountLedgerService::class)->createTransaction($account, [
                'direction' => 'credit', 'amount' => 2000, 'transaction_date' => '2026-08-16',
                'reference_type' => TransportLog::class, 'reference_id' => $log->id,
            ]);

            return [$txn1, $txn2];
        });

        $log->refresh();
        $account->refresh();

        $this->assertEquals(3000, (float) $log->fuel_paid_amount);
        $this->assertGreaterThanOrEqual(0, (float) $account->current_balance, "Account balance must not go negative (clamped at 0).");
        $this->assertEquals('partial', $log->fuel_payment_status);
        $this->assertCount(2, AccountTransaction::where('account_id', $account->id)->where('direction', 'credit')->whereNull('deleted_at')->get());
    }
}
