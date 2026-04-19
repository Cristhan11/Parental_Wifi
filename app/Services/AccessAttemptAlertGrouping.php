<?php

namespace App\Services;

use App\Models\BlockedWebsite;
use App\Models\FlaggedWebsite;

/**
 * Derives one "site" key per household rule so subdomains (e.g. graph.facebook.com, web.facebook.com)
 * collapse to the same alert bucket for throttling and parent-facing labels.
 */
final class AccessAttemptAlertGrouping
{
    public static function normalizeHost(string $domain): string
    {
        $h = strtolower(trim($domain));
        $h = rtrim($h, '.');

        return $h;
    }

    /**
     * Stable key stored on {@see \App\Models\AccessAttempt::$domain} and used for throttle queries.
     */
    public static function groupDomainForBlockedRule(BlockedWebsite $rule, string $requestedHost): string
    {
        $requestedHost = self::normalizeHost($requestedHost);

        if ($rule->block_type === 'url') {
            $u = (string) ($rule->url ?? '');
            if ($u !== '') {
                $parts = parse_url($u);
                $blockedHost = self::normalizeHost((string) ($parts['host'] ?? ''));
            } else {
                $blockedHost = self::normalizeHost((string) ($rule->domain ?? ''));
            }

            return $blockedHost !== '' ? $blockedHost : $requestedHost;
        }

        $primary = self::normalizeHost((string) $rule->domain);

        return $primary !== '' ? $primary : $requestedHost;
    }

    public static function groupDomainForFlaggedRule(FlaggedWebsite $rule, string $requestedHost): string
    {
        $requestedHost = self::normalizeHost($requestedHost);
        $primary = self::normalizeHost((string) $rule->domain);

        return $primary !== '' ? $primary : $requestedHost;
    }

    /**
     * Short label for email subjects (facebook.com → facebook).
     */
    public static function subjectSiteLabel(string $groupDomain): string
    {
        $groupDomain = self::normalizeHost($groupDomain);
        if ($groupDomain === '') {
            return 'unknown-site';
        }

        $parts = explode('.', $groupDomain);
        if (count($parts) === 1) {
            return $parts[0];
        }

        return $parts[0];
    }

    /**
     * Prefer the real hostname from the captured URL for email body / detail lines.
     */
    public static function detailHostFromEvent(?string $url, ?string $groupDomain): string
    {
        if ($url) {
            $host = parse_url($url, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return self::normalizeHost($host);
            }
        }

        $fallback = self::normalizeHost((string) ($groupDomain ?? ''));

        return $fallback !== '' ? $fallback : 'N/A';
    }
}
