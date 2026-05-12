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

    public function test_status_only_path_uses_short_timeout_and_returns_signed_in_email(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.timeout_seconds', 240);
        Config::set('pi_agent.quick_timeout_seconds', 90);
        Config::set('pi_agent.status_timeout_seconds', 5);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/auth-link' => Http::response([
                'status' => 'already_authenticated',
                'auth_url' => null,
                'expires_at' => null,
                'message' => 'Pi is signed in to Tailscale as cristhangray@gmail.com.',
                'signed_in_as' => 'cristhangray@gmail.com',
                'matches_dashboard' => true,
                'dashboard_url' => 'http://100.88.1.1/dashboard',
            ], 200),
        ]);

        $service = new PiTailscaleAuthLinkService;
        $result = $service->fetchAuthLink(false, 'cristhangray@gmail.com', true);

        $this->assertTrue($result['ok']);
        $this->assertSame('already_authenticated', $result['status']);
        $this->assertSame('cristhangray@gmail.com', $result['signed_in_as']);
        $this->assertTrue($result['matches_dashboard']);
        $this->assertSame('http://100.88.1.1/dashboard', $result['dashboard_url']);

        Http::assertSent(function (Request $request): bool {
            $body = json_decode($request->body(), true);

            return $request->url() === 'http://127.0.0.1:9098/v1/tailscale/auth-link'
                && is_array($body)
                && ($body['status_only'] ?? null) === true
                && ($body['dashboard_email'] ?? null) === 'cristhangray@gmail.com';
        });
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

    public function test_fetch_tailscale_dashboard_url_returns_sanitized_url(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.status_timeout_seconds', 5);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/dashboard-url' => Http::response([
                'ok' => true,
                'dashboard_url' => 'http://100.99.1.7/dashboard',
            ], 200),
        ]);

        $service = new PiTailscaleAuthLinkService;
        $this->assertSame('http://100.99.1.7/dashboard', $service->fetchTailscaleDashboardUrl());

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'http://127.0.0.1:9098/v1/tailscale/dashboard-url';
        });
    }

    public function test_fetch_tailscale_dashboard_url_returns_null_when_agent_not_configured(): void
    {
        Config::set('pi_agent.base_url', '');
        Config::set('pi_agent.token', '');

        Http::fake();

        $service = new PiTailscaleAuthLinkService;
        $this->assertNull($service->fetchTailscaleDashboardUrl());

        Http::assertNothingSent();
    }

    public function test_fetch_tailscale_dashboard_url_rejects_non_tailnet_ip(): void
    {
        Config::set('pi_agent.base_url', 'http://127.0.0.1:9098');
        Config::set('pi_agent.token', 'secret');
        Config::set('pi_agent.status_timeout_seconds', 5);

        Http::fake([
            'http://127.0.0.1:9098/v1/tailscale/dashboard-url' => Http::response([
                'ok' => true,
                'dashboard_url' => 'http://192.168.4.1/dashboard',
            ], 200),
        ]);

        $service = new PiTailscaleAuthLinkService;
        $this->assertNull($service->fetchTailscaleDashboardUrl());
    }
}
