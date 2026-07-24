<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_public(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_protected_pages_redirect_guests(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/users')->assertRedirect('/login');
        $this->get('/settings')->assertRedirect('/login');
        $this->get('/transport-logs')->assertRedirect('/login');
    }

    public function test_super_admin_can_access_all_areas(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
        $this->get('/users')->assertOk();
        $this->get('/settings')->assertOk();
        $this->get('/transport-logs')->assertOk();
        $this->get('/activity-logs')->assertOk();
    }

    public function test_admin_cannot_access_super_admin_areas(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);

        $this->get('/users')->assertForbidden();
        $this->get('/activity-logs')->assertForbidden();
        $this->get('/transport-logs')->assertOk();
    }
}
