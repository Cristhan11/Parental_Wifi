<?php

namespace Tests\Feature;

use App\Models\DeviceRegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRegistrationRequestQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_can_submit_registration_request_without_mac_input(): void
    {
        $response = $this->post(route('device-request.store'), [
            'device_name' => 'Kid Tablet',
        ], [
            'User-Agent' => 'Mozilla/Test',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('device_registration_requests', [
            'device_name' => 'Kid Tablet',
            'status' => 'pending',
        ]);
    }

    public function test_portal_landing_uses_portal_dev_client_mac_on_loopback_when_configured(): void
    {
        config(['portal.dev_client_mac' => 'AA:BB:CC:DD:EE:FE']);

        $response = $this->get('http://127.0.0.1/portal');

        $response->assertOk();
        $response->assertSee('Request to Register', false);
    }

    public function test_portal_landing_shows_request_to_register_when_session_has_unregistered_device_mac(): void
    {
        $response = $this->withSession(['device_mac' => 'AA:BB:CC:DD:EE:01'])
            ->get(route('portal.landing'));

        $response->assertOk();
        $response->assertSee('Request to Register', false);
        $response->assertSee('name="device_name"', false);
    }

    public function test_registration_store_uses_session_device_mac_from_portal(): void
    {
        $this->withSession(['device_mac' => 'aa:bb:cc:dd:ee:02'])
            ->post(route('device-request.store'), [
                'device_name' => 'Portal Tablet',
            ], [
                'User-Agent' => 'Mozilla/TestPortal',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('device_registration_requests', [
            'device_name' => 'Portal Tablet',
            'mac_address' => 'AA:BB:CC:DD:EE:02',
            'status' => 'pending',
        ]);
    }

    public function test_parent_owner_must_assign_role_before_approval(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $pending = DeviceRegistrationRequest::create([
            'device_name' => 'Nintendo Switch',
            'fingerprint' => sha1('switch'),
            'status' => 'pending',
            'seen_on_home_wifi' => true,
        ]);

        $response = $this
            ->actingAs($owner)
            ->post(route('accounts.registration-requests.approve', $pending), []);

        $response->assertSessionHasErrors('assigned_role');
        $this->assertDatabaseMissing('devices', ['name' => 'Nintendo Switch']);
    }

    public function test_guest_or_parent_role_is_auto_whitelisted_on_approval(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $pending = DeviceRegistrationRequest::create([
            'device_name' => 'Guest Phone',
            'fingerprint' => sha1('guest-phone'),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->post(route('accounts.registration-requests.approve', $pending), [
                'assigned_role' => 'guest',
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('devices', [
            'name' => 'Guest Phone',
            'role' => 'guest',
            'status' => 'whitelisted',
        ]);
    }

    public function test_parent_can_edit_pending_device_name_and_initial_time_before_approval(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $pending = DeviceRegistrationRequest::create([
            'device_name' => 'Old Tablet Name',
            'fingerprint' => sha1('old-tablet'),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->post(route('accounts.registration-requests.approve', $pending), [
                'assigned_role' => 'child',
                'device_name' => 'Ariana iPad',
                'initial_time_minutes' => 120,
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('devices', [
            'name' => 'Ariana iPad',
            'role' => 'child',
            'remaining_time_minutes' => 120,
            'total_time_allocated' => 120,
        ]);
    }

    public function test_parent_can_disapprove_pending_request(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_PARENT_ADMIN,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);

        $pending = DeviceRegistrationRequest::create([
            'device_name' => 'Test Pending Device',
            'fingerprint' => sha1('pending-device'),
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->post(route('accounts.registration-requests.reject', $pending))
            ->assertRedirect(route('accounts.create'));

        $this->assertDatabaseHas('device_registration_requests', [
            'id' => $pending->id,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseMissing('devices', [
            'name' => 'Test Pending Device',
        ]);
    }
}
