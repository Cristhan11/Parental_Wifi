<?php

namespace Tests\Feature;

use App\Jobs\ProcessDebouncedPolicyApplyJob;
use App\Models\Device;
use App\Models\User;
use App\Services\PolicyApplyDebouncer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeviceDnsBypassObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_update_triggers_dhcp_dns_bypass_sync_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $device = Device::withoutEvents(fn () => Device::factory()
            ->for($user)
            ->role('child')
            ->active()
            ->create());

        $device->update(['role' => 'parent']);

        Queue::assertPushed(ProcessDebouncedPolicyApplyJob::class, 1);
    }

    public function test_device_user_change_triggers_sync_for_old_and_new_account(): void
    {
        Queue::fake();

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $device = Device::withoutEvents(fn () => Device::factory()
            ->for($userA)
            ->role('guest')
            ->active()
            ->create());

        $device->update(['user_id' => $userB->id]);

        Queue::assertPushed(ProcessDebouncedPolicyApplyJob::class, 2);
    }

    public function test_incrementing_remaining_time_does_not_queue_policy_apply(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $device = Device::withoutEvents(fn () => Device::factory()
            ->for($user)
            ->role('child')
            ->active()
            ->create());

        $this->partialMock(PolicyApplyDebouncer::class, function ($mock) {
            $mock->shouldNotReceive('requestApply');
        });

        $device->increment('remaining_time_minutes', 10);
    }
}
