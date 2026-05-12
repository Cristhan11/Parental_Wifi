<?php

namespace Tests\Feature;

use App\Models\SecurityAuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfileTailscaleAuthLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_request_tailscale_auth_link(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'action_required',
                'auth_url' => 'https://login.tailscale.com/a/abc123',
                'expires_at' => null,
                'message' => 'Open link now.',
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('profile.tailscale.auth-link'));
        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('tailscale_auth_link');

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'http://127.0.0.1:9098/v1/tailscale/auth-link' || ! $request->hasHeader('X-Pi-Agent-Token', 'secret')) {
                return false;
            }
            $data = json_decode($request->body(), true);

            return is_array($data)
                && ($data['force_reauth'] ?? false) === false
                && ! array_key_exists('dashboard_email', $data);
        });

        $this->assertDatabaseHas('security_audit_events', [
            'event' => SecurityAuditEvent::EVENT_TAILSCALE_AUTH_LINK_REQUEST,
            'user_id' => $user->id,
            'route_name' => 'profile.tailscale.auth-link',
        ]);
    }

    public function test_single_button_posts_dashboard_email_so_pi_matches_parent_account(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'action_required',
                'auth_url' => 'https://login.tailscale.com/a/single-button',
                'expires_at' => null,
                'message' => 'Open link to finish sign-in.',
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'email' => 'parent-onebutton@example.com',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('profile.tailscale.auth-link'), [
            'sync_tailscale_with_dashboard' => true,
        ]);
        $response->assertOk();
        $response->assertJsonPath('status', 'action_required');
        $response->assertJsonPath('auth_url', 'https://login.tailscale.com/a/single-button');
        $response->assertJsonPath('sync_tailscale_with_dashboard', true);
        $response->assertJsonPath('force_reauth', false);

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'http://127.0.0.1:9098/v1/tailscale/auth-link' || ! $request->hasHeader('X-Pi-Agent-Token', 'secret')) {
                return false;
            }
            $data = json_decode($request->body(), true);

            return is_array($data)
                && ($data['force_reauth'] ?? null) === false
                && ($data['dashboard_email'] ?? null) === 'parent-onebutton@example.com';
        });
    }

    public function test_parent_can_request_tailscale_auth_link_with_force_reauth(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'action_required',
                'auth_url' => 'https://login.tailscale.com/a/forced123',
                'expires_at' => null,
                'message' => 'Open link after switch.',
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('profile.tailscale.auth-link'), [
            'force_reauth' => true,
        ]);
        $response->assertOk();
        $response->assertJsonPath('status', 'action_required');
        $response->assertJsonPath('force_reauth', true);
        $response->assertJsonPath('sync_tailscale_with_dashboard', false);

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'http://127.0.0.1:9098/v1/tailscale/auth-link' || ! $request->hasHeader('X-Pi-Agent-Token', 'secret')) {
                return false;
            }
            $data = json_decode($request->body(), true);

            return is_array($data) && ($data['force_reauth'] ?? null) === true;
        });
    }

    public function test_sync_tailscale_sends_dashboard_email_to_pi_agent(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'already_authenticated',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Matched.',
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'email' => 'parent-sync@example.com',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('profile.tailscale.auth-link'), [
            'sync_tailscale_with_dashboard' => true,
        ]);
        $response->assertOk();
        $response->assertJsonPath('sync_tailscale_with_dashboard', true);
        $response->assertJsonPath('force_reauth', false);

        Http::assertSent(function (Request $request): bool {
            if ($request->url() !== 'http://127.0.0.1:9098/v1/tailscale/auth-link' || ! $request->hasHeader('X-Pi-Agent-Token', 'secret')) {
                return false;
            }
            $data = json_decode($request->body(), true);

            return is_array($data)
                && ($data['force_reauth'] ?? null) === false
                && ($data['dashboard_email'] ?? null) === 'parent-sync@example.com';
        });
    }

    public function test_status_only_check_uses_dashboard_email_and_returns_signed_in_as(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.status_timeout_seconds', 5);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'already_authenticated',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Pi is signed in to Tailscale as parent-status@example.com.',
                'signed_in_as' => 'parent-status@example.com',
                'matches_dashboard' => true,
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'email' => 'parent-status@example.com',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('profile.tailscale.auth-link'), [
            'status_only' => true,
        ]);
        $response->assertOk();
        $response->assertJsonPath('status', 'already_authenticated');
        $response->assertJsonPath('status_only', true);
        $response->assertJsonPath('signed_in_as', 'parent-status@example.com');
        $response->assertJsonPath('matches_dashboard', true);
        $response->assertJsonPath('sync_tailscale_with_dashboard', false);

        Http::assertSent(function (Request $request): bool {
            $body = json_decode($request->body(), true);

            return $request->url() === 'http://127.0.0.1:9098/v1/tailscale/auth-link'
                && is_array($body)
                && ($body['status_only'] ?? null) === true
                && ($body['dashboard_email'] ?? null) === 'parent-status@example.com';
        });
    }

    public function test_target_email_is_used_when_it_matches_session_verified_new_email(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'action_required',
                'auth_url' => 'https://login.tailscale.com/a/target-email',
                'expires_at' => null,
                'message' => 'Open this link to finish.',
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'email' => 'old-parent@example.com',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        // Mirror what `verifyProfileEmailChangeCode` puts in the session after the parent confirms
        // the 6-digit code for their new email; the controller must trust target_email only when it
        // matches this verified value.
        $verifiedPayload = [
            'email' => 'new-parent@example.com',
            'expires_at' => now()->addMinutes(20)->getTimestamp(),
        ];

        $response = $this->actingAs($user)
            ->withSession(['profile_email_change_verified' => $verifiedPayload])
            ->postJson(route('profile.tailscale.auth-link'), [
                'sync_tailscale_with_dashboard' => true,
                'target_email' => 'new-parent@example.com',
            ]);
        $response->assertOk();
        $response->assertJsonPath('used_target_email', true);

        Http::assertSent(function (Request $request): bool {
            $body = json_decode($request->body(), true);

            return is_array($body)
                && ($body['dashboard_email'] ?? null) === 'new-parent@example.com';
        });
    }

    public function test_target_email_is_ignored_when_session_has_no_matching_verified_email(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'already_authenticated',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Already signed in.',
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'email' => 'real-parent@example.com',
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('profile.tailscale.auth-link'), [
            'sync_tailscale_with_dashboard' => true,
            'target_email' => 'attacker@example.com',
        ]);
        $response->assertOk();
        $response->assertJsonPath('used_target_email', false);

        Http::assertSent(function (Request $request): bool {
            $body = json_decode($request->body(), true);

            // Falls back to the saved email — attacker-supplied target ignored.
            return is_array($body)
                && ($body['dashboard_email'] ?? null) === 'real-parent@example.com';
        });
    }

    public function test_rate_limit_blocks_excessive_auth_link_requests(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'already_authenticated',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Already signed in.',
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user)->post(route('profile.tailscale.auth-link'))->assertRedirect(route('profile.edit'));
        }
        $this->actingAs($user)->post(route('profile.tailscale.auth-link'))->assertStatus(429);
    }

    public function test_profile_edit_includes_tailscale_remote_access_section_for_parent(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PARENT,
            'email_verified_at' => now(),
            'approved_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Remote dashboard access (Tailscale)', false)
            ->assertSee('How to set this up', false)
            ->assertSee('Get Tailscale sign-in link', false)
            ->assertDontSee('Match Pi to my login email', false)
            ->assertDontSee('Get a new sign-in link', false);
    }
}
