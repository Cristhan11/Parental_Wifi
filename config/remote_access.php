<?php

/**
 * Remote dashboard access: reverse-proxy trust and LAN classification for audit logs.
 *
 * @see docs/pi_remote_dashboard_access.md
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Trusted reverse proxies
    |--------------------------------------------------------------------------
    |
    | Comma-separated IPs, or * to trust the connecting IP only (typical behind
    | one hop). When empty, Laravel does not trust X-Forwarded-* for client IP.
    | Set when nginx, Caddy, Cloudflare Tunnel, or another proxy fronts the app.
    |
    */
    'trusted_proxies' => env('TRUSTED_PROXIES') === '*'
        ? '*'
        : array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Trusted proxy header bit field (optional)
    |--------------------------------------------------------------------------
    |
    | Raw integer of Request::HEADER_* bits. Leave null for framework defaults.
    |
    */
    'trusted_proxy_headers' => env('TRUSTED_PROXY_HEADERS') !== null && env('TRUSTED_PROXY_HEADERS') !== ''
        ? (int) env('TRUSTED_PROXY_HEADERS')
        : null,

    /*
    |--------------------------------------------------------------------------
    | Trusted "local LAN" CIDRs (is_remote = false when client IP matches)
    |--------------------------------------------------------------------------
    |
    | Tailscale (100.64.0.0/10) is intentionally omitted so tailnet sessions
    | are labeled remote in audits. Adjust if your policy differs.
    |
    */
    'trusted_local_cidrs' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'TRUSTED_LOCAL_CIDRS',
            '192.168.0.0/16,10.0.0.0/8,172.16.0.0/12'
        ))
    ))),

];
