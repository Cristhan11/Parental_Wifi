<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Video;
use App\Services\NetworkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Device Management Feature Tests
 *
 * Tests all CRUD operations, validation, authorization, and status management
 * for the Device Management system (TODO 18).
 *
 * These tests verify:
 * - Device creation, reading, updating, deletion
 * - MAC address validation and normalization
 * - Authorization (users can only manage their own devices)
 * - Status management (active/blocked/whitelisted)
 * - Time allocation management
 * - Role management
 * - Network service integration
 *
 * Database: Uses RefreshDatabase trait (compatible with MariaDB)
 */
class DeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the accounts page displays correctly.
     */
    public function test_accounts_page_displays_correctly(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('accounts.index'));

        $response->assertOk();
        $response->assertSee($device->name);
        $response->assertSee($device->mac_address);
    }

    /**
     * Test that users can only see their own devices.
     */
    public function test_users_can_only_see_their_own_devices(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $device1 = Device::factory()->create(['user_id' => $user1->id, 'name' => 'User 1 Device']);
        $device2 = Device::factory()->create(['user_id' => $user2->id, 'name' => 'User 2 Device']);

        $response = $this->actingAs($user1)->get(route('accounts.index'));

        $response->assertOk();
        $response->assertSee($device1->name);
        $response->assertDontSee($device2->name);
    }

    /**
     * Test that the create device form displays correctly.
     */
    public function test_create_device_form_displays(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('accounts.create'));

        $response->assertOk();
        $response->assertSee('ADD DEVICE');
        $response->assertDontSee('MAC Address *');
    }

    /**
     * Network suggestions on create should omit MACs already registered for this parent.
     */
    public function test_create_page_network_list_excludes_registered_devices(): void
    {
        $user = User::factory()->create();
        // Avoid AA:BB:… placeholder text on the same page (MAC input hint).
        Device::factory()->create([
            'user_id' => $user->id,
            'mac_address' => 'C0:FF:EE:00:00:01',
        ]);

        $this->mock(NetworkService::class, function ($mock) {
            $mock->shouldReceive('getConnectedDevices')
                ->once()
                ->andReturn([
                    ['mac_address' => 'C0-FF-EE-00-00-01', 'ip_address' => '192.168.1.10'],
                    ['mac_address' => '11:22:33:44:55:66', 'ip_address' => '192.168.1.20'],
                ]);
        });

        $response = $this->actingAs($user)->get(route('accounts.create.advanced'));

        $response->assertOk();
        $response->assertDontSee('C0:FF:EE:00:00:01');
        $response->assertSee('11:22:33:44:55:66');
    }

    /**
     * Test creating a device with valid data.
     */
    public function test_can_create_device_with_valid_data(): void
    {
        $user = User::factory()->create();

        $deviceData = [
            'name' => 'Test Device',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'advanced_mode' => '1',
            'role' => 'child',
            'status' => 'active',
            'remaining_time_minutes' => 30,
            'total_time_allocated' => 30,
        ];

        $response = $this->actingAs($user)
            ->post(route('accounts.store'), $deviceData);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'name' => 'Test Device',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'status' => 'active',
            'role' => 'child',
        ]);
    }

    /**
     * Test that MAC address is normalized to uppercase with colons.
     */
    public function test_mac_address_is_normalized_on_create(): void
    {
        $user = User::factory()->create();

        $deviceData = [
            'name' => 'Test Device',
            'mac_address' => 'aa-bb-cc-dd-ee-ff', // Lowercase with hyphens
            'advanced_mode' => '1',
            'role' => 'child',
            'status' => 'active',
        ];

        $this->actingAs($user)->post(route('accounts.store'), $deviceData);

        $this->assertDatabaseHas('devices', [
            'mac_address' => 'AA:BB:CC:DD:EE:FF', // Should be normalized
        ]);
    }

    /**
     * Test that default time allocation is set if not provided.
     */
    public function test_default_time_allocation_on_create(): void
    {
        $user = User::factory()->create();

        $deviceData = [
            'name' => 'Test Device',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'advanced_mode' => '1',
            'role' => 'child',
            'status' => 'active',
            // Not providing remaining_time_minutes
        ];

        $this->actingAs($user)->post(route('accounts.store'), $deviceData);

        $device = Device::where('mac_address', 'AA:BB:CC:DD:EE:FF')->first();
        $this->assertEquals(15, $device->remaining_time_minutes);
        $this->assertEquals(15, $device->total_time_allocated);
    }

    /**
     * Test validation: name is required.
     */
    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $deviceData = [
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'role' => 'child',
            'status' => 'active',
        ];

        $response = $this->actingAs($user)
            ->post(route('accounts.store'), $deviceData);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test validation: MAC address is required on advanced/debug path.
     */
    public function test_mac_address_is_required(): void
    {
        $user = User::factory()->create();

        $deviceData = [
            'name' => 'Test Device',
            'advanced_mode' => '1',
            'role' => 'child',
            'status' => 'active',
        ];

        $response = $this->actingAs($user)
            ->post(route('accounts.store'), $deviceData);

        $response->assertSessionHasErrors('mac_address');
    }

    /**
     * Test validation: MAC address must be unique.
     */
    public function test_mac_address_must_be_unique(): void
    {
        $user = User::factory()->create();
        $existingDevice = Device::factory()->create(['user_id' => $user->id]);

        $deviceData = [
            'name' => 'Test Device',
            'mac_address' => $existingDevice->mac_address, // Duplicate MAC
            'advanced_mode' => '1',
            'role' => 'child',
            'status' => 'active',
        ];

        $response = $this->actingAs($user)
            ->post(route('accounts.store'), $deviceData);

        $response->assertSessionHasErrors('mac_address');
    }

    /**
     * Test validation: invalid MAC address format.
     */
    public function test_invalid_mac_address_format(): void
    {
        $user = User::factory()->create();

        $deviceData = [
            'name' => 'Test Device',
            'mac_address' => 'INVALID-MAC-ADDRESS',
            'advanced_mode' => '1',
            'role' => 'child',
            'status' => 'active',
        ];

        $response = $this->actingAs($user)
            ->post(route('accounts.store'), $deviceData);

        $response->assertSessionHasErrors('mac_address');
    }

    /**
     * Test validation: status must be valid.
     */
    public function test_status_must_be_valid(): void
    {
        $user = User::factory()->create();

        $deviceData = [
            'name' => 'Test Device',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'advanced_mode' => '1',
            'role' => 'child',
            'status' => 'invalid_status',
        ];

        $response = $this->actingAs($user)
            ->post(route('accounts.store'), $deviceData);

        $response->assertSessionHasErrors('status');
    }

    /**
     * Test validation: role must be valid.
     */
    public function test_role_must_be_valid(): void
    {
        $user = User::factory()->create();

        $deviceData = [
            'name' => 'Test Device',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'advanced_mode' => '1',
            'role' => 'invalid_role',
            'status' => 'active',
        ];

        $response = $this->actingAs($user)
            ->post(route('accounts.store'), $deviceData);

        $response->assertSessionHasErrors('role');
    }

    /**
     * Test validation: time allocation bounds.
     */
    public function test_time_allocation_bounds(): void
    {
        $user = User::factory()->create();

        // Test negative time
        $deviceData = [
            'name' => 'Test Device',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'advanced_mode' => '1',
            'role' => 'child',
            'status' => 'active',
            'remaining_time_minutes' => -10,
        ];

        $response = $this->actingAs($user)
            ->post(route('accounts.store'), $deviceData);

        $response->assertSessionHasErrors('remaining_time_minutes');

        // Test time exceeding max
        $deviceData['remaining_time_minutes'] = 10000;
        $response = $this->actingAs($user)
            ->post(route('accounts.store'), $deviceData);

        $response->assertSessionHasErrors('remaining_time_minutes');
    }

    /**
     * Test that the edit device form displays correctly.
     */
    public function test_edit_device_form_displays(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('accounts.edit', $device));

        $response->assertOk();
        $response->assertSee('EDIT DEVICE');
        $response->assertSee($device->name);
    }

    /**
     * Test that users cannot edit other users' devices.
     */
    public function test_users_cannot_edit_other_users_devices(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->get(route('accounts.edit', $device));

        $response->assertForbidden();
    }

    /**
     * Test updating a device with valid data.
     */
    public function test_can_update_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'status' => 'active',
        ]);

        $updateData = [
            'name' => 'New Name',
            'mac_address' => $device->mac_address,
            'role' => 'child',
            'status' => 'blocked',
            'remaining_time_minutes' => 60,
            'total_time_allocated' => 60,
        ];

        $response = $this->actingAs($user)
            ->put(route('accounts.update', $device), $updateData);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'name' => 'New Name',
            'status' => 'blocked',
        ]);
    }

    /**
     * Test that MAC address can be kept the same when updating.
     */
    public function test_can_keep_same_mac_address_on_update(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'name' => 'Updated Name',
            'mac_address' => $device->mac_address, // Same MAC
            'role' => 'child',
            'status' => 'active',
        ];

        $response = $this->actingAs($user)
            ->put(route('accounts.update', $device), $updateData);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'mac_address' => $device->mac_address,
        ]);
    }

    /**
     * Test that users cannot update other users' devices.
     */
    public function test_users_cannot_update_other_users_devices(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user1->id]);

        $updateData = [
            'name' => 'Hacked Name',
            'mac_address' => $device->mac_address,
            'role' => 'child',
            'status' => 'active',
        ];

        $response = $this->actingAs($user2)
            ->put(route('accounts.update', $device), $updateData);

        $response->assertForbidden();
    }

    /**
     * Test deleting a device.
     */
    public function test_can_delete_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->delete(route('accounts.destroy', $device));

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }

    /**
     * Test that users cannot delete other users' devices.
     */
    public function test_users_cannot_delete_other_users_devices(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->delete(route('accounts.destroy', $device));

        $response->assertForbidden();
        $this->assertDatabaseHas('devices', ['id' => $device->id]);
    }

    /**
     * Test updating device status.
     */
    public function test_can_update_device_status(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->post(route('accounts.status.update', $device), [
                'status' => 'blocked',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'blocked',
        ]);
    }

    /**
     * Test updating device status via AJAX.
     */
    public function test_can_update_device_status_via_ajax(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('accounts.status.update', $device), [
                'status' => 'whitelisted',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'whitelisted',
        ]);

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'whitelisted',
        ]);
    }

    /**
     * Test updating device time allocation.
     */
    public function test_can_update_time_allocation(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'remaining_time_minutes' => 30,
        ]);

        $response = $this->actingAs($user)
            ->post(route('accounts.time.update', $device), [
                'remaining_time_minutes' => 60,
                'total_time_allocated' => 120,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $device->refresh();
        $this->assertEquals(60, $device->remaining_time_minutes);
        $this->assertEquals(120, $device->total_time_allocated);
    }

    /**
     * Test updating device role.
     */
    public function test_can_update_device_role(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
        ]);

        $response = $this->actingAs($user)
            ->post(route('accounts.role.update', $device), [
                'role' => 'guest',
            ]);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'role' => 'guest',
        ]);
    }

    /**
     * Test blocklist page displays only blocked devices.
     */
    public function test_blocklist_page_displays_blocked_devices(): void
    {
        $user = User::factory()->create();
        $blockedDevice = Device::factory()->blocked()->create(['user_id' => $user->id]);
        $activeDevice = Device::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('accounts.blocklist'));

        $response->assertOk();
        $response->assertSee($blockedDevice->name);
        $response->assertDontSee($activeDevice->name);
    }

    /**
     * Test whitelist page displays only whitelisted devices.
     */
    public function test_whitelist_page_displays_whitelisted_devices(): void
    {
        $user = User::factory()->create();
        $whitelistedDevice = Device::factory()->whitelisted()->create(['user_id' => $user->id]);
        $activeDevice = Device::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('accounts.whitelist'));

        $response->assertOk();
        $response->assertSee($whitelistedDevice->name);
        $response->assertDontSee($activeDevice->name);
    }

    /**
     * Test child devices stats page displays correctly.
     */
    public function test_child_devices_stats_page_displays(): void
    {
        $user = User::factory()->create();
        Device::factory()->active()->role('child')->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('child_devices.index'));

        $response->assertOk();
    }

    public function test_child_devices_page_excludes_parent_and_guest_devices(): void
    {
        $user = User::factory()->create();
        $parentDevice = Device::factory()->active()->role('parent')->create([
            'user_id' => $user->id,
            'name' => 'Parent Laptop Unique',
        ]);
        Device::factory()->active()->role('guest')->create([
            'user_id' => $user->id,
            'name' => 'Guest Phone Unique',
        ]);
        Device::factory()->active()->role('child')->create([
            'user_id' => $user->id,
            'name' => 'Kid Tablet Unique',
        ]);

        $response = $this->actingAs($user)->get(route('child_devices.index', ['device' => $parentDevice->id]));

        $response->assertOk();
        $response->assertSee('Kid Tablet Unique');
        $response->assertDontSee('Parent Laptop Unique');
        $response->assertDontSee('Guest Phone Unique');

        $response = $this->actingAs($user)->get(route('child_devices.show', $parentDevice));
        $response->assertOk();
        $response->assertSee('Kid Tablet Unique');
        $response->assertDontSee('Parent Laptop Unique');
    }

    public function test_child_devices_page_lists_assigned_quizzes_and_videos(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->active()->role('child')->create(['user_id' => $user->id]);
        $quiz = Quiz::create([
            'user_id' => $user->id,
            'title' => 'Child Portal Math Quiz',
            'description' => null,
            'level' => 'Elementary',
            'subject' => 'Math',
            'question_count' => 5,
            'scoring_mode' => 'pass_score',
            'minutes_per_correct' => 1,
            'passing_score' => 70,
            'time_reward_minutes' => 10,
            'questions' => ['questions' => []],
            'is_active' => true,
        ]);
        $quiz->devices()->sync([$device->id]);
        $video = Video::create([
            'user_id' => $user->id,
            'title' => 'Child Portal Science Clip',
            'description' => null,
            'video_path' => 'videos/sample.mp4',
            'duration_seconds' => 120,
            'dictionary_words_enabled' => false,
            'word_count' => 0,
            'time_reward_minutes' => 10,
            'is_active' => true,
        ]);
        $video->devices()->sync([$device->id]);

        $response = $this->actingAs($user)->get(route('child_devices.index', ['device' => $device->id]));

        $response->assertOk();
        $response->assertSee('ASSIGNED QUIZ & VIDEO');
        $response->assertSee('Child Portal Math Quiz');
        $response->assertSee('Child Portal Science Clip');
        $response->assertSee('Quizzes');
        $response->assertSee('Videos');
        $response->assertDontSee('Edit assignments');
    }

    /**
     * Test connected devices API endpoint.
     */
    public function test_connected_devices_api_endpoint(): void
    {
        $user = User::factory()->create();

        // Mock NetworkService to return test data
        $this->mock(NetworkService::class, function ($mock) {
            $mock->shouldReceive('getConnectedDevices')
                ->once()
                ->andReturn([
                    [
                        'mac_address' => 'AA:BB:CC:DD:EE:FF',
                        'ip_address' => '192.168.1.100',
                    ],
                ]);
        });

        $response = $this->actingAs($user)
            ->getJson(route('child_devices.api.connected'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'devices' => [
                [
                    'mac_address' => 'AA:BB:CC:DD:EE:FF',
                    'ip_address' => '192.168.1.100',
                ],
            ],
        ]);
    }

    /**
     * Test that unauthenticated users cannot access device management.
     */
    public function test_unauthenticated_users_cannot_access_devices(): void
    {
        $response = $this->get(route('accounts.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('accounts.create'));
        $response->assertRedirect(route('login'));
    }
}
