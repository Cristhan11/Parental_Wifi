<?php

/**
 * Reporting module defaults (non-secret).
 *
 * Secrets stay in `.env` (MAIL_*, etc.). This file only holds app-level defaults consumed by
 * {@see \App\Models\ReportingPreference}, listeners, and {@see \App\Http\Controllers\ReportsController}.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Default reporting timezone
    |--------------------------------------------------------------------------
    |
    | IANA timezone used when creating reporting preferences and as a fallback
    | when the stored value is empty. The Philippines uses Asia/Manila (UTC+8,
    | no daylight saving). Override with REPORTING_DEFAULT_TIMEZONE in .env.
    |
    */

    'default_timezone' => env('REPORTING_DEFAULT_TIMEZONE', 'Asia/Manila'),

    /*
    |--------------------------------------------------------------------------
    | Blocked-domain immediate alerts (ParseNetworkLogs)
    |--------------------------------------------------------------------------
    |
    | When the same child triggers the same block rule repeatedly (including many
    | different subdomains of one site), skip duplicate AccessAttempt rows (and
    | duplicate emails) within this window. Set to 0 to disable throttling.
    |
    */

    'blocked_access_alert_throttle_minutes' => (int) env('BLOCKED_ACCESS_ALERT_THROTTLE_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Flagged-domain immediate alerts (ParseNetworkLogs)
    |--------------------------------------------------------------------------
    |
    | Same idea as blocked throttling: many hostnames can map to one flagged rule.
    | Set to 0 to disable throttling.
    |
    */

    'flagged_access_alert_throttle_minutes' => (int) env('FLAGGED_ACCESS_ALERT_THROTTLE_MINUTES', 10),

];
