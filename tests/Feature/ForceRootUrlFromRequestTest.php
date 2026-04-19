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
}
