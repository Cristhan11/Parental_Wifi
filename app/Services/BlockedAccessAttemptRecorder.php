<?php

namespace App\Services;

use App\Models\AccessAttempt;
use App\Models\BlockedWebsite;
use App\Models\Device;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * When network log parsing sees a DNS/TCP query for a hostname, record a blocked-site access attempt
 * if that hostname matches a {@see BlockedWebsite} rule for the device's parent (household block list).
 *
 * This bridges DNS-level blocking to {@see AccessAttempt}, which fires {@see \App\Events\BlockedWebsiteAccessed}
 * and therefore immediate parent email + websocket alerts.
 */
class BlockedAccessAttemptRecorder
{
    /**
     * If the device queried a blocked domain, insert {@see AccessAttempt} unless throttled.
     *
     * @return bool True when a new row was created
     */
    public function recordIfBlocked(
        Device $device,
        string $domainFromLog,
        string $urlFromLog,
        ?string $clientIp,
        CarbonInterface $attemptedAt
    ): bool {
        $host = $this->normalizeHost($domainFromLog);
        if ($host === '') {
            return false;
        }

        $blockedWebsites = BlockedWebsite::query()
            ->where('user_id', $device->user_id)
            ->get();

        if ($blockedWebsites->isEmpty()) {
            return false;
        }

        $matched = null;
        foreach ($blockedWebsites as $rule) {
            if ($this->hostMatchesBlockedRule($host, $rule)) {
                $matched = $rule;
                break;
            }
        }

        if (! $matched) {
            return false;
        }

        $alertGroupDomain = AccessAttemptAlertGrouping::groupDomainForBlockedRule($matched, $host);

        $throttleMinutes = (int) config('reporting.blocked_access_alert_throttle_minutes', 10);
        if ($throttleMinutes > 0 && $this->isThrottled($device->id, $alertGroupDomain, $attemptedAt, $throttleMinutes)) {
            return false;
        }

        try {
            AccessAttempt::create([
                'device_id' => $device->id,
                'type' => 'blocked_website',
                'url' => $urlFromLog ?: ('https://'.$host.'/'),
                'domain' => $alertGroupDomain,
                'ip_address' => $clientIp,
                'attempted_at' => $attemptedAt,
            ]);
        } catch (\Throwable $e) {
            Log::warning('BlockedAccessAttemptRecorder: failed to create access attempt', [
                'device_id' => $device->id,
                'domain' => $host,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * True when any household {@see BlockedWebsite} rule covers this hostname (e.g. for skipping flagged alerts).
     */
    public function hostMatchesAnyBlock(Device $device, string $domainFromLog): bool
    {
        $host = $this->normalizeHost($domainFromLog);
        if ($host === '') {
            return false;
        }

        $blockedWebsites = BlockedWebsite::query()
            ->where('user_id', $device->user_id)
            ->get();

        foreach ($blockedWebsites as $rule) {
            if ($this->hostMatchesBlockedRule($host, $rule)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHost(string $domain): string
    {
        $h = strtolower(trim($domain));
        $h = rtrim($h, '.');

        return $h;
    }

    /**
     * Whether $requestedHost (normalized) is covered by this block row (aligned with {@see BlockedWebsite::getDomainsToBlock()}).
     */
    public function hostMatchesBlockedRule(string $requestedHost, BlockedWebsite $rule): bool
    {
        $requestedHost = $this->normalizeHost($requestedHost);
        if ($requestedHost === '') {
            return false;
        }

        if ($rule->block_type === 'url') {
            $u = (string) ($rule->url ?? '');
            if ($u !== '') {
                $parts = parse_url($u);
                $blockedHost = $this->normalizeHost((string) ($parts['host'] ?? ''));
            } else {
                $blockedHost = $this->normalizeHost((string) ($rule->domain ?? ''));
            }

            if ($blockedHost === '') {
                return false;
            }

            if ($requestedHost === $blockedHost) {
                return true;
            }

            return $rule->shouldBlockSubdomains()
                && str_ends_with($requestedHost, '.'.$blockedHost);
        }

        foreach ($rule->getDomainsToBlock() as $d) {
            $d = $this->normalizeHost((string) $d);
            if ($d === '') {
                continue;
            }

            if ($rule->shouldBlockSubdomains()) {
                if ($requestedHost === $d || str_ends_with($requestedHost, '.'.$d)) {
                    return true;
                }
            } elseif ($requestedHost === $d) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string  $alertGroupDomain  Same value stored on {@see AccessAttempt::$domain} (rule primary / URL host), not the raw queried hostname.
     */
    private function isThrottled(int $deviceId, string $alertGroupDomain, CarbonInterface $attemptedAt, int $throttleMinutes): bool
    {
        $windowStart = $attemptedAt->copy()->subMinutes($throttleMinutes);

        return AccessAttempt::query()
            ->where('device_id', $deviceId)
            ->where('type', 'blocked_website')
            ->where('domain', $alertGroupDomain)
            ->where('attempted_at', '>=', $windowStart)
            ->exists();
    }
}
