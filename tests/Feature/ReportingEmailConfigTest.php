<?php

namespace Tests\Feature;

use App\Jobs\DispatchDigestReportJob;
use App\Mail\DailyDigestReportMail;
use App\Mail\ImmediateBlockedWebsiteAlertMail;
use App\Models\AccessAttempt;
use App\Models\BrowsingLog;
use App\Models\Device;
use App\Models\ReportingPreference;
use App\Models\ReportingRecipient;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Feature tests for the reporting stack: routes, preferences, recipients, digest job wiring, and mail fakes.
 *
 * Run: `php artisan test --filter=ReportingEmailConfigTest`
 */
class ReportingEmailConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Web PUT/POST tests do not submit a browser _token; disable CSRF for this feature suite only.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_parent_can_view_and_update_reporting_preferences(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parent)
            ->get(route('reports.index'))
            ->assertOk();

        $this->actingAs($parent)
            ->put(route('reports.preferences.update'), [
                'immediate_alerts_enabled' => '1',
                'daily_digest_enabled' => '1',
                'weekly_digest_enabled' => '0',
                'monthly_digest_enabled' => '1',
                'skip_empty_digests' => '1',
                'timezone' => 'UTC',
            ])
            ->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('reporting_preferences', [
            'user_id' => $parent->id,
            'immediate_alerts_enabled' => 1,
            'daily_digest_enabled' => 1,
            'weekly_digest_enabled' => 0,
            'monthly_digest_enabled' => 1,
            'skip_empty_digests' => 1,
            'timezone' => 'UTC',
        ]);
    }

    public function test_blocked_website_attempt_sends_immediate_email_to_enabled_recipient(): void
    {
        Mail::fake();

        $parent = User::factory()->create(['role' => 'parent']);
        $device = Device::create([
            'user_id' => $parent->id,
            'name' => 'Child Phone',
            'mac_address' => 'AA:BB:CC:DD:EE:11',
            'status' => 'active',
            'role' => 'child',
            'remaining_time_minutes' => 60,
            'total_time_allocated' => 60,
        ]);

        ReportingPreference::create([
            'user_id' => $parent->id,
            'immediate_alerts_enabled' => true,
            'daily_digest_enabled' => true,
            'weekly_digest_enabled' => true,
            'monthly_digest_enabled' => true,
            'timezone' => 'UTC',
            'skip_empty_digests' => true,
        ]);

        ReportingRecipient::create([
            'user_id' => $parent->id,
            'label' => 'Primary',
            'email' => 'alerts@example.test',
            'is_enabled' => true,
        ]);

        AccessAttempt::create([
            'device_id' => $device->id,
            'type' => 'blocked_website',
            'url' => 'https://blocked.example',
            'domain' => 'blocked.example',
            'attempted_at' => now(),
        ]);

        Mail::assertSent(ImmediateBlockedWebsiteAlertMail::class, function ($mail): bool {
            return $mail->hasTo('alerts@example.test');
        });

        $this->assertDatabaseHas('report_dispatch_logs', [
            'user_id' => $parent->id,
            'report_type' => 'immediate_blocked_website',
            'recipient_email' => 'alerts@example.test',
            'status' => 'sent',
        ]);
    }

    public function test_daily_digest_skips_when_empty_and_skip_empty_enabled(): void
    {
        Mail::fake();

        $parent = User::factory()->create(['role' => 'parent']);
        ReportingPreference::create([
            'user_id' => $parent->id,
            'immediate_alerts_enabled' => true,
            'daily_digest_enabled' => true,
            'weekly_digest_enabled' => true,
            'monthly_digest_enabled' => true,
            'timezone' => 'UTC',
            'skip_empty_digests' => true,
        ]);
        ReportingRecipient::create([
            'user_id' => $parent->id,
            'email' => 'digest@example.test',
            'is_enabled' => true,
        ]);

        DispatchDigestReportJob::dispatchSync($parent->id, 'daily');

        Mail::assertNothingSent();
        $this->assertDatabaseHas('report_dispatch_logs', [
            'user_id' => $parent->id,
            'report_type' => 'digest',
            'frequency' => 'daily',
            'status' => 'skipped',
        ]);
    }

    public function test_daily_digest_sends_when_period_has_activity(): void
    {
        Mail::fake();

        $parent = User::factory()->create(['role' => 'parent']);
        $device = Device::create([
            'user_id' => $parent->id,
            'name' => 'Tablet',
            'mac_address' => 'AA:BB:CC:DD:EE:22',
            'status' => 'active',
            'role' => 'child',
            'remaining_time_minutes' => 60,
            'total_time_allocated' => 60,
        ]);

        ReportingPreference::create([
            'user_id' => $parent->id,
            'immediate_alerts_enabled' => true,
            'daily_digest_enabled' => true,
            'weekly_digest_enabled' => true,
            'monthly_digest_enabled' => true,
            'timezone' => 'UTC',
            'skip_empty_digests' => true,
        ]);
        ReportingRecipient::create([
            'user_id' => $parent->id,
            'email' => 'digest@example.test',
            'is_enabled' => true,
        ]);

        BrowsingLog::create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'visited_at' => now()->subDay()->setTime(10, 0),
        ]);

        DispatchDigestReportJob::dispatchSync($parent->id, 'daily');

        Mail::assertSent(DailyDigestReportMail::class, function ($mail): bool {
            return $mail->hasTo('digest@example.test');
        });

        $this->assertDatabaseHas('report_dispatch_logs', [
            'user_id' => $parent->id,
            'report_type' => 'digest',
            'frequency' => 'daily',
            'recipient_email' => 'digest@example.test',
            'status' => 'sent',
        ]);
    }
}

