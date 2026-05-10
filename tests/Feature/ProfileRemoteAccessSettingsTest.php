<?php

namespace Tests\Feature;

use App\Models\RemoteAccessSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileRemoteAccessSettingsTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertDatabaseCount('remote_access_settings', 0);

        $this->get(route('login'));

        $expected = rtrim((string) config('app.url'), '/').'/dashboard';
        $this->assertSame($expected, config('reporting.email_dashboard_url'));
    }

    public function test_profile_page_loads_without_remote_access_form(): void
    {
        $user = User::factory()->parentAdmin()->create();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }
}
