<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_change_email_or_password_via_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('settings.update'), [
                'name' => $admin->name,
                'email' => 'changed@example.com',
                'current_password' => 'password',
            ])
            ->assertSessionHasErrors(['email', 'current_password']);

        $this->assertDatabaseMissing('users', ['id' => $admin->id, 'email' => 'changed@example.com']);
    }

    public function test_admin_can_update_only_name_via_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('settings.update'), [
                'name' => 'Updated Admin',
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'Updated Admin']);
    }

    public function test_super_admin_can_change_email_and_password_via_settings(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('settings.update'), [
                'name' => $superAdmin->name,
                'email' => 'newemail@example.com',
                'current_password' => 'password',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'email' => 'newemail@example.com']);
    }
}
