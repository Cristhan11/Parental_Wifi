<?php

namespace App\Support;

/**
 * Read-only lookup for predefined related hostnames used by blocked mobile apps / sites.
 * Lives outside {@see \App\Services\DomainBlockingService} so models can merge lists without resolving the service container.
 */
final class BlockedAppRelatedDomains
{
    /**
     * @return array<int, string>
     */
    public static function lookup(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $domain = (string) preg_replace('/^www\./', '', $domain);

        $map = config('blocked_app_related_domains', []);
        if (! is_array($map)) {
            return [];
        }

        $list = $map[$domain] ?? [];

        return is_array($list) ? array_values(array_filter(array_map('strval', $list))) : [];
    }
}
