<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_shows_exactly_seeded_users_and_no_drivers(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['name' => 'Super Admin', 'email' => 'admin@sls.com']);
        $admin1 = User::factory()->admin()->create(['name' => 'Admin One', 'email' => 'admin1@sls.com']);
        $admin2 = User::factory()->admin()->create(['name' => 'Admin Two', 'email' => 'admin2@sls.com']);

        $driver1 = Driver::factory()->create(['name' => 'Driver One']);
        $driver2 = Driver::factory()->create(['name' => 'Driver Two']);

        $this->actingAs($superAdmin);

        $response = $this->get('/users');

        $response->assertOk();
        $response->assertSee('Super Admin');
        $response->assertSee('Admin One');
        $response->assertSee('Admin Two');
        $response->assertDontSee('Driver One');
        $response->assertDontSee('Driver Two');

        $response->assertSee('admin@sls.com');
        $response->assertSee('admin1@sls.com');
        $response->assertSee('admin2@sls.com');

        $dom = new \DOMDocument();
        @$dom->loadHTML($response->content());
        $rows = $dom->getElementsByTagName('tr');
        $userRows = 0;
        foreach ($rows as $row) {
            if ($row->getElementsByTagName('td')->length > 0) {
                $userRows++;
            }
        }
        $this->assertEquals(3, $userRows, 'Users index should show exactly 3 user rows');
    }
}
