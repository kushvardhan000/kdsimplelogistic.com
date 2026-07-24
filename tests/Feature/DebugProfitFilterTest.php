<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TransportLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugProfitFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_profit_filter(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $profitLog = TransportLog::factory()->create();
        $lossLog = TransportLog::factory()->create();

        $profitLog->update([
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

        // Check DB state
        $this->assertEquals(1000, TransportLog::find($profitLog->id)->profit);
        $this->assertEquals(-4000, TransportLog::find($lossLog->id)->profit);
        $this->assertEquals(1, TransportLog::where('profit', '>', 0)->count());
        $this->assertEquals(1, TransportLog::where('profit', '<', 0)->count());

        // Check request
        $request = request()->create('/transport-logs?status=profit', 'GET');
        $this->assertTrue($request->filled('status'));
        $this->assertEquals('profit', $request->string('status'));

        $response = $this->get('/transport-logs?status=profit');
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString($profitLog->vehicle_no, $content, 'Profit log should be present');
        $this->assertStringNotContainsString($lossLog->vehicle_no, $content, 'Loss log should NOT be present');
    }
}
