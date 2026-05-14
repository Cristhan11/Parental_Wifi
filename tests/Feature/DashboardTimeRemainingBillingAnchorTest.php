<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTimeRemainingBillingAnchorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Active sessions must load last_incremental_bill_at so the dashboard uses the real billing
     * anchor. Otherwise billingAnchor() falls back to started_at and massively understates time left.
     */
    public function test_dashboard_time_left_uses_last_incremental_bill_at_not_session_start(): void
    {
        $timezone = (string) (config('app.timezone') ?: 'UTC');
        Carbon::setTestNow(Carbon::create(2026, 5, 15, 12, 0, 0, $timezone));

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'name' => 'Child Basilio',
            'role' => 'child',
            'status' => 'active',
            'remaining_time_minutes' => 60,
            'total_time_allocated' => 120,
        ]);

        DeviceSession::create([
            'device_id' => $device->id,
            'started_at' => now()->subMinutes(30),
            'last_incremental_bill_at' => now()->subMinutes(1),
            'ended_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('59 min remaining');

        Carbon::setTestNow();
    }
}
