<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\FlaggedWebsite;
use App\Models\User;
use App\Policies\FlaggedWebsitePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flagged Website Policy Unit Tests
 * 
 * Tests the FlaggedWebsitePolicy authorization logic.
 */
class FlaggedWebsitePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected FlaggedWebsitePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FlaggedWebsitePolicy();
    }

    /**
     * Test that any authenticated user can view flagged websites list.
     */
    public function test_any_user_can_view_flagged_websites_list(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /**
     * Test that user can view flagged website for their own device.
     */
    public function test_user_can_view_flagged_website_for_own_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertTrue($this->policy->view($user, $flaggedWebsite));
    }

    /**
     * Test that user cannot view flagged website for other user's device.
     */
    public function test_user_cannot_view_flagged_website_for_other_users_device(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device2 = Device::factory()->create(['user_id' => $user2->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertFalse($this->policy->view($user1, $flaggedWebsite));
    }

    /**
     * Test that any authenticated user can create flagged websites.
     */
    public function test_any_user_can_create_flagged_websites(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /**
     * Test that user can update flagged website for their own device.
     */
    public function test_user_can_update_flagged_website_for_own_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertTrue($this->policy->update($user, $flaggedWebsite));
    }

    /**
     * Test that user cannot update flagged website for other user's device.
     */
    public function test_user_cannot_update_flagged_website_for_other_users_device(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device2 = Device::factory()->create(['user_id' => $user2->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertFalse($this->policy->update($user1, $flaggedWebsite));
    }

    /**
     * Test that user can delete flagged website for their own device.
     */
    public function test_user_can_delete_flagged_website_for_own_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertTrue($this->policy->delete($user, $flaggedWebsite));
    }

    /**
     * Test that user cannot delete flagged website for other user's device.
     */
    public function test_user_cannot_delete_flagged_website_for_other_users_device(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device2 = Device::factory()->create(['user_id' => $user2->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device2->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertFalse($this->policy->delete($user1, $flaggedWebsite));
    }
}

