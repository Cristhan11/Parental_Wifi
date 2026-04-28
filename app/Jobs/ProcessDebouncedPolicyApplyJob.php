<?php

namespace App\Jobs;

use App\Models\User;
use App\PolicyApplyFlags;
use App\Services\PolicyApplyDebouncer;
use App\Services\PolicySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessDebouncedPolicyApplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $userId,
        public int $version,
    ) {
        $this->afterCommit();
    }

    public function handle(PolicySyncService $policySync, PolicyApplyDebouncer $debouncer): void
    {
        $vKey = $debouncer->versionKey($this->userId);
        $fKey = $debouncer->flagsKey($this->userId);

        if ((int) Cache::get($vKey, 0) !== $this->version) {
            return;
        }

        $flagsValue = (int) Cache::get($fKey, 0);
        if ($flagsValue === 0) {
            return;
        }

        $user = User::query()->find($this->userId);
        if (! $user) {
            Cache::forget($fKey);

            return;
        }

        $flags = PolicyApplyFlags::from($flagsValue);

        $result = $policySync->applyForUser($user, $flags, broadcast: true);

        $stillLatest = ((int) Cache::get($vKey, 0) === $this->version);
        if ($stillLatest) {
            Cache::forget($fKey);
        }

        if (! $result['ok']) {
            Log::warning('Debounced policy apply completed with errors', [
                'user_id' => $this->userId,
                'version' => $this->version,
                'result' => $result,
            ]);
        }
    }
}
