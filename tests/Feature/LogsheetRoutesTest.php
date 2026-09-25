<?php

namespace Tests\Feature;

use App\Http\Controllers\LogsheetController;
use App\Models\Logsheet;
use App\Models\LogsheetImport;
use App\Models\LogsheetRawRow;
use App\Models\LogsheetDetail;
use App\Models\LogsheetClearing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LogsheetRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_logsheet_routes_have_callable_controller_methods()
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $name = $route->getName();

            if (!str_starts_with($name, 'logsheets.')) {
                continue;
            }

            $action = $route->getAction('controller');
            $this->assertNotNull($action, "Route {$name} has no controller action");

            [$controllerClass, $method] = explode('@', $action);
            $this->assertTrue(
                class_exists($controllerClass),
                "Controller class {$controllerClass} for route {$name} does not exist"
            );

            $this->assertTrue(
                method_exists($controllerClass, $method),
                "Method {$method} does not exist on {$controllerClass} for route {$name}"
            );

            $controller = app()->make($controllerClass);
            $this->assertTrue(
                is_callable([$controller, $method]),
                "Method {$method} on {$controllerClass} is not callable for route {$name}"
            );
        }
    }

    public function test_logsheet_pages_return_200_and_no_download_links()
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);

        // /logsheets
        $response = $this->get(route('logsheets.index'));
        $response->assertStatus(200);
        $this->assertStringNotContainsString('download', strtolower($response->getContent()));

        // /logsheets/records
        $response = $this->get(route('logsheets.records'));
        $response->assertStatus(200);
        $this->assertStringNotContainsString('download', strtolower($response->getContent()));

        // /logsheets/imports/{id}
        $import = \App\Models\LogsheetImport::create([
            'date_from' => now()->subDays(5)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'original_filename' => 'test.xlsx',
            'file_path' => 'logsheet_imports/test.xlsx',
            'uploaded_by' => $user->id,
            'row_count' => 10,
            'consolidated_count' => 5,
            'duplicate_count' => 0,
            'invalid_count' => 0,
            'status' => 'completed',
            'total_amount' => 1000.00,
            'total_booked_amount' => 1000.00,
            'total_diff' => 0.00,
            'total_gross_wt' => 500.000,
            'out_of_range_rows' => 0,
            'skipped_out_of_range_groups' => 0,
            'fully_out_of_range_groups' => 0,
        ]);

        $response = $this->get(route('logsheets.imports.show', $import));
        
        // Debug: dump response if error
        if ($response->getStatusCode() !== 200) {
            dump('Status: ' . $response->getStatusCode());
            dump('Content: ' . substr($response->getContent(), 0, 2000));
        }
        
        $response->assertStatus(200);
        $this->assertStringNotContainsString('download', strtolower($response->getContent()));
    }

    public function test_no_download_routes_exist()
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $name = $route->getName();

            if (!str_starts_with($name, 'logsheets.')) {
                continue;
            }

            $this->assertFalse(
                str_contains($name, 'download'),
                "Download route {$name} should not exist"
            );
        }
    }

    public function test_index_page_shows_invalid_row_count_for_import(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);

        // Create an import with invalid rows
        $import = LogsheetImport::create([
            'date_from' => now()->subDays(5)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'original_filename' => 'test_with_invalid.xlsx',
            'file_path' => 'logsheet_imports/test_with_invalid.xlsx',
            'uploaded_by' => $user->id,
            'row_count' => 10,
            'consolidated_count' => 5,
            'duplicate_count' => 0,
            'invalid_count' => 3,
            'status' => 'completed',
            'total_amount' => 1000.00,
            'total_booked_amount' => 1000.00,
            'total_diff' => 0.00,
            'total_gross_wt' => 500.000,
            'out_of_range_rows' => 0,
            'skipped_out_of_range_groups' => 0,
            'fully_out_of_range_groups' => 0,
        ]);

        // Create a valid import without invalid rows for comparison
        LogsheetImport::create([
            'date_from' => now()->subDays(10)->format('Y-m-d'),
            'date_to' => now()->subDays(5)->format('Y-m-d'),
            'original_filename' => 'test_clean.xlsx',
            'file_path' => 'logsheet_imports/test_clean.xlsx',
            'uploaded_by' => $user->id,
            'row_count' => 5,
            'consolidated_count' => 2,
            'duplicate_count' => 0,
            'invalid_count' => 0,
            'status' => 'completed',
            'total_amount' => 500.00,
            'total_booked_amount' => 500.00,
            'total_diff' => 0.00,
            'total_gross_wt' => 250.000,
            'out_of_range_rows' => 0,
            'skipped_out_of_range_groups' => 0,
            'fully_out_of_range_groups' => 0,
        ]);

        $response = $this->get(route('logsheets.index'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Verify the import with invalid rows shows the invalid count with link
        $this->assertStringContainsString('test_with_invalid.xlsx', $content);
        $this->assertStringContainsString('3 invalid', $content);
        
        // Check for the anchor link to the invalid rows section
        $this->assertStringContainsString('#invalid-rows', $content);
        $this->assertStringContainsString('href="http://127.0.0.1:8000/logsheets/imports/' . $import->id . '#invalid-rows"', $content);

        // Verify the clean import shows 0 invalid
        $this->assertStringContainsString('test_clean.xlsx', $content);
        $this->assertStringContainsString('<span>0</span>', $content);
    }

    public function test_import_with_no_dates_auto_derives_range(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        
        // Visit a page first to ensure CSRF token is generated in session
        $this->get('/logsheets');

        Excel::fake();

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 26, null),
                    [
                        'Log Sheet No', 'Date', 'Invoice No', 'Inv- Date', 'Payer', 'Payer Name',
                        'Town', 'Gross Wt', 'difference', 'amount', 'Volume', 'Tprt Code',
                        'Tprt Name', 'Container ID', 'Destination', 'SAPInvoiceNo', 'Posting Date',
                        'Bill Date', 'VendorInvNo', 'Route', 'Town', 'Gross weight', 'Booked Amount',
                        'Actual Rate', 'Actual Amount', 'Diff',
                    ],
                    [
                        'AUTO001', '2026-06-10', 'INV-001', '2026-06-10', 'PAY-001', 'Payer One',
                        'Town A', '1000.000', '100.00', '1100.00', '25', 'T01', 'Transporter A',
                        'VH-01', 'Chennai', 'SAP-001', '2026-06-10', '2026-06-11', 'VEN-001',
                        'Route A', 'Town A', '1000.000', '5000.00', '100', '6100.00', '100.00',
                    ],
                    [
                        'AUTO001', '2026-06-15', 'INV-002', '2026-06-15', 'PAY-002', 'Payer Two',
                        'Town B', '2000.000', '200.00', '2200.00', '30', 'T01', 'Transporter A',
                        'VH-02', 'Chennai', 'SAP-001', '2026-06-15', '2026-06-16', 'VEN-002',
                        'Route A', 'Town B', '2000.000', '10000.00', '100', '10200.00', '200.00',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        $response = $this->call('POST', '/logsheets', [
            'file' => $file,
            '_token' => csrf_token(),
            // No date_from or date_to provided
        ]);

        $response->assertRedirect('/logsheets');
        $response->assertSessionHasNoErrors();

        $import = LogsheetImport::latest()->first();
        $this->assertNotNull($import);
        
        // Should auto-derive date range from file (2026-06-10 to 2026-06-15)
        $this->assertEquals('2026-06-10', $import->date_from->format('Y-m-d'));
        $this->assertEquals('2026-06-15', $import->date_to->format('Y-m-d'));

        // Should have correct totals (both rows in range)
        $this->assertEquals('16300.00', $import->total_amount); // 6100 + 10200
        $this->assertEquals(2, $import->row_count);
        $this->assertEquals(1, $import->consolidated_count);

        // Verify logsheet created with correct totals
        $logsheet = Logsheet::where('log_sheet_no', 'AUTO001')->first();
        $this->assertNotNull($logsheet);
        $this->assertEquals('3000.000', $logsheet->total_gross_wt); // 1000 + 2000
        $this->assertEquals('16300.00', $logsheet->total_actual_amount);
    }

    public function test_import_with_non_overlapping_range_creates_flagged_logsheet(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        
        // Visit a page first to ensure CSRF token is generated in session
        $this->get('/logsheets');

        Excel::fake();

        // File has dates in June 2026, but user selects September 2026
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 26, null),
                    [
                        'Log Sheet No', 'Date', 'Invoice No', 'Inv- Date', 'Payer', 'Payer Name',
                        'Town', 'Gross Wt', 'difference', 'amount', 'Volume', 'Tprt Code',
                        'Tprt Name', 'Container ID', 'Destination', 'SAPInvoiceNo', 'Posting Date',
                        'Bill Date', 'VendorInvNo', 'Route', 'Town', 'Gross weight', 'Booked Amount',
                        'Actual Rate', 'Actual Amount', 'Diff',
                    ],
                    [
                        'NON001', '2026-06-10', 'INV-001', '2026-06-10', 'PAY-001', 'Payer One',
                        'Town A', '1000.000', '100.00', '1100.00', '25', 'T01', 'Transporter A',
                        'VH-01', 'Chennai', 'SAP-001', '2026-06-10', '2026-06-11', 'VEN-001',
                        'Route A', 'Town A', '1000.000', '5000.00', '100', '6100.00', '100.00',
                    ],
                    [
                        'NON001', '2026-06-15', 'INV-002', '2026-06-15', 'PAY-002', 'Payer Two',
                        'Town B', '2000.000', '200.00', '2200.00', '30', 'T01', 'Transporter A',
                        'VH-02', 'Chennai', 'SAP-001', '2026-06-15', '2026-06-16', 'VEN-002',
                        'Route A', 'Town B', '2000.000', '10000.00', '100', '10200.00', '200.00',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        $response = $this->call('POST', '/logsheets', [
            'file' => $file,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect('/logsheets');

        $import = LogsheetImport::latest()->first();
        $this->assertNotNull($import);

        // Should use user-provided range
        $this->assertEquals('2026-09-01', $import->date_from->format('Y-m-d'));
        $this->assertEquals('2026-09-30', $import->date_to->format('Y-m-d'));

        // All rows out of range, but logsheet still created with flag
        $this->assertEquals(2, $import->row_count);
        $this->assertEquals(1, $import->consolidated_count);
        $this->assertEquals(2, $import->out_of_range_rows);
        $this->assertEquals(1, $import->fully_out_of_range_groups);

        $logsheet = Logsheet::where('log_sheet_no', 'NON001')->first();
        $this->assertNotNull($logsheet);
        $this->assertTrue($logsheet->fully_out_of_requested_range);
        // Totals should use ALL rows since group is fully out of range
        $this->assertEquals('3000.000', $logsheet->total_gross_wt);
        $this->assertEquals('16300.00', $logsheet->total_actual_amount);

        // Flash message should contain the range mismatch banner
        $response->assertSessionHas('success', fn ($msg) => 
            str_contains($msg, '2026-06-10') && 
            str_contains($msg, '2026-06-15') && 
            str_contains($msg, '2026-09-01') && 
            str_contains($msg, '2026-09-30') &&
            str_contains($msg, '2 rows') &&
            str_contains($msg, '1 log sheet')
        );
    }

    public function test_import_with_partial_overlap_filters_correctly(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        
        // Visit a page first to ensure CSRF token is generated in session
        $this->get('/logsheets');

        Excel::fake();

        // File has dates June 10 and June 15, user selects June 10 to June 12
        // First row in range, second row out of range
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 26, null),
                    [
                        'Log Sheet No', 'Date', 'Invoice No', 'Inv- Date', 'Payer', 'Payer Name',
                        'Town', 'Gross Wt', 'difference', 'amount', 'Volume', 'Tprt Code',
                        'Tprt Name', 'Container ID', 'Destination', 'SAPInvoiceNo', 'Posting Date',
                        'Bill Date', 'VendorInvNo', 'Route', 'Town', 'Gross weight', 'Booked Amount',
                        'Actual Rate', 'Actual Amount', 'Diff',
                    ],
                    [
                        'PART001', '2026-06-10', 'INV-001', '2026-06-10', 'PAY-001', 'Payer One',
                        'Town A', '1000.000', '100.00', '1100.00', '25', 'T01', 'Transporter A',
                        'VH-01', 'Chennai', 'SAP-001', '2026-06-10', '2026-06-11', 'VEN-001',
                        'Route A', 'Town A', '1000.000', '5000.00', '100', '6100.00', '100.00',
                    ],
                    [
                        'PART001', '2026-06-15', 'INV-002', '2026-06-15', 'PAY-002', 'Payer Two',
                        'Town B', '2000.000', '200.00', '2200.00', '30', 'T01', 'Transporter A',
                        'VH-02', 'Chennai', 'SAP-001', '2026-06-15', '2026-06-16', 'VEN-002',
                        'Route A', 'Town B', '2000.000', '10000.00', '100', '10200.00', '200.00',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        $response = $this->call('POST', '/logsheets', [
            'file' => $file,
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-12',
            '_token' => csrf_token(),
        ]);

        $response->assertRedirect('/logsheets');

        $import = LogsheetImport::latest()->first();
        $this->assertNotNull($import);

        // Only first row in range
        $this->assertEquals(2, $import->row_count);
        $this->assertEquals(1, $import->consolidated_count);
        $this->assertEquals(1, $import->out_of_range_rows);
        $this->assertEquals(0, $import->fully_out_of_range_groups); // Mixed group, not fully out

        // Logsheet totals should only include in-range row
        $logsheet = Logsheet::where('log_sheet_no', 'PART001')->first();
        $this->assertNotNull($logsheet);
        $this->assertFalse($logsheet->fully_out_of_requested_range);
        $this->assertEquals('1000.000', $logsheet->total_gross_wt); // Only first row
        $this->assertEquals('6100.00', $logsheet->total_actual_amount); // Only first row

        // Flash message should show out of range info
        $response->assertSessionHas('success', fn ($msg) => 
            str_contains($msg, '1 rows') && 
            str_contains($msg, '2026-06-10') && 
            str_contains($msg, '2026-06-15')
        );
    }

    public function test_delete_import_cascades_correctly(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        
        // Visit a page first to ensure CSRF token is generated in session
        $this->get('/logsheets');

        // Create import with logsheets, details, raw rows, clearings
        $import = LogsheetImport::create([
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'original_filename' => 'test_delete.xlsx',
            'file_path' => 'logsheet_imports/test_delete.xlsx',
            'uploaded_by' => $user->id,
            'row_count' => 2,
            'consolidated_count' => 1,
            'duplicate_count' => 0,
            'invalid_count' => 0,
            'status' => 'completed',
            'total_amount' => '1000.00',
            'total_booked_amount' => '1000.00',
            'total_diff' => '0.00',
            'total_gross_wt' => '1000.000',
            'out_of_range_rows' => 0,
            'skipped_out_of_range_groups' => 0,
            'fully_out_of_range_groups' => 0,
        ]);

        $logsheet = Logsheet::create([
            'log_sheet_no' => 'DEL001',
            'date' => '2026-06-15',
            'total_gross_wt' => '1000.000',
            'total_booked_amount' => '1000.00',
            'total_actual_amount' => '1000.00',
            'total_diff' => '0.00',
            'consignment_count' => 2,
            'status' => 'pending',
            'last_import_id' => $import->id,
        ]);

        // Add detail
        LogsheetDetail::create([
            'logsheet_id' => $logsheet->id,
            'log_sheet_no' => 'DEL001',
            'date' => '2026-06-15',
            'gross_wt' => '1000.000',
            'diff' => '0.00',
            'amount' => '1000.00',
        ]);

        // Add raw row
        LogsheetRawRow::create([
            'import_id' => $import->id,
            'log_sheet_no' => 'DEL001',
            'raw_data' => json_encode(['test' => 'data']),
            'row_number_in_file' => 1,
            'is_valid' => true,
        ]);

        // Add clearing
        \App\Models\LogsheetClearing::create([
            'logsheet_id' => $logsheet->id,
            'amount' => '500.00',
            'cleared_by' => $user->id,
            'cleared_at' => now(),
        ]);

        // Delete the import
        $response = $this->call('DELETE', route('logsheets.imports.destroy', $import), [
            '_token' => csrf_token(),
        ]);
        $response->assertRedirect('/logsheets');
        $response->assertSessionHas('success');

        // Import should be deleted
        $this->assertNull(LogsheetImport::find($import->id));

        // Logsheet should be force deleted (no soft delete remaining)
        $this->assertNull(Logsheet::withTrashed()->find($logsheet->id));

        // Detail should be deleted
        $this->assertEquals(0, LogsheetDetail::where('log_sheet_no', 'DEL001')->count());

        // Raw rows should be deleted
        $this->assertEquals(0, LogsheetRawRow::where('import_id', $import->id)->count());

        // Clearings should be deleted
        $this->assertEquals(0, \App\Models\LogsheetClearing::where('logsheet_id', $logsheet->id)->count());
    }

    public function test_delete_single_logsheet_preserves_siblings(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        
        // Visit a page first to ensure CSRF token is generated in session
        $this->get('/logsheets/records');

        $import = LogsheetImport::create([
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'original_filename' => 'test_sibling.xlsx',
            'file_path' => 'logsheet_imports/test_sibling.xlsx',
            'uploaded_by' => $user->id,
            'row_count' => 4,
            'consolidated_count' => 2,
            'duplicate_count' => 0,
            'invalid_count' => 0,
            'status' => 'completed',
            'total_amount' => '2000.00',
            'total_booked_amount' => '2000.00',
            'total_diff' => '0.00',
            'total_gross_wt' => '2000.000',
            'out_of_range_rows' => 0,
            'skipped_out_of_range_groups' => 0,
            'fully_out_of_range_groups' => 0,
        ]);

        // Create two logsheets in same import
        $logsheet1 = Logsheet::create([
            'log_sheet_no' => 'SIB001',
            'date' => '2026-06-15',
            'total_gross_wt' => '1000.000',
            'total_booked_amount' => '1000.00',
            'total_actual_amount' => '1000.00',
            'total_diff' => '0.00',
            'consignment_count' => 2,
            'status' => 'pending',
            'last_import_id' => $import->id,
        ]);

        $logsheet2 = Logsheet::create([
            'log_sheet_no' => 'SIB002',
            'date' => '2026-06-16',
            'total_gross_wt' => '1000.000',
            'total_booked_amount' => '1000.00',
            'total_actual_amount' => '1000.00',
            'total_diff' => '0.00',
            'consignment_count' => 2,
            'status' => 'pending',
            'last_import_id' => $import->id,
        ]);

        // Add details and raw rows for both
        LogsheetDetail::create([
            'logsheet_id' => $logsheet1->id,
            'log_sheet_no' => 'SIB001',
            'date' => '2026-06-15',
            'gross_wt' => '1000.000',
        ]);
        LogsheetDetail::create([
            'logsheet_id' => $logsheet2->id,
            'log_sheet_no' => 'SIB002',
            'date' => '2026-06-16',
            'gross_wt' => '1000.000',
        ]);

        LogsheetRawRow::create([
            'import_id' => $import->id,
            'log_sheet_no' => 'SIB001',
            'raw_data' => json_encode(['test' => 'data1']),
            'row_number_in_file' => 1,
            'is_valid' => true,
        ]);
        LogsheetRawRow::create([
            'import_id' => $import->id,
            'log_sheet_no' => 'SIB002',
            'raw_data' => json_encode(['test' => 'data2']),
            'row_number_in_file' => 2,
            'is_valid' => true,
        ]);

        // Delete only the first logsheet
        $response = $this->call('DELETE', route('logsheets.destroy', $logsheet1), [
            '_token' => csrf_token(),
        ]);
        $response->assertRedirect('/logsheets/records');

        // First logsheet should be soft deleted
        $this->assertNotNull(Logsheet::withTrashed()->find($logsheet1->id));
        $this->assertSoftDeleted($logsheet1);

        // Second logsheet should be untouched
        $logsheet2Fresh = Logsheet::find($logsheet2->id);
        $this->assertNotNull($logsheet2Fresh);
        $this->assertEquals('SIB002', $logsheet2Fresh->log_sheet_no);

        // First logsheet's details and raw rows should be deleted
        $this->assertEquals(0, LogsheetDetail::where('log_sheet_no', 'SIB001')->count());
        $this->assertEquals(0, LogsheetRawRow::where('log_sheet_no', 'SIB001')->count());

        // Second logsheet's details and raw rows should remain
        $this->assertEquals(1, LogsheetDetail::where('log_sheet_no', 'SIB002')->count());
        $this->assertEquals(1, LogsheetRawRow::where('log_sheet_no', 'SIB002')->count());
    }

    public function test_delete_import_returns_403_for_non_super_admin(): void
    {
        $user = User::factory()->create(); // Not super_admin
        $this->actingAs($user);
        
        // Visit a page first to ensure CSRF token is generated in session
        $this->get('/logsheets');

        $import = LogsheetImport::create([
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'original_filename' => 'test.xlsx',
            'file_path' => 'logsheet_imports/test.xlsx',
            'uploaded_by' => $user->id,
            'row_count' => 1,
            'consolidated_count' => 1,
            'duplicate_count' => 0,
            'invalid_count' => 0,
            'status' => 'completed',
            'total_amount' => '1000.00',
            'total_booked_amount' => '1000.00',
            'total_diff' => '0.00',
            'total_gross_wt' => '1000.000',
            'out_of_range_rows' => 0,
            'skipped_out_of_range_groups' => 0,
            'fully_out_of_range_groups' => 0,
        ]);

        $response = $this->call('DELETE', route('logsheets.imports.destroy', $import), [
            '_token' => csrf_token(),
        ]);
        $response->assertStatus(403);
    }

    public function test_delete_logsheet_returns_403_for_non_super_admin(): void
    {
        $user = User::factory()->create(); // Not super_admin
        $this->actingAs($user);
        
        // Visit a page first to ensure CSRF token is generated in session
        $this->get('/logsheets/records');

        $import = LogsheetImport::create([
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'original_filename' => 'test.xlsx',
            'file_path' => 'logsheet_imports/test.xlsx',
            'uploaded_by' => $user->id,
            'row_count' => 1,
            'consolidated_count' => 1,
            'duplicate_count' => 0,
            'invalid_count' => 0,
            'status' => 'completed',
            'total_amount' => '1000.00',
            'total_booked_amount' => '1000.00',
            'total_diff' => '0.00',
            'total_gross_wt' => '1000.000',
            'out_of_range_rows' => 0,
            'skipped_out_of_range_groups' => 0,
            'fully_out_of_range_groups' => 0,
        ]);

        $logsheet = Logsheet::create([
            'log_sheet_no' => 'DEL001',
            'date' => '2026-06-15',
            'total_gross_wt' => '1000.000',
            'total_booked_amount' => '1000.00',
            'total_actual_amount' => '1000.00',
            'total_diff' => '0.00',
            'consignment_count' => 1,
            'status' => 'pending',
            'last_import_id' => $import->id,
        ]);

        $response = $this->call('DELETE', route('logsheets.destroy', $logsheet), [
            '_token' => csrf_token(),
        ]);
        $response->assertStatus(403);
    }

    public function test_show_page_renders_with_blank_numeric_fields_in_raw_data(): void
    {
        $user = User::factory()->superAdmin()->create();
        $this->actingAs($user);
        
        // Visit a page first to ensure CSRF token is generated in session
        $this->get('/logsheets');

        // Create import
        $import = LogsheetImport::create([
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'original_filename' => 'test.xlsx',
            'file_path' => 'logsheet_imports/test.xlsx',
            'uploaded_by' => $user->id,
            'row_count' => 2,
            'consolidated_count' => 1,
            'duplicate_count' => 0,
            'invalid_count' => 0,
            'status' => 'completed',
            'total_amount' => '2000.00',
            'total_booked_amount' => '2000.00',
            'total_diff' => '0.00',
            'total_gross_wt' => '2000.000',
            'out_of_range_rows' => 0,
            'skipped_out_of_range_groups' => 0,
            'fully_out_of_range_groups' => 0,
        ]);

        // Create logsheet
        $logsheet = Logsheet::create([
            'log_sheet_no' => 'SHOW001',
            'date' => '2026-06-15',
            'total_gross_wt' => '2000.000',
            'total_booked_amount' => '2000.00',
            'total_actual_amount' => '2000.00',
            'total_diff' => '0.00',
            'consignment_count' => 2,
            'status' => 'pending',
            'last_import_id' => $import->id,
        ]);

        // Create detail rows (model attributes - cast to decimal, these are safe)
        LogsheetDetail::create([
            'logsheet_id' => $logsheet->id,
            'log_sheet_no' => 'SHOW001',
            'date' => '2026-06-15',
            'invoice_no' => 'INV-001',
            'gross_wt' => '1000.000',
            'difference' => '10.000',
            'amount' => '1000.00',
            'volume' => '5.000',
            'booked_amount' => '1000.00',
            'actual_rate' => '100.00',
            'actual_amount' => '1000.00',
            'diff' => '0.00',
            'gross_weight_2' => '1000.000',
        ]);

        LogsheetDetail::create([
            'logsheet_id' => $logsheet->id,
            'log_sheet_no' => 'SHOW001',
            'date' => '2026-06-15',
            'invoice_no' => 'INV-002',
            'gross_wt' => '1000.000',
            'difference' => '10.000',
            'amount' => '1000.00',
            'volume' => '5.000',
            'booked_amount' => '1000.00',
            'actual_rate' => '100.00',
            'actual_amount' => '1000.00',
            'diff' => '0.00',
            'gross_weight_2' => '1000.000',
        ]);

        // Create raw rows with BLANK numeric fields in raw_data (strings from JSON)
        // This simulates real production data where some cells are empty
        LogsheetRawRow::create([
            'import_id' => $import->id,
            'log_sheet_no' => 'SHOW001',
            'raw_data' => [
                'gross_wt' => '1000.000',
                'diff' => '10.000',
                'amount' => '1000.00',
                'volume' => '5.000',
                'booked_amount' => '1000.00',
                'actual_rate' => '100.00',
                'actual_amount' => '1000.00',
                'no_of_packs' => '50',
                // All fields present - should render fine
            ],
            'row_number_in_file' => 1,
            'is_valid' => true,
            'validation_error' => null,
        ]);

        LogsheetRawRow::create([
            'import_id' => $import->id,
            'log_sheet_no' => 'SHOW001',
            'raw_data' => [
                'gross_wt' => '',           // BLANK - was causing TypeError
                'diff' => '',               // BLANK - was causing TypeError
                'amount' => '',             // BLANK - was causing TypeError
                'volume' => '',             // BLANK - was causing TypeError
                'booked_amount' => '',      // BLANK - was causing TypeError
                'actual_rate' => '',        // BLANK - was causing TypeError
                'actual_amount' => '',      // BLANK - was causing TypeError
                'no_of_packs' => '',        // BLANK
            ],
            'row_number_in_file' => 2,
            'is_valid' => true,
            'validation_error' => null,
        ]);

        // This request would throw TypeError: number_format(): Argument #1 ($num) must be of type int|float, string given
        // on PHP 8.4 if the fix is not applied
        $response = $this->get(route('logsheets.show', $logsheet));
        
        $response->assertStatus(200);
        $response->assertSee('SHOW001');
        $response->assertSee('INV-001');
        $response->assertSee('INV-002');
    }
}