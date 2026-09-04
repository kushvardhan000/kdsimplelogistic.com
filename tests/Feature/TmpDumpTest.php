<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TmpDumpTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);
        Account::factory()->fuelStation()->count(24)->create();
        $response = $this->get(route('accounts.index', ['type' => 'fuel_station']));
        file_put_contents(storage_path('logs/dump_fuel.html'), $response->getContent());
        $this->assertTrue(true);
    }
}
