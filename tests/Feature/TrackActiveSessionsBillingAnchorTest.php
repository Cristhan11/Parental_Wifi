<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\User;
use App\Services\TimeTrackingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackActiveSessionsBillingAnchorTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_active_sessions_keeps_started_at_and_end_session_records_full_duration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-06 10:00:00'));

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
            'remaining_time_minutes' => 60,
        ]);

        $session = DeviceSession::create([
            'device_id' => $device->id,
            'started_at' => now(),
            'ended_at' => null,
        ]);

        $originalStart = $session->started_at->copy();

        Carbon::setTestNow(Carbon::parse('2026-05-06 10:06:00'));

        app(TimeTrackingService::class)->trackActiveSessions();

        $session->refresh();
        $device->refresh();

        $this->assertTrue($session->started_at->equalTo($originalStart));
        $this->assertNotNull($session->last_incremental_bill_at);
        $this->assertSame(54, (int) $device->remaining_time_minutes);

        Carbon::setTestNow(Carbon::parse('2026-05-06 10:08:00'));

        app(TimeTrackingService::class)->endSession($session->fresh());

        $device->refresh();
        $session->refresh();

        $this->assertNotNull($session->ended_at);
        $this->assertSame(480, (int) $session->duration_seconds);
        $this->assertSame(52, (int) $device->remaining_time_minutes);

        Carbon::setTestNow();
    }
}
