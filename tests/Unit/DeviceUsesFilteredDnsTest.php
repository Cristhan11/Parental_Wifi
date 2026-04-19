<?php

namespace Tests\Unit;

use App\Models\Device;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeviceUsesFilteredDnsTest extends TestCase
{
    public static function roleStatusExpectations(): array
    {
        return [
            'child active uses Pi DNS' => ['child', 'active', true],
            'child blocked still uses Pi DNS' => ['child', 'blocked', true],
            'child whitelisted bypasses Pi DNS' => ['child', 'whitelisted', false],
            'parent active bypasses' => ['parent', 'active', false],
            'guest active bypasses' => ['guest', 'active', false],
            'parent whitelisted bypasses' => ['parent', 'whitelisted', false],
        ];
    }

    #[DataProvider('roleStatusExpectations')]
    public function test_uses_filtered_dns(string $role, string $status, bool $expectedUsesFiltered): void
    {
        $device = new Device(['role' => $role, 'status' => $status]);

        $this->assertSame($expectedUsesFiltered, $device->usesFilteredDns());
    }

    public function test_null_role_treated_as_child_for_dns_policy(): void
    {
        $device = new Device(['role' => null, 'status' => 'active']);

        $this->assertTrue($device->usesFilteredDns());
    }
}
