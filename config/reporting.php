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

];
