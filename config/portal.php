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
];

