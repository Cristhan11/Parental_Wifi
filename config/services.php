<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | When true, blocked-website actions show a flash if dnsmasq sync fails (e.g. missing sudoers).
    | Defaults to false on Windows so local dev is not noisy; set DNSMASQ_WARN_WHEN_SYNC_FAILS=true to test.
    */
    'dnsmasq' => [
        'warn_when_sync_fails' => env(
            'DNSMASQ_WARN_WHEN_SYNC_FAILS',
            PHP_OS_FAMILY !== 'Windows'
        ),
        /** Upstream DNS pushed via DHCP for parent/guest/whitelisted devices (not Pi resolver). */
        'bypass_dns_primary' => env('DNSMASQ_BYPASS_DNS_PRIMARY', '8.8.8.8'),
        'bypass_dns_secondary' => env('DNSMASQ_BYPASS_DNS_SECONDARY', '1.1.1.1'),
    ],

];
