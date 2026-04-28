<?php

namespace App\Services;

use App\Jobs\ProcessDebouncedPolicyApplyJob;
use App\PolicyApplyFlags;
use Illuminate\Support\Facades\Cache;

/**
 * Coalesces rapid policy changes per parent user into one delayed job (Raspberry Pi friendly).
 */
class PolicyApplyDebouncer
{
    public function __construct(
        protected int $debounceSeconds,
    ) {}

    public static function fromConfig(): self
    {
        $seconds = (int) config('network.policy_apply_debounce_seconds', 4);
        $seconds = max(3, min(5, $seconds));

        return new self($seconds);
    }

    /**
     * Queue a debounced apply. Returns monotonically increasing version for this user (for tests / debugging).
     */
    public function requestApply(int $userId, PolicyApplyFlags $flags): int
    {
        if ($flags === PolicyApplyFlags::None) {
            return (int) Cache::get($this->versionKey($userId), 0);
        }

        $vKey = $this->versionKey($userId);
        $fKey = $this->flagsKey($userId);
        $version = (int) Cache::increment($vKey);

        $existing = (int) Cache::get($fKey, 0);
        Cache::put($fKey, $existing | $flags->value, 3600);

        ProcessDebouncedPolicyApplyJob::dispatch($userId, $version)
            ->delay(now()->addSeconds($this->debounceSeconds))
            ->afterCommit();

        return $version;
    }

    public function versionKey(int $userId): string
    {
        return "policy_apply_v:{$userId}";
    }

    public function flagsKey(int $userId): string
    {
        return "policy_apply_f:{$userId}";
    }
}
