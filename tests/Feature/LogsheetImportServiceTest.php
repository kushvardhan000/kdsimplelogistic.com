<?php

namespace Tests\Feature;

use App\Models\Logsheet;
use App\Models\LogsheetDetail;
use App\Models\LogsheetRawRow;
use App\Models\User;
use App\Services\LogsheetImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LogsheetImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->superAdmin()->create());
    }

    public function test_service_groups_by_logsheet_number_and_sums_requested_columns(): void
    {
        Excel::fake();

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    [
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                    ],
                    [
                        'Log Sheet No        ',
                        'Date      ',
                        'Invoice No          ',
                        'Inv- Date ',
                        'Payer     ',
                        'Payer Name                    ',
                        'Town                ',
                        'Gross Wt',
                        'difference ',
                        'amount',
                        'Volume  ',
                        'Tprt Code           ',
                        'Tprt Name           ',
                        'Container ID        ',
                        'Destination         ',
                        'SAPInvoiceNo        ',
                        'Posting Date',
                        'Bill Date   ',
                        'VendorInvNo         ',
                        'Route',
                        'Town',
                        'Gross weight',
                        'Booked Amount',
                        'Actual Rate ',
                        'Actual Amount',
                        'Diff',
                    ],
                    [
                        'LS-1001',
                        '2026-09-19',
                        'INV-001',
                        '2026-09-19',
                        'PAY-01',
                        'Payer One',
                        'Town A',
                        '1600',
                        '100',
                        '1800',
                        '12',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-19',
                        '2026-09-20',
                        'VIN-001',
                        'Route A',
                        'Town A',
                        '1600',
                        '1800',
                        '120',
                        '1900',
                        '100',
                    ],
                    [
                        'LS-1001',
                        '2026-09-19',
                        'INV-002',
                        '2026-09-19',
                        'PAY-02',
                        'Payer Two',
                        'Town B',
                        '1400',
                        '100',
                        '1700',
                        '8',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-19',
                        '2026-09-20',
                        'VIN-001',
                        'Route A',
                        'Town B',
                        '1400',
                        '1700',
                        '120',
                        '1800',
                        '100',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('sample.xlsx');

        $summary = app(LogsheetImportService::class)->import($file, '2026-09-19', '2026-09-19');

        $this->assertSame(2, $summary['rows_imported']);
        $this->assertSame(1, $summary['consolidated']);
        $this->assertSame(0, $summary['duplicates']);
        $this->assertSame(0, $summary['invalid']);
        $this->assertSame('3700.00', $summary['total_amount']);
        $this->assertSame(0, $summary['out_of_range_rows']);
        $this->assertNotNull($summary['import_id']);

        $this->assertDatabaseCount('logsheets', 1);
        $this->assertDatabaseHas('logsheets', [
            'log_sheet_no' => 'LS-1001',
            'total_gross_wt' => '3000.000',
            'total_booked_amount' => '3500.00',
            'total_actual_amount' => '3700.00',
            'total_diff' => '200.00',
        ]);

        $this->assertDatabaseCount('logsheet_raw_rows', 2);
        $this->assertSame(2, Logsheet::first()->consignment_count);
    }

    public function test_import_with_extra_columns_time_cust_group_no_of_packs_stored_in_extra_fields(): void
    {
        Excel::fake();

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 28, null),
                    [
                        'Log Sheet No',
                        'Date',
                        'Invoice No',
                        'Inv Date',
                        'Payer',
                        'Payer Name',
                        'Town',
                        'Gross Wt',
                        'difference',
                        'amount',
                        'Volume',
                        'Tprt Code',
                        'Tprt Name',
                        'Container ID',
                        'Destination',
                        'SAPInvoiceNo',
                        'Posting Date',
                        'Bill Date',
                        'VendorInvNo',
                        'Route',
                        'Town',
                        'Gross weight',
                        'Booked Amount',
                        'Actual Rate',
                        'Actual Amount',
                        'Diff',
                        'Time',
                        'Cust Group',
                        'No of Packs',
                    ],
                    [
                        'LS-2001',
                        '2026-09-19',
                        'INV-001',
                        '2026-09-19',
                        'PAY-01',
                        'Payer One',
                        'Town A',
                        '1600',
                        '100',
                        '1800',
                        '12',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-19',
                        '2026-09-20',
                        'VIN-001',
                        'Route A',
                        'Town A',
                        '1600',
                        '1800',
                        '120',
                        '1900',
                        '100',
                        '14:30',
                        'GROUP-A',
                        '50',
                    ],
                    [
                        'LS-2001',
                        '2026-09-19',
                        'INV-002',
                        '2026-09-19',
                        'PAY-02',
                        'Payer Two',
                        'Town B',
                        '1400',
                        '100',
                        '1700',
                        '8',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-19',
                        '2026-09-20',
                        'VIN-001',
                        'Route A',
                        'Town B',
                        '1400',
                        '1700',
                        '120',
                        '1800',
                        '100',
                        '15:45',
                        'GROUP-B',
                        '75',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('sample_with_extras.xlsx');

        $summary = app(LogsheetImportService::class)->import($file, '2026-09-19', '2026-09-19');

        $this->assertSame(2, $summary['rows_imported']);
        $this->assertSame(1, $summary['consolidated']);

        $logsheet = Logsheet::where('log_sheet_no', 'LS-2001')->first();
        $this->assertNotNull($logsheet);
        $this->assertSame(2, $logsheet->consignment_count);

        $details = LogsheetDetail::where('logsheet_id', $logsheet->id)->get();
        $this->assertCount(2, $details);

        // Check first detail row has extra_fields with Time, Cust Group, No of Packs
        $detail1 = $details->first();
        $this->assertNotEmpty($detail1->extra_fields);
        $this->assertArrayHasKey('Time', $detail1->extra_fields);
        $this->assertArrayHasKey('Cust Group', $detail1->extra_fields);
        $this->assertArrayHasKey('No of Packs', $detail1->extra_fields);
        $this->assertEquals('14:30', $detail1->extra_fields['Time']);
        $this->assertEquals('GROUP-A', $detail1->extra_fields['Cust Group']);
        $this->assertEquals('50', $detail1->extra_fields['No of Packs']);

        // Check second detail row
        $detail2 = $details->last();
        $this->assertNotEmpty($detail2->extra_fields);
        $this->assertArrayHasKey('Time', $detail2->extra_fields);
        $this->assertArrayHasKey('Cust Group', $detail2->extra_fields);
        $this->assertArrayHasKey('No of Packs', $detail2->extra_fields);
        $this->assertEquals('15:45', $detail2->extra_fields['Time']);
        $this->assertEquals('GROUP-B', $detail2->extra_fields['Cust Group']);
        $this->assertEquals('75', $detail2->extra_fields['No of Packs']);

        // Also verify raw_data in logsheet_raw_rows contains the extra columns
        $rawRows = LogsheetRawRow::where('log_sheet_no', $logsheet->log_sheet_no)->get();
        $this->assertCount(2, $rawRows);
        $raw1 = $rawRows->first()->raw_data; // Already cast to array
        $this->assertArrayHasKey('Time', $raw1);
        $this->assertArrayHasKey('Cust Group', $raw1);
        $this->assertArrayHasKey('No of Packs', $raw1);
    }

    public function test_import_without_extra_columns_works_cleanly(): void
    {
        Excel::fake();

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 25, null),
                    [
                        'Log Sheet No',
                        'Date',
                        'Invoice No',
                        'Inv Date',
                        'Payer',
                        'Payer Name',
                        'Town',
                        'Gross Wt',
                        'difference',
                        'amount',
                        'Volume',
                        'Tprt Code',
                        'Tprt Name',
                        'Container ID',
                        'Destination',
                        'SAPInvoiceNo',
                        'Posting Date',
                        'Bill Date',
                        'VendorInvNo',
                        'Route',
                        'Town',
                        'Gross weight',
                        'Booked Amount',
                        'Actual Rate',
                        'Actual Amount',
                        'Diff',
                    ],
                    [
                        'LS-3001',
                        '2026-09-19',
                        'INV-001',
                        '2026-09-19',
                        'PAY-01',
                        'Payer One',
                        'Town A',
                        '1600',
                        '100',
                        '1800',
                        '12',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-19',
                        '2026-09-20',
                        'VIN-001',
                        'Route A',
                        'Town A',
                        '1600',
                        '1800',
                        '120',
                        '1900',
                        '100',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('june_logdate_template.xlsx');

        $summary = app(LogsheetImportService::class)->import($file, '2026-09-19', '2026-09-19');

        $this->assertSame(1, $summary['rows_imported']);
        $this->assertSame(1, $summary['consolidated']);
        $this->assertSame(0, $summary['invalid']);

        $logsheet = Logsheet::where('log_sheet_no', 'LS-3001')->first();
        $this->assertNotNull($logsheet);

        $detail = LogsheetDetail::where('logsheet_id', $logsheet->id)->first();
        $this->assertNotNull($detail);

        // extra_fields should be null or empty array
        $this->assertTrue(
            $detail->extra_fields === null ||
            (is_array($detail->extra_fields) && empty($detail->extra_fields)),
            'extra_fields should be null or empty when no extra columns present'
        );

        // Raw data should not have extra columns
        $rawRow = LogsheetRawRow::where('log_sheet_no', $logsheet->log_sheet_no)->first();
        $rawData = $rawRow->raw_data; // Already cast to array
        $this->assertArrayNotHasKey('Time', $rawData);
        $this->assertArrayNotHasKey('Cust Group', $rawData);
        $this->assertArrayNotHasKey('No of Packs', $rawData);
    }

    public function test_two_imports_with_different_extra_columns_do_not_leak(): void
    {
        Excel::fake();

        // First import: has "Time" and "Cust Group" columns
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 27, null),
                    [
                        'Log Sheet No',
                        'Date',
                        'Invoice No',
                        'Inv Date',
                        'Payer',
                        'Payer Name',
                        'Town',
                        'Gross Wt',
                        'difference',
                        'amount',
                        'Volume',
                        'Tprt Code',
                        'Tprt Name',
                        'Container ID',
                        'Destination',
                        'SAPInvoiceNo',
                        'Posting Date',
                        'Bill Date',
                        'VendorInvNo',
                        'Route',
                        'Town',
                        'Gross weight',
                        'Booked Amount',
                        'Actual Rate',
                        'Actual Amount',
                        'Diff',
                        'Time',
                        'Cust Group',
                    ],
                    [
                        'LS-4001',
                        '2026-09-19',
                        'INV-001',
                        '2026-09-19',
                        'PAY-01',
                        'Payer One',
                        'Town A',
                        '1600',
                        '100',
                        '1800',
                        '12',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-19',
                        '2026-09-20',
                        'VIN-001',
                        'Route A',
                        'Town A',
                        '1600',
                        '1800',
                        '120',
                        '1900',
                        '100',
                        '14:30',
                        'GROUP-A',
                    ],
                ],
            ]);

        $file1 = UploadedFile::fake()->create('file1.xlsx');
        $summary1 = app(LogsheetImportService::class)->import($file1, '2026-09-19', '2026-09-19');

        $this->assertSame(1, $summary1['rows_imported']);

        $logsheet1 = Logsheet::where('log_sheet_no', 'LS-4001')->first();
        $detail1 = LogsheetDetail::where('logsheet_id', $logsheet1->id)->first();
        $this->assertArrayHasKey('Time', $detail1->extra_fields);
        $this->assertArrayHasKey('Cust Group', $detail1->extra_fields);
        $this->assertArrayNotHasKey('No of Packs', $detail1->extra_fields);
        $this->assertEquals('14:30', $detail1->extra_fields['Time']);
        $this->assertEquals('GROUP-A', $detail1->extra_fields['Cust Group']);

        // Second import: has "No of Packs" and "Custom Field" columns (different set)
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 27, null),
                    [
                        'Log Sheet No',
                        'Date',
                        'Invoice No',
                        'Inv Date',
                        'Payer',
                        'Payer Name',
                        'Town',
                        'Gross Wt',
                        'difference',
                        'amount',
                        'Volume',
                        'Tprt Code',
                        'Tprt Name',
                        'Container ID',
                        'Destination',
                        'SAPInvoiceNo',
                        'Posting Date',
                        'Bill Date',
                        'VendorInvNo',
                        'Route',
                        'Town',
                        'Gross weight',
                        'Booked Amount',
                        'Actual Rate',
                        'Actual Amount',
                        'Diff',
                        'No of Packs',
                        'Custom Field',
                    ],
                    [
                        'LS-5001',
                        '2026-09-20',
                        'INV-002',
                        '2026-09-20',
                        'PAY-02',
                        'Payer Two',
                        'Town B',
                        '1400',
                        '100',
                        '1700',
                        '8',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-20',
                        '2026-09-21',
                        'VIN-002',
                        'Route B',
                        'Town B',
                        '1400',
                        '1700',
                        '120',
                        '1800',
                        '100',
                        '100',
                        'CUSTOM-VALUE',
                    ],
                ],
            ]);

        $file2 = UploadedFile::fake()->create('file2.xlsx');
        $summary2 = app(LogsheetImportService::class)->import($file2, '2026-09-20', '2026-09-20');

        $this->assertSame(1, $summary2['rows_imported']);

        $logsheet2 = Logsheet::where('log_sheet_no', 'LS-5001')->first();
        $detail2 = LogsheetDetail::where('logsheet_id', $logsheet2->id)->first();
        $this->assertArrayHasKey('No of Packs', $detail2->extra_fields);
        $this->assertArrayHasKey('Custom Field', $detail2->extra_fields);
        $this->assertArrayNotHasKey('Time', $detail2->extra_fields);
        $this->assertArrayNotHasKey('Cust Group', $detail2->extra_fields);
        $this->assertEquals('100', $detail2->extra_fields['No of Packs']);
        $this->assertEquals('CUSTOM-VALUE', $detail2->extra_fields['Custom Field']);

        // Verify first logsheet's details still only have their original extra fields
        $detail1Fresh = LogsheetDetail::where('logsheet_id', $logsheet1->id)->first();
        $this->assertArrayHasKey('Time', $detail1Fresh->extra_fields);
        $this->assertArrayHasKey('Cust Group', $detail1Fresh->extra_fields);
        $this->assertArrayNotHasKey('No of Packs', $detail1Fresh->extra_fields);
        $this->assertArrayNotHasKey('Custom Field', $detail1Fresh->extra_fields);

        // Verify second logsheet's details only have their extra fields
        $this->assertArrayHasKey('No of Packs', $detail2->extra_fields);
        $this->assertArrayHasKey('Custom Field', $detail2->extra_fields);
        $this->assertArrayNotHasKey('Time', $detail2->extra_fields);
        $this->assertArrayNotHasKey('Cust Group', $detail2->extra_fields);
    }

    public function test_import_with_blank_difference_and_gross_weight_columns_succeeds(): void
    {
        Excel::fake();

        // Test file with "difference" and "Gross weight" columns BLANK for some rows
        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 25, null),
                    [
                        'Log Sheet No',
                        'Date',
                        'Invoice No',
                        'Inv Date',
                        'Payer',
                        'Payer Name',
                        'Town',
                        'Gross Wt',
                        'difference',
                        'amount',
                        'Volume',
                        'Tprt Code',
                        'Tprt Name',
                        'Container ID',
                        'Destination',
                        'SAPInvoiceNo',
                        'Posting Date',
                        'Bill Date',
                        'VendorInvNo',
                        'Route',
                        'Town',
                        'Gross weight',
                        'Booked Amount',
                        'Actual Rate',
                        'Actual Amount',
                        'Diff',
                    ],
                    // Row 1: all fields present
                    [
                        'LS-6001',
                        '2026-09-19',
                        'INV-001',
                        '2026-09-19',
                        'PAY-01',
                        'Payer One',
                        'Town A',
                        '1600',
                        '100',
                        '1800',
                        '12',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-19',
                        '2026-09-20',
                        'VIN-001',
                        'Route A',
                        'Town A',
                        '1600',
                        '1800',
                        '120',
                        '1900',
                        '100',
                    ],
                    // Row 2: difference and Gross weight are BLANK
                    [
                        'LS-6001',
                        '2026-09-19',
                        'INV-002',
                        '2026-09-19',
                        'PAY-02',
                        'Payer Two',
                        'Town B',
                        '1400',
                        '',  // difference is blank
                        '1700',
                        '8',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-19',
                        '2026-09-20',
                        'VIN-001',
                        'Route A',
                        'Town B',
                        '',  // Gross weight is blank
                        '1700',
                        '120',
                        '1800',
                        '100',
                    ],
                    // Row 3: difference has value, Gross weight blank
                    [
                        'LS-6001',
                        '2026-09-19',
                        'INV-003',
                        '2026-09-19',
                        'PAY-03',
                        'Payer Three',
                        'Town C',
                        '1200',
                        '50',  // difference has value
                        '1500',
                        '6',
                        'T01',
                        'Transporter A',
                        'VH-01',
                        'Chennai',
                        'SAP-001',
                        '2026-09-19',
                        '2026-09-20',
                        'VIN-001',
                        'Route A',
                        'Town C',
                        '',  // Gross weight is blank
                        '1500',
                        '120',
                        '1600',
                        '100',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('blank_difference_gross_weight.xlsx');

        // This should NOT throw SQLSTATE[22007] Incorrect decimal value
        $summary = app(LogsheetImportService::class)->import($file, '2026-09-19', '2026-09-19');

        $this->assertSame(3, $summary['rows_imported']);
        $this->assertSame(1, $summary['consolidated']);

        $logsheet = Logsheet::where('log_sheet_no', 'LS-6001')->first();
        $this->assertNotNull($logsheet);
        $this->assertSame(3, $logsheet->consignment_count);

        $details = LogsheetDetail::where('logsheet_id', $logsheet->id)->orderBy('invoice_no')->get();
        $this->assertCount(3, $details);

        // Row 1: all fields present - difference_placeholder and gross_weight_2 should have values
        $detail1 = $details[0];
        $this->assertEquals('100.000', $detail1->difference_placeholder);
        $this->assertEquals('1600.000', $detail1->gross_weight_2);

        // Row 2: difference and Gross weight BLANK - should be NULL in DB, not empty string
        $detail2 = $details[1];
        $this->assertNull($detail2->difference_placeholder, 'difference_placeholder should be null when source is blank');
        $this->assertNull($detail2->gross_weight_2, 'gross_weight_2 should be null when source is blank');

        // Row 3: difference has value, Gross weight blank
        $detail3 = $details[2];
        $this->assertEquals('50.000', $detail3->difference_placeholder, 'difference_placeholder should have value when source has value');
        $this->assertNull($detail3->gross_weight_2, 'gross_weight_2 should be null when source is blank');

        // Verify import succeeded without SQL error
        $this->assertEquals(3, $summary['rows_imported']);
        $this->assertEquals(1, $summary['consolidated']);
    }
}