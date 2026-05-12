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
    */
    'timeout_seconds' => (int) env('PI_AGENT_TIMEOUT_SECONDS', 240),
];
