<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\TransportLog;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityChangeTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_transport_log_records_changes_in_activity_log(): void
    {
        $user = User::factory()->superAdmin()->create();
        $log = TransportLog::factory()->create([
            'freight' => 1000,
            'profit' => 3000,
        ]);

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

        $activity = ActivityLog::where('table_name', 'transport_logs')
            ->where('record_id', $log->id)
            ->where('action', 'update')
            ->whereNotNull('changes')
            ->first();

        $this->assertNotNull($activity, 'Activity log entry should exist');
        $this->assertNotNull($activity->changes, 'Changes payload should not be null');
        $this->assertIsArray($activity->changes);
        $this->assertTrue(
            collect($activity->changes)->contains(fn ($change) => $change['field'] === 'freight'),
            'Changes payload should contain the freight field'
        );
    }
}
