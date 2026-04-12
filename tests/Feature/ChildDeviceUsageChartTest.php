<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildDeviceUsageChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_receives_single_series_for_selected_device(): void
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

        $other = Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
            'remaining_time_minutes' => 120,
            'total_time_allocated' => 120,
        ]);

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

        DeviceSession::create([
            'device_id' => $other->id,
            'started_at' => $sessionStart->copy()->addDay(),
            'ended_at' => $sessionEnd->copy()->addDay(),
            'duration_seconds' => $sessionStart->diffInSeconds($sessionEnd),
            'total_bytes_sent' => 0,
            'total_bytes_received' => 0,
        ]);

        $response = $this->actingAs($user)->getJson(route('child_devices.usage-chart', [
            'device' => $device,
            'range' => 'yearly',
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertSame('yearly', $data['range']);
        $this->assertCount(1, $data['series']);
        $this->assertSame((int) $device->id, (int) $data['series'][0]['device_id']);

        $labels = $data['labels'];
        $expectedJuneLabel = $sessionStart->copy()->startOfMonth()->format('M');
        $idxJune = array_search($expectedJuneLabel, $labels, true);
        $this->assertIsInt($idxJune);
        $this->assertSame(0.5, (float) $data['series'][0]['values'][$idxJune]);
    }

    public function test_other_user_cannot_view_usage_chart(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($intruder)->getJson(route('child_devices.usage-chart', [
            'device' => $device,
        ]));

        $response->assertForbidden();
    }
}
