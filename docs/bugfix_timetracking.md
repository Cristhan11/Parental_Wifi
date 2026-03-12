# Bug Fix: Time Tracking Not Deducting for Child Devices

## Issue Summary

On Raspberry Pi testing, child device `remaining_time_minutes` stayed the same while connected to parental WiFi.

## Root Cause

Two related issues caused this behavior:

1. **No session creation for connected active devices**
   - `TimeTrackingService::startSession()` existed but was never called during device connection monitoring.
   - Result: no active `device_sessions`, so `TrackActiveSessions` had nothing to deduct.

2. **Cumulative deduction logic**
   - `trackActiveSessions()` deducted total session duration repeatedly on each run.
   - Result (after fixing #1): over-deduction risk (5 + 10 + 15 ...) instead of incremental deduction (5 + 5 + 5).

## Code Changes Applied

### 1) Ensure sessions start for active connected devices

- **File:** `app/Jobs/MonitorDeviceConnections.php`
- **Change:** Added call to `TimeTrackingService::startSession($device)` for connected devices with `status === 'active'`.
- **Why:** Guarantees active session records exist so scheduled time deduction can run.

### 2) Deduct incrementally, not cumulatively

- **File:** `app/Services/TimeTrackingService.php`
- **Change:** After deducting minutes for an active session, reset session `started_at` to `now()`.
- **Why:** Next cycle only deducts elapsed time since last deduction, preventing cumulative over-deduction.

## Local Push-Readiness Checklist

Run from project root:

```bash
php artisan cache:clear
php artisan config:clear
php artisan queue:restart
```

Optional syntax check:

```bash
php -l app/Jobs/MonitorDeviceConnections.php
php -l app/Services/TimeTrackingService.php
```

Check changed files:

```bash
git status
git diff -- app/Jobs/MonitorDeviceConnections.php app/Services/TimeTrackingService.php docs/bugfix_timetracking.md
```

Commit and push:

```bash
git add app/Jobs/MonitorDeviceConnections.php app/Services/TimeTrackingService.php docs/bugfix_timetracking.md
git commit -m "fix time tracking deduction by ensuring active sessions start and deducting incrementally"
git push origin <your-branch>
```

## Raspberry Pi 4 Deployment Steps (Pull + Apply)

Run on RPi4:

```bash
cd /path/to/parental_wifi
git fetch --all
git checkout <your-branch-or-main>
git pull
```

Refresh app/runtime state:

```bash
php artisan optimize:clear
php artisan queue:restart
sudo systemctl restart parental-wifi-queue.service
```

Verify scheduler + queue worker:

```bash
crontab -l
sudo systemctl status parental-wifi-queue.service
```

Expected:
- crontab has scheduler entry (`* * * * * ... php artisan schedule:run ...`)
- queue service is `active (running)`

## Raspberry Pi 4 Verification Steps (Functional Test)

1. **Prepare a child device for test**
   - Set status to `active`
   - Give known time (ex: 30 minutes)
   - Ensure no stale active sessions remain

2. **Connect child device to parental WiFi**
   - Keep the device actively connected for at least 6-10 minutes

3. **Watch logs**

```bash
tail -f storage/logs/laravel.log
```

Look for:
- `MonitorDeviceConnections job started`
- `Session ensured for connected active device`
- `TrackActiveSessions job started`
- `Time deducted from active session`

4. **Check DB values after first cycle**
   - `remaining_time_minutes` should be lower than initial value
   - `device_sessions` should have an active session for the test device

5. **Check DB values after next cycle**
   - Remaining time should continue to decrease incrementally
   - No duplicate active sessions for same device

## Fast Manual Verification (Without Waiting for Scheduler)

If needed, dispatch jobs manually:

```bash
php artisan tinker
```

Inside Tinker:

```php
\App\Jobs\MonitorDeviceConnections::dispatch();
sleep(5);
\App\Jobs\TrackActiveSessions::dispatch();
```

Then inspect device remaining time and active session state.

## Success Criteria

- Active child devices create/maintain one active session while connected.
- `remaining_time_minutes` decreases while connected and using WiFi.
- Deduction is incremental per run (not cumulative).
- No errors in queue/scheduler logs.
