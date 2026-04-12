<?php

namespace App\Services;

use App\Models\AccessAttempt;
use App\Models\Device;
use App\Models\FlaggedWebsite;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

/**
 * When network log parsing sees a DNS/TCP query for a hostname, record a flagged-site visit
 * if that hostname matches a {@see FlaggedWebsite} for the device (and the host is not blocked).
 *
 * Creates {@see AccessAttempt} with type {@code flagged_website}, which fires
 * {@see \App\Events\FlaggedWebsiteVisited} and immediate digest reporting.
 */
class FlaggedAccessAttemptRecorder
{
    public function __construct(
        private BlockedAccessAttemptRecorder $blockedAccessRecorder
    ) {}

    /**
     * If the device queried a flagged domain, insert {@see AccessAttempt} unless throttled or blocked.
     *
     * @return bool True when a new row was created
     */
    public function recordIfFlagged(
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

        if ($this->blockedAccessRecorder->hostMatchesAnyBlock($device, $host)) {
            return false;
        }

        $flaggedWebsites = FlaggedWebsite::query()
            ->where('device_id', $device->id)
            ->get();

        if ($flaggedWebsites->isEmpty()) {
            return false;
        }

        $matched = null;
        foreach ($flaggedWebsites as $rule) {
            if ($this->hostMatchesFlaggedRule($host, $rule)) {
                $matched = $rule;
                break;
            }
        }

        if (! $matched) {
            return false;
        }

        $throttleMinutes = (int) config('reporting.flagged_access_alert_throttle_minutes', 15);
        if ($throttleMinutes > 0 && $this->isThrottled($device->id, $host, $attemptedAt, $throttleMinutes)) {
            return false;
        }

        try {
            AccessAttempt::create([
                'device_id' => $device->id,
                'type' => 'flagged_website',
                'url' => $urlFromLog ?: ('https://'.$host.'/'),
                'domain' => $host,
                'ip_address' => $clientIp,
                'attempted_at' => $attemptedAt,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FlaggedAccessAttemptRecorder: failed to create access attempt', [
                'device_id' => $device->id,
                'domain' => $host,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Whether $requestedHost (normalized) is covered by this flagged row (domain + subdomains).
     */
    public function hostMatchesFlaggedRule(string $requestedHost, FlaggedWebsite $rule): bool
    {
        $requestedHost = $this->normalizeHost($requestedHost);
        $d = $this->normalizeHost((string) $rule->domain);
        if ($requestedHost === '' || $d === '') {
            return false;
        }

        if ($requestedHost === $d) {
            return true;
        }

        return str_ends_with($requestedHost, '.'.$d);
    }

    private function normalizeHost(string $domain): string
    {
        $h = strtolower(trim($domain));
        $h = rtrim($h, '.');

        return $h;
    }

    private function isThrottled(int $deviceId, string $normalizedHost, CarbonInterface $attemptedAt, int $throttleMinutes): bool
    {
        $windowStart = $attemptedAt->copy()->subMinutes($throttleMinutes);

        return AccessAttempt::query()
            ->where('device_id', $deviceId)
            ->where('type', 'flagged_website')
            ->where('domain', $normalizedHost)
            ->where('attempted_at', '>=', $windowStart)
            ->exists();
    }
}
