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

    /*
    |--------------------------------------------------------------------------
    | Max log lines to process per run
    |--------------------------------------------------------------------------
    |
    | Large dnsmasq files (months of history) can contain hundreds of thousands of
    | lines. Processing them all every 10 minutes will freeze a Raspberry Pi and
    | hammer the database. Only the most recent N lines are scanned.
    |
    | Set to 0 to process the entire file (not recommended on Pi).
    |
    */
    'log_max_lines' => (int) env('NETWORK_LOG_MAX_LINES', 25000),
];
