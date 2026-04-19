<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\User;
use App\Services\DomainBlockingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceDnsBypassObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_update_triggers_dhcp_dns_bypass_sync_once(): void
    {
        $this->partialMock(DomainBlockingService::class, function ($mock) {
            $mock->shouldReceive('syncDnsmasqDhcpDnsBypassForUser')->once()->andReturn(true);
        });

        $user = User::factory()->create();
        $device = Device::withoutEvents(fn () => Device::factory()
            ->for($user)
            ->role('child')
            ->active()
            ->create());

        $device->update(['role' => 'parent']);
    }

    public function test_device_user_change_triggers_sync_for_old_and_new_account(): void
    {
        $this->partialMock(DomainBlockingService::class, function ($mock) {
            $mock->shouldReceive('syncDnsmasqDhcpDnsBypassForUser')->twice()->andReturn(true);
        });

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $device = Device::withoutEvents(fn () => Device::factory()
            ->for($userA)
            ->role('guest')
            ->active()
            ->create());

        $device->update(['user_id' => $userB->id]);
    }
}
