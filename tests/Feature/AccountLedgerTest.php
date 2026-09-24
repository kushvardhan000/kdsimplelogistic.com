<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\FuelStation;
use App\Models\TransportLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CustomFieldOptionSeeder::class);
    }

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        return $user;
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);
        return $user;
    }

    // -------------------------------------------------------------------------
    // 1. Account CRUD for all 4 types
    // -------------------------------------------------------------------------

    public function test_super_admin_can_create_fuel_station_account(): void
    {
        $this->actingAsSuperAdmin();
        $station = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'fuel_station',
            'name' => 'Test Fuel Station',
            'linked_fuel_station_id' => $station->id,
            'branch_id' => $branch->id,
            'opening_balance' => 1000,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'fuel_station',
            'name' => 'Test Fuel Station',
            'linked_fuel_station_id' => $station->id,
            'branch_id' => $branch->id,
            'opening_balance' => 1000,
            'current_balance' => 1000,
        ]);
    }

    public function test_super_admin_can_create_staff_account(): void
    {
        $this->actingAsSuperAdmin();
        $driver = Driver::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'staff',
            'name' => 'Test Staff',
            'linked_driver_id' => $driver->id,
            'branch_id' => $branch->id,
            'aadhar_no' => '123456789012',
            'driving_license_no' => 'DL1420110012345',
            'opening_balance' => 0,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'staff',
            'name' => 'Test Staff',
            'linked_driver_id' => $driver->id,
        ]);
    }

    public function test_super_admin_can_create_motor_parts_shop_account(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'motor_parts_shop',
            'name' => 'Test Motor Parts',
            'branch_id' => $branch->id,
            'opening_balance' => 500,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'motor_parts_shop',
            'name' => 'Test Motor Parts',
            'opening_balance' => 500,
            'current_balance' => 500,
        ]);
    }

    public function test_super_admin_can_create_company_expense_account(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'company_expense',
            'name' => 'Test Company Expense',
            'branch_id' => $branch->id,
            'opening_balance' => 0,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'company_expense',
            'name' => 'Test Company Expense',
        ]);
    }

    public function test_empty_opening_balance_defaults_both_balances_to_zero(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'company_expense',
            'name' => 'Empty Balance Expense',
            'branch_id' => $branch->id,
            'opening_balance' => '',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'name' => 'Empty Balance Expense',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);
    }

    public function test_super_admin_can_update_account(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->fuelStation()->create();

        $response = $this->put(route('accounts.update', $account), [
            'type' => 'fuel_station',
            'name' => 'Updated Fuel Station',
            'linked_fuel_station_id' => $account->linked_fuel_station_id,
            'branch_id' => $account->branch_id,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Updated Fuel Station',
        ]);
    }

    public function test_super_admin_can_delete_account(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();

        $response = $this->delete(route('accounts.destroy', $account));

        $response->assertRedirect(route('accounts.index'));
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    // -------------------------------------------------------------------------
    // 2. Transactions
    // -------------------------------------------------------------------------

    public function test_super_admin_can_add_full_payment_transaction(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create(['opening_balance' => 0, 'current_balance' => 0]);
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 1500,
            'payment_mode' => 'cash',
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('accounts.show', $account));
        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $account->id,
            'direction' => 'debit',
            'amount' => 1500,
            'payment_plan' => 'full',
            'branch_id' => $branch->id,
        ]);

        $account->refresh();
        $this->assertEquals(1500, $account->current_balance);
    }

    public function test_super_admin_can_add_emi_transaction_with_installments(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create(['opening_balance' => 0, 'current_balance' => 0]);
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 2000,
            'payment_mode' => 'bank_transfer',
            'payment_plan' => 'emi',
            'installment_no' => 1,
            'installment_total' => 12,
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('accounts.show', $account));
        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $account->id,
            'direction' => 'credit',
            'amount' => 2000,
            'payment_plan' => 'emi',
            'installment_no' => 1,
            'installment_total' => 12,
        ]);
    }

    public function test_super_admin_can_add_partial_transaction(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create(['opening_balance' => 0, 'current_balance' => 0]);
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 750,
            'payment_mode' => 'upi',
            'payment_plan' => 'partial',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('accounts.show', $account));
        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $account->id,
            'payment_plan' => 'partial',
        ]);
    }

    // -------------------------------------------------------------------------
    // 3. Running balance correctness
    // -------------------------------------------------------------------------

    public function test_running_balance_chain_after_multiple_transactions(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create(['opening_balance' => 1000, 'current_balance' => 1000]);
        $branch = Branch::factory()->create();

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 500,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 200,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 300,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $txns = AccountTransaction::where('account_id', $account->id)->orderBy('id')->get();
        $this->assertEquals(1500, (float) $txns[0]->running_balance);
        $this->assertEquals(1300, (float) $txns[1]->running_balance);
        $this->assertEquals(1600, (float) $txns[2]->running_balance);

        $account->refresh();
        $this->assertEquals(1600, $account->current_balance);
    }

    public function test_running_balance_recalculates_after_transaction_edit(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create(['opening_balance' => 0, 'current_balance' => 0]);
        $branch = Branch::factory()->create();

        $txn = AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'debit',
            'amount' => 500,
            'running_balance' => 500,
        ]);

        $account->update(['current_balance' => 500]);

        $this->put(route('accounts.transactions.update', ['account' => $account, 'transaction' => $txn]), [
            'direction' => 'credit',
            'amount' => 500,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $newTxn = AccountTransaction::where('account_id', $account->id)->orderByDesc('id')->first();
        $this->assertNotNull($newTxn);
        $this->assertEquals(0, (float) $newTxn->running_balance);

        $account->refresh();
        $this->assertEquals(0, $account->current_balance);
    }

    public function test_running_balance_recalculates_after_transaction_delete(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create(['opening_balance' => 0, 'current_balance' => 0]);
        $branch = Branch::factory()->create();

        $txn1 = AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'debit',
            'amount' => 500,
            'running_balance' => 500,
        ]);

        $txn2 = AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 200,
            'running_balance' => 300,
        ]);

        $account->update(['current_balance' => 300]);

        $this->delete(route('accounts.transactions.destroy', ['account' => $account, 'transaction' => $txn1]));

        $this->assertSoftDeleted('account_transactions', ['id' => $txn1->id]);

        $txn2->refresh();
        $this->assertEquals(0, (float) $txn2->running_balance);

        $account->refresh();
        $this->assertEquals(0, $account->current_balance);
    }

    // -------------------------------------------------------------------------
    // 4. Super-admin-only access
    // -------------------------------------------------------------------------

    public function test_admin_is_forbidden_on_accounts_index(): void
    {
        $this->actingAsAdmin();
        $response = $this->get(route('accounts.index'));
        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_accounts_index(): void
    {
        $response = $this->get(route('accounts.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_is_forbidden_on_accounts_create(): void
    {
        $this->actingAsAdmin();
        $response = $this->get(route('accounts.create'));
        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_accounts_store(): void
    {
        $this->actingAsAdmin();
        $response = $this->post(route('accounts.store'), []);
        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_accounts_show(): void
    {
        $this->actingAsAdmin();
        $account = Account::factory()->create();
        $response = $this->get(route('accounts.show', $account));
        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_accounts_edit(): void
    {
        $this->actingAsAdmin();
        $account = Account::factory()->create();
        $response = $this->get(route('accounts.edit', $account));
        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_accounts_update(): void
    {
        $this->actingAsAdmin();
        $account = Account::factory()->create();
        $response = $this->put(route('accounts.update', $account), []);
        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_accounts_destroy(): void
    {
        $this->actingAsAdmin();
        $account = Account::factory()->create();
        $response = $this->delete(route('accounts.destroy', $account));
        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_transaction_store(): void
    {
        $this->actingAsAdmin();
        $account = Account::factory()->create();
        $response = $this->post(route('accounts.transactions.store', $account), []);
        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_transaction_update(): void
    {
        $this->actingAsAdmin();
        $account = Account::factory()->create();
        $txn = AccountTransaction::factory()->create(['account_id' => $account->id]);
        $response = $this->put(route('accounts.transactions.update', ['account' => $account, 'transaction' => $txn]), []);
        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_on_transaction_destroy(): void
    {
        $this->actingAsAdmin();
        $account = Account::factory()->create();
        $txn = AccountTransaction::factory()->create(['account_id' => $account->id]);
        $response = $this->delete(route('accounts.transactions.destroy', ['account' => $account, 'transaction' => $txn]));
        $response->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // 5. Filters
    // -------------------------------------------------------------------------

    public function test_filters_by_date_range(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = Branch::factory()->create();

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'transaction_date' => '2026-01-15',
            'amount' => 100,
        ]);

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'transaction_date' => '2026-06-15',
            'amount' => 200,
        ]);

        $response = $this->get(route('accounts.show', $account) . '?date_from=2026-06-01&date_to=2026-06-30');

        $response->assertOk();
        $response->assertSee('200');
        $response->assertDontSee('2026-01-15');
    }

    public function test_filters_by_direction(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = Branch::factory()->create();

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'debit',
            'amount' => 100,
            'transaction_date' => '2026-01-15',
        ]);

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 200,
            'transaction_date' => '2026-06-15',
        ]);

        $response = $this->get(route('accounts.show', $account) . '?direction=credit');

        $response->assertOk();
        $response->assertSee('200');
        $response->assertDontSee('2026-01-15');
    }

    public function test_filters_by_payment_mode(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = Branch::factory()->create();

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'payment_mode' => 'cash',
            'amount' => 100,
            'transaction_date' => '2026-01-15',
        ]);

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'payment_mode' => 'upi',
            'amount' => 200,
            'transaction_date' => '2026-06-15',
        ]);

        $response = $this->get(route('accounts.show', $account) . '?payment_mode=upi');

        $response->assertOk();
        $response->assertSee('200');
        $response->assertDontSee('2026-01-15');
    }

    public function test_filters_by_payment_plan(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = Branch::factory()->create();

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'payment_plan' => 'full',
            'amount' => 100,
            'transaction_date' => '2026-01-15',
        ]);

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'payment_plan' => 'emi',
            'amount' => 200,
            'transaction_date' => '2026-06-15',
        ]);

        $response = $this->get(route('accounts.show', $account) . '?payment_plan=emi');

        $response->assertOk();
        $response->assertSee('200');
        $response->assertDontSee('2026-01-15');
    }

    public function test_filters_by_month_and_year(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = Branch::factory()->create();

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'transaction_date' => '2026-01-15',
            'amount' => 100,
        ]);

        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'transaction_date' => '2026-06-15',
            'amount' => 200,
        ]);

        $response = $this->get(route('accounts.show', $account) . '?month=6&year=2026');

        $response->assertOk();
        $response->assertSee('200');
        $response->assertDontSee('2026-01-15');
    }

    // -------------------------------------------------------------------------
    // 6. Transport log show fuel station ledger integration
    // -------------------------------------------------------------------------

    public function test_transport_log_show_renders_fuel_station_ledger_section(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);

        $log = TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 500,
        ]);

        $response = $this->get(route('transport-logs.show', $log));

        $response->assertOk();
        $response->assertSee('Payment History for This Log');
        $response->assertSee('500.00');
        $response->assertSee('Unpaid');
        $response->assertSee('No payments recorded yet');
    }

    // -------------------------------------------------------------------------
    // 7. Validation failures
    // -------------------------------------------------------------------------

    public function test_create_account_requires_type(): void
    {
        $this->actingAsSuperAdmin();
        $response = $this->post(route('accounts.store'), [
            'name' => 'Test',
            'branch_id' => Branch::factory()->create()->id,
        ]);
        $response->assertSessionHasErrors('type');
    }

    public function test_create_account_requires_valid_type(): void
    {
        $this->actingAsSuperAdmin();
        $response = $this->post(route('accounts.store'), [
            'type' => 'invalid_type',
            'name' => 'Test',
            'branch_id' => Branch::factory()->create()->id,
        ]);
        $response->assertSessionHasErrors('type');
    }

    public function test_create_account_requires_name(): void
    {
        $this->actingAsSuperAdmin();
        $response = $this->post(route('accounts.store'), [
            'type' => 'fuel_station',
            'branch_id' => Branch::factory()->create()->id,
        ]);
        $response->assertSessionHasErrors('name');
    }

    public function test_create_account_requires_valid_branch(): void
    {
        $this->actingAsSuperAdmin();
        $response = $this->post(route('accounts.store'), [
            'type' => 'fuel_station',
            'name' => 'Test',
            'branch_id' => 99999,
        ]);
        $response->assertSessionHasErrors('branch_id');
    }

    public function test_create_transaction_requires_amount(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'transaction_date' => now()->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors('amount');
    }

    public function test_create_transaction_rejects_negative_amount(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => -10,
            'transaction_date' => now()->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors('amount');
    }

    public function test_create_transaction_requires_valid_direction(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'invalid',
            'amount' => 100,
            'transaction_date' => now()->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors('direction');
    }

    public function test_create_transaction_requires_installments_for_emi(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 100,
            'payment_plan' => 'emi',
            'transaction_date' => now()->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors('installment_no');
        $response->assertSessionHasErrors('installment_total');
    }

    public function test_create_transaction_validates_payment_mode_enum(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 100,
            'payment_mode' => 'invalid_mode',
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors('payment_mode');
    }

    public function test_create_transaction_validates_payment_plan_enum(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 100,
            'payment_plan' => 'invalid_plan',
            'transaction_date' => now()->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors('payment_plan');
    }

    public function test_create_transaction_validates_date(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 100,
            'payment_plan' => 'full',
            'transaction_date' => 'not-a-date',
        ]);
        $response->assertSessionHasErrors('transaction_date');
    }

    // -------------------------------------------------------------------------
    // 8. Soft-delete behavior
    // -------------------------------------------------------------------------

    public function test_soft_deleted_transaction_excluded_from_balance_summary(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create(['opening_balance' => 0, 'current_balance' => 0]);
        $branch = Branch::factory()->create();

        $txn = AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'debit',
            'amount' => 500,
            'running_balance' => 500,
        ]);

        $account->update(['current_balance' => 500]);

        $txn->delete();

        $this->assertSoftDeleted('account_transactions', ['id' => $txn->id]);

        $summary = app(\App\Services\AccountLedgerService::class)->getBalanceSummary($account);
        $this->assertEquals(0, $summary['total_debited']);
        $this->assertEquals(0, $summary['current_balance']);
    }

    public function test_soft_deleted_transaction_excluded_from_ledger(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = Branch::factory()->create();

        $txn = AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'amount' => 500,
        ]);

        $txn->delete();

        $transactions = app(\App\Services\AccountLedgerService::class)->getFilteredTransactions($account, []);
        $this->assertTrue($transactions->isEmpty());
    }

    // -------------------------------------------------------------------------
    // 9. Dashboard KPIs
    // -------------------------------------------------------------------------

    public function test_dashboard_shows_account_kpis_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();

        $fuelAccount = Account::factory()->fuelStation()->create(['current_balance' => 1000]);
        $motorAccount = Account::factory()->motorPartsShop()->create(['current_balance' => 2000]);
        $staffAccount = Account::factory()->staff()->create();
        $expenseAccount = Account::factory()->companyExpense()->create();
        $branch = Branch::factory()->create();

        AccountTransaction::factory()->create([
            'account_id' => $staffAccount->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 3000,
            'transaction_date' => now()->startOfMonth()->format('Y-m-d'),
        ]);

        AccountTransaction::factory()->create([
            'account_id' => $expenseAccount->id,
            'branch_id' => $branch->id,
            'direction' => 'debit',
            'amount' => 1500,
            'transaction_date' => now()->startOfMonth()->format('Y-m-d'),
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Total Fuel Station Dues');
        $response->assertSee('Total Motor Parts Dues');
        $response->assertSee('Monthly Staff Salary Paid');
        $response->assertSee('Monthly Company Expenses');
    }

    public function test_dashboard_hides_account_kpis_for_non_super_admin(): void
    {
        $this->actingAsAdmin();
        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('Total Fuel Station Dues');
        $response->assertDontSee('Total Motor Parts Dues');
    }

    // -------------------------------------------------------------------------
    // 10. Browser-like account creation (HTTP POST persists to DB)
    // -------------------------------------------------------------------------

    public function test_real_http_post_creates_fuel_station_account_in_database(): void
    {
        $this->actingAsSuperAdmin();
        $station = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'fuel_station',
            'name' => 'HTTP Fuel Station',
            'linked_fuel_station_id' => $station->id,
            'branch_id' => $branch->id,
            'opening_balance' => 2500,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'fuel_station',
            'name' => 'HTTP Fuel Station',
            'linked_fuel_station_id' => $station->id,
            'branch_id' => $branch->id,
            'opening_balance' => 2500,
            'current_balance' => 2500,
            'is_active' => true,
        ]);
    }

    public function test_real_http_post_creates_staff_account_with_empty_linked_fuel_station(): void
    {
        $this->actingAsSuperAdmin();
        $driver = Driver::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'staff',
            'name' => 'HTTP Staff',
            'linked_fuel_station_id' => '',
            'linked_driver_id' => $driver->id,
            'branch_id' => $branch->id,
            'aadhar_no' => '999999999999',
            'driving_license_no' => 'DL9999999999999',
            'opening_balance' => 0,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'staff',
            'name' => 'HTTP Staff',
            'linked_fuel_station_id' => null,
            'linked_driver_id' => $driver->id,
        ]);
    }

    public function test_real_http_post_creates_inactive_account_when_checkbox_unchecked(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'company_expense',
            'name' => 'HTTP Inactive Expense',
            'branch_id' => $branch->id,
            'opening_balance' => 0,
            'is_active' => null,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'company_expense',
            'name' => 'HTTP Inactive Expense',
            'is_active' => false,
        ]);
    }

    public function test_type_filter_pages_load_without_error_for_all_types(): void
    {
        $this->actingAsSuperAdmin();

        foreach (['fuel_station', 'motor_parts_shop', 'staff', 'company_expense'] as $type) {
            $response = $this->get('/accounts?type=' . $type);
            $response->assertOk();
            $response->assertSee(ucwords(str_replace('_', ' ', $type)) . ' Accounts');
        }
    }

    public function test_transaction_edit_view_loads_successfully(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = Branch::factory()->create();
        $transaction = \App\Models\AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
        ]);

        $response = $this->get(route('accounts.transactions.edit', [$account, $transaction]));

        $response->assertOk();
        $response->assertSee('Edit Transaction');
        $response->assertSee($transaction->description ?? '');
    }

    // -------------------------------------------------------------------------
    // 11. View existence checks for every accounts.* route
    // -------------------------------------------------------------------------

    public function test_accounts_index_view_renders_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();
        $response = $this->get(route('accounts.index'));
        $response->assertOk();
        $response->assertSee('Accounts Hub');
    }

    public function test_accounts_create_view_renders_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();
        $response = $this->get(route('accounts.create'));
        $response->assertOk();
        $response->assertSee('New Account');
    }

    public function test_accounts_show_view_renders_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->get(route('accounts.show', $account));
        $response->assertOk();
        $response->assertSee($account->name);
    }

    public function test_accounts_edit_view_renders_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->get(route('accounts.edit', $account));
        $response->assertOk();
        $response->assertSee('Edit Account');
    }

    public function test_type_filter_view_renders_for_all_types(): void
    {
        $this->actingAsSuperAdmin();
        foreach (['fuel_station', 'motor_parts_shop', 'staff', 'company_expense'] as $type) {
            $account = Account::factory()->create(['type' => $type]);
            $response = $this->get(route('accounts.index', ['type' => $type]));
            $response->assertOk();
            $response->assertSee(ucwords(str_replace('_', ' ', $type)) . ' Accounts');
        }
    }

    public function test_fuel_station_type_index_renders_account_names_in_html(): void
    {
        $this->actingAsSuperAdmin();
        $accounts = Account::factory()->fuelStation()->count(3)->create();

        $response = $this->get(route('accounts.index', ['type' => 'fuel_station']));

        $response->assertOk();
        foreach ($accounts as $account) {
            $response->assertSee($account->name);
        }
    }

    public function test_fuel_station_type_index_renders_all_pump_names_without_pagination(): void
    {
        $this->actingAsSuperAdmin();
        $accounts = Account::factory()->fuelStation()->count(20)->create();

        $response = $this->get(route('accounts.index', ['type' => 'fuel_station']));

        $response->assertOk();
        foreach ($accounts as $account) {
            $response->assertSee($account->name);
        }
    }

    // -------------------------------------------------------------------------
    // 12. Real HTTP POST persistence checks for each account type
    // -------------------------------------------------------------------------

    public function test_real_http_post_creates_motor_parts_shop_account_with_persistence(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'motor_parts_shop',
            'name' => 'Persistent Motor Parts',
            'branch_id' => $branch->id,
            'opening_balance' => 1500,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'motor_parts_shop',
            'name' => 'Persistent Motor Parts',
            'opening_balance' => 1500,
            'current_balance' => 1500,
        ]);
    }

    public function test_real_http_post_creates_company_expense_account_with_persistence(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'company_expense',
            'name' => 'Persistent Company Expense',
            'branch_id' => $branch->id,
            'opening_balance' => 0,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'company_expense',
            'name' => 'Persistent Company Expense',
        ]);
    }

    public function test_real_http_post_account_appears_in_type_list(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $this->post(route('accounts.store'), [
            'type' => 'staff',
            'name' => 'Listed Staff',
            'branch_id' => $branch->id,
            'aadhar_no' => '888888888888',
            'driving_license_no' => 'DL8888888888888',
            'opening_balance' => 0,
            'is_active' => '1',
        ]);

        $response = $this->get(route('accounts.index', ['type' => 'staff']));
        $response->assertOk();
        $response->assertSee('Listed Staff');
    }

    // -------------------------------------------------------------------------
    // 13. Validation and form behavior tests for fixed bugs
    // -------------------------------------------------------------------------

    public function test_transaction_store_requires_branch_id(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 100,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
        ]);
        $response->assertSessionHasErrors('branch_id');
    }

    public function test_transaction_update_changes_direction_correctly(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create(['opening_balance' => 0, 'current_balance' => 0]);
        $branch = Branch::factory()->create();
        $txn = AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'debit',
            'amount' => 500,
            'running_balance' => 500,
        ]);

        $this->put(route('accounts.transactions.update', ['account' => $account, 'transaction' => $txn]), [
            'direction' => 'credit',
            'amount' => 500,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $newTxn = AccountTransaction::where('account_id', $account->id)->orderByDesc('id')->first();
        $this->assertNotNull($newTxn);
        $this->assertEquals('credit', $newTxn->direction);
        $this->assertEquals(0, (float) $newTxn->running_balance);
    }

    public function test_account_create_with_empty_linked_fields_does_not_crash(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'motor_parts_shop',
            'name' => 'No Crash Account',
            'linked_fuel_station_id' => '',
            'linked_driver_id' => '',
            'branch_id' => $branch->id,
            'opening_balance' => 0,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'name' => 'No Crash Account',
            'linked_fuel_station_id' => null,
            'linked_driver_id' => null,
        ]);
    }

    public function test_balance_summary_matches_running_balance_after_credit(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create(['opening_balance' => 500, 'current_balance' => 500]);
        $branch = Branch::factory()->create();

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 300,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $summary = app(\App\Services\AccountLedgerService::class)->getBalanceSummary($account);
        $this->assertEquals(200, $summary['current_balance']);
        $this->assertEquals(200, $account->fresh()->current_balance);
    }

    // -------------------------------------------------------------------------
    // 14. Fuel settlement recalculation (transport log ↔ account transaction)
    // -------------------------------------------------------------------------

    public function test_linked_credit_transaction_updates_fuel_payment_status_to_paid(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 0,
            'fuel_payment_status' => 'unpaid',
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 1000,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
        ]);

        $log->refresh();
        $this->assertEquals(1000, (float) $log->fuel_paid_amount);
        $this->assertEquals('paid', $log->fuel_payment_status);
    }

    public function test_multiple_partial_payments_sum_correctly(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 0,
            'fuel_payment_status' => 'unpaid',
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 300,
            'payment_plan' => 'partial',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 200,
            'payment_plan' => 'partial',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
        ]);

        $log->refresh();
        $this->assertEquals(500, (float) $log->fuel_paid_amount);
        $this->assertEquals('partial', $log->fuel_payment_status);
    }

    public function test_deleting_linked_payment_recalculates_status(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 1000,
            'fuel_payment_status' => 'paid',
        ]);

        $txn = \App\Models\AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 1000,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $this->delete(route('accounts.transactions.destroy', ['account' => $account, 'transaction' => $txn]));

        $log->refresh();
        $this->assertEquals(0, (float) $log->fuel_paid_amount);
        $this->assertEquals('unpaid', $log->fuel_payment_status);
    }

    public function test_overpayment_is_flagged_correctly(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 0,
            'fuel_payment_status' => 'unpaid',
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 1500,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
        ]);

        $log->refresh();
        $this->assertEquals(1500, (float) $log->fuel_paid_amount);
        $this->assertEquals('overpaid', $log->fuel_payment_status);
    }

    public function test_fuel_stations_scope_avoids_n_plus_one(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);

        \App\Models\AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'debit',
            'amount' => 500,
        ]);

        $accounts = Account::fuelStations()->get();

        $this->assertCount(1, $accounts);
        $this->assertEquals('fuel_station', $accounts->first()->type);
        $this->assertTrue($accounts->first()->relationLoaded('transactions'));
    }

    // -------------------------------------------------------------------------
    // 15. Transport log search endpoint
    // -------------------------------------------------------------------------

    public function test_transport_log_search_returns_matches_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $log = \App\Models\TransportLog::factory()->create([
            'vehicle_no' => 'ABC-1234',
            'logsheet_no' => 'LS-001',
            'date' => '2026-08-01',
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1500,
        ]);

        $response = $this->get('/accounts/transport-logs/search?q=ABC-1234');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $log->id, 'vehicle_no' => 'ABC-1234']);
        $response->assertJsonFragment(['diesel_advance' => 1500]);
    }

    public function test_transport_log_search_blocks_non_super_admin(): void
    {
        $this->actingAsAdmin();
        $response = $this->get('/accounts/transport-logs/search?q=test');
        $response->assertForbidden();
    }

    public function test_transport_log_search_blocks_guest(): void
    {
        $response = $this->get('/accounts/transport-logs/search?q=test');
        $response->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // 16. End-to-end linked transport log settlement via HTTP
    // -------------------------------------------------------------------------

    public function test_linked_transaction_end_to_end_updates_settlement_status(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 0,
            'fuel_payment_status' => 'unpaid',
        ]);

        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 1000,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
        ]);

        $response->assertRedirect(route('accounts.show', $account));

        $log->refresh();
        $this->assertEquals(1000, (float) $log->fuel_paid_amount);
        $this->assertEquals('paid', $log->fuel_payment_status);
    }

    public function test_multiple_partial_payments_end_to_end_update_settlement(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 0,
            'fuel_payment_status' => 'unpaid',
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 400,
            'payment_plan' => 'partial',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 300,
            'payment_plan' => 'partial',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
        ]);

        $log->refresh();
        $this->assertEquals(700, (float) $log->fuel_paid_amount);
        $this->assertEquals('partial', $log->fuel_payment_status);
    }

    public function test_deleting_linked_payment_end_to_end_recalculates_status(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 1000,
            'fuel_payment_status' => 'paid',
        ]);

        $txn = \App\Models\AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 1000,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $this->delete(route('accounts.transactions.destroy', ['account' => $account, 'transaction' => $txn]));

        $log->refresh();
        $this->assertEquals(0, (float) $log->fuel_paid_amount);
        $this->assertEquals('unpaid', $log->fuel_payment_status);
    }

    public function test_overpayment_end_to_end_is_flagged(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 0,
            'fuel_payment_status' => 'unpaid',
        ]);

        $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'credit',
            'amount' => 1500,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
        ]);

        $log->refresh();
        $this->assertEquals(1500, (float) $log->fuel_paid_amount);
        $this->assertEquals('overpaid', $log->fuel_payment_status);
    }

    // -------------------------------------------------------------------------
    // 17. Transport log show page payment history
    // -------------------------------------------------------------------------

    public function test_transport_log_show_renders_payment_history_for_fuel_station(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
        ]);

        \App\Models\AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 500,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->get(route('transport-logs.show', $log));

        $response->assertOk();
        $response->assertSee('Payment History for This Log');
        $response->assertSee('500.00');
        $response->assertSee('Partial');
    }

    public function test_transport_log_show_shows_empty_state_when_no_payments(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
        ]);

        $response = $this->get(route('transport-logs.show', $log));

        $response->assertOk();
        $response->assertSee('Payment History for This Log');
        $response->assertSee('No payments recorded yet');
    }

    // -------------------------------------------------------------------------
    // 18. Transport log edit page compact payment history
    // -------------------------------------------------------------------------

    public function test_transport_log_edit_shows_compact_payment_history(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_paid_amount' => 500,
            'fuel_payment_status' => 'partial',
        ]);

        \App\Models\AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 500,
            'reference_type' => \App\Models\TransportLog::class,
            'reference_id' => $log->id,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->get(route('transport-logs.edit', $log));

        $response->assertOk();
        $response->assertSee('Fuel Settlement');
        $response->assertSee('Partial');
        $response->assertSee('500.00');
    }

    // -------------------------------------------------------------------------
    // 19. Dashboard pending fuel settlements KPI
    // -------------------------------------------------------------------------

    public function test_dashboard_shows_pending_fuel_settlements_kpi(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $account = Account::factory()->fuelStation()->create(['linked_fuel_station_id' => $fuelStation->id]);
        \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 1000,
            'fuel_payment_status' => 'partial',
            'fuel_paid_amount' => 400,
        ]);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Pending Fuel Settlements');
    }

    // -------------------------------------------------------------------------
    // 20. BUG fixes verification
    // -------------------------------------------------------------------------

    public function test_filters_bar_uses_custom_event_instead_of_form_submit(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = Branch::factory()->create();
        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'amount' => 100,
        ]);

        $response = $this->get(route('accounts.show', $account));

        $response->assertOk();
        $response->assertDontSee('form.submit()');
        $response->assertSee('ledger-filters-changed');
        $response->assertSee('CustomEvent');
        $response->assertSee('dispatchEvent');
    }

    public function test_transport_log_edit_has_single_fuel_settlement_block(): void
    {
        $this->actingAsSuperAdmin();
        $fuelStation = \App\Models\FuelStation::factory()->create();
        $branch = Branch::factory()->create();
        $log = \App\Models\TransportLog::factory()->create([
            'fuel_station_id' => $fuelStation->id,
            'branch_id' => $branch->id,
            'diesel_advance' => 500,
        ]);

        $response = $this->get(route('transport-logs.edit', $log));

        $response->assertOk();
        $response->assertSee('Fuel Settlement');
        $response->assertSee('Remaining Due');
        $response->assertSee('500.00');
        $response->assertDontSee('No payments recorded for this log yet');
    }

    public function test_fuel_station_type_index_renders_step_flow(): void
    {
        $this->actingAsSuperAdmin();
        $accounts = collect();
        for ($i = 1; $i <= 8; $i++) {
            $accounts->push(Account::factory()->fuelStation()->create(['name' => 'Pump Station ' . $i]));
        }

        $response = $this->get(route('accounts.index', ['type' => 'fuel_station']));

        $response->assertOk();
        $response->assertSee('Search pumps...');
        $response->assertSee('Back to all pumps');
        $response->assertSee('fuelStationFlow');
        foreach ($accounts as $account) {
            $response->assertSee($account->name);
        }
    }

    public function test_fuel_station_pump_flow_renders_summary_and_transactions(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->fuelStation()->create();
        $branch = Branch::factory()->create();
        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'direction' => 'credit',
            'amount' => 250,
            'transaction_date' => '2026-01-10',
            'running_balance' => 250,
        ]);

        $response = $this->get(route('accounts.pump-flow', $account));

        $response->assertOk();
        $response->assertSee('Current Balance');
        $response->assertSee('Credited');
        $response->assertSee('Debited');
        $response->assertSee('Balance');
        $response->assertSee('2026-01-10');
    }

    public function test_ledger_table_uses_responsive_card_breakpoint(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = Branch::factory()->create();
        AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'amount' => 100,
        ]);

        $response = $this->get(route('accounts.show', $account));

        $response->assertOk();
        $response->assertSee('lg:hidden');
        $response->assertSee('hidden lg:block');
    }
}
