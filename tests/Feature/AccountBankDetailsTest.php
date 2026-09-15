<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\FuelStation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountBankDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_valid_ifsc_format_accepted(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'staff',
            'name' => 'Banked Staff',
            'branch_id' => $branch->id,
            'aadhar_no' => '111111111111',
            'bank_account_no' => '123456789012',
            'bank_ifsc_code' => 'sbin0001234',
            'bank_name' => 'State Bank of India',
            'opening_balance' => 0,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'staff',
            'name' => 'Banked Staff',
            'bank_account_no' => '123456789012',
            'bank_ifsc_code' => 'SBIN0001234',
            'bank_name' => 'State Bank of India',
        ]);
    }

    public function test_invalid_ifsc_format_rejected(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'staff',
            'name' => 'Invalid IFSC Staff',
            'branch_id' => $branch->id,
            'aadhar_no' => '222222222222',
            'bank_account_no' => '123456789012',
            'bank_ifsc_code' => 'INVALID123',
            'bank_name' => 'Some Bank',
            'opening_balance' => 0,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('bank_ifsc_code');
    }

    public function test_bank_detail_fields_are_optional(): void
    {
        $this->actingAsSuperAdmin();
        $station = FuelStation::factory()->create();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'fuel_station',
            'name' => 'Pump No Bank',
            'linked_fuel_station_id' => $station->id,
            'branch_id' => $branch->id,
            'opening_balance' => 0,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'type' => 'fuel_station',
            'name' => 'Pump No Bank',
            'bank_account_no' => null,
            'bank_ifsc_code' => null,
            'bank_name' => null,
        ]);
    }

    public function test_partial_bank_fields_persist_when_some_provided(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->post(route('accounts.store'), [
            'type' => 'staff',
            'name' => 'Partial Bank Staff',
            'branch_id' => $branch->id,
            'aadhar_no' => '333333333333',
            'bank_account_no' => '9876543210',
            'opening_balance' => 0,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'bank_account_no' => '9876543210',
            'bank_ifsc_code' => null,
            'bank_name' => null,
        ]);
    }

    public function test_staff_show_page_displays_masked_bank_details(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();
        $account = Account::factory()->staff()->create([
            'branch_id' => $branch->id,
            'bank_account_no' => '556677889900',
            'bank_ifsc_code' => 'sbin0001234',
            'bank_name' => 'State Bank of India',
        ]);

        $response = $this->get(route('accounts.show', $account));

        $response->assertOk();
        $response->assertSee('Bank Details');
        $response->assertSee('XXXX XXXX 9900');
        $response->assertSee('SBIN0001234');
        $response->assertSee('State Bank of India');
    }

    public function test_pump_flow_displays_masked_bank_details_for_fuel_station(): void
    {
        $this->actingAsSuperAdmin();
        $station = FuelStation::factory()->create();
        $account = Account::factory()->fuelStation()->create([
            'linked_fuel_station_id' => $station->id,
            'bank_account_no' => '123456789012',
            'bank_ifsc_code' => 'sbin0001234',
            'bank_name' => 'State Bank of India',
        ]);

        $response = $this->get(route('accounts.pump-flow', $account));

        $response->assertOk();
        $response->assertSee('Bank Details');
        $response->assertSee('XXXX XXXX 9012');
        $response->assertSee('SBIN0001234');
        $response->assertSee('State Bank of India');
    }

    public function test_staff_show_page_omits_bank_details_when_empty(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();
        $account = Account::factory()->staff()->create([
            'branch_id' => $branch->id,
        ]);

        $response = $this->get(route('accounts.show', $account));

        $response->assertOk();
        $response->assertDontSee('Bank Details');
        $response->assertDontSee('XXXX XXXX');
    }
}
