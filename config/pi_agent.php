<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pi local helper service
    |--------------------------------------------------------------------------
    |
    | Laravel calls a localhost-only helper on the Raspberry Pi to request the
    | current Tailscale authentication URL.
    |
    */
    'base_url' => env('PI_AGENT_BASE_URL', 'http://127.0.0.1:9098'),

    /*
    |--------------------------------------------------------------------------
    | Shared secret header
    |--------------------------------------------------------------------------
    */
    'token' => env('PI_AGENT_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | Profile "sync with dashboard" asks the Pi agent to run several tailscale
    | subprocesses in sequence (status, optional logout/login). The Pi agent uses a long
    | timeout only for `tailscale login`; this value must still exceed the worst-case total
    | wall time or Laravel will abort with a connection error while the agent keeps working.
    |
    | Nginx fastcgi_read_timeout and PHP-FPM max_execution_time often default to ~60s;
    | PiTailscaleAuthLinkService raises PHP's cap for this request; nginx must allow a long
    | enough read (see docs/PI_TAILSCALE_AUTH_LINK_AGENT.md).
    |
    */
    'timeout_seconds' => (int) env('PI_AGENT_TIMEOUT_SECONDS', 240),

    /*
    |--------------------------------------------------------------------------
    | Quick-path HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | Profile "Get Tailscale sign-in link" does not send dashboard_email; the Pi agent only
    | runs status + login. Use a smaller HTTP timeout so parents are not left waiting minutes.
    |
    */
    'quick_timeout_seconds' => (int) env('PI_AGENT_QUICK_TIMEOUT_SECONDS', 90),

    /*
    |--------------------------------------------------------------------------
    | Status-snapshot HTTP timeout (seconds)
    |--------------------------------------------------------------------------
    |
    | Read-only `status_only` path used by the profile page to render the current
    | Tailscale state on load (no `tailscale login` / `tailscale logout`). A short
    | timeout keeps the page snappy; the Pi agent only runs `tailscale status`.
    |
    */
    'status_timeout_seconds' => (int) env('PI_AGENT_STATUS_TIMEOUT_SECONDS', 8),
];
