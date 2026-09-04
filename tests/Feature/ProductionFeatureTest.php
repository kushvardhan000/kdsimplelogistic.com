<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TransportLog;
use App\Models\ActivityLog;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')->assertOk()->assertSee('Email');
    }

    public function test_guest_can_view_forgot_password_page(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Forgot Password');
    }

    public function test_guest_redirected_to_login_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guest_redirected_to_login_from_transport_logs(): void
    {
        $this->get('/transport-logs')->assertRedirect('/login');
    }

    public function test_guest_redirected_to_login_from_users(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_guest_redirected_to_login_from_settings(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_guest_redirected_to_login_from_activity_logs(): void
    {
        $this->get('/activity-logs')->assertRedirect('/login');
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function test_logout_redirects_to_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/logout');

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_logout_regenerates_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $originalToken = session()->token();

        $response = $this->followingRedirects()->post('/logout');
        $newToken = $response->session()->token();

        $this->assertNotEquals($originalToken, $newToken);
    }

    public function test_logout_writes_activity_log(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/logout');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
            'table_name' => 'users',
            'record_id' => $user->id,
            'description' => 'User logged out',
        ]);
    }

    public function test_logout_succeeds_even_if_activity_log_creation_fails(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        ActivityLog::creating(fn () => throw new \RuntimeException('forced failure'));

        try {
            $response = $this->post('/logout');

            $response->assertRedirect('/login');
            $this->assertGuest();
        } finally {
            ActivityLog::flushEventListeners();
        }
    }

    public function test_logout_returns_204_for_json_requests(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/logout');

        $response->assertStatus(204);
    }

    public function test_logout_response_has_no_cache_headers(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');
    }

    public function test_second_logout_does_not_crash(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');
    }

    // -------------------------------------------------------------------------
    // Authorization & Policies
    // -------------------------------------------------------------------------

    public function test_super_admin_can_access_all_areas(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
        $this->get('/users')->assertOk();
        $this->get('/settings')->assertOk();
        $this->get('/transport-logs')->assertOk();
        $this->get('/activity-logs')->assertOk();
    }

    public function test_admin_cannot_access_super_admin_areas(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);

        $this->get('/users')->assertForbidden();
        $this->get('/activity-logs')->assertForbidden();
        $this->get('/transport-logs')->assertOk();
    }

    public function test_admin_can_create_transport_log(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);

        $response = $this->get('/transport-logs/create');
        $response->assertOk();
        $response->assertSee('New Transport Log');
    }

    public function test_inactive_user_cannot_create_transport_log(): void
    {
        $user = User::factory()->admin()->create(['is_active' => false]);

        $this->actingAs($user);

        $this->post('/transport-logs')
            ->assertRedirect('/login');
    }

    public function test_super_admin_can_view_all_transport_logs_via_policy(): void
    {
        $user = User::factory()->superAdmin()->create();
        $log = TransportLog::factory()->create();

        $this->actingAs($user);

        $this->get('/transport-logs')->assertOk();
        $this->get('/transport-logs/' . $log->id)->assertOk();
    }

    public function test_admin_can_view_all_transport_logs_via_policy(): void
    {
        $user = User::factory()->admin()->create();
        $log = TransportLog::factory()->create();

        $this->actingAs($user);

        $this->get('/transport-logs')->assertOk();
        $this->get('/transport-logs/' . $log->id)->assertOk();
    }

    // -------------------------------------------------------------------------
    // Transport Log CRUD & Calculations
    // -------------------------------------------------------------------------

    public function test_super_admin_can_create_transport_log(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $response = $this->post('/transport-logs', [
            'date' => '2025-01-15',
            'vehicle_no' => 'MH01AB1234',
            'company' => 'Test Company',
            'transport_name' => 'Test Transport',
            'destination' => 'Mumbai',
            'km' => 100,
            'weight' => 50,
            'to_bb_sale' => 1000,
            'paid_sale' => 2000,
            'to_pay' => 500,
            'freight' => 500,
            'loading' => 100,
            'unloading' => 100,
            'dd' => 50,
            'tempu_expense' => 100,
            'commission' => 200,
            'payment' => 3000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transport_logs', [
            'company' => 'Test Company',
            'total_sale' => 3500,
            'total_expense' => 1050,
            'profit' => 2450,
            'total_advance' => 0,
            'balance_vehicle_payment' => 500,
        ]);
    }

    public function test_computed_fields_are_enforced_server_side(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $this->post('/transport-logs', [
            'date' => '2025-01-15',
            'vehicle_no' => 'MH01AB1234',
            'company' => 'Test Company',
            'transport_name' => 'Test Transport',
            'destination' => 'Mumbai',
            'km' => 100,
            'weight' => 50,
            'to_bb_sale' => 1000,
            'paid_sale' => 2000,
            'to_pay' => 500,
            'total_sale' => 99999,
            'freight' => 500,
            'loading' => 100,
            'unloading' => 100,
            'dd' => 50,
            'tempu_expense' => 100,
            'commission' => 200,
            'total_expense' => 99999,
            'profit' => 99999,
            'payment' => 3000,
        ]);

        $this->assertDatabaseHas('transport_logs', [
            'total_sale' => 3500,
            'total_expense' => 1050,
            'profit' => 2450,
        ]);
        $this->assertDatabaseMissing('transport_logs', ['total_sale' => 99999]);
    }

    public function test_computed_fields_can_go_negative(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $this->post('/transport-logs', [
            'date' => '2025-01-15',
            'vehicle_no' => 'MH01AB1234',
            'company' => 'Test Company',
            'transport_name' => 'Test Transport',
            'destination' => 'Mumbai',
            'km' => 100,
            'weight' => 50,
            'to_bb_sale' => 100,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 5000,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'payment' => 0,
        ]);

        $this->assertDatabaseHas('transport_logs', [
            'profit' => -4900,
        ]);
    }

    public function test_super_admin_can_update_transport_log(): void
    {
        $user = User::factory()->superAdmin()->create();
        $log = TransportLog::factory()->create();

        $this->actingAs($user);

        $this->put('/transport-logs/' . $log->id, [
            'date' => $log->date,
            'vehicle_no' => $log->vehicle_no,
            'company' => $log->company,
            'transport_name' => $log->transport_name,
            'destination' => $log->destination,
            'km' => $log->km,
            'weight' => $log->weight,
            'to_bb_sale' => 5000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 2000,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
            'payment' => 4000,
        ])->assertRedirect('/transport-logs/' . $log->id);

        $this->assertDatabaseHas('transport_logs', [
            'id' => $log->id,
            'total_sale' => 5000,
            'total_expense' => 2000,
            'profit' => 3000,
            'balance_vehicle_payment' => 1000,
        ]);
    }

    public function test_super_admin_can_delete_transport_log(): void
    {
        $user = User::factory()->superAdmin()->create();
        $log = TransportLog::factory()->create();

        $this->actingAs($user);

        $this->delete('/transport-logs/' . $log->id)
            ->assertRedirect('/transport-logs');

        $this->assertSoftDeleted('transport_logs', ['id' => $log->id]);
    }

    public function test_admin_cannot_delete_transport_log(): void
    {
        $user = User::factory()->admin()->create();
        $log = TransportLog::factory()->create();

        $this->actingAs($user);

        $this->delete('/transport-logs/' . $log->id)
            ->assertForbidden();

        $this->assertDatabaseHas('transport_logs', ['id' => $log->id, 'deleted_at' => null]);
    }

    // -------------------------------------------------------------------------
    // Search, Filter, Sort, Pagination
    // -------------------------------------------------------------------------

    public function test_transport_logs_index_supports_search(): void
    {
        $user = User::factory()->superAdmin()->create();
        TransportLog::factory()->create(['vehicle_no' => 'MH01AB1234']);
        TransportLog::factory()->create(['vehicle_no' => 'DL05CD5678']);

        $this->actingAs($user);

        $response = $this->get('/transport-logs?search=MH01AB1234');
        $response->assertOk();
        $response->assertSee('MH01AB1234');
        $response->assertDontSee('DL05CD5678');
    }

    public function test_transport_logs_index_supports_company_filter(): void
    {
        $user = User::factory()->superAdmin()->create();
        TransportLog::factory()->create(['company' => 'Alpha Logistics']);
        TransportLog::factory()->create(['company' => 'Beta Transport']);

        $this->actingAs($user);

        $response = $this->get('/transport-logs?company=Alpha');
        $response->assertOk();
        $response->assertSee('Alpha Logistics');
        $response->assertDontSee('Beta Transport');
    }

    public function test_transport_logs_index_defaults_to_newest_created_first(): void
    {
        $user = User::factory()->superAdmin()->create();
        $log1 = TransportLog::factory()->create();
        $log2 = TransportLog::factory()->create();

        TransportLog::where('id', $log1->id)->update(['created_at' => '2025-01-01 00:00:00']);
        TransportLog::where('id', $log2->id)->update(['created_at' => '2025-02-01 00:00:00']);

        $this->actingAs($user);

        $response = $this->get('/transport-logs');
        $response->assertOk();
        $response->assertSeeInOrder([$log2->vehicle_no, $log1->vehicle_no]);
    }

    public function test_transport_logs_index_supports_created_date_filter(): void
    {
        $user = User::factory()->superAdmin()->create();
        $log1 = TransportLog::factory()->create();
        $log2 = TransportLog::factory()->create();

        TransportLog::where('id', $log1->id)->update(['created_at' => '2025-01-01 00:00:00']);
        TransportLog::where('id', $log2->id)->update(['created_at' => '2025-02-01 00:00:00']);

        $this->actingAs($user);

        $response = $this->get('/transport-logs?created_date=2025-02-01');
        $response->assertOk();
        $response->assertSee($log2->vehicle_no);
        $response->assertDontSee($log1->vehicle_no);
    }

    public function test_transport_logs_index_supports_sorting(): void
    {
        $user = User::factory()->superAdmin()->create();
        $log1 = TransportLog::factory()->create(['total_sale' => 1000]);
        $log2 = TransportLog::factory()->create(['total_sale' => 5000]);

        $this->actingAs($user);

        $response = $this->get('/transport-logs?sort=total_sale&direction=desc');
        $response->assertOk();
        $response->assertSee('sort=total_sale');
        $response->assertSee($log2->vehicle_no);
        $response->assertSee($log1->vehicle_no);
    }

    public function test_transport_logs_index_supports_pagination(): void
    {
        $user = User::factory()->superAdmin()->create();

        $vehicles = [];
        foreach (range(1, 30) as $i) {
            $vehicles[] = 'Test' . $i;
        }

        foreach ($vehicles as $vehicleNo) {
            TransportLog::factory()->create(['vehicle_no' => $vehicleNo]);
        }

        $this->actingAs($user);

        $page1 = $this->get('/transport-logs?sort=vehicle_no&direction=asc')->assertOk();
        $page1->assertSee('Test1');
        $page1->assertDontSee('Test30');
        $page2 = $this->get('/transport-logs?sort=vehicle_no&direction=asc&page=2')->assertOk();
        $page2->assertSee('Test30');
    }

    public function test_transport_logs_index_supports_status_profit_filter(): void
    {
        $user = User::factory()->superAdmin()->create();
        $profitLog = TransportLog::factory()->create();
        $lossLog = TransportLog::factory()->create();

        $profitLog->update([
            'to_bb_sale' => 3000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
        ]);
        $profitLog->refresh();

        $lossLog->update([
            'to_bb_sale' => 1000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 5000,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
        ]);
        $lossLog->refresh();

        $this->actingAs($user);

        $response = $this->get('/transport-logs?status=profit');
        $response->assertOk();
        $response->assertSee($profitLog->vehicle_no);
        $response->assertDontSee($lossLog->vehicle_no);
    }

    public function test_transport_logs_index_supports_status_loss_filter(): void
    {
        $user = User::factory()->superAdmin()->create();
        $profitLog = TransportLog::factory()->create();
        $lossLog = TransportLog::factory()->create();

        $profitLog->update([
            'to_bb_sale' => 3000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
        ]);
        $profitLog->refresh();

        $lossLog->update([
            'to_bb_sale' => 1000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 5000,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
        ]);
        $lossLog->refresh();

        $this->actingAs($user);

        $response = $this->get('/transport-logs?status=loss');
        $response->assertOk();
        $response->assertSee($lossLog->vehicle_no);
        $response->assertDontSee($profitLog->vehicle_no);
    }

    public function test_transport_logs_index_supports_status_breakeven_filter(): void
    {
        $user = User::factory()->superAdmin()->create();
        $breakevenLog = TransportLog::factory()->create();
        $profitLog = TransportLog::factory()->create();

        $breakevenLog->update([
            'to_bb_sale' => 1000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 1000,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
        ]);
        $breakevenLog->refresh();

        $profitLog->update([
            'to_bb_sale' => 3000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
        ]);
        $profitLog->refresh();

        $this->actingAs($user);

        $response = $this->get('/transport-logs?status=breakeven');
        $response->assertOk();
        $response->assertSee($breakevenLog->vehicle_no);
        $response->assertDontSee($profitLog->vehicle_no);
    }

    public function test_transport_logs_index_supports_clearing_date_filter(): void
    {
        $user = User::factory()->superAdmin()->create();
        $clearedLog = TransportLog::factory()->create(['clearing_date' => '2025-03-10']);
        $pendingLog = TransportLog::factory()->create(['clearing_date' => null]);

        $this->actingAs($user);

        $response = $this->get('/transport-logs?clearing_date=2025-03-10');
        $response->assertOk();
        $response->assertSee($clearedLog->vehicle_no);
        $response->assertDontSee($pendingLog->vehicle_no);
    }

    public function test_transport_logs_index_filter_combination_search_company_status_created_date_clearing_date(): void
    {
        $user = User::factory()->superAdmin()->create();
        $match = TransportLog::factory()->create(['clearing_date' => '2025-03-15']);
        $mismatch1 = TransportLog::factory()->create();
        $mismatch2 = TransportLog::factory()->create(['clearing_date' => '2025-03-15']);
        $mismatch3 = TransportLog::factory()->create(['clearing_date' => '2025-03-15']);

        $match->update([
            'created_at' => '2025-03-15 10:00:00',
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
            'company' => 'Acme Logistics',
            'vehicle_no' => 'MATCH01',
        ]);
        $match->refresh();

        $mismatch1->update([
            'created_at' => '2025-03-15 10:00:00',
            'company' => 'Beta Transport',
            'to_bb_sale' => 1000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'vehicle_no' => 'MISMATCH01',
        ]);
        $mismatch1->refresh();

        $mismatch2->update([
            'created_at' => '2025-03-16 10:00:00',
            'to_bb_sale' => 1000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'company' => 'Acme Logistics',
            'vehicle_no' => 'MISMATCH02',
        ]);
        $mismatch2->refresh();

        $mismatch3->update([
            'created_at' => '2025-03-15 10:00:00',
            'to_bb_sale' => 1000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 5000,
            'company' => 'Acme Logistics',
            'vehicle_no' => 'MISMATCH03',
        ]);
        $mismatch3->refresh();

        $this->actingAs($user);

        $response = $this->get('/transport-logs?' . http_build_query([
            'search' => 'MATCH',
            'company' => 'Acme',
            'status' => 'profit',
            'created_date' => '2025-03-15',
            'clearing_date' => '2025-03-15',
        ]));

        $response->assertOk();
        $response->assertSee('MATCH01');
        $response->assertDontSee('MISMATCH01');
        $response->assertDontSee('MISMATCH02');
        $response->assertDontSee('MISMATCH03');
    }

    public function test_transport_logs_index_shows_no_records_state_when_no_filters_match(): void
    {
        $user = User::factory()->superAdmin()->create();
        TransportLog::factory()->create(['company' => 'Acme Logistics']);

        $this->actingAs($user);

        $response = $this->get('/transport-logs?company=NonExistingCompany');
        $response->assertOk();
        $response->assertSee('No transport logs found.');
    }

    public function test_transport_logs_pagination_preserves_filters(): void
    {
        $user = User::factory()->superAdmin()->create();

        foreach (range(1, 20) as $i) {
            $log = TransportLog::factory()->create([
                'company' => 'Acme Logistics',
                'vehicle_no' => 'Vehicle ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);
            TransportLog::where('id', $log->id)->update(['created_at' => now()->subDays(20 - $i)]);
        }

        $this->actingAs($user);

        $page1 = $this->get('/transport-logs?company=Acme&page=1')->assertOk();
        $page1->assertSee('Vehicle 20');
        $page1->assertDontSee('Vehicle 05');

        $page2 = $this->get('/transport-logs?company=Acme&page=2')->assertOk();
        $page2->assertSee('Vehicle 05');
    }

    public function test_transport_logs_sorting_preserves_filters(): void
    {
        $user = User::factory()->superAdmin()->create();
        $log1 = TransportLog::factory()->create(['company' => 'Acme Logistics']);
        $log2 = TransportLog::factory()->create(['company' => 'Acme Logistics']);

        $log1->update([
            'to_bb_sale' => 3000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'dtg_office_expense' => 0,
        ]);
        $log1->refresh();

        $log2->update([
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
        ]);
        $log2->refresh();

        $this->actingAs($user);

        $response = $this->get('/transport-logs?company=Acme&sort=total_sale&direction=asc');
        $response->assertOk();
        $response->assertSeeInOrder([$log2->vehicle_no, $log1->vehicle_no]);
    }

    public function test_transport_logs_index_handles_invalid_date_filters_gracefully(): void
    {
        $user = User::factory()->superAdmin()->create();
        TransportLog::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/transport-logs?created_date=not-a-date');
        $response->assertOk();
    }

    public function test_transport_logs_index_handles_future_date_filters(): void
    {
        $user = User::factory()->superAdmin()->create();
        TransportLog::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/transport-logs?created_date=' . now()->addYear()->format('Y-m-d'));
        $response->assertOk();
        $response->assertSee('No transport logs found.');
    }

    public function test_transport_logs_index_filter_preserves_query_string_on_clear(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $response = $this->get('/transport-logs?search=test&company=Acme&status=profit&created_date=2025-01-01&clearing_date=2025-01-01');
        $response->assertOk();
        $this->assertStringContainsString('value="test"', $response->getContent());
        $this->assertStringContainsString('value="Acme"', $response->getContent());
        $this->assertStringContainsString('selected>Profit</option>', $response->getContent());
        $this->assertStringContainsString('value="2025-01-01"', $response->getContent());
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function test_dashboard_loads_for_super_admin(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk()->assertSee('Dashboard');
    }

    public function test_dashboard_loads_for_admin(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk()->assertSee('Dashboard');
    }

    // -------------------------------------------------------------------------
    // Users CRUD
    // -------------------------------------------------------------------------

    public function test_super_admin_can_create_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin);

        $this->post('/users', [
            'name' => 'New User',
            'email' => 'newuser@transport.app',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@transport.app',
            'role' => 'admin',
        ]);
    }

    public function test_super_admin_can_update_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        $this->put('/users/' . $admin->id, [
            'name' => 'Updated Name',
            'email' => $admin->email,
            'role' => 'admin',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_super_admin_can_delete_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($superAdmin);

        $this->delete('/users/' . $admin->id)
            ->assertRedirect('/users');

        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }

    public function test_super_admin_can_activate_deactivate_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create(['is_active' => false]);

        $this->actingAs($superAdmin);

        $this->post('/users/' . $admin->id . '/activate', [
            'current_password' => 'password',
        ])->assertRedirect('/users');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }

    public function test_admin_cannot_access_user_management(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->get('/users')->assertForbidden();
        $this->get('/users/create')->assertForbidden();
        $this->post('/users', [
            'name' => 'Bad',
            'email' => 'bad@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ])->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Settings / Profile
    // -------------------------------------------------------------------------

    public function test_user_can_update_profile_with_current_password(): void
    {
        $user = User::factory()->create(['email' => 'user@test.com']);

        $this->actingAs($user);

        $this->get('/settings')->assertOk();

        $this->patch('/settings', [
            'name' => 'Updated Name',
        ])->assertRedirect('/settings');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    public function test_user_cannot_update_profile_without_current_password(): void
    {
        $user = User::factory()->create(['email' => 'user@test.com']);

        $this->actingAs($user);

        $this->from('/settings')->patch('/settings', [
            'name' => 'Updated Name',
            'email' => 'user@test.com',
        ])->assertSessionHasErrors();
    }

    // -------------------------------------------------------------------------
    // Activity Logs
    // -------------------------------------------------------------------------

    public function test_super_admin_can_view_activity_logs(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        ActivityLog::factory()->count(5)->create();

        $this->actingAs($superAdmin);

        $this->get('/activity-logs')->assertOk()->assertSee('Activity Log');
    }

    public function test_admin_cannot_access_activity_logs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->get('/activity-logs')->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Error Pages
    // -------------------------------------------------------------------------

    public function test_403_error_page_renders(): void
    {
        $this->get('/403')->assertOk()->assertSee('403');
    }

    public function test_404_error_page_renders(): void
    {
        $this->get('/nonexistent')->assertOk()->assertSee('404');
    }

    // -------------------------------------------------------------------------
    // Transport Log List View
    // -------------------------------------------------------------------------

    public function test_transport_log_index_shows_filter_and_sort_ui(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $this->get('/transport-logs')->assertOk()
            ->assertSee('Search')
            ->assertSee('Filter by company')
            ->assertSee('Date');
    }

    public function test_transport_log_index_shows_pagination(): void
    {
        $user = User::factory()->superAdmin()->create();

        foreach (range(1, 30) as $i) {
            TransportLog::factory()->create();
        }

        $this->actingAs($user);

        $this->get('/transport-logs')->assertOk()
            ->assertSee('1')
            ->assertSee('2');
    }

    // -------------------------------------------------------------------------
    // Route Smoke Tests
    // -------------------------------------------------------------------------

    public function test_all_authenticated_routes_return_success_for_super_admin(): void
    {
        $user = User::factory()->superAdmin()->create();
        $log = TransportLog::factory()->create();
        $activity = ActivityLog::factory()->create();

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
        $this->get('/transport-logs')->assertOk();
        $this->get('/transport-logs/create')->assertOk();
        $this->get('/transport-logs/' . $log->id)->assertOk();
        $this->get('/transport-logs/' . $log->id . '/edit')->assertOk();
        $this->get('/users')->assertOk();
        $this->get('/users/create')->assertOk();
        $this->get('/users/' . User::factory()->admin()->create()->id)->assertOk();
        $this->get('/settings')->assertOk();
        $this->get('/activity-logs')->assertOk();
        $this->get('/activity-logs/' . $activity->id)->assertOk();
    }

    // -------------------------------------------------------------------------
    // Factory State Edge Cases
    // -------------------------------------------------------------------------

    public function test_loss_making_transport_log_can_be_created(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $this->post('/transport-logs', [
            'date' => '2025-03-10',
            'vehicle_no' => 'MH99XY8888',
            'company' => 'Loss Corp',
            'transport_name' => 'Lossy Transport',
            'destination' => 'Pune',
            'km' => 500,
            'weight' => 100,
            'to_bb_sale' => 1000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 8000,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'payment' => 0,
        ]);

        $this->assertDatabaseHas('transport_logs', [
            'profit' => -7000,
        ]);
    }

    public function test_overpaid_transport_log_can_be_created(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $this->post('/transport-logs', [
            'date' => '2025-03-10',
            'vehicle_no' => 'MH99XY8888',
            'company' => 'Pay Corp',
            'transport_name' => 'Pay Transport',
            'destination' => 'Pune',
            'km' => 500,
            'weight' => 100,
            'to_bb_sale' => 2000,
            'paid_sale' => 0,
            'to_pay' => 0,
            'freight' => 0,
            'loading' => 0,
            'unloading' => 0,
            'dd' => 0,
            'tempu_expense' => 0,
            'commission' => 0,
            'payment' => 5000,
        ]);

        $this->assertDatabaseHas('transport_logs', [
            'balance_vehicle_payment' => -3000,
        ]);
    }

    public function test_edit_page_preserves_text_field_values(): void
    {
        $user = User::factory()->superAdmin()->create();

        $log = TransportLog::factory()->create([
            'vehicle_no' => 'MH 01 AB 1234A',
            'company' => 'Alpha Logistics',
            'destination' => 'Mumbai',
            'transport_name' => 'Beta Transport',
            'fuel_station_name' => 'Indian Oil - Andheri',
        ]);

        $this->actingAs($user);

        $response = $this->get('/transport-logs/' . $log->id . '/edit');
        $response->assertOk();

        $response->assertSee('MH 01 AB 1234A');
        $response->assertSee('Alpha Logistics');
        $response->assertSee('Mumbai');
        $response->assertSee('Beta Transport');
        $response->assertSee('Indian Oil - Andheri');

        $response->assertSee('name="vehicle_no"', false);
        $response->assertSee('name="company"', false);
        $response->assertSee('name="destination"', false);
        $response->assertSee('name="transport_name"', false);
        $response->assertSee('name="fuel_station_name"', false);
    }
}
