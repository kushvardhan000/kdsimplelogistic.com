<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffAccountIdentityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->superAdmin()->create();
        $this->actingAs($this->admin);
    }

    private function validStaffPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'staff',
            'name' => 'Test Staff',
            'branch_id' => Branch::factory()->create()->id,
            'aadhar_no' => '123456789012',
            'is_active' => true,
        ], $overrides);
    }

    public function test_creating_staff_account_without_aadhar_no_fails_validation(): void
    {
        $payload = $this->validStaffPayload(['aadhar_no' => '']);
        $response = $this->post(route('accounts.store'), $payload);
        $response->assertSessionHasErrors('aadhar_no');
    }

    public function test_creating_staff_account_with_invalid_aadhar_format_fails(): void
    {
        $payload = $this->validStaffPayload(['aadhar_no' => '12345ABCD7890']);
        $response = $this->post(route('accounts.store'), $payload);
        $response->assertSessionHasErrors('aadhar_no');

        $payload = $this->validStaffPayload(['aadhar_no' => '12345678901']);
        $response = $this->post(route('accounts.store'), $payload);
        $response->assertSessionHasErrors('aadhar_no');
    }

    public function test_creating_staff_account_with_is_driver_checked_but_no_license_fails(): void
    {
        $payload = $this->validStaffPayload([
            'is_driver' => '1',
            'driving_license_no' => '',
        ]);
        $response = $this->post(route('accounts.store'), $payload);
        $response->assertSessionHasErrors('driving_license_no');
    }

    public function test_creating_staff_account_with_valid_driver_details_succeeds(): void
    {
        $driver = Driver::factory()->create(['license_no' => 'DL1420110012345']);
        $payload = $this->validStaffPayload([
            'is_driver' => '1',
            'driving_license_no' => 'DL1420110012345',
            'linked_driver_id' => $driver->id,
        ]);

        $response = $this->post(route('accounts.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $account = Account::where('type', 'staff')->where('aadhar_no', '123456789012')->first();
        $this->assertNotNull($account);
        $this->assertEquals('DL1420110012345', $account->driving_license_no);
        $this->assertEquals($driver->id, $account->linked_driver_id);
    }

    public function test_formatted_aadhar_number_is_normalized_before_creation(): void
    {
        $response = $this->post(route('accounts.store'), $this->validStaffPayload([
            'aadhar_no' => '1234 5678 9012',
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', [
            'type' => 'staff',
            'aadhar_no' => '123456789012',
        ]);
    }

    public function test_staff_account_show_page_displays_masked_aadhar_and_license(): void
    {
        $account = Account::factory()->staff()->create([
            'aadhar_no' => '123456789012',
            'driving_license_no' => 'DL1420110012345',
        ]);

        $response = $this->get(route('accounts.show', $account));
        $response->assertOk();
        $response->assertSee('XXXX XXXX 9012');
        $response->assertSee('DL1420110012345');
    }

    public function test_non_staff_account_does_not_require_aadhar(): void
    {
        $payload = [
            'type' => 'fuel_station',
            'name' => 'Test Pump',
            'branch_id' => Branch::factory()->create()->id,
            'linked_fuel_station_id' => \App\Models\FuelStation::factory()->create()->id,
        ];

        $response = $this->post(route('accounts.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_aadhar_no_is_unique_across_staff_accounts(): void
    {
        Account::factory()->staff()->create(['aadhar_no' => '111111111111']);

        $payload = $this->validStaffPayload(['aadhar_no' => '111111111111']);
        $response = $this->post(route('accounts.store'), $payload);
        $response->assertSessionHasErrors('aadhar_no');
    }
}
