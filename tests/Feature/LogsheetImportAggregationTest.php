<?php

namespace Tests\Feature;

use App\Models\Logsheet;
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
            ->andReturn($this->getFixtureData());

        $file = UploadedFile::fake()->create('test.xlsx');

        $this->from('/logsheets')->post('/logsheets', [
            'file' => $file,
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-13',
        ]);

        // Verify Log Sheet 45348787 (2 consignments)
        $ls1 = Logsheet::where('log_sheet_no', '45348787')->first();
        $this->assertNotNull($ls1);
        $this->assertEquals('45348787', $ls1->log_sheet_no);
        $this->assertEquals(4733.100, (float) $ls1->total_gross_wt);
        $this->assertEquals(12779.37, (float) $ls1->total_booked_amount);
        $this->assertEquals(13059.30, (float) $ls1->total_actual_amount);
        $this->assertEquals(110.70, (float) $ls1->total_diff);
        $this->assertEquals('VH-01', $ls1->vehicle_no);
        $this->assertEquals('JAMSHEDPUR', $ls1->destination);
        $this->assertEquals(2, $ls1->consignment_count);

        // Verify Log Sheet 45350959 (3 consignments) - confirmed spec: total_gross_wt = 5999.802
        $ls2 = Logsheet::where('log_sheet_no', '45350959')->first();
        $this->assertNotNull($ls2);
        $this->assertEquals(5999.802, (float) $ls2->total_gross_wt);
        $this->assertEquals(18626.87, (float) $ls2->total_booked_amount);
        $this->assertEquals(18311.74, (float) $ls2->total_actual_amount);
        $this->assertEquals(300.20, (float) $ls2->total_diff);
        $this->assertEquals('VH-03', $ls2->vehicle_no);
        $this->assertEquals('GARHWA', $ls2->destination);
        $this->assertEquals(3, $ls2->consignment_count);

        // Verify Log Sheet 45352790 (2 consignments)
        $ls3 = Logsheet::where('log_sheet_no', '45352790')->first();
        $this->assertNotNull($ls3);
        $this->assertEquals(5077.205, (float) $ls3->total_gross_wt);
        $this->assertEquals(14622.35, (float) $ls3->total_booked_amount);
        $this->assertEquals(14455.62, (float) $ls3->total_actual_amount);
        $this->assertEquals(200.20, (float) $ls3->total_diff);
        $this->assertEquals('VH-06', $ls3->vehicle_no);
        $this->assertEquals('LATEHAR', $ls3->destination);
        $this->assertEquals(2, $ls3->consignment_count);

        // Verify raw rows preserved (one per consignment, 7 total)
        $this->assertDatabaseCount('logsheet_raw_rows', 7);

        // Verify raw row data integrity for 45350959
        $raw2 = LogsheetRawRow::where('log_sheet_no', '45350959')->orderBy('row_number_in_file')->get();
        $this->assertCount(3, $raw2);
        $this->assertEquals('INV-301', $raw2[0]->raw_data['invoice_no']);
        $this->assertEquals('Payer Gamma', $raw2[0]->raw_data['payer_name']);
        $this->assertEquals('Town C', $raw2[0]->raw_data['town']);
        $this->assertEquals('20', $raw2[0]->raw_data['volume']);
        $this->assertEquals('INV-302', $raw2[1]->raw_data['invoice_no']);
        $this->assertEquals('Payer Delta', $raw2[1]->raw_data['payer_name']);
        $this->assertEquals('Town D', $raw2[1]->raw_data['town']);
        $this->assertEquals('INV-303', $raw2[2]->raw_data['invoice_no']);
        $this->assertEquals('Payer Epsilon', $raw2[2]->raw_data['payer_name']);

        // Verify show page displays raw row data correctly
        $response = $this->from('/logsheets/' . $ls2->id)->get('/logsheets/' . $ls2->id);
        $response->assertOk();
        $response->assertSee('INV-301');
        $response->assertSee('Payer Gamma');
        $response->assertSee('Town C');
        $response->assertSee('20');
        $response->assertSee('INV-303');
        $response->assertSee('Payer Epsilon');
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

        $this->from('/logsheets')->post('/logsheets', [
            'file' => $file,
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-13',
        ]);

        $ls = Logsheet::where('log_sheet_no', '45350959')->first();
        $this->assertEquals(5999.802, (float) $ls->total_gross_wt);
        $this->assertEquals(2, $ls->consignment_count);
        $this->assertDatabaseCount('logsheet_raw_rows', 2);
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
}