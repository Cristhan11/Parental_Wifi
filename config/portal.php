<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Portal URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the captive portal. This should be the IP address
    | of the WiFi Access Point interface (wlan0) so that devices on the
    | WiFi network can access it.
    |
    | Default: http://192.168.4.1 (standard WiFi AP IP)
    |
    */

    'url' => env('PORTAL_URL', 'http://192.168.4.1'),

    /*
    |--------------------------------------------------------------------------
    | Local development: fake client MAC (loopback only)
    |--------------------------------------------------------------------------
    |
    | On Windows/Mac with `php artisan serve`, the browser is 127.0.0.1 and
    | NoDogSplash / ndsctl are not available, so the portal cannot learn a MAC.
    | Set PORTAL_DEV_CLIENT_MAC in .env while APP_ENV is local (or testing in
    | PHPUnit) to use this address only when the client IP is 127.0.0.1 or ::1.
    | Leave unset on the Raspberry Pi.
    |
    */

    'dev_client_mac' => env('PORTAL_DEV_CLIENT_MAC'),

    /*
    |--------------------------------------------------------------------------
    | Portal video stream: PHP execution time ceiling
    |--------------------------------------------------------------------------
    |
    | Streaming a long MP4 keeps the PHP request open for the whole download.
    | If this is too low, the response is cut off mid-file and the browser shows
    | MEDIA_ERR_DECODE ("playback failed"). Default 86400 (24h) is safe for 30+
    | minute files. Set PORTAL_VIDEO_STREAM_MAX_EXECUTION in .env to override.
    |
    */

    'video_stream_max_execution_seconds' => (int) env('PORTAL_VIDEO_STREAM_MAX_EXECUTION', 86400),
];
