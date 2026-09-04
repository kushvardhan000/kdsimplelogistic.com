<?php

namespace Tests\Feature;

use App\Models\TransportLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceSearchTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        return $user;
    }

    public function test_transport_logs_index_searches_trace_code(): void
    {
        $this->actingAsSuperAdmin();
        $vehicle = Vehicle::factory()->create(['vehicle_no' => 'TEST-001']);
        $log = TransportLog::factory()->create([
            'trace_code' => 'TL-000001',
            'vehicle_no' => 'TEST-001',
        ]);

        $response = $this->get(route('transport-logs.index', ['search' => 'TL-000001']));
        $response->assertRedirect(route('trace.show', 'TL-000001'));

        $response = $this->get(route('transport-logs.index', ['search' => 'TL-000']));
        $response->assertOk();
        $response->assertSee('TEST-001');
    }

    public function test_header_search_with_exact_trace_code_redirects_to_trace_page(): void
    {
        $this->actingAsSuperAdmin();
        $log = TransportLog::factory()->create(['trace_code' => 'TL-000042']);

        $response = $this->get(route('transport-logs.index', ['search' => 'TL-000042']));
        $response->assertRedirect(route('trace.show', 'TL-000042'));
    }

    public function test_header_search_with_partial_trace_code_falls_through_to_transport_logs(): void
    {
        $this->actingAsSuperAdmin();
        $vehicle = Vehicle::factory()->create(['vehicle_no' => 'TEST-002']);
        $log = TransportLog::factory()->create([
            'trace_code' => 'TL-000099',
            'vehicle_no' => 'TEST-002',
        ]);

        $response = $this->get(route('transport-logs.index', ['search' => 'TL-000']));
        $response->assertOk();
        $response->assertSee('TEST-002');
    }

    public function test_trace_page_shows_creator_editor_vehicle_company_branch(): void
    {
        $this->actingAsSuperAdmin();
        $creator = User::factory()->create(['name' => 'Creator User']);
        $editor = User::factory()->create(['name' => 'Editor User']);
        $vehicle = Vehicle::factory()->create(['vehicle_no' => 'MH01AB1234']);
        $company = Company::factory()->create(['name' => 'Test Company']);
        $branch = Branch::factory()->create(['name' => 'Main Branch']);
        $log = TransportLog::factory()->create([
            'trace_code' => 'TL-000200',
            'created_by' => $creator->id,
            'updated_by' => $editor->id,
            'vehicle_id' => $vehicle->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'vehicle_no' => 'MH01AB1234',
            'company' => 'Test Company',
        ]);

        $response = $this->get(route('trace.show', 'TL-000200'));
        $response->assertOk();
        $response->assertSee('Creator User');
        $response->assertSee('Editor User');
        $response->assertSee('MH01AB1234');
        $response->assertSee('Test Company');
        $response->assertSee('Main Branch');
        $response->assertSee('Edit Log');
        $response->assertSee('View Full Log');
    }
}
