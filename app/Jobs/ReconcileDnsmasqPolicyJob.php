<?php

namespace App\Jobs;

use App\Models\BlockedWebsite;
use App\Models\Device;
use App\Models\User;
use App\Services\PolicySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fallback self-healing: full dnsmasq sync for every household that has devices or saved block rules.
 */
class ReconcileDnsmasqPolicyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(PolicySyncService $policySync): void
    {
        $deviceUserIds = Device::query()->whereNotNull('user_id')->distinct()->pluck('user_id');
        $blockedUserIds = BlockedWebsite::query()->distinct()->pluck('user_id');
        $ids = $deviceUserIds->merge($blockedUserIds)->unique()->filter();

        foreach ($ids as $userId) {
            $user = User::query()->find($userId);
            if (! $user) {
                continue;
            }

            $result = $policySync->reconcileUserQuiet($user);
            if (! $result['ok']) {
                Log::notice('Scheduled dnsmasq reconcile reported partial failure', [
                    'user_id' => $user->id,
                    'result' => $result,
                ]);
            }
        }
    }
}
