<?php

namespace Tests\Feature;

use App\Jobs\ProcessDebouncedPolicyApplyJob;
use App\PolicyApplyFlags;
use App\Services\PolicyApplyDebouncer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PolicyApplyDebouncerTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_apply_increments_version_and_dispatches_debounced_job(): void
    {
        Queue::fake();

        $debouncer = new PolicyApplyDebouncer(4);
        $v1 = $debouncer->requestApply(7, PolicyApplyFlags::DhcpBypass);
        $v2 = $debouncer->requestApply(7, PolicyApplyFlags::Blocklist);

        $this->assertSame(1, $v1);
        $this->assertSame(2, $v2);

        Queue::assertPushed(ProcessDebouncedPolicyApplyJob::class, 2);
        Queue::assertPushed(ProcessDebouncedPolicyApplyJob::class, function (ProcessDebouncedPolicyApplyJob $job) {
            return $job->userId === 7 && $job->version === 2;
        });
    }

    public function test_request_apply_with_none_does_not_dispatch(): void
    {
        Queue::fake();

        $debouncer = new PolicyApplyDebouncer(4);
        $debouncer->requestApply(3, PolicyApplyFlags::None);

        Queue::assertNothingPushed();
    }
}
