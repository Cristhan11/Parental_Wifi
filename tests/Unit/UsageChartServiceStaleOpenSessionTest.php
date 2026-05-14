<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\User;
use App\Services\UsageChartService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageChartServiceStaleOpenSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_chart_counts_stale_open_session_after_time_expired(): void
    {
        $timezone = (string) (config('app.timezone') ?: 'Asia/Manila');
        $now = Carbon::create(2026, 5, 14, 18, 0, 0, $timezone);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'blocked',
            'remaining_time_minutes' => 0,
            'total_time_allocated' => 120,
        ]);

        $sessionStart = $now->copy()->subHours(3);
        $anchor = $now->copy()->subHour();

        DeviceSession::create([
            'device_id' => $device->id,
            'started_at' => $sessionStart,
            'last_incremental_bill_at' => $anchor,
            'ended_at' => null,
            'duration_seconds' => 0,
            'total_bytes_sent' => 0,
            'total_bytes_received' => 0,
        ]);

        $svc = app(UsageChartService::class);
        $payload = $svc->buildChartPayload($user, 'daily', (int) $device->id);

        $this->assertSame('daily', $payload['range']);
        $this->assertSame('minutes', $payload['unit']);
        $this->assertCount(1, $payload['series']);

        $values = $payload['series'][0]['values'];
        $this->assertGreaterThan(0.0, array_sum($values));

        $idxStart = (int) $sessionStart->format('H');
        $this->assertGreaterThan(0.0, (float) $values[$idxStart]);

        Carbon::setTestNow();
    }
}
