<?php

namespace Tests\Unit;

use App\Models\AccessAttempt;
use App\Models\BlockedWebsite;
use App\Models\Device;
use App\Models\FlaggedWebsite;
use App\Models\User;
use App\Services\BlockedAccessAttemptRecorder;
use App\Services\FlaggedAccessAttemptRecorder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlaggedAccessAttemptRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_access_attempt_when_subdomain_matches_flagged_domain(): void
    {
        config(['reporting.flagged_access_alert_throttle_minutes' => 0]);

        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        FlaggedWebsite::create([
            'user_id' => $user->id,
            'url' => 'https://example.com/',
            'domain' => 'example.com',
            'reason' => null,
        ]);

        $recorder = new FlaggedAccessAttemptRecorder(new BlockedAccessAttemptRecorder);
        $at = Carbon::parse('2026-04-12 12:00:00');

        $this->assertTrue($recorder->recordIfFlagged(
            $device,
            'www.example.com',
            'https://www.example.com/',
            '192.168.4.10',
            $at
        ));

        $this->assertDatabaseHas('access_attempts', [
            'device_id' => $device->id,
            'type' => 'flagged_website',
            'domain' => 'example.com',
        ]);
    }

    public function test_subdomains_share_throttle_bucket_for_same_flag_rule(): void
    {
        config(['reporting.flagged_access_alert_throttle_minutes' => 60]);

        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        FlaggedWebsite::create([
            'user_id' => $user->id,
            'url' => 'https://example.com/',
            'domain' => 'example.com',
            'reason' => null,
        ]);

        $recorder = new FlaggedAccessAttemptRecorder(new BlockedAccessAttemptRecorder);
        $t0 = Carbon::parse('2026-04-12 14:00:00');

        $this->assertTrue($recorder->recordIfFlagged(
            $device,
            'www.example.com',
            'https://www.example.com/',
            '192.168.4.20',
            $t0
        ));

        $this->assertFalse($recorder->recordIfFlagged(
            $device,
            'api.example.com',
            'https://api.example.com/',
            '192.168.4.20',
            $t0->copy()->addMinutes(5)
        ));

        $this->assertSame(1, AccessAttempt::query()->where('device_id', $device->id)->count());
    }

    public function test_throttle_suppresses_second_attempt_within_window(): void
    {
        config(['reporting.flagged_access_alert_throttle_minutes' => 60]);

        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        FlaggedWebsite::create([
            'user_id' => $user->id,
            'url' => 'https://news.test/',
            'domain' => 'news.test',
            'reason' => null,
        ]);

        $recorder = new FlaggedAccessAttemptRecorder(new BlockedAccessAttemptRecorder);
        $t0 = Carbon::parse('2026-04-12 14:00:00');

        $this->assertTrue($recorder->recordIfFlagged(
            $device,
            'news.test',
            'https://news.test/',
            '192.168.4.20',
            $t0
        ));

        $this->assertFalse($recorder->recordIfFlagged(
            $device,
            'news.test',
            'https://news.test/',
            '192.168.4.20',
            $t0->copy()->addMinutes(5)
        ));

        $this->assertSame(1, AccessAttempt::query()->where('device_id', $device->id)->count());
    }

    public function test_does_not_record_flagged_when_host_is_blocked(): void
    {
        config(['reporting.flagged_access_alert_throttle_minutes' => 0]);

        $user = User::factory()->create();
        $device = Device::factory()->create(['user_id' => $user->id]);

        BlockedWebsite::create([
            'user_id' => $user->id,
            'url' => null,
            'domain' => 'badsite.test',
            'reason' => null,
            'block_type' => 'domain',
            'block_subdomains' => true,
            'related_domains' => null,
        ]);

        FlaggedWebsite::create([
            'user_id' => $user->id,
            'url' => 'https://badsite.test/',
            'domain' => 'badsite.test',
            'reason' => null,
        ]);

        $recorder = new FlaggedAccessAttemptRecorder(new BlockedAccessAttemptRecorder);
        $at = Carbon::parse('2026-04-12 12:00:00');

        $this->assertFalse($recorder->recordIfFlagged(
            $device,
            'www.badsite.test',
            'https://www.badsite.test/',
            '192.168.4.10',
            $at
        ));

        $this->assertSame(0, AccessAttempt::query()->where('device_id', $device->id)->count());
    }

    public function test_host_matches_flagged_rule_exact_host_only(): void
    {
        $recorder = new FlaggedAccessAttemptRecorder(new BlockedAccessAttemptRecorder);

        $rule = new FlaggedWebsite([
            'domain' => 'flag.test',
        ]);

        $this->assertTrue($recorder->hostMatchesFlaggedRule('flag.test', $rule));
        $this->assertTrue($recorder->hostMatchesFlaggedRule('sub.flag.test', $rule));
        $this->assertFalse($recorder->hostMatchesFlaggedRule('other.test', $rule));
    }
}
