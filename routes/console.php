<?php

use App\Jobs\CheckTimeExpiration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
    ->withoutOverlapping() // Prevent multiple instances running at once
    ->runInBackground(); // Run in background (non-blocking)
