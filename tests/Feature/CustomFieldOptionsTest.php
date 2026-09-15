<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\CustomFieldOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomFieldOptionsTest extends TestCase
{
    use RefreshDatabase;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CustomFieldOptionSeeder::class);
    }

    public function test_super_admin_can_view_custom_fields_settings(): void
    {
        $superAdmin = $this->actingAsSuperAdmin();
        $response = $this->get(route('settings.custom-fields.index'));
        $response->assertOk();
    }

    public function test_admin_cannot_access_custom_fields_settings(): void
    {
        $this->actingAsAdmin();
        $response = $this->get(route('settings.custom-fields.index'));
        $response->assertForbidden();
    }

    public function test_guest_cannot_access_custom_fields_settings(): void
    {
        $response = $this->get(route('settings.custom-fields.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_add_new_payment_mode(): void
    {
        $superAdmin = $this->actingAsSuperAdmin();
        $response = $this->post(route('settings.custom-fields.store'), [
            'field_key' => 'payment_mode',
            'label' => 'Digital Wallet',
            'value' => 'digital_wallet',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('custom_field_options', [
            'field_key' => 'payment_mode',
            'label' => 'Digital Wallet',
            'value' => 'digital_wallet',
            'is_active' => true,
            'created_by' => $superAdmin->id,
        ]);
    }

    public function test_super_admin_can_deactivate_payment_mode_option(): void
    {
        $this->actingAsSuperAdmin();
        $option = CustomFieldOption::where('field_key', 'payment_mode')->first();

        $response = $this->delete(route('settings.custom-fields.destroy', $option->id));
        $response->assertRedirect();

        $option->refresh();
        $this->assertFalse($option->is_active);
    }

    public function test_new_payment_mode_appears_in_transaction_form(): void
    {
        $superAdmin = $this->actingAsSuperAdmin();
        CustomFieldOption::create([
            'field_key' => 'payment_mode',
            'label' => 'Digital Wallet',
            'value' => 'digital_wallet',
            'sort_order' => 60,
            'is_active' => true,
            'created_by' => $superAdmin->id,
        ]);

        $account = Account::factory()->create();

        $response = $this->get(route('accounts.show', $account));
        $response->assertOk();
        $response->assertSee('Digital Wallet');
    }

    public function test_transaction_validation_accepts_new_payment_mode(): void
    {
        $superAdmin = $this->actingAsSuperAdmin();
        CustomFieldOption::create([
            'field_key' => 'payment_mode',
            'label' => 'Digital Wallet',
            'value' => 'digital_wallet',
            'sort_order' => 60,
            'is_active' => true,
            'created_by' => $superAdmin->id,
        ]);

        $account = Account::factory()->create();
        $branch = \App\Models\Branch::factory()->create();

        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 100,
            'payment_mode' => 'digital_wallet',
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('accounts.show', $account));
        $this->assertDatabaseHas('account_transactions', [
            'account_id' => $account->id,
            'payment_mode' => 'digital_wallet',
        ]);
    }

    public function test_deactivated_option_not_accepted_in_new_transactions(): void
    {
        $this->actingAsSuperAdmin();
        $option = CustomFieldOption::where('field_key', 'payment_mode')->first();
        $option->update(['is_active' => false]);

        $account = Account::factory()->create();
        $branch = \App\Models\Branch::factory()->create();

        $response = $this->post(route('accounts.transactions.store', $account), [
            'direction' => 'debit',
            'amount' => 100,
            'payment_mode' => $option->value,
            'payment_plan' => 'full',
            'transaction_date' => now()->format('Y-m-d'),
            'branch_id' => $branch->id,
        ]);

        $response->assertSessionHasErrors('payment_mode');
    }

    public function test_historical_transactions_preserved_after_deactivation(): void
    {
        $this->actingAsSuperAdmin();
        $account = Account::factory()->create();
        $branch = \App\Models\Branch::factory()->create();

        $transaction = AccountTransaction::factory()->create([
            'account_id' => $account->id,
            'branch_id' => $branch->id,
            'payment_mode' => 'cash',
            'payment_plan' => 'full',
        ]);

        $option = CustomFieldOption::where('field_key', 'payment_mode')->where('value', 'cash')->first();
        $option->update(['is_active' => false]);

        $this->assertDatabaseHas('account_transactions', [
            'id' => $transaction->id,
            'payment_mode' => 'cash',
        ]);

        $response = $this->get(route('accounts.show', $account));
        $response->assertOk();
        $response->assertSee('cash');
    }

    public function test_reorder_endpoint_works_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();
        $modes = CustomFieldOption::forField('payment_mode')->ordered()->get();
        $ordered = $modes->pluck('id')->reverse()->values()->toArray();

        $response = $this->patch(route('settings.custom-fields.reorder'), [
            'field_key' => 'payment_mode',
            'order' => $ordered,
        ]);

        $response->assertRedirect();
    }

    public function test_update_endpoint_works_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();
        $option = CustomFieldOption::where('field_key', 'payment_mode')->first();

        $response = $this->put(route('settings.custom-fields.update', $option->id), [
            'label' => 'Updated Label',
            'value' => $option->value,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('custom_field_options', [
            'id' => $option->id,
            'label' => 'Updated Label',
        ]);
    }
}