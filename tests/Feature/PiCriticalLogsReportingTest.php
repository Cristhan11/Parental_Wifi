<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Minimal smoke tests aimed at Raspberry Pi deployment: proves HTTP routes resolve and reporting Artisan
 * commands are wired. Uses the same in-memory SQLite + sync queue as phpunit.xml (no real SMTP).
 *
 * Run on Pi:
 *   php artisan test tests/Feature/PiCriticalLogsReportingTest.php
 */
class PiCriticalLogsReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_view_unified_logs_index(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $response = $this->actingAs($parent)->get(route('logs.index'));

        $response->assertOk();
    }

    public function test_parent_can_view_reports_configuration_page(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $response = $this->actingAs($parent)->get(route('reports.index'));

        $response->assertOk();
    }

    /**
     * Ensures the console command is registered and runs without throwing (even when no parents are opted in).
     * Critical on Pi: PHP CLI + autoload + Schedule must resolve this command.
     */
    public function test_reporting_send_digest_artisan_command_exits_successfully(): void
    {
        $this->artisan('reporting:send-digest', ['frequency' => 'daily'])
            ->assertExitCode(0);
    }

    /**
     * Same as digest command but for the manual test helper — fails fast if recipients are missing when you pass a real ID on Pi.
     */
    public function test_reporting_send_test_command_fails_gracefully_without_recipients(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->artisan('reporting:send-test', ['user_id' => (string) $parent->id])
            ->assertExitCode(1);
    }
}
