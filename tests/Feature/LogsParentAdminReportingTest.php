<?php

namespace Tests\Feature;

use App\Models\ReportingPreference;
use App\Models\ReportingRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ensures reporting email setup (recipients, preferences) appears in the Parent/Admin Changes log stream.
 *
 * @see \App\Http\Controllers\LogsController::buildParentAdminEntries
 */
class LogsParentAdminReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_admin_stream_lists_reporting_recipient_add(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        ReportingRecipient::create([
            'user_id' => $parent->id,
            'label' => 'Mom',
            'email' => 'mom@example.test',
            'is_enabled' => true,
        ]);

        $from = now()->subDay()->format('Y-m-d\TH:i');
        $to = now()->addDay()->format('Y-m-d\TH:i');

        $response = $this->actingAs($parent)->get(route('logs.index', [
            'stream' => 'parent_admin_changes',
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        $response->assertSee('Reporting recipient added', false);
        $response->assertSee('mom@example.test', false);
    }

    public function test_parent_admin_stream_lists_reporting_preferences_update(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $pref = ReportingPreference::create([
            'user_id' => $parent->id,
            'immediate_alerts_enabled' => true,
            'daily_digest_enabled' => true,
            'weekly_digest_enabled' => true,
            'monthly_digest_enabled' => true,
            'timezone' => 'UTC',
            'skip_empty_digests' => true,
        ]);

        // Must be a different second than created_at so LogsController emits an "updated" row.
        $this->travel(2)->seconds();
        $pref->update(['daily_digest_enabled' => false]);

        $from = now()->subDay()->format('Y-m-d\TH:i');
        $to = now()->addDay()->format('Y-m-d\TH:i');

        $response = $this->actingAs($parent)->get(route('logs.index', [
            'stream' => 'parent_admin_changes',
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        $response->assertSee('Reporting preferences updated', false);
    }

    public function test_parent_admin_stream_lists_recipient_email_change_and_disable(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $recipient = ReportingRecipient::create([
            'user_id' => $parent->id,
            'label' => 'Test',
            'email' => 'old@example.test',
            'is_enabled' => true,
        ]);

        $this->travel(2)->seconds();
        $recipient->update(['email' => 'new@example.test']);

        $this->travel(2)->seconds();
        $recipient->update(['is_enabled' => false]);

        $from = now()->subDay()->format('Y-m-d\TH:i');
        $to = now()->addDay()->format('Y-m-d\TH:i');

        $response = $this->actingAs($parent)->get(route('logs.index', [
            'stream' => 'parent_admin_changes',
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        $response->assertSee('email changed from old@example.test to new@example.test', false);
        $response->assertSee('recipient disabled for notifications', false);
    }

    public function test_parent_admin_stream_lists_recipient_removal(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $recipient = ReportingRecipient::create([
            'user_id' => $parent->id,
            'label' => 'Gone',
            'email' => 'gone@example.test',
            'is_enabled' => true,
        ]);

        $this->travel(2)->seconds();
        $recipient->delete();

        $from = now()->subDay()->format('Y-m-d\TH:i');
        $to = now()->addDay()->format('Y-m-d\TH:i');

        $response = $this->actingAs($parent)->get(route('logs.index', [
            'stream' => 'parent_admin_changes',
            'from' => $from,
            'to' => $to,
        ]));

        $response->assertOk();
        $response->assertSee('Reporting recipient removed', false);
        $response->assertSee('gone@example.test', false);
    }
}
