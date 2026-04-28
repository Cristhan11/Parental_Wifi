<?php

namespace Tests\Feature;

use App\Jobs\ProcessDebouncedPolicyApplyJob;
use App\Models\User;
use App\PolicyApplyFlags;
use App\Services\PolicyApplyDebouncer;
use App\Services\PolicySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ProcessDebouncedPolicyApplyJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_job_version_skips_sync(): void
    {
        $user = User::factory()->create();
        Cache::put((new PolicyApplyDebouncer(4))->versionKey($user->id), 9, 3600);
        Cache::put((new PolicyApplyDebouncer(4))->flagsKey($user->id), PolicyApplyFlags::Blocklist->value, 3600);

        $sync = Mockery::mock(PolicySyncService::class);
        $sync->shouldNotReceive('applyForUser');
        $this->app->instance(PolicySyncService::class, $sync);

        $job = new ProcessDebouncedPolicyApplyJob((int) $user->id, 3);
        $job->handle($sync, new PolicyApplyDebouncer(4));
    }

    public function test_current_version_runs_sync(): void
    {
        $user = User::factory()->create();
        $debouncer = new PolicyApplyDebouncer(4);
        Cache::put($debouncer->versionKey($user->id), 4, 3600);
        Cache::put($debouncer->flagsKey($user->id), PolicyApplyFlags::Blocklist->value, 3600);

        $sync = Mockery::mock(PolicySyncService::class);
        $sync->shouldReceive('applyForUser')
            ->once()
            ->withArgs(function (User $u, PolicyApplyFlags $flags, bool $broadcast) use ($user) {
                return $u->id === $user->id
                    && $flags === PolicyApplyFlags::Blocklist
                    && $broadcast === true;
            })
            ->andReturn(['ok' => true, 'blocklist_ok' => true, 'bypass_ok' => null]);

        $this->app->instance(PolicySyncService::class, $sync);

        $job = new ProcessDebouncedPolicyApplyJob((int) $user->id, 4);
        $job->handle($sync, $debouncer);
    }
}
