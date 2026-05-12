<?php

namespace Tests\Feature;

use App\Models\RemoteAccessSetting;
use App\Models\User;
use App\Services\TailscaleDashboardUrlResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProfileRemoteAccessSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Default: assume Tailscale detection finds nothing so legacy fallback assertions stay
        // stable in CI where `tailscale ip -4` is not available. Individual tests override.
        $this->app->instance(TailscaleDashboardUrlResolver::class, $this->stubResolver(null));
    }

    public function test_legacy_database_reporting_dashboard_url_overrides_config_when_present(): void
    {
        RemoteAccessSetting::query()->create([
            'reporting_dashboard_url' => 'http://from-database.example/dashboard',
        ]);

        RemoteAccessSetting::applyReportingDashboardUrlToConfig();

        $this->assertSame('http://from-database.example/dashboard', config('reporting.email_dashboard_url'));
    }

    public function test_without_database_or_env_override_uses_app_url_dashboard(): void
    {
        config(['reporting.tailscale_auto_detect' => false]);
        $this->assertDatabaseCount('remote_access_settings', 0);

        $this->get(route('login'));

        $expected = rtrim((string) config('app.url'), '/').'/dashboard';
        $this->assertSame($expected, config('reporting.email_dashboard_url'));
    }

    public function test_tailscale_auto_detect_preferred_over_app_url_for_email_dashboard(): void
    {
        config(['reporting.tailscale_auto_detect' => true]);
        config(['reporting.env_reporting_dashboard_url' => null]);
        $this->app->instance(
            TailscaleDashboardUrlResolver::class,
            $this->stubResolver('http://100.113.109.90/dashboard'),
        );
        $this->assertDatabaseCount('remote_access_settings', 0);

        RemoteAccessSetting::applyReportingDashboardUrlToConfig();

        $this->assertSame('http://100.113.109.90/dashboard', config('reporting.email_dashboard_url'));
    }

    public function test_env_override_wins_over_tailscale_auto_detect(): void
    {
        config(['reporting.tailscale_auto_detect' => true]);
        config(['reporting.env_reporting_dashboard_url' => 'http://parentalpi/dashboard']);
        $this->app->instance(
            TailscaleDashboardUrlResolver::class,
            $this->stubResolver('http://100.113.109.90/dashboard'),
        );

        RemoteAccessSetting::applyReportingDashboardUrlToConfig();

        $this->assertSame('http://parentalpi/dashboard', config('reporting.email_dashboard_url'));
    }

    public function test_profile_page_loads_without_remote_access_form(): void
    {
        $user = User::factory()->parentAdmin()->create();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }

    private function stubResolver(?string $value): TailscaleDashboardUrlResolver
    {
        $mock = Mockery::mock(TailscaleDashboardUrlResolver::class);
        $mock->shouldReceive('resolve')->andReturn($value);
        $mock->shouldReceive('forget')->andReturnNull();

        return $mock;
    }
}
