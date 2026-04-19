<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForceRootUrlFromRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirect_uses_request_host_not_only_app_url(): void
    {
        $response = $this->get('http://tailscale-test-host.example/');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringStartsWith('http://tailscale-test-host.example/', $location);
    }

    public function test_root_redirect_uses_tailscale_server_addr_when_fastcgi_host_is_lan_gateway(): void
    {
        $response = $this->withServerVariables([
            'SERVER_ADDR' => '100.102.52.117',
        ])->get('http://192.168.4.1/');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringStartsWith('http://100.102.52.117/', $location);
    }
}
