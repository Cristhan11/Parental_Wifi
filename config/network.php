<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Network Log Path
    |--------------------------------------------------------------------------
    |
    | Path to the network traffic log file used by ParseNetworkLogs job.
    | Override per environment with NETWORK_LOG_PATH in .env (e.g. on Raspberry Pi).
    | Default matches routes/console.php documentation.
    |
    */
    'log_path' => env('NETWORK_LOG_PATH', '/var/log/tcpdump/network.log'),
];
