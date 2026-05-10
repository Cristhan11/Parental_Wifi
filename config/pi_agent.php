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
    */
    'timeout_seconds' => (int) env('PI_AGENT_TIMEOUT_SECONDS', 8),
];
