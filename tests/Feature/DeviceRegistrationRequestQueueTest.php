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
