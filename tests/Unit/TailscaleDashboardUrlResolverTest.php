<?php

namespace Tests\Unit;

use App\Services\PiTailscaleAuthLinkService;
use App\Services\TailscaleDashboardUrlResolver;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class TailscaleDashboardUrlResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_resolve_uses_pi_agent_when_it_returns_a_url(): void
    {
        Cache::forget(TailscaleDashboardUrlResolver::CACHE_KEY);
        config(['reporting.tailscale_dashboard_cache_seconds' => 300]);

        $pi = Mockery::mock(PiTailscaleAuthLinkService::class);
        $pi->shouldReceive('fetchTailscaleDashboardUrl')->once()->andReturn('http://100.101.2.3/dashboard');
        $this->app->instance(PiTailscaleAuthLinkService::class, $pi);

        $resolver = app(TailscaleDashboardUrlResolver::class);
        $this->assertSame('http://100.101.2.3/dashboard', $resolver->resolve());
    }
}
