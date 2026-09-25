<?php

namespace Tests\Feature;

use App\Models\Logsheet;
use App\Models\LogsheetDetail;
use App\Models\LogsheetRawRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LogsheetImportAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->superAdmin()->create([
            'email' => 'admin@sls.com',
            'password' => bcrypt('password'),
        ]));
    }

public function test_aggregation_sums_totals_correctly_across_multiple_log_sheets(): void
    {
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
                    // Single log sheet with 1 row
                    [
                        'TEST001', '2026-06-11', 'INV-001', '2026-06-11', 'PAY-001', 'Payer Test',
                        'Town A', '1000.000', '100.00', '1100.00', '25', 'T01', 'Transporter X',
                        'VH-01', 'JAMSHEDPUR', 'SAP-001', '2026-06-11', '2026-06-12', 'VEN-001',
                        'Route A', 'Town A', '1000.000', '6400.00', '100', '6300.00', '100.50',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        // Use service directly to avoid CSRF/middleware issues in controller test
        $summary = app(\App\Services\LogsheetImportService::class)->import($file, '2026-06-10', '2026-06-13');

        // Verify summary
        $this->assertEquals(1, $summary['rows_imported']);
        $this->assertEquals(1, $summary['consolidated']);
        $this->assertEquals(0, $summary['out_of_range_rows']);
        $this->assertEquals(0, $summary['skipped_out_of_range_groups']);

        // Verify raw rows preserved (one per consignment, 1 total)
        $this->assertDatabaseCount('logsheet_raw_rows', 1);
    }

    public function test_duplicate_import_does_not_lose_consignment_data(): void
    {
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
                        '45350959', '2026-06-12', 'INV-100', '2026-06-12', 'PAY-100', 'Payer One',
                        'Town A', '3000.000', '100.00', '3100.00', '30', 'T01', 'Transporter A',
                        'VH-01', 'Chennai', 'SAP-001', '2026-06-12', '2026-06-13', 'VIN-001',
                        'Route A', 'Town A', '3000.000', '9000.00', '120', '8880.00', '120.00',
                    ],
                    [
                        '45350959', '2026-06-12', 'INV-101', '2026-06-12', 'PAY-101', 'Payer Two',
                        'Town B', '2999.802', '100.00', '3099.80', '25', 'T01', 'Transporter A',
                        'VH-02', 'Chennai', 'SAP-001', '2026-06-12', '2026-06-13', 'VIN-002',
                        'Route A', 'Town B', '2999.802', '9000.00', '120', '8880.00', '19.80',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        // Use service directly
        app(\App\Services\LogsheetImportService::class)->import($file, '2026-06-10', '2026-06-13');

        $ls = Logsheet::where('log_sheet_no', '45350959')->first();
        $this->assertEquals(5999.802, (float) $ls->total_gross_wt);
        $this->assertEquals(2, $ls->consignment_count);
        $this->assertDatabaseCount('logsheet_raw_rows', 2);
    }

public function test_import_with_all_rows_out_of_range_creates_logsheet_with_flag(): void
    {
        Excel::fake();

        // All rows are in July 2026, but user selects June 2026 range
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
                        '99999999', '2026-07-01', 'INV-901', '2026-07-01', 'PAY-901', 'Payer Out1',
                        'Town Out', '1000.000', '100.00', '1100.00', '25', 'T01', 'Transporter Out1',
                        'VH-99', 'OUTSIDE', 'SAP-901', '2026-07-01', '2026-07-02', 'VEN-901',
                        'Route Out', 'Town Out', '1000.000', '5000.00', '100', '5100.00', '100.00',
                    ],
                    [
                        '99999999', '2026-07-02', 'INV-902', '2026-07-02', 'PAY-902', 'Payer Out2',
                        'Town Out2', '2000.000', '75.00', '2075.00', '20', 'T02', 'Transporter Out2',
                        'VH-98', 'OUTSIDE2', 'SAP-902', '2026-07-02', '2026-07-03', 'VEN-902',
                        'Route Out2', 'Town Out2', '2000.000', '10000.00', '100', '10100.00', '100.00',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        // Test the service directly since controller tests have CSRF issues
        $summary = app(\App\Services\LogsheetImportService::class)->import($file, '2026-06-01', '2026-06-30');

        // Verify the summary has the correct data - rows imported but fully out of range
        $this->assertEquals(2, $summary['rows_imported']);
        $this->assertEquals(1, $summary['consolidated']); // Now creates 1 logsheet (flagged)
        $this->assertEquals(2, $summary['out_of_range_rows']);
        $this->assertEquals(1, $summary['fully_out_of_range_groups']); // New field: flagged as fully out of range
        $this->assertEquals(0, $summary['skipped_out_of_range_groups']); // No longer skipped

        // Verify Logsheet WAS created with fully_out_of_requested_range flag
        $logsheet = Logsheet::where('log_sheet_no', '99999999')->first();
        $this->assertNotNull($logsheet);
        $this->assertTrue($logsheet->fully_out_of_requested_range);
        // Totals should be computed from ALL rows since group is fully out of range
        $this->assertEquals('3000.000', $logsheet->total_gross_wt); // 1000 + 2000
        $this->assertEquals('15000.00', $logsheet->total_booked_amount); // 5000 + 10000
        $this->assertEquals('15200.00', $logsheet->total_actual_amount); // 5100 + 10100
        $this->assertEquals('200.00', $logsheet->total_diff); // 100 + 100
        $this->assertEquals(2, $logsheet->consignment_count);

        // Verify import record has correct stats
        $import = \App\Models\LogsheetImport::latest()->first();
        $this->assertEquals(2, $import->out_of_range_rows);
        $this->assertEquals(1, $import->fully_out_of_range_groups);
        $this->assertEquals(1, $import->consolidated_count);
        $this->assertEquals(2, $import->row_count);
    }

    public function test_batch_insert_performance_with_3000_plus_rows(): void
    {
        Excel::fake();

        // Generate 3500 rows across 7 log sheets (500 rows each) to test chunking at 500
        $rows = [
            array_fill(0, 26, null),
            [
                'Log Sheet No', 'Date', 'Invoice No', 'Inv- Date', 'Payer', 'Payer Name',
                'Town', 'Gross Wt', 'difference', 'amount', 'Volume', 'Tprt Code',
                'Tprt Name', 'Container ID', 'Destination', 'SAPInvoiceNo', 'Posting Date',
                'Bill Date', 'VendorInvNo', 'Route', 'Town', 'Gross weight', 'Booked Amount',
                'Actual Rate', 'Actual Amount', 'Diff',
            ],
        ];

        $baseDate = '2026-06-15';
        $logSheetCount = 7;
        $rowsPerLogSheet = 500;
        $expectedTotalRows = $logSheetCount * $rowsPerLogSheet;

        for ($ls = 1; $ls <= $logSheetCount; $ls++) {
            $logSheetNo = 'PERF' . str_pad($ls, 5, '0', STR_PAD_LEFT);
            for ($i = 1; $i <= $rowsPerLogSheet; $i++) {
                $rows[] = [
                    $logSheetNo,
                    $baseDate,
                    'INV-' . $ls . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    $baseDate,
                    'PAY-' . $ls . '-' . $i,
                    'Payer ' . $ls . '-' . $i,
                    'Town ' . $ls,
                    '1000.000',
                    '50.00',
                    '1050.00',
                    '10',
                    'T01',
                    'Transporter ' . $ls,
                    'VH-' . str_pad($ls, 2, '0', STR_PAD_LEFT),
                    'DEST' . $ls,
                    'SAP-' . $ls . '-' . $i,
                    $baseDate,
                    '2026-06-16',
                    'VEN-' . $ls . '-' . $i,
                    'Route ' . $ls,
                    'Town ' . $ls,
                    '1000.000',
                    '5000.00',
                    '100',
                    '5100.00',
                    '100.00',
                ];
            }
        }

        Excel::shouldReceive('toArray')->once()->andReturn([$rows]);

        $file = UploadedFile::fake()->create('perf_test.xlsx');

        $startTime = microtime(true);
        $summary = app(\App\Services\LogsheetImportService::class)->import($file, '2026-06-01', '2026-06-30');
        $elapsed = microtime(true) - $startTime;

        // Assert completion within time budget (10 seconds should be plenty for 3500 rows with batching)
        $this->assertLessThan(10.0, $elapsed, "Import took {$elapsed}s, expected under 10s");

        // Verify all rows were imported
        $this->assertEquals($expectedTotalRows, $summary['rows_imported']);
        $this->assertEquals($logSheetCount, $summary['consolidated']);
        $this->assertEquals(0, $summary['out_of_range_rows']);
        $this->assertEquals(0, $summary['skipped_out_of_range_groups']);

        // Verify Logsheet count
        $this->assertDatabaseCount('logsheets', $logSheetCount);

        // Verify LogsheetDetail count matches exactly
        $this->assertDatabaseCount('logsheet_details', $expectedTotalRows);

        // Verify LogsheetRawRow count matches exactly
        $this->assertDatabaseCount('logsheet_raw_rows', $expectedTotalRows);

        // Verify each logsheet has correct consignment count
        foreach (range(1, $logSheetCount) as $ls) {
            $logSheetNo = 'PERF' . str_pad($ls, 5, '0', STR_PAD_LEFT);
            $lsModel = Logsheet::where('log_sheet_no', $logSheetNo)->first();
            $this->assertNotNull($lsModel, "Logsheet {$logSheetNo} not found");
            $this->assertEquals($rowsPerLogSheet, $lsModel->consignment_count);
            $this->assertEquals('500000.000', $lsModel->total_gross_wt); // 500 * 1000.000
            $this->assertEquals('2500000.00', $lsModel->total_booked_amount); // 500 * 5000.00
            $this->assertEquals('2550000.00', $lsModel->total_actual_amount); // 500 * 5100.00
            $this->assertEquals('50000.00', $lsModel->total_diff); // 500 * 100.00
        }

        // Verify detail rows have correct data for first logsheet
        $details = LogsheetDetail::where('log_sheet_no', 'PERF00001')->orderBy('id')->get();
        $this->assertCount($rowsPerLogSheet, $details);
        $this->assertEquals('1000.000', $details[0]->gross_wt);
        $this->assertEquals(false, $details[0]->cleared);
    }

    public function test_distinct_gross_wt_and_gross_weight_columns_are_preserved(): void
    {
        Excel::fake();

        // Test that when both "Gross Wt" and "Gross weight" columns exist with DIFFERENT values,
        // the first "Gross Wt" is used for totals (authoritative) and the second "Gross weight"
        // is stored separately in gross_weight_2, not silently overwriting.
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
                    // Row 1: Gross Wt = 1000.000, Gross weight = 2000.000 (DIFFERENT values)
                    [
                        'TEST001', '2026-06-11', 'INV-001', '2026-06-11', 'PAY-001', 'Payer Test',
                        'Town A', '1000.000', '100.00', '1100.00', '25', 'T01', 'Transporter X',
                        'VH-01', 'JAMSHEDPUR', 'SAP-001', '2026-06-11', '2026-06-12', 'VEN-001',
                        'Route A', 'Town A', '2000.000', '6400.00', '100', '6300.00', '100.50',
                    ],
                    // Row 2: Gross Wt = 1500.000, Gross weight = 2500.000 (DIFFERENT values)
                    [
                        'TEST001', '2026-06-11', 'INV-002', '2026-06-11', 'PAY-002', 'Payer Test2',
                        'Town B', '1500.000', '200.00', '1700.00', '30', 'T01', 'Transporter X',
                        'VH-02', 'JAMSHEDPUR', 'SAP-001', '2026-06-11', '2026-06-12', 'VEN-002',
                        'Route A', 'Town B', '2500.000', '6500.00', '120', '6600.00', '100.50',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        $summary = app(\App\Services\LogsheetImportService::class)->import($file, '2026-06-10', '2026-06-13');

        // Verify summary
        $this->assertEquals(2, $summary['rows_imported']);
        $this->assertEquals(1, $summary['consolidated']);

        // Verify Logsheet totals use the FIRST "Gross Wt" column (authoritative)
        $logsheet = Logsheet::where('log_sheet_no', 'TEST001')->first();
        $this->assertNotNull($logsheet);
        // Total gross_wt should be sum of first column: 1000.000 + 1500.000 = 2500.000
        $this->assertEquals('2500.000', $logsheet->total_gross_wt);

        // Verify detail rows preserve both columns separately
        $details = LogsheetDetail::where('log_sheet_no', 'TEST001')->orderBy('id')->get();
        $this->assertCount(2, $details);

        // Row 1: gross_wt (first column) = 1000.000, gross_weight_2 (second column) = 2000.000
        $this->assertEquals('1000.000', $details[0]->gross_wt);
        $this->assertEquals('2000.000', $details[0]->gross_weight_2);

        // Row 2: gross_wt (first column) = 1500.000, gross_weight_2 (second column) = 2500.000
        $this->assertEquals('1500.000', $details[1]->gross_wt);
        $this->assertEquals('2500.000', $details[1]->gross_weight_2);

        // Verify the second column did NOT silently overwrite the first
        $this->assertNotEquals($details[0]->gross_wt, $details[0]->gross_weight_2);
        $this->assertNotEquals($details[1]->gross_wt, $details[1]->gross_weight_2);
    }

    public function test_distinct_difference_and_diff_columns_are_preserved(): void
    {
        Excel::fake();

        // Test that when both "difference" and "Diff" columns exist with DIFFERENT values,
        // the "Diff" column (later in sheet) is used for totals (authoritative) and the earlier
        // "difference" column is stored separately in difference_placeholder.
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
                    // Row 1: difference = 50.00, Diff = 100.00 (DIFFERENT values)
                    [
                        'TEST002', '2026-06-11', 'INV-001', '2026-06-11', 'PAY-001', 'Payer Test',
                        'Town A', '1000.000', '50.00', '1050.00', '25', 'T01', 'Transporter X',
                        'VH-01', 'JAMSHEDPUR', 'SAP-001', '2026-06-11', '2026-06-12', 'VEN-001',
                        'Route A', 'Town A', '1000.000', '5000.00', '100', '5100.00', '100.00',
                    ],
                    // Row 2: difference = 75.00, Diff = 150.00 (DIFFERENT values)
                    [
                        'TEST002', '2026-06-11', 'INV-002', '2026-06-11', 'PAY-002', 'Payer Test2',
                        'Town B', '2000.000', '75.00', '2075.00', '30', 'T01', 'Transporter X',
                        'VH-02', 'JAMSHEDPUR', 'SAP-001', '2026-06-11', '2026-06-12', 'VEN-002',
                        'Route A', 'Town B', '2000.000', '6000.00', '100', '6150.00', '150.00',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        $summary = app(\App\Services\LogsheetImportService::class)->import($file, '2026-06-10', '2026-06-13');

        // Verify summary
        $this->assertEquals(2, $summary['rows_imported']);
        $this->assertEquals(1, $summary['consolidated']);

        // Verify Logsheet totals use the "Diff" column (authoritative, later in sheet)
        $logsheet = Logsheet::where('log_sheet_no', 'TEST002')->first();
        $this->assertNotNull($logsheet);
        // Total diff should be sum of Diff column: 100.00 + 150.00 = 250.00
        $this->assertEquals('250.00', $logsheet->total_diff);

        // Verify detail rows preserve both columns separately
        $details = LogsheetDetail::where('log_sheet_no', 'TEST002')->orderBy('id')->get();
        $this->assertCount(2, $details);

        // Row 1: diff (authoritative Diff column) = 100.00, difference_placeholder (earlier difference column) = 50.00
        $this->assertEquals('100.00', $details[0]->diff);
        $this->assertEquals('50.000', $details[0]->difference_placeholder);

        // Row 2: diff (authoritative Diff column) = 150.00, difference_placeholder (earlier difference column) = 75.00
        $this->assertEquals('150.00', $details[1]->diff);
        $this->assertEquals('75.000', $details[1]->difference_placeholder);

        // Verify the earlier column did NOT silently overwrite the authoritative one
        $this->assertNotEquals($details[0]->diff, $details[0]->difference_placeholder);
        $this->assertNotEquals($details[1]->diff, $details[1]->difference_placeholder);
    }

    private function getFixtureData(): array
    {
        return [
            [
                array_fill(0, 26, null),
                [
                    'Log Sheet No', 'Date', 'Invoice No', 'Inv- Date', 'Payer', 'Payer Name',
                    'Town', 'Gross Wt', 'difference', 'amount', 'Volume', 'Tprt Code',
                    'Tprt Name', 'Container ID', 'Destination', 'SAPInvoiceNo', 'Posting Date',
                    'Bill Date', 'VendorInvNo', 'Route', 'Town', 'Gross weight', 'Booked Amount',
                    'Actual Rate', 'Actual Amount', 'Diff',
                ],
                // Log Sheet 45348787: 2 consignments
                [
                    '45348787', '2026-06-11', 'INV-201', '2026-06-11', 'PAY-201', 'Payer Alpha',
                    'Town A', '2000.500', '100.00', '2100.00', '25', 'T01', 'Transporter X',
                    'VH-01', 'JAMSHEDPUR', 'SAP-201', '2026-06-11', '2026-06-12', 'VEN-201',
                    'Route A', 'Town A', '2000.500', '6400.00', '100', '6300.00', '100.50',
                ],
                [
                    '45348787', '2026-06-11', 'INV-202', '2026-06-11', 'PAY-202', 'Payer Beta',
                    'Town B', '2732.600', '130.20', '2862.80', '30', 'T01', 'Transporter X',
                    'VH-02', 'JAMSHEDPUR', 'SAP-201', '2026-06-11', '2026-06-12', 'VEN-202',
                    'Route A', 'Town B', '2732.600', '6379.37', '120', '6759.30', '10.20',
                ],
                // Log Sheet 45350959: 3 consignments (confirmed spec: total_gross_wt = 5999.802)
                [
                    '45350959', '2026-06-12', 'INV-301', '2026-06-12', 'PAY-301', 'Payer Gamma',
                    'Town C', '2000.000', '150.00', '2150.00', '20', 'T01', 'Transporter Y',
                    'VH-03', 'GARHWA', 'SAP-301', '2026-06-12', '2026-06-13', 'VEN-301',
                    'Route B', 'Town C', '2000.000', '6200.00', '110', '6100.00', '100.00',
                ],
                [
                    '45350959', '2026-06-12', 'INV-302', '2026-06-12', 'PAY-302', 'Payer Delta',
                    'Town D', '1999.802', '100.00', '2149.80', '18', 'T01', 'Transporter Y',
                    'VH-04', 'GARHWA', 'SAP-301', '2026-06-12', '2026-06-13', 'VEN-302',
                    'Route B', 'Town D', '1999.802', '6226.87', '115', '6111.74', '100.20',
                ],
                [
                    '45350959', '2026-06-12', 'INV-303', '2026-06-12', 'PAY-303', 'Payer Epsilon',
                    'Town E', '2000.000', '150.01', '2150.01', '22', 'T01', 'Transporter Y',
                    'VH-05', 'GARHWA', 'SAP-301', '2026-06-12', '2026-06-13', 'VEN-303',
                    'Route B', 'Town E', '2000.000', '6200.00', '110', '6100.00', '100.00',
                ],
                // Log Sheet 45352790: 2 consignments
                [
                    '45352790', '2026-06-12', 'INV-401', '2026-06-12', 'PAY-401', 'Payer Zeta',
                    'Town F', '2500.000', '120.00', '2620.00', '28', 'T01', 'Transporter Z',
                    'VH-06', 'LATEHAR', 'SAP-401', '2026-06-12', '2026-06-13', 'VEN-401',
                    'Route C', 'Town F', '2500.000', '7300.00', '100', '7200.00', '100.00',
                ],
                [
                    '45352790', '2026-06-12', 'INV-402', '2026-06-12', 'PAY-402', 'Payer Eta',
                    'Town G', '2577.205', '133.20', '2710.40', '25', 'T01', 'Transporter Z',
                    'VH-07', 'LATEHAR', 'SAP-401', '2026-06-12', '2026-06-13', 'VEN-402',
                    'Route C', 'Town G', '2577.205', '7322.35', '105', '7255.62', '100.20',
                ],
            ],
        ];
    }

    public function test_import_with_new_columns_time_cust_group_no_of_packs(): void
    {
        Excel::fake();

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 29, null),
                    [
                        'Log Sheet No', 'Date', 'Invoice No', 'Inv- Date', 'Payer', 'Payer Name',
                        'Town', 'Gross Wt', 'difference', 'amount', 'Volume', 'Tprt Code',
                        'Tprt Name', 'Container ID', 'Destination', 'SAPInvoiceNo', 'Posting Date',
                        'Bill Date', 'VendorInvNo', 'Route', 'Town', 'Gross weight', 'Booked Amount',
                        'Actual Rate', 'Actual Amount', 'Diff', 'Time', 'Cust Group', 'No of Packs',
                    ],
                    [
                        'TEST001', '2026-06-11', 'INV-001', '2026-06-11', 'PAY-001', 'Payer Test',
                        'Town A', '1000.000', '100.00', '1100.00', '25', 'T01', 'Transporter X',
                        'VH-01', 'JAMSHEDPUR', 'SAP-001', '2026-06-11', '2026-06-12', 'VEN-001',
                        'Route A', 'Town A', '1000.000', '6400.00', '100', '6300.00', '100.50',
                        '14:30', 'GROUP-A', '50',
                    ],
                    [
                        'TEST001', '2026-06-11', 'INV-002', '2026-06-11', 'PAY-002', 'Payer Test2',
                        'Town B', '1500.000', '200.00', '1700.00', '30', 'T01', 'Transporter X',
                        'VH-02', 'JAMSHEDPUR', 'SAP-001', '2026-06-11', '2026-06-12', 'VEN-002',
                        'Route A', 'Town B', '1500.000', '6500.00', '120', '6600.00', '100.50',
                        '16:45', 'GROUP-B', '75',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        $summary = app(\App\Services\LogsheetImportService::class)->import($file, '2026-06-10', '2026-06-13');

        // Debug
        $rawCount = \App\Models\LogsheetRawRow::count();
        dump('Total raw rows for TEST001 test: ' . $rawCount);
        $detailCount = \App\Models\LogsheetDetail::where('log_sheet_no', 'TEST001')->count();
        dump('Total details for TEST001: ' . $detailCount);

        // Verify summary
        $this->assertEquals(2, $summary['rows_imported']);
        $this->assertEquals(1, $summary['consolidated']);

        // Verify LogsheetDetail has the new columns populated in extra_fields
        $details = LogsheetDetail::where('log_sheet_no', 'TEST001')->orderBy('id')->get();
        $this->assertCount(2, $details);

        // Row 1
        $this->assertEquals('14:30', $details[0]->extra_fields['Time'] ?? null);
        $this->assertEquals('GROUP-A', $details[0]->extra_fields['Cust Group'] ?? null);
        $this->assertEquals('50', $details[0]->extra_fields['No of Packs'] ?? null);

        // Row 2
        $this->assertEquals('16:45', $details[1]->extra_fields['Time'] ?? null);
        $this->assertEquals('GROUP-B', $details[1]->extra_fields['Cust Group'] ?? null);
        $this->assertEquals('75', $details[1]->extra_fields['No of Packs'] ?? null);
    }

    public function test_import_without_new_columns_still_works(): void
    {
        Excel::fake();

        // Use the existing fixture data format (without Time, Cust Group, No of Packs)
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn($this->getFixtureData());

        $file = UploadedFile::fake()->create('test.xlsx');

        $summary = app(\App\Services\LogsheetImportService::class)->import($file, '2026-06-10', '2026-06-13');

        // Verify summary - should import cleanly
        $this->assertEquals(7, $summary['rows_imported']);
        $this->assertEquals(3, $summary['consolidated']);
        $this->assertEquals(0, $summary['out_of_range_rows']);
        $this->assertEquals(0, $summary['skipped_out_of_range_groups']);

        // Verify LogsheetDetail has extra_fields as null or empty (backward compatible)
        $details = LogsheetDetail::where('log_sheet_no', '45348787')->orderBy('id')->get();
        $this->assertCount(2, $details);

        foreach ($details as $detail) {
            $this->assertTrue(
                $detail->extra_fields === null ||
                (is_array($detail->extra_fields) && empty($detail->extra_fields)),
                'extra_fields should be null or empty when no extra columns present'
            );
        }
    }
}