<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Resolves the Raspberry Pi's current Tailscale IPv4 so reporting emails point parents to a URL
 * that works from outside the home network (e.g. http://100.113.109.90/dashboard).
 *
 * Prefer the Pi local agent (runs Tailscale as root) when {@see config('pi_agent.base_url')} is set;
 * otherwise runs `tailscale ip -4` on this host. Cached for {@see config('reporting.tailscale_dashboard_cache_seconds')}.
 * Returns null when Tailscale is unavailable so callers can fall back to APP_URL.
 *
 * @see \App\Models\RemoteAccessSetting::applyReportingDashboardUrlToConfig()
 */
class TailscaleDashboardUrlResolver
{
    public const CACHE_KEY = 'reporting.tailscale_dashboard_url';

    /**
     * Resolve and return the Pi's Tailscale dashboard URL, e.g. `http://100.113.109.90/dashboard`.
     * Returns null when Tailscale is unavailable or no usable IPv4 is detected.
     */
    public function resolve(): ?string
    {
        $ttl = max(0, (int) config('reporting.tailscale_dashboard_cache_seconds', 300));
        if ($ttl === 0) {
            return $this->detect();
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached === '__none__' ? null : $cached;
        }

        $resolved = $this->detect();
        Cache::put(self::CACHE_KEY, $resolved ?? '__none__', $ttl);

        return $resolved;
    }

    /**
     * Force re-detection on the next call (e.g. after the parent finishes the Tailscale sign-in flow).
     */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Ask the Pi agent first (Tailscale CLI as root), then fall back to local `tailscale ip -4`.
     */
    private function detect(): ?string
    {
        $fromPi = app(PiTailscaleAuthLinkService::class)->fetchTailscaleDashboardUrl();
        if (is_string($fromPi) && $fromPi !== '') {
            return $fromPi;
        }

        return $this->detectViaLocalTailscaleCli();
    }

    /**
     * Execute `tailscale ip -4` on this machine and turn the first tailnet IPv4 into a dashboard URL.
     * Kept defensive: any failure (missing binary, not signed in, timeout) returns null
     * so the reporting URL fallback chain can take over without surfacing the error.
     */
    private function detectViaLocalTailscaleCli(): ?string
    {
        $binary = (string) config('reporting.tailscale_binary', '/usr/bin/tailscale');
        $timeout = max(1, (int) config('reporting.tailscale_command_timeout_seconds', 4));
        $path = (string) config('reporting.tailscale_dashboard_path', '/dashboard');

        try {
            $process = new Process([$binary, 'ip', '-4']);
            $process->setTimeout((float) $timeout);
            $process->run();
        } catch (ProcessFailedException|RuntimeException) {
            return null;
        } catch (Throwable) {
            return null;
        }

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = (string) $process->getOutput();
        foreach (preg_split('/\R/', trim($output)) ?: [] as $line) {
            $candidate = trim($line);
            if ($candidate === '') {
                continue;
            }
            if (! filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }
            if (! $this->isTailscaleCgNatIp($candidate)) {
                continue;
            }

            return 'http://'.$candidate.'/'.ltrim($path, '/');
        }

        return null;
    }

    /** Tailscale assigns from 100.64.0.0/10 (CGNAT). Reject any other IPv4. */
    private function isTailscaleCgNatIp(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) {
            return false;
        }
        $start = ip2long('100.64.0.0');
        $end = ip2long('100.127.255.255');

        return $long >= $start && $long <= $end;
    }
}
