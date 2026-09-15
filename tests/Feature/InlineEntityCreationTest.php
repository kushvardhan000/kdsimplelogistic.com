<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InlineEntityCreationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);

        return $user;
    }

    // -------------------------------------------------------------------------
    // Branch inline creation
    // -------------------------------------------------------------------------

    public function test_inline_create_branch_persists_and_reflected_in_accounts_dropdown(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->postJson(route('entities.branches.store'), [
            'name' => 'Delhi Branch',
            'code' => 'DEL',
            'address' => 'Delhi, India',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('entity.name', 'Delhi Branch');

        $this->assertDatabaseHas('branches', [
            'name' => 'Delhi Branch',
            'code' => 'DEL',
            'address' => 'Delhi, India',
        ]);

        $dropdown = $this->get(route('accounts.create'));

        $dropdown->assertOk();
        $dropdown->assertSee('Delhi Branch');
        $dropdown->assertSee('DEL');
    }

    public function test_inline_create_branch_normalizes_code_to_uppercase(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson(route('entities.branches.store'), [
            'name' => 'Mumbai Branch',
            'code' => 'bom',
        ]);

        $this->assertDatabaseHas('branches', [
            'name' => 'Mumbai Branch',
            'code' => 'BOM',
        ]);
    }

    public function test_inline_create_branch_requires_name_and_code(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->postJson(route('entities.branches.store'), [
            'name' => '',
            'code' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'code']);
        $this->assertDatabaseMissing('branches', ['name' => '']);
    }

    public function test_inline_create_branch_rejects_duplicate_code(): void
    {
        $this->actingAsSuperAdmin();
        Branch::factory()->create(['code' => 'DEL']);

        $response = $this->postJson(route('entities.branches.store'), [
            'name' => 'Delhi Branch Two',
            'code' => 'del',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('code');
    }

    // -------------------------------------------------------------------------
    // Fuel station inline creation
    // -------------------------------------------------------------------------

    public function test_inline_create_fuel_station_persists_and_reflected_in_accounts_dropdown(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->postJson(route('entities.fuel-stations.store'), [
            'name' => 'City Petrol Pump',
            'branch_id' => $branch->id,
            'contact_info' => '9876543210',
            'address' => '123 Main Road',
            'is_active' => '1',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('entity.name', 'City Petrol Pump');

        $this->assertDatabaseHas('fuel_stations', [
            'name' => 'City Petrol Pump',
            'slug' => 'city-petrol-pump',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $dropdown = $this->get(route('accounts.create'));

        $dropdown->assertOk();
        $dropdown->assertSee('City Petrol Pump');
    }

    public function test_inline_create_fuel_station_is_optional_with_branch(): void
    {
        $this->actingAsSuperAdmin();
        $branch = Branch::factory()->create();

        $response = $this->postJson(route('entities.fuel-stations.store'), [
            'name' => 'No Branch Pump',
            'branch_id' => null,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('fuel_stations', [
            'name' => 'No Branch Pump',
            'slug' => 'no-branch-pump',
            'branch_id' => null,
        ]);
    }

    public function test_inline_create_fuel_station_requires_name(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->postJson(route('entities.fuel-stations.store'), [
            'name' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_inline_create_fuel_station_validates_branch_exists(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->postJson(route('entities.fuel-stations.store'), [
            'name' => 'Bad Branch Pump',
            'branch_id' => 9999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('branch_id');
    }

    public function test_inline_create_fuel_station_generates_unique_slug(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson(route('entities.fuel-stations.store'), ['name' => 'Dup Pump']);
        $this->postJson(route('entities.fuel-stations.store'), ['name' => 'Dup Pump']);

        $this->assertDatabaseHas('fuel_stations', ['name' => 'Dup Pump', 'slug' => 'dup-pump']);
        $this->assertDatabaseHas('fuel_stations', ['name' => 'Dup Pump', 'slug' => 'dup-pump-2']);
    }

    // -------------------------------------------------------------------------
    // Form rendering
    // -------------------------------------------------------------------------

    public function test_accounts_create_form_renders_add_new_affordances_and_modals(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(route('accounts.create'));

        $response->assertOk();
        $response->assertSee('add-fuel-station-modal');
        $response->assertSee('add-branch-modal');
        $response->assertSee('Add new fuel station', false);
        $response->assertSee('Add new branch', false);
    }

    public function test_transport_log_create_form_renders_fuel_station_modal(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(route('transport-logs.create'));

        $response->assertOk();
        $response->assertSee('add-fuel-station-modal');
    }

    public function test_transport_log_create_form_keeps_new_tab_fallback_link(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(route('transport-logs.create'));

        $response->assertOk();
        $response->assertSee(route('accounts.create', ['type' => 'fuel_station']));
    }

    public function test_inline_create_requires_authentication(): void
    {
        $response = $this->postJson(route('entities.branches.store'), [
            'name' => 'Ghost Branch',
            'code' => 'GHO',
        ]);

        $response->assertStatus(401);
    }

    public function test_inline_create_branch_then_select_on_create_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson(route('entities.branches.store'), [
            'name' => 'Create-Select Branch',
            'code' => 'CSB',
        ]);

        $dropdown = $this->get(route('accounts.create'));

        $dropdown->assertOk();
        $dropdown->assertSee('Create-Select Branch (CSB)');
        $dropdown->assertSee('name="branch_id"', false);
    }
}
