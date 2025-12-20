<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\FlaggedWebsite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flagged Website Model Unit Tests
 * 
 * Tests the FlaggedWebsite model relationships, attributes, and behavior.
 */
class FlaggedWebsiteModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that flagged website belongs to a device.
     */
    public function test_flagged_website_belongs_to_device(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertInstanceOf(Device::class, $flaggedWebsite->device);
        $this->assertEquals($device->id, $flaggedWebsite->device->id);
    }

    /**
     * Test that device has many flagged websites.
     */
    public function test_device_has_many_flagged_websites(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $flagged1 = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example1.com',
            'domain' => 'example1.com',
        ]);

        $flagged2 = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example2.com',
            'domain' => 'example2.com',
        ]);

        $this->assertCount(2, $device->flaggedWebsites);
        $this->assertTrue($device->flaggedWebsites->contains($flagged1));
        $this->assertTrue($device->flaggedWebsites->contains($flagged2));
    }

    /**
     * Test that flagged website has fillable attributes.
     */
    public function test_flagged_website_has_fillable_attributes(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'reason' => 'Test reason',
        ];

        $flaggedWebsite = FlaggedWebsite::create($data);

        $this->assertEquals($data['device_id'], $flaggedWebsite->device_id);
        $this->assertEquals($data['url'], $flaggedWebsite->url);
        $this->assertEquals($data['domain'], $flaggedWebsite->domain);
        $this->assertEquals($data['reason'], $flaggedWebsite->reason);
    }

    /**
     * Test that reason can be null.
     */
    public function test_reason_can_be_null(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $flaggedWebsite = FlaggedWebsite::create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'reason' => null,
        ]);

        $this->assertNull($flaggedWebsite->reason);
    }

    /**
     * Test that timestamps are automatically set.
     */
    public function test_timestamps_are_automatically_set(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $flaggedWebsite = FlaggedWebsite::create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $this->assertNotNull($flaggedWebsite->created_at);
        $this->assertNotNull($flaggedWebsite->updated_at);
    }

    /**
     * Test that flagged website is deleted when device is deleted (cascade).
     */
    public function test_flagged_website_deleted_when_device_deleted(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);
        $flaggedWebsite = FlaggedWebsite::factory()->create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
        ]);

        $device->delete();

        $this->assertDatabaseMissing('flagged_websites', [
            'id' => $flaggedWebsite->id,
        ]);
    }
}

