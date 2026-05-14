<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\User;
use App\Services\NetworkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardUsageChartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Daily chart should bucket session overlap into the correct hours.
     */
    public function test_daily_usage_chart_counts_hour_overlap(): void
    {
        $timezone = (string) (config('app.timezone') ?: 'Asia/Manila');
        $now = Carbon::create(2026, 3, 25, 10, 30, 0, $timezone);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
            'remaining_time_minutes' => 120,
            'total_time_allocated' => 120,
        ]);

        // Session spans 01:15 -> 03:45, so it should contribute:
        // - 01:00-02:00 => 45 minutes
        // - 02:00-03:00 => 60 minutes
        // - 03:00-04:00 => 45 minutes
        $sessionStart = $now->copy()->setTime(1, 15, 0);
        $sessionEnd = $now->copy()->setTime(3, 45, 0);

        DeviceSession::create([
            'device_id' => $device->id,
            'started_at' => $sessionStart,
            'ended_at' => $sessionEnd,
            'duration_seconds' => $sessionStart->diffInSeconds($sessionEnd),
            'total_bytes_sent' => 0,
            'total_bytes_received' => 0,
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.usage-chart', [
            'range' => 'daily',
        ]));

        $response->assertOk();

        $data = $response->json();
        $this->assertSame('daily', $data['range']);
        $this->assertSame('minutes', $data['unit']);
        $this->assertCount(24, $data['labels']);
        $this->assertCount(1, $data['series']);
        $this->assertCount(24, $data['series'][0]['values']);

        foreach ($data['series'][0]['values'] as $value) {
            $this->assertGreaterThanOrEqual(0, (float) $value);
        }

        $labels = $data['labels'];
        $idx01 = array_search('01', $labels, true);
        $idx02 = array_search('02', $labels, true);
        $idx03 = array_search('03', $labels, true);

        $this->assertIsInt($idx01);
        $this->assertIsInt($idx02);
        $this->assertIsInt($idx03);

        $this->assertSame(45.0, (float) $data['series'][0]['values'][$idx01]);
        $this->assertSame(60.0, (float) $data['series'][0]['values'][$idx02]);
        $this->assertSame(45.0, (float) $data['series'][0]['values'][$idx03]);
    }

    /**
     * Yearly chart should return 12 month buckets with a series aligned to labels.
     */
    public function test_yearly_usage_chart_returns_12_month_points(): void
    {
        $timezone = (string) (config('app.timezone') ?: 'Asia/Manila');
        $now = Carbon::create(2026, 3, 25, 10, 30, 0, $timezone);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
            'remaining_time_minutes' => 120,
            'total_time_allocated' => 120,
        ]);

        // Session fully inside June 2026 (current year) => should count fully in the June bucket.
        $sessionStart = Carbon::create(2026, 6, 10, 12, 0, 0, $timezone);
        $sessionEnd = Carbon::create(2026, 6, 10, 12, 30, 0, $timezone);

        DeviceSession::create([
            'device_id' => $device->id,
            'started_at' => $sessionStart,
            'ended_at' => $sessionEnd,
            'duration_seconds' => $sessionStart->diffInSeconds($sessionEnd),
            'total_bytes_sent' => 0,
            'total_bytes_received' => 0,
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.usage-chart', [
            'range' => 'yearly',
        ]));

        $response->assertOk();

        $data = $response->json();
        $this->assertSame('yearly', $data['range']);
        $this->assertSame('hours', $data['unit']);
        $this->assertCount(12, $data['labels']);
        $this->assertCount(1, $data['series']);
        $this->assertCount(12, $data['series'][0]['values']);

        $labels = $data['labels'];
        $expectedJuneLabel = $sessionStart->copy()->startOfMonth()->format('M');
        $idxJune = array_search($expectedJuneLabel, $labels, true);
        $this->assertIsInt($idxJune);

        // 30 minutes session => 0.5 hours in that month bucket.
        $this->assertSame(0.5, (float) $data['series'][0]['values'][$idxJune]);
    }

    public function test_dashboard_bandwidth_chart_reads_browsing_log_bytes(): void
    {
        $timezone = (string) (config('app.timezone') ?: 'Asia/Manila');
        $now = Carbon::create(2026, 3, 25, 10, 30, 0, $timezone);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
        ]);

        \App\Models\BrowsingLog::create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'bytes_sent' => 1048576,
            'bytes_received' => 2097152,
            'visited_at' => $now->copy()->setTime(10, 15, 0),
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.bandwidth-chart', [
            'range' => 'daily',
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertSame('daily', $data['range']);
        $this->assertSame('gb', $data['unit']);
        $this->assertCount(1, $data['series']);

        $idx10 = array_search('10', $data['labels'], true);
        $this->assertIsInt($idx10);
        $this->assertSame(0.003146, (float) $data['series'][0]['values'][$idx10]);
    }

    public function test_dashboard_bandwidth_chart_accepts_display_unit_mb(): void
    {
        $timezone = (string) (config('app.timezone') ?: 'Asia/Manila');
        $now = Carbon::create(2026, 3, 25, 10, 30, 0, $timezone);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
        ]);

        \App\Models\BrowsingLog::create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'bytes_sent' => 1048576,
            'bytes_received' => 2097152,
            'visited_at' => $now->copy()->setTime(10, 15, 0),
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.bandwidth-chart', [
            'range' => 'daily',
            'display_unit' => 'mb',
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertSame('mb', $data['unit']);
        $idx10 = array_search('10', $data['labels'], true);
        $this->assertIsInt($idx10);
        $this->assertSame(3.146, (float) $data['series'][0]['values'][$idx10]);
    }

    public function test_dashboard_bandwidth_chart_uses_live_fallback_when_logs_empty(): void
    {
        $timezone = (string) (config('app.timezone') ?: 'Asia/Manila');
        $now = Carbon::create(2026, 3, 25, 10, 30, 0, $timezone);
        Carbon::setTestNow($now);

        $user = User::factory()->create();
        $device = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);

        $mock = Mockery::mock(NetworkService::class);
        $mock->shouldReceive('getTrafficStats')
            ->once()
            ->andReturn([[
                'mac_address' => 'AA:BB:CC:DD:EE:FF',
                'bytes_sent' => 1048576,
                'bytes_received' => 2097152,
            ]]);
        $this->app->instance(NetworkService::class, $mock);

        $response = $this->actingAs($user)->getJson(route('dashboard.bandwidth-chart', [
            'range' => 'daily',
        ]));

        $response->assertOk();
        $data = $response->json();
        $idx10 = array_search('10', $data['labels'], true);
        $this->assertIsInt($idx10);
        $this->assertSame(0.003146, (float) $data['series'][0]['values'][$idx10]);
        $this->assertSame((int) $device->id, (int) $data['series'][0]['device_id']);
    }
}
