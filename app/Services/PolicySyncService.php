<?php

namespace App\Services;

use App\Events\PolicyApplyStatus;
use App\Models\User;
use App\PolicyApplyFlags;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fast-path household policy sync: dnsmasq blocklist + DHCP DNS bypass, with optional broadcasts.
 */
class PolicySyncService
{
    public function __construct(
        protected DomainBlockingService $domainBlocking,
    ) {}

    /**
     * Run sync immediately (used by scheduled reconciliation and the debounced job).
     *
     * @return array{ok: bool, blocklist_ok: bool|null, bypass_ok: bool|null}
     */
    public function applyForUser(User $user, PolicyApplyFlags $flags, bool $broadcast = true): array
    {
        $lock = Cache::lock('policy_sync_user:'.$user->id, 120);

        if (! $lock->get()) {
            Log::notice('Policy sync skipped: lock held', ['user_id' => $user->id]);

            if ($broadcast) {
                event(new PolicyApplyStatus(
                    userId: $user->id,
                    state: 'retry',
                    message: 'Another change is still saving. Please wait a moment and try again.',
                ));
            }

            return ['ok' => false, 'blocklist_ok' => null, 'bypass_ok' => null];
        }

        try {
            $hasDevices = $user->devices()->exists();

            if (! $flags->hasBlocklist() && $flags->hasDhcpBypass() && ! $hasDevices) {
                if ($broadcast) {
                    event(new PolicyApplyStatus(
                        userId: $user->id,
                        state: 'applied',
                        message: 'Account ready.',
                    ));
                }

                return ['ok' => true, 'blocklist_ok' => null, 'bypass_ok' => true];
            }

            if ($broadcast) {
                event(new PolicyApplyStatus(
                    userId: $user->id,
                    state: 'applying',
                    message: 'Saving your changes…',
                ));
            }

            $blocklistOk = null;
            $bypassOk = null;

            if ($flags->hasBlocklist()) {
                $blocklistOk = $this->domainBlocking->syncDnsmasqBlocklistForUser($user);
            }

            if ($flags->hasDhcpBypass()) {
                $bypassOk = $this->domainBlocking->syncDnsmasqDhcpDnsBypassForUser($user);
            }

            $ok = ($blocklistOk === null || $blocklistOk) && ($bypassOk === null || $bypassOk);

            if ($broadcast) {
                event(new PolicyApplyStatus(
                    userId: $user->id,
                    state: $ok ? 'applied' : 'failed',
                    message: $ok
                        ? 'Your changes are saved.'
                        : 'That did not finish updating. Please try again in a minute.',
                ));
            }

            return [
                'ok' => $ok,
                'blocklist_ok' => $blocklistOk,
                'bypass_ok' => $bypassOk,
            ];
        } catch (Throwable $e) {
            Log::error('Policy sync exception', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            if ($broadcast) {
                event(new PolicyApplyStatus(
                    userId: $user->id,
                    state: 'failed',
                    message: 'We could not save that change. Please try again.',
                ));
            }

            return ['ok' => false, 'blocklist_ok' => null, 'bypass_ok' => null];
        } finally {
            $lock->release();
        }
    }

    /**
     * Scheduled self-healing: full dnsmasq sync, no websocket noise.
     */
    public function reconcileUserQuiet(User $user): array
    {
        return $this->applyForUser($user, PolicyApplyFlags::full(), broadcast: false);
    }
}
