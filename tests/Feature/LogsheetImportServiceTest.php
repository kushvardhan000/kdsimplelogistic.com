<?php

namespace Tests\Feature;

use App\Models\Logsheet;
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
                        '2026-09-01',
                        'INV-001',
                        '2026-09-01',
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
                        '2026-09-01',
                        '2026-09-02',
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
                        '2026-09-01',
                        'INV-002',
                        '2026-09-01',
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
                        '2026-09-01',
                        '2026-09-02',
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

        $summary = app(LogsheetImportService::class)->import($file);

        $this->assertSame(2, $summary['rows_imported']);
        $this->assertSame(1, $summary['consolidated']);
        $this->assertSame(0, $summary['duplicates']);
        $this->assertSame(0, $summary['invalid']);

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
}
