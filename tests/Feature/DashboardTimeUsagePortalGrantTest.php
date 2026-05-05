<?php

namespace Tests\Feature;

use App\Events\TimeGranted;
use App\Models\Device;
use App\Models\User;
use App\Services\TimeGrantingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DashboardTimeUsagePortalGrantTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_sync_on_quiz_grant_sets_ip_session_and_time_granted_payload(): void
    {
        Event::fake([TimeGranted::class]);

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
            'remaining_time_minutes' => 10,
            'ip_address' => null,
            'mac_address' => 'AA:BB:CC:DD:EE:01',
        ]);

        $service = app(TimeGrantingService::class);
        $service->grantTime($device, 5, 'quiz', null, ['client_ip' => '192.168.1.50']);

        $device->refresh();
        $this->assertSame('192.168.1.50', $device->ip_address);
        $this->assertNotNull($device->activeSession());

        Event::assertDispatched(TimeGranted::class, function (TimeGranted $e) use ($device) {
            return $e->deviceId === $device->id
                && $e->isConnected === true
                && $e->ipAddress === '192.168.1.50'
                && is_string($e->activeSessionStartedAt)
                && $e->activeSessionStartedAt !== '';
        });
    }

    public function test_quiz_grant_without_portal_sync_does_not_set_ip(): void
    {
        Event::fake([TimeGranted::class]);

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
            'remaining_time_minutes' => 10,
            'ip_address' => null,
            'mac_address' => 'AA:BB:CC:DD:EE:02',
        ]);

        $service = app(TimeGrantingService::class);
        $service->grantTime($device, 5, 'quiz', null, []);

        $device->refresh();
        $this->assertNull($device->ip_address);
    }
}
