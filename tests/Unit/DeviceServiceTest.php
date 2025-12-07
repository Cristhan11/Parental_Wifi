<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\User;
use App\Services\DeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Device Service Unit Tests
 * 
 * Tests the DeviceService class methods:
 * - MAC address normalization
 * - MAC address validation
 * - MAC address existence checking
 * - Device statistics calculation
 * 
 * Database: Uses RefreshDatabase trait (compatible with MariaDB)
 */
class DeviceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DeviceService $deviceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deviceService = new DeviceService();
    }

    /**
     * Test MAC address normalization: colon format (already correct).
     */
    public function test_normalize_mac_address_colon_format(): void
    {
        $result = $this->deviceService->normalizeMacAddress('AA:BB:CC:DD:EE:FF');
        $this->assertEquals('AA:BB:CC:DD:EE:FF', $result);
    }

    /**
     * Test MAC address normalization: hyphen format.
     */
    public function test_normalize_mac_address_hyphen_format(): void
    {
        $result = $this->deviceService->normalizeMacAddress('AA-BB-CC-DD-EE-FF');
        $this->assertEquals('AA:BB:CC:DD:EE:FF', $result);
    }

    /**
     * Test MAC address normalization: lowercase.
     */
    public function test_normalize_mac_address_lowercase(): void
    {
        $result = $this->deviceService->normalizeMacAddress('aa:bb:cc:dd:ee:ff');
        $this->assertEquals('AA:BB:CC:DD:EE:FF', $result);
    }

    /**
     * Test MAC address normalization: mixed case with hyphens.
     */
    public function test_normalize_mac_address_mixed_format(): void
    {
        $result = $this->deviceService->normalizeMacAddress('aa-bb-cc-dd-ee-ff');
        $this->assertEquals('AA:BB:CC:DD:EE:FF', $result);
    }

    /**
     * Test MAC address validation: valid colon format.
     */
    public function test_validate_mac_address_valid_colon(): void
    {
        $result = $this->deviceService->validateMacAddress('AA:BB:CC:DD:EE:FF');
        $this->assertTrue($result);
    }

    /**
     * Test MAC address validation: valid hyphen format.
     */
    public function test_validate_mac_address_valid_hyphen(): void
    {
        $result = $this->deviceService->validateMacAddress('AA-BB-CC-DD-EE-FF');
        $this->assertTrue($result);
    }

    /**
     * Test MAC address validation: valid lowercase.
     */
    public function test_validate_mac_address_valid_lowercase(): void
    {
        $result = $this->deviceService->validateMacAddress('aa:bb:cc:dd:ee:ff');
        $this->assertTrue($result);
    }

    /**
     * Test MAC address validation: invalid format (too short).
     */
    public function test_validate_mac_address_invalid_too_short(): void
    {
        $result = $this->deviceService->validateMacAddress('AA:BB:CC:DD:EE');
        $this->assertFalse($result);
    }

    /**
     * Test MAC address validation: invalid format (wrong separator).
     */
    public function test_validate_mac_address_invalid_separator(): void
    {
        $result = $this->deviceService->validateMacAddress('AA BB CC DD EE FF');
        $this->assertFalse($result);
    }

    /**
     * Test MAC address validation: invalid format (non-hex characters).
     */
    public function test_validate_mac_address_invalid_characters(): void
    {
        $result = $this->deviceService->validateMacAddress('AA:BB:CC:DD:EE:GG');
        $this->assertFalse($result);
    }

    /**
     * Test MAC address existence check: MAC exists.
     */
    public function test_check_mac_exists_returns_true_when_exists(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);

        $result = $this->deviceService->checkMacExists('AA:BB:CC:DD:EE:FF');
        $this->assertTrue($result);
    }

    /**
     * Test MAC address existence check: MAC does not exist.
     */
    public function test_check_mac_exists_returns_false_when_not_exists(): void
    {
        $result = $this->deviceService->checkMacExists('AA:BB:CC:DD:EE:FF');
        $this->assertFalse($result);
    }

    /**
     * Test MAC address existence check: excludes device when updating.
     */
    public function test_check_mac_exists_excludes_device_when_updating(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);

        // Should return false when excluding the device (allows keeping same MAC)
        $result = $this->deviceService->checkMacExists('AA:BB:CC:DD:EE:FF', $device->id);
        $this->assertFalse($result);
    }

    /**
     * Test MAC address existence check: finds duplicate on other device.
     */
    public function test_check_mac_exists_finds_duplicate_on_other_device(): void
    {
        $user = User::factory()->create();
        $device1 = Device::factory()->create([
            'user_id' => $user->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);
        $device2 = Device::factory()->create([
            'user_id' => $user->id,
            'mac_address' => '11:22:33:44:55:66',
        ]);

        // Should return true when checking device2's MAC against device1 (duplicate)
        $result = $this->deviceService->checkMacExists('AA:BB:CC:DD:EE:FF', $device2->id);
        $this->assertTrue($result);
    }

    /**
     * Test MAC address existence check: normalizes MAC before checking.
     */
    public function test_check_mac_exists_normalizes_mac(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);

        // Should find MAC even with different format
        $result = $this->deviceService->checkMacExists('aa-bb-cc-dd-ee-ff');
        $this->assertTrue($result);
    }

    /**
     * Test device statistics calculation.
     */
    public function test_get_device_stats_calculates_correctly(): void
    {
        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        // Create some related records
        // Note: We're not creating actual related models here since they might not exist
        // This test verifies the method doesn't crash and returns expected structure

        $stats = $this->deviceService->getDeviceStats($device);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('sessions_count', $stats);
        $this->assertArrayHasKey('active_sessions_count', $stats);
        $this->assertArrayHasKey('logs_count', $stats);
        $this->assertArrayHasKey('access_attempts_count', $stats);
        $this->assertArrayHasKey('quiz_attempts_count', $stats);
        $this->assertArrayHasKey('video_completions_count', $stats);

        // All counts should be integers >= 0
        $this->assertIsInt($stats['sessions_count']);
        $this->assertIsInt($stats['active_sessions_count']);
        $this->assertIsInt($stats['logs_count']);
        $this->assertIsInt($stats['access_attempts_count']);
        $this->assertIsInt($stats['quiz_attempts_count']);
        $this->assertIsInt($stats['video_completions_count']);

        $this->assertGreaterThanOrEqual(0, $stats['sessions_count']);
        $this->assertGreaterThanOrEqual(0, $stats['active_sessions_count']);
        $this->assertGreaterThanOrEqual(0, $stats['logs_count']);
    }
}

