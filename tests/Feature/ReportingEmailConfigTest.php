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
use App\Services\NetworkService;
use App\Services\ReportingDigestService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
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

    public function test_parent_can_bulk_save_recipients_replace_list(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $a = ReportingRecipient::create([
            'user_id' => $parent->id,
            'label' => 'A',
            'email' => 'a@example.test',
            'is_enabled' => true,
        ]);
        $b = ReportingRecipient::create([
            'user_id' => $parent->id,
            'label' => 'B',
            'email' => 'b@example.test',
            'is_enabled' => true,
        ]);

        $this->actingAs($parent)->post(route('reports.recipients.bulk-save'), [
            '_form' => 'recipients_bulk',
            'recipients' => [
                ['id' => $a->id, 'label' => 'A2', 'email' => 'a@example.test', 'is_enabled' => '1'],
                ['id' => '', 'label' => '', 'email' => 'c@example.test', 'is_enabled' => '0'],
            ],
        ])->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('reporting_recipients', [
            'user_id' => $parent->id,
            'email' => 'a@example.test',
            'label' => 'A2',
            'is_enabled' => 1,
        ]);
        $this->assertDatabaseHas('reporting_recipients', [
            'user_id' => $parent->id,
            'email' => 'c@example.test',
            'is_enabled' => 0,
        ]);
        $this->assertDatabaseMissing('reporting_recipients', ['id' => $b->id]);
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

        $digestDay = CarbonImmutable::now('UTC')->subDay()->startOfDay()->addHours(12);
        BrowsingLog::create([
            'device_id' => $device->id,
            'url' => 'https://example.com',
            'domain' => 'example.com',
            'visited_at' => $digestDay,
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

    public function test_manual_test_daily_digest_includes_unique_test_suffix_in_subject(): void
    {
        Mail::fake();

        $parent = User::factory()->create(['role' => 'parent']);
        $device = Device::create([
            'user_id' => $parent->id,
            'name' => 'Tablet',
            'mac_address' => 'AA:BB:CC:DD:EE:33',
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

        $digestDay = CarbonImmutable::now('UTC')->startOfDay()->addHours(12);
        BrowsingLog::create([
            'device_id' => $device->id,
            'url' => 'https://example.org',
            'domain' => 'example.org',
            'visited_at' => $digestDay,
        ]);

        DispatchDigestReportJob::dispatchSync($parent->id, 'daily', isManualTest: true);

        Mail::assertSent(DailyDigestReportMail::class, function (DailyDigestReportMail $mail): bool {
            return $mail->hasTo('digest@example.test')
                && str_contains($mail->subjectLine, '[Test ')
                && preg_match('/\[Test \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}\]$/', $mail->subjectLine) === 1;
        });
    }

    public function test_digest_payload_includes_bandwidth_totals_from_browsing_logs(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $device = Device::create([
            'user_id' => $parent->id,
            'name' => 'Child Laptop',
            'mac_address' => 'AA:BB:CC:DD:EE:44',
            'status' => 'active',
            'role' => 'child',
            'remaining_time_minutes' => 60,
            'total_time_allocated' => 60,
        ]);

        $start = CarbonImmutable::now('UTC')->subDay()->startOfDay();
        $end = $start->addDay();

        BrowsingLog::create([
            'device_id' => $device->id,
            'url' => 'https://bandwidth.example',
            'domain' => 'bandwidth.example',
            'bytes_sent' => 1048576,
            'bytes_received' => 2097152,
            'visited_at' => $start->addHours(8),
        ]);

        $payload = app(ReportingDigestService::class)->buildDigestPayload(
            $parent,
            $start,
            $end,
            'UTC'
        );

        $this->assertSame('browsing_logs', $payload['bandwidth']['source']);
        $this->assertSame(3145728, $payload['bandwidth']['family_total_bytes']);
        $this->assertSame('0.025 Gb', $payload['bandwidth']['family_total_formatted']);
        $this->assertSame('0.025 Gb', $payload['devices'][0]['bandwidth']['bytes_total_formatted']);
    }

    public function test_digest_payload_uses_live_bandwidth_fallback_when_logs_are_empty(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        Device::create([
            'user_id' => $parent->id,
            'name' => 'Child Laptop',
            'mac_address' => 'AA:BB:CC:DD:EE:55',
            'status' => 'active',
            'role' => 'child',
            'remaining_time_minutes' => 60,
            'total_time_allocated' => 60,
        ]);

        $mock = Mockery::mock(NetworkService::class);
        $mock->shouldReceive('getTrafficStats')
            ->once()
            ->andReturn([[
                'mac_address' => 'AA:BB:CC:DD:EE:55',
                'bytes_sent' => 1048576,
                'bytes_received' => 1048576,
            ]]);
        $this->app->instance(NetworkService::class, $mock);

        $start = CarbonImmutable::now('UTC')->subDay()->startOfDay();
        $end = $start->addDay();

        $payload = app(ReportingDigestService::class)->buildDigestPayload(
            $parent,
            $start,
            $end,
            'UTC'
        );

        $this->assertSame('live_traffic_fallback', $payload['bandwidth']['source']);
        $this->assertSame(2097152, $payload['bandwidth']['family_total_bytes']);
        $this->assertSame('0.017 Gb', $payload['bandwidth']['family_total_formatted']);
    }
}
