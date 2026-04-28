<?php

use App\Jobs\CheckTimeExpiration;
use App\Jobs\EnforceSchedules;
use App\Jobs\MonitorDeviceConnections;
use App\Jobs\ParseNetworkLogs;
use App\Jobs\ReconcileDnsmasqPolicyJob;
use App\Jobs\TrackActiveSessions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schedule;

/**
 * Console Routes (Scheduled Tasks)
 *
 * This file defines scheduled tasks that run automatically in the background.
 * Laravel's scheduler runs these tasks based on the schedule you define.
 *
 * What is a Scheduled Task?
 * - A scheduled task is code that runs automatically at specified intervals
 * - Think of it like a "cron job" - it runs every minute, hour, day, etc.
 * - Laravel's scheduler checks this file and runs tasks that are due
 * - Tasks run in the background without user interaction
 *
 * How to Run the Scheduler:
 * - Add this to your server's crontab: * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 * - This runs Laravel's scheduler every minute
 * - Laravel then decides which tasks need to run based on their schedule
 *
 * For Development:
 * - You can test scheduled tasks manually: php artisan schedule:run
 * - Or run specific tasks: php artisan schedule:test
 */

// Example command (can be removed if not needed)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Run {@see ParseNetworkLogs} once in the current PHP process (no queue worker).
 * Use this on the Pi to verify NETWORK_LOG_PATH and parsing after `config:cache` / worker restarts.
 */
Artisan::command('network:parse-logs {--lines= : Process at most this many lines from the end of the file (overrides NETWORK_LOG_MAX_LINES for this run)}', function () {
    $path = config('network.log_path');
    $this->line('network.log_path = '.$path);
    $this->line('file_exists = '.(file_exists($path) ? 'yes' : 'no'));

    $linesOption = $this->option('lines');
    if ($linesOption !== null && $linesOption !== '') {
        Config::set('network.log_max_lines', max(0, (int) $linesOption));
    }

    $this->line('log_max_lines (this run) = '.config('network.log_max_lines').' (0 = entire file)');
    $this->warn('Large dnsmasq logs can take a while; use --lines=5000 for a quick test.');

    Bus::dispatchSync(new ParseNetworkLogs);
    $this->info('ParseNetworkLogs finished (see storage/logs for entries_created).');
})->purpose('Run ParseNetworkLogs synchronously using current config');

/**
 * Schedule CheckTimeExpiration Job
 *
 * This schedules the CheckTimeExpiration job to run every 2 minutes.
 * The job checks for devices whose internet time has expired and
 * automatically blocks them at the network level and redirects them to the portal.
 *
 * Why Every 2 Minutes?
 * - Balance between responsiveness and performance
 * - Too frequent (every 30 seconds): Wastes server resources, may cause conflicts
 * - Too infrequent (every 5 minutes): Devices may use extra time before being blocked
 * - 2 minutes is a good balance: Catches expired devices quickly without overloading server
 *
 * How It Works:
 * 1. Scheduler runs every minute (via crontab)
 * 2. Laravel checks if CheckTimeExpiration job is due (every 2 minutes)
 * 3. If due, dispatches the job to the queue
 * 4. Queue worker processes the job
 * 5. Job finds expired devices and blocks/redirects them
 *
 * What the Job Does:
 * - Finds all devices whose time has expired (remaining_time_minutes <= 0)
 * - For each expired device:
 *   - Updates device status to 'blocked' in database
 *   - Blocks device at network level (via NetworkService - iptables)
 *   - Redirects device to portal (via NoDogSplashService)
 * - Logs all operations for debugging
 *
 * Error Handling:
 * - If job fails, Laravel will retry it (based on queue configuration)
 * - Errors are logged so we can debug issues
 * - One failed device doesn't stop processing of other devices
 *
 * Testing:
 * - Test manually: php artisan schedule:test
 * - Or dispatch job directly: php artisan queue:work
 * - Check logs: storage/logs/laravel.log
 */
Schedule::job(new CheckTimeExpiration)
    ->everyTwoMinutes() // Run every 2 minutes
    ->name('check-time-expiration') // Name for logging and monitoring
    ->withoutOverlapping(); // Prevent multiple instances running at once

/**
 * Schedule TrackActiveSessions Job
 *
 * This schedules the TrackActiveSessions job to run every 5 minutes.
 * The job tracks all active internet sessions and deducts time from devices
 * based on how long they've been browsing.
 *
 * Why Every 5 Minutes?
 * - Balance between accuracy and performance
 * - Too frequent (every 1 minute): Wastes server resources, may cause conflicts
 * - Too infrequent (every 10 minutes): Time tracking becomes less accurate
 * - 5 minutes is a good balance: Accurate time tracking without overloading server
 *
 * How It Works:
 * 1. Scheduler runs every minute (via crontab)
 * 2. Laravel checks if TrackActiveSessions job is due (every 5 minutes)
 * 3. If due, dispatches the job to the queue
 * 4. Queue worker processes the job
 * 5. Job calls TimeTrackingService::trackActiveSessions() to deduct time
 *
 * What the Job Does:
 * - Finds all active sessions (sessions that haven't ended)
 * - For each session, calculates how long it's been running
 * - Deducts that time from device's remaining_time_minutes
 * - Updates device's last_seen_at timestamp
 * - Skips whitelisted devices (they don't have time deducted)
 *
 * Error Handling:
 * - If job fails, Laravel will retry it (based on queue configuration)
 * - Errors are logged so we can debug issues
 * - One failed session doesn't stop processing of other sessions
 *
 * Testing:
 * - Test manually: php artisan schedule:test
 * - Or dispatch job directly: TrackActiveSessions::dispatch()
 * - Check logs: storage/logs/laravel.log
 */
Schedule::job(new TrackActiveSessions)
    ->everyFiveMinutes() // Run every 5 minutes
    ->name('track-active-sessions') // Name for logging and monitoring
    ->withoutOverlapping(); // Prevent multiple instances running at once

/**
 * Schedule MonitorDeviceConnections Job
 *
 * This schedules the MonitorDeviceConnections job to run every 2 minutes.
 * The job monitors the network to detect new devices connecting to the WiFi
 * access point and devices that have disconnected.
 *
 * Why Every 2 Minutes?
 * - Balance between detection speed and performance
 * - Too frequent (every 30 seconds): Wastes server resources, may cause conflicts
 * - Too infrequent (every 5 minutes): Device connections/disconnections detected slowly
 * - 2 minutes is a good balance: Fast detection without overloading server
 *
 * How It Works:
 * 1. Scheduler runs every minute (via crontab)
 * 2. Laravel checks if MonitorDeviceConnections job is due (every 2 minutes)
 * 3. If due, dispatches the job to the queue
 * 4. Queue worker processes the job
 * 5. Job gets connected devices from network and compares with database
 *
 * What the Job Does:
 * - Gets list of currently connected devices from network (via NetworkService)
 * - Updates device IP addresses when they reconnect
 * - Updates device last_seen_at timestamps
 * - Ends active sessions for devices that disconnected
 * - Logs new device connections for parent review
 *
 * Error Handling:
 * - If job fails, Laravel will retry it (based on queue configuration)
 * - Errors are logged so we can debug issues
 * - One failed device doesn't stop processing of other devices
 *
 * Testing:
 * - Test manually: php artisan schedule:test
 * - Or dispatch job directly: MonitorDeviceConnections::dispatch()
 * - Check logs: storage/logs/laravel.log
 */
Schedule::job(new MonitorDeviceConnections)
    ->everyTwoMinutes() // Run every 2 minutes
    ->name('monitor-device-connections') // Name for logging and monitoring
    ->withoutOverlapping(); // Prevent multiple instances running at once

/**
 * Schedule EnforceSchedules Job
 *
 * This schedules the EnforceSchedules job to run every 1 minute.
 * The job enforces time-based access rules for devices (e.g., "Internet allowed
 * Monday-Friday 3PM-9PM") and blocks/unblocks devices accordingly.
 *
 * Why Every 1 Minute?
 * - Schedules need precise enforcement (e.g., block at exactly 9:00 PM)
 * - Too infrequent (every 5 minutes): Devices might use extra time before being blocked
 * - 1 minute ensures schedules are enforced within 1 minute of scheduled time
 *
 * How It Works:
 * 1. Scheduler runs every minute (via crontab)
 * 2. Laravel checks if EnforceSchedules job is due (every 1 minute)
 * 3. If due, dispatches the job to the queue
 * 4. Queue worker processes the job
 * 5. Job checks current day and time, finds active schedules, and enforces rules
 *
 * What the Job Does:
 * - Gets current day of week and time
 * - Finds all active schedules matching current day
 * - For each schedule, checks if current time is within allowed time window
 * - Checks if daily duration limit has been reached
 * - Blocks/unblocks devices based on schedule rules
 * - Skips whitelisted devices (they bypass all schedules)
 *
 * Error Handling:
 * - If job fails, Laravel will retry it (based on queue configuration)
 * - Errors are logged so we can debug issues
 * - One failed schedule doesn't stop processing of other schedules
 *
 * Testing:
 * - Test manually: php artisan schedule:test
 * - Or dispatch job directly: EnforceSchedules::dispatch()
 * - Check logs: storage/logs/laravel.log
 */
Schedule::job(new EnforceSchedules)
    ->everyMinute() // Run every 1 minute (precise schedule enforcement)
    ->name('enforce-schedules') // Name for logging and monitoring
    ->withoutOverlapping(); // Prevent multiple instances running at once

/**
 * Schedule ParseNetworkLogs Job
 *
 * This schedules the ParseNetworkLogs job to run every 10 minutes.
 * The job parses network traffic logs to extract browsing history and create
 * BrowsingLog records in the database.
 *
 * Why Every 10 Minutes?
 * - Logs accumulate over time, don't need real-time parsing
 * - Too frequent (every 1 minute): Wastes server resources
 * - Too infrequent (every 30 minutes): Browsing history becomes stale
 * - 10 minutes is a good balance: Recent history without overloading server
 *
 * How It Works:
 * 1. Scheduler runs every minute (via crontab)
 * 2. Laravel checks if ParseNetworkLogs job is due (every 10 minutes)
 * 3. If due, dispatches the job to the queue
 * 4. Queue worker processes the job
 * 5. Job reads log file, parses entries, and creates BrowsingLog records
 *
 * What the Job Does:
 * - Reads network log files (tcpdump or iptables logs)
 * - Parses log entries to extract HTTP requests
 * - Extracts URL, domain, IP address, timestamp, and other information
 * - Matches requests to devices by MAC address
 * - Creates BrowsingLog records in database
 * - Handles duplicate prevention (skips entries that already exist)
 *
 * Error Handling:
 * - If job fails, Laravel will retry it (based on queue configuration)
 * - Errors are logged so we can debug issues
 * - One failed entry doesn't stop processing of other entries
 *
 * Testing:
 * - Test manually: php artisan schedule:test
 * - Or dispatch job directly: ParseNetworkLogs::dispatch()
 * - Check logs: storage/logs/laravel.log
 *
 * Configuration:
 * - Log file path: config('network.log_path', '/var/log/tcpdump/network.log')
 * - Set in .env: NETWORK_LOG_PATH=/var/log/tcpdump/network.log
 */
Schedule::job(new ParseNetworkLogs)
    ->everyTenMinutes() // Run every 10 minutes
    ->name('parse-network-logs') // Name for logging and monitoring
    ->withoutOverlapping(); // Prevent multiple instances running at once

/**
 * Reporting digest schedules (locked scope).
 *
 * Daily/weekly/monthly runs are split to keep each cadence independently
 * observable in scheduler output and easier to retry per frequency.
 */
Schedule::command('reporting:send-digest daily')
    ->dailyAt('06:00')
    ->name('reporting-digest-daily')
    ->withoutOverlapping();

Schedule::command('reporting:send-digest weekly')
    ->weeklyOn(1, '06:05')
    ->name('reporting-digest-weekly')
    ->withoutOverlapping();

Schedule::command('reporting:send-digest monthly')
    ->monthlyOn(1, '06:10')
    ->name('reporting-digest-monthly')
    ->withoutOverlapping();

/**
 * Household dnsmasq full reconcile (fallback / self-healing).
 *
 * Debounced applies cover normal UX; this job recovers drift if a script failed mid-flight.
 */
Schedule::job(new ReconcileDnsmasqPolicyJob)
    ->hourly()
    ->name('reconcile-dnsmasq-policy')
    ->withoutOverlapping();
