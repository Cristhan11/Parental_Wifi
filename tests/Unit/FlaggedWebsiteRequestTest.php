<?php

namespace Tests\Unit;

use App\Http\Requests\StoreFlaggedWebsiteRequest;
use App\Http\Requests\UpdateFlaggedWebsiteRequest;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Flagged Website Form Request Unit Tests
 * 
 * Tests validation rules for StoreFlaggedWebsiteRequest and UpdateFlaggedWebsiteRequest.
 */
class FlaggedWebsiteRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test StoreFlaggedWebsiteRequest validation rules.
     */
    public function test_store_request_validation_rules(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $request = new StoreFlaggedWebsiteRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('device_id', $rules);
        $this->assertArrayHasKey('url', $rules);
        $this->assertArrayHasKey('reason', $rules);
    }

    /**
     * Test that device_id is required in store request.
     */
    public function test_store_request_device_id_is_required(): void
    {
        $user = User::factory()->create();

        $data = [
            'url' => 'https://example.com',
        ];

        $request = new StoreFlaggedWebsiteRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('device_id'));
    }

    /**
     * Test that url is required in store request.
     */
    public function test_store_request_url_is_required(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
        ];

        $request = new StoreFlaggedWebsiteRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('url'));
    }

    /**
     * Test that url must be valid format in store request.
     */
    public function test_store_request_url_must_be_valid(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'not-a-valid-url',
        ];

        $request = new StoreFlaggedWebsiteRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('url'));
    }

    /**
     * Test that reason is optional in store request.
     */
    public function test_store_request_reason_is_optional(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://example.com',
        ];

        $request = new StoreFlaggedWebsiteRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    /**
     * Test that reason has max length in store request.
     */
    public function test_store_request_reason_has_max_length(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        $data = [
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'reason' => str_repeat('a', 501),
        ];

        $request = new StoreFlaggedWebsiteRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('reason'));
    }

    /**
     * Test that device ownership is validated in store request.
     */
    public function test_store_request_validates_device_ownership(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device2 = Device::factory()->create(['user_id' => $user2->id]);

        $data = [
            'device_id' => $device2->id,
            'url' => 'https://example.com',
        ];

        $request = new StoreFlaggedWebsiteRequest();
        $request->setUserResolver(function () use ($user1) {
            return $user1;
        });

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('device_id'));
    }

    /**
     * Test UpdateFlaggedWebsiteRequest validation rules.
     */
    public function test_update_request_validation_rules(): void
    {
        $request = new UpdateFlaggedWebsiteRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('device_id', $rules);
        $this->assertArrayHasKey('url', $rules);
        $this->assertArrayHasKey('reason', $rules);
    }

    /**
     * Test that update request has same rules as store request.
     */
    public function test_update_request_has_same_rules_as_store(): void
    {
        $storeRequest = new StoreFlaggedWebsiteRequest();
        $updateRequest = new UpdateFlaggedWebsiteRequest();

        $storeRules = $storeRequest->rules();
        $updateRules = $updateRequest->rules();

        // Check that both have the same keys
        $this->assertEquals(array_keys($storeRules), array_keys($updateRules));
    }

    /**
     * Test that update request validates device ownership.
     */
    public function test_update_request_validates_device_ownership(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $device2 = Device::factory()->create(['user_id' => $user2->id]);

        $data = [
            'device_id' => $device2->id,
            'url' => 'https://example.com',
        ];

        $request = new UpdateFlaggedWebsiteRequest();
        $request->setUserResolver(function () use ($user1) {
            return $user1;
        });

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('device_id'));
    }
}

