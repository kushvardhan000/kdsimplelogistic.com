<?php

namespace Tests\Feature;

use App\Models\Logsheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LogsheetClearingTest extends TestCase
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

    public function test_clearing_a_real_logsheet_updates_status_and_creates_audit_record(): void
    {
        Excel::fake();

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 26, null),
                    [
                        'Log Sheet No', 'Date', 'Invoice No', 'Inv-Date', 'Payer', 'Payer Name',
                        'Town', 'Gross Wt', 'difference', 'amount', 'Volume', 'Tprt Code',
                        'Tprt Name', 'Container ID', 'Destination', 'SAPInvoiceNo', 'Posting Date',
                        'Bill Date', 'VendorInvNo', 'Route', 'Town', 'Gross weight', 'Booked Amount',
                        'Actual Rate', 'Actual Amount', 'Diff',
                    ],
                    [
                        '45350959', '2026-06-12', 'INV-100', '2026-06-12', 'PAY-100', 'Test Payer',
                        'Test Town', '5999.802', '100.00', '6000.00', '30', 'T01', 'Transporter',
                        'VH-01', 'Test Dest', 'SAP-001', '2026-06-12', '2026-06-13', 'VEN-001',
                        'Route A', 'Town A', '5999.802', '18626.87', '120', '17999.41', '627.46',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        // Import
        $this->from('/logsheets')->post('/logsheets', [
            'file' => $file,
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-13',
        ]);
        $this->assertDatabaseHas('logsheets', ['log_sheet_no' => '45350959']);

        $logsheet = Logsheet::where('log_sheet_no', '45350959')->first();
        $this->assertEquals(5999.802, (float) $logsheet->total_gross_wt);

        // Clear it
        $this->from('/logsheets')->post('/logsheets/clear', ['log_sheet_no' => '45350959'])
            ->assertRedirect('/logsheets');

        $logsheet->refresh();
        $this->assertEquals('cleared', $logsheet->status);
        $this->assertNotNull($logsheet->cleared_at);
        $this->assertEquals($this->getSuperAdmin()->id, $logsheet->cleared_by);

        $this->assertDatabaseHas('logsheet_clearings', [
            'logsheet_id' => $logsheet->id,
            'cleared_by' => $this->getSuperAdmin()->id,
        ]);
    }

    public function test_clearing_already_cleared_logsheet_shows_info_message_and_is_idempotent(): void
    {
        Excel::fake();

        Excel::shouldReceive('toArray')
            ->once()
            ->andReturn([
                [
                    array_fill(0, 26, null),
                    [
                        'Log Sheet No', 'Date', 'Invoice No', 'Inv-Date', 'Payer', 'Payer Name',
                        'Town', 'Gross Wt', 'difference', 'amount', 'Volume', 'Tprt Code',
                        'Tprt Name', 'Container ID', 'Destination', 'SAPInvoiceNo', 'Posting Date',
                        'Bill Date', 'VendorInvNo', 'Route', 'Town', 'Gross weight', 'Booked Amount',
                        'Actual Rate', 'Actual Amount', 'Diff',
                    ],
                    [
                        '45350959', '2026-06-12', 'INV-100', '2026-06-12', 'PAY-100', 'Test Payer',
                        'Test Town', '5999.802', '100.00', '6000.00', '30', 'T01', 'Transporter',
                        'VH-01', 'Test Dest', 'SAP-001', '2026-06-12', '2026-06-13', 'VEN-001',
                        'Route A', 'Town A', '5999.802', '18626.87', '120', '17999.41', '627.46',
                    ],
                ],
            ]);

        $file = UploadedFile::fake()->create('test.xlsx');

        $this->from('/logsheets')->post('/logsheets', [
            'file' => $file,
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-13',
        ]);

        // Clear once
        $this->from('/logsheets')->post('/logsheets/clear', ['log_sheet_no' => '45350959'])
            ->assertRedirect('/logsheets');

        // Clear again - should show info message, not create duplicate
        $this->from('/logsheets')->post('/logsheets/clear', ['log_sheet_no' => '45350959'])
            ->assertRedirect('/logsheets');

        $logsheet = Logsheet::where('log_sheet_no', '45350959')->first();
        $this->assertEquals('cleared', $logsheet->status);

        // Only 1 clearing record should exist - idempotent
        $this->assertDatabaseCount('logsheet_clearings', 1);
    }

    public function test_clearing_nonexistent_logsheet_shows_validation_error(): void
    {
        $response = $this->actingAs($this->getSuperAdmin())->from('/logsheets')->post('/logsheets/clear', ['log_sheet_no' => '99999999']);
        $response->assertRedirect('/logsheets');
        $response->assertSessionHasErrors('log_sheet_no');
    }

    public function test_clearing_requires_log_sheet_no(): void
    {
        $response = $this->actingAs($this->getSuperAdmin())->from('/logsheets')->post('/logsheets/clear', []);
        $response->assertRedirect('/logsheets');
        $response->assertSessionHasErrors('log_sheet_no');
    }

    private function getSuperAdmin(): User
    {
        return User::where('email', 'admin@sls.com')->first();
    }
}
