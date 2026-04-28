<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->seedOwnerSetupCompleted();

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->seedOwnerSetupCompleted();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
    }

    public function test_registration_is_blocked_before_parent_owner_initial_setup_is_completed(): void
    {
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'requires_email_setup' => true,
            'force_password_change' => true,
            'email_verified_at' => null,
        ]);

        $this->get('/register')->assertNotFound();
    }

    private function seedOwnerSetupCompleted(): void
    {
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'requires_email_setup' => false,
            'force_password_change' => false,
            'email_verified_at' => now(),
        ]);
    }
}
