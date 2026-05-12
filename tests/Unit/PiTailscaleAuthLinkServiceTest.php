<?php

namespace Tests\Unit;

use App\Services\PiTailscaleAuthLinkService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PiTailscaleAuthLinkServiceTest extends TestCase
{
    public function test_fetch_auth_link_maps_successful_action_required_response(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'action_required',
                'auth_url' => 'https://login.tailscale.com/a/example-token',
                'expires_at' => '2026-05-08T11:15:00Z',
                'message' => 'Open this link.',
            ], 200),
        ]);

        $service = new PiTailscaleAuthLinkService;
        $result = $service->fetchAuthLink();

        $this->assertTrue($result['ok']);
        $this->assertSame('action_required', $result['status']);
        $this->assertSame('https://login.tailscale.com/a/example-token', $result['auth_url']);
        $this->assertSame('2026-05-08T11:15:00Z', $result['expires_at']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://127.0.0.1:9098/v1/tailscale/auth-link'
                && $request->hasHeader('X-Pi-Agent-Token', 'secret');
        });
    }

    public function test_fetch_auth_link_rewrites_localhost_to_ipv4_loopback(): void
    {
        Config::set('pi_agent.base_url', 'http://localhost:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'already_authenticated',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'OK',
            ], 200),
        ]);

        $service = new PiTailscaleAuthLinkService;
        $result = $service->fetchAuthLink();

        $this->assertTrue($result['ok']);
        $this->assertSame('already_authenticated', $result['status']);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'http://127.0.0.1:9098/v1/tailscale/auth-link';
        });
    }

    public function test_fetch_auth_link_handles_connection_failure(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        Http::fake(function () {
            throw new ConnectionException('boom');
        });

        $service = new PiTailscaleAuthLinkService;
        $result = $service->fetchAuthLink();

        $this->assertFalse($result['ok']);
        $this->assertSame('unavailable', $result['status']);
        $this->assertNull($result['auth_url']);
    }

    public function test_fetch_auth_link_accepts_regional_tailscale_login_host(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        $regional = 'https://login.us.tailscale.com/a/example-token';

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'action_required',
                'auth_url' => $regional,
                'expires_at' => null,
                'message' => 'Open this link.',
            ], 200),
        ]);

        $service = new PiTailscaleAuthLinkService;
        $result = $service->fetchAuthLink();

        $this->assertTrue($result['ok']);
        $this->assertSame('action_required', $result['status']);
        $this->assertSame($regional, $result['auth_url']);
    }

    public function test_invalid_auth_url_is_sanitized_out(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 8);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'action_required',
                'auth_url' => 'https://evil.example.com/not-allowed',
                'expires_at' => null,
                'message' => 'Open this link.',
            ], 200),
        ]);

        $service = new PiTailscaleAuthLinkService;
        $result = $service->fetchAuthLink();

        $this->assertTrue($result['ok']);
        $this->assertSame('action_required', $result['status']);
        $this->assertNull($result['auth_url']);
    }
}
