<?php

namespace Tests\Unit;

use App\Services\NetworkService;
use App\Services\ScriptExecutor;
use Mockery;
use Tests\TestCase;

class NetworkServiceTrafficStatsJsonTest extends TestCase
{
    public function test_get_traffic_stats_accepts_mac_key_from_monitor_script_json(): void
    {
        $json = '[{"mac":"AA:BB:CC:DD:EE:01","bytes_sent":100,"bytes_received":200}]';

        $executor = Mockery::mock(ScriptExecutor::class);
        $executor->shouldReceive('execute')
            ->once()
            ->with('monitor_traffic.sh', [])
            ->andReturn([
                'success' => true,
                'output' => $json,
                'error' => '',
                'return_code' => 0,
            ]);

        $service = new NetworkService($executor);
        $stats = $service->getTrafficStats();

        $this->assertCount(1, $stats);
        $this->assertSame('AA:BB:CC:DD:EE:01', $stats[0]['mac_address']);
        $this->assertSame(100, $stats[0]['bytes_sent']);
        $this->assertSame(200, $stats[0]['bytes_received']);
    }

    public function test_get_traffic_stats_still_accepts_mac_address_key(): void
    {
        $json = '[{"mac_address":"AA:BB:CC:DD:EE:02","bytes_sent":1,"bytes_received":2}]';

        $executor = Mockery::mock(ScriptExecutor::class);
        $executor->shouldReceive('execute')
            ->once()
            ->andReturn([
                'success' => true,
                'output' => $json,
                'error' => '',
                'return_code' => 0,
            ]);

        $service = new NetworkService($executor);
        $stats = $service->getTrafficStats();

        $this->assertCount(1, $stats);
        $this->assertSame('AA:BB:CC:DD:EE:02', $stats[0]['mac_address']);
    }
}
