<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\User;
use App\Services\BandwidthUsageService;
use App\Services\NetworkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class BandwidthUsageServiceLiveDailyPeakTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_daily_chart_retains_live_bandwidth_peak_after_clock_advances(): void
    {
        $timezone = (string) (config('app.timezone') ?: 'Asia/Manila');
        Carbon::setTestNow(Carbon::create(2026, 5, 14, 17, 30, 0, $timezone));
        Cache::flush();

        $user = User::factory()->create();
        Device::factory()->create([
            'user_id' => $user->id,
            'role' => 'child',
            'status' => 'active',
            'name' => 'Alpha Child',
            'mac_address' => 'E6:6A:8F:19:BE:B1',
        ]);

        $mock = Mockery::mock(NetworkService::class);
        $mock->shouldReceive('getTrafficStats')
            ->twice()
            ->andReturn(
                [[
                    'mac_address' => 'E6:6A:8F:19:BE:B1',
                    'bytes_sent' => 1000,
                    'bytes_received' => 5_086_070 - 1000,
                ]],
                [[
                    'mac_address' => 'E6:6A:8F:19:BE:B1',
                    'bytes_sent' => 0,
                    'bytes_received' => 0,
                ]],
            );
        $this->app->instance(NetworkService::class, $mock);

        /** @var BandwidthUsageService $svc */
        $svc = $this->app->make(BandwidthUsageService::class);

        $payload1 = $svc->buildChartPayload($user, 'daily', null, 'mb');
        $idx17 = array_search('17', $payload1['labels'], true);
        $this->assertIsInt($idx17);
        $this->assertGreaterThan(0.0, (float) $payload1['series'][0]['values'][$idx17]);

        Carbon::setTestNow(Carbon::create(2026, 5, 14, 18, 30, 0, $timezone));

        $payload2 = $svc->buildChartPayload($user, 'daily', null, 'mb');
        $this->assertGreaterThan(0.0, (float) $payload2['series'][0]['values'][$idx17]);

        Carbon::setTestNow();
    }
}
