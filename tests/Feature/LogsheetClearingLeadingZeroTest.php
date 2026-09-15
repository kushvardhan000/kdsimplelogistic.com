<?php

namespace Tests\Feature;

use App\Models\Logsheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogsheetClearingLeadingZeroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->superAdmin()->create([
            'email' => 'admin@sls.com',
            'password' => bcrypt('password'),
        ]));

        // Create a logsheet as it would be stored from Excel import (no leading zeros)
        Logsheet::create([
            'log_sheet_no' => '45350959',
            'date' => '2026-06-12',
            'vehicle_no' => 'JH03AV7689',
            'destination' => 'GARHWA',
            'total_gross_wt' => 5999.802,
            'total_booked_amount' => 18626.87,
            'total_actual_amount' => 17999.41,
            'total_diff' => 627.46,
            'status' => 'pending',
        ]);
    }

    public function test_clearing_with_exact_log_sheet_no_works(): void
    {
        $response = $this->from('/logsheets')->post('/logsheets/clear', ['log_sheet_no' => '45350959']);
        $response->assertRedirect('/logsheets');

        $logsheet = Logsheet::where('log_sheet_no', '45350959')->first();
        $this->assertEquals('cleared', $logsheet->status);
    }

    public function test_clearing_with_leading_zeros_matches_stored_value(): void
    {
        // Invoice shows "0045350959" but DB stores "45350959"
        $response = $this->actingAs($this->getSuperAdmin())->from('/logsheets')->post('/logsheets/clear', ['log_sheet_no' => '0045350959']);
        $response->assertRedirect('/logsheets');
        $response->assertSessionHasNoErrors();

        // Log sheet should be cleared
        $logsheet = Logsheet::where('log_sheet_no', '45350959')->first();
        $this->assertEquals('cleared', $logsheet->status);
    }

    private function getSuperAdmin(): User
    {
        return User::where('email', 'admin@sls.com')->first();
    }
}
