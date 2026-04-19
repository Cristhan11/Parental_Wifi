<?php

namespace Tests\Feature;

use App\Models\SecurityAuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityAuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_writes_security_audit_row(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('security_audit_events', [
            'event' => SecurityAuditEvent::EVENT_LOGIN_SUCCESS,
            'user_id' => $user->id,
        ]);

        $row = SecurityAuditEvent::query()->where('event', SecurityAuditEvent::EVENT_LOGIN_SUCCESS)->first();
        $this->assertNotNull($row);
        $this->assertFalse($row->is_remote);
    }

    public function test_failed_login_writes_security_audit_row(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('security_audit_events', [
            'event' => SecurityAuditEvent::EVENT_LOGIN_FAILURE,
            'attempted_identifier' => $user->email,
        ]);
    }

    public function test_lockout_writes_security_audit_row(): void
    {
        $email = 'test@example.com';
        RateLimiter::clear(Str::transliterate(Str::lower($email)).'|127.0.0.1');

        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('right-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('security_audit_events', [
            'event' => SecurityAuditEvent::EVENT_LOCKOUT,
            'attempted_identifier' => $user->email,
        ]);
    }

    public function test_successful_profile_update_writes_sensitive_action_audit(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
        ]);

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
        ])->assertRedirect(route('profile.edit', absolute: false));

        $this->assertDatabaseHas('security_audit_events', [
            'event' => SecurityAuditEvent::EVENT_SENSITIVE_ACTION,
            'user_id' => $user->id,
            'route_name' => 'profile.update',
        ]);
    }

    public function test_login_marks_remote_when_ip_not_in_trusted_local_cidrs(): void
    {
        config()->set('remote_access.trusted_local_cidrs', ['192.168.1.0/24']);

        $user = User::factory()->create();

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route('dashboard', absolute: false));

        $row = SecurityAuditEvent::query()->where('event', SecurityAuditEvent::EVENT_LOGIN_SUCCESS)->first();
        $this->assertNotNull($row);
        $this->assertTrue($row->is_remote);
        $this->assertSame('10.0.0.5', $row->ip_address);
    }
}
