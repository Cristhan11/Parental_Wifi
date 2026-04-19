<?php

namespace Tests\Unit;

use App\Models\AccessAttempt;
use App\Models\BlockedWebsite;
use App\Models\Device;
use App\Models\User;
use App\Services\BlockedAccessAttemptRecorder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedAccessAttemptRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_access_attempt_when_domain_matches_app_block_with_subdomains(): void
    {
        config(['reporting.blocked_access_alert_throttle_minutes' => 0]);

        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        BlockedWebsite::create([
            'user_id' => $user->id,
            'url' => null,
            'domain' => 'youtube.com',
            'reason' => null,
            'block_type' => 'app',
            'block_subdomains' => true,
            'related_domains' => ['ytimg.com'],
        ]);

        $recorder = new BlockedAccessAttemptRecorder;
        $at = Carbon::parse('2026-04-12 12:00:00');

        $this->assertTrue($recorder->recordIfBlocked(
            $device,
            'www.youtube.com',
            'https://www.youtube.com/',
            '192.168.4.10',
            $at
        ));

        $this->assertDatabaseHas('access_attempts', [
            'device_id' => $device->id,
            'type' => 'blocked_website',
            'domain' => 'youtube.com',
        ]);
    }

    public function test_subdomains_share_throttle_bucket_for_same_domain_rule(): void
    {
        config(['reporting.blocked_access_alert_throttle_minutes' => 60]);

        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        BlockedWebsite::create([
            'user_id' => $user->id,
            'url' => null,
            'domain' => 'facebook.com',
            'reason' => null,
            'block_type' => 'domain',
            'block_subdomains' => true,
            'related_domains' => null,
        ]);

        $recorder = new BlockedAccessAttemptRecorder;
        $t0 = Carbon::parse('2026-04-12 14:00:00');

        $this->assertTrue($recorder->recordIfBlocked(
            $device,
            'web.facebook.com',
            'https://web.facebook.com/',
            '192.168.4.20',
            $t0
        ));

        $this->assertFalse($recorder->recordIfBlocked(
            $device,
            'graph.facebook.com',
            'https://graph.facebook.com/',
            '192.168.4.20',
            $t0->copy()->addMinutes(5)
        ));

        $this->assertSame(1, AccessAttempt::query()->where('device_id', $device->id)->count());
        $this->assertDatabaseHas('access_attempts', [
            'device_id' => $device->id,
            'domain' => 'facebook.com',
            'url' => 'https://web.facebook.com/',
        ]);
    }

    public function test_throttle_suppresses_second_attempt_within_window(): void
    {
        config(['reporting.blocked_access_alert_throttle_minutes' => 60]);

        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        BlockedWebsite::create([
            'user_id' => $user->id,
            'url' => null,
            'domain' => 'facebook.com',
            'reason' => null,
            'block_type' => 'domain',
            'block_subdomains' => false,
            'related_domains' => null,
        ]);

        $recorder = new BlockedAccessAttemptRecorder;
        $t0 = Carbon::parse('2026-04-12 14:00:00');

        $this->assertTrue($recorder->recordIfBlocked(
            $device,
            'facebook.com',
            'https://facebook.com/',
            '192.168.4.20',
            $t0
        ));

        $this->assertFalse($recorder->recordIfBlocked(
            $device,
            'facebook.com',
            'https://facebook.com/',
            '192.168.4.20',
            $t0->copy()->addMinutes(5)
        ));

        $this->assertSame(1, AccessAttempt::query()->where('device_id', $device->id)->count());
    }

    public function test_host_matches_blocked_rule_respects_subdomain_flag(): void
    {
        $recorder = new BlockedAccessAttemptRecorder;

        $bw = new BlockedWebsite([
            'domain' => 'example.com',
            'block_type' => 'domain',
            'block_subdomains' => false,
            'related_domains' => null,
        ]);

        $this->assertTrue($recorder->hostMatchesBlockedRule('example.com', $bw));
        $this->assertFalse($recorder->hostMatchesBlockedRule('www.example.com', $bw));

        $bw2 = new BlockedWebsite([
            'domain' => 'example.com',
            'block_type' => 'domain',
            'block_subdomains' => true,
            'related_domains' => null,
        ]);

        $this->assertTrue($recorder->hostMatchesBlockedRule('www.example.com', $bw2));
    }
}
