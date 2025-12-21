# Browsing Logs Setup Reference

**Purpose:** Complete reference guide for setting up and maintaining browsing history tracking using DNS logging on Raspberry Pi.

**Status:** ✅ Production Ready - DNS Logging Method

**Last Updated:** December 2025

---

## Table of Contents

1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Prerequisites](#prerequisites)
4. [Complete Setup Guide](#complete-setup-guide)
5. [Configuration Files](#configuration-files)
6. [Testing & Verification](#testing--verification)
7. [Troubleshooting](#troubleshooting)
8. [Maintenance](#maintenance)
9. [Related Documentation](#related-documentation)

---

## Overview

### What Are Browsing Logs?

Browsing logs track all websites visited by child devices, providing parents with a complete history of internet activity. The system captures domain names (e.g., `youtube.com`, `facebook.com`, `google.com`) from DNS queries and stores them in the database for review through the parent dashboard.

### How It Works

1. **DNS Logging**: dnsmasq logs all DNS queries to systemd journal
2. **Log Forwarding**: Systemd service forwards DNS logs to `/var/log/dnsmasq.log`
3. **Parsing**: `ParseNetworkLogs` job runs every 10 minutes to extract domains
4. **Storage**: Creates `BrowsingLog` records in database
5. **Display**: Parents view browsing history at `/browsing-logs` in dashboard

### Why DNS Logging?

| Feature | DNS Logging | tcpdump SNI |
|---------|-------------|-------------|
| **Reliability** | ✅ Very reliable | ❌ May not work with all HTTPS |
| **Setup Complexity** | ✅ Simple | ❌ Complex (needs SNI parsing) |
| **Captures HTTP** | ✅ Yes | ✅ Yes |
| **Captures HTTPS** | ✅ Yes (via DNS lookup) | ⚠️ Maybe (depends on SNI visibility) |
| **Performance** | ✅ Low overhead | ⚠️ Higher overhead |
| **Domain Accuracy** | ✅ 100% accurate | ⚠️ May miss some domains |

**DNS logging is the recommended method** because it:
- Captures all domain lookups (HTTP and HTTPS)
- Works with existing dnsmasq setup
- Simple and reliable
- Shows actual domains visited

---

## System Architecture

```
┌─────────────────┐
│  Child Device   │
│  (Browses Web)  │
└────────┬────────┘
         │
         │ DNS Query: "youtube.com"
         ▼
┌─────────────────┐
│    dnsmasq      │
│  (DNS Server)   │
└────────┬────────┘
         │
         │ Logs to systemd journal
         ▼
┌─────────────────┐
│ systemd journal │
└────────┬────────┘
         │
         │ Forwarded by service
         ▼
┌─────────────────┐
│ /var/log/       │
│ dnsmasq.log     │
└────────┬────────┘
         │
         │ Parsed every 10 minutes
         ▼
┌─────────────────┐
│ParseNetworkLogs │
│     Job         │
└────────┬────────┘
         │
         │ Creates records
         ▼
┌─────────────────┐
│  BrowsingLog    │
│   (Database)    │
└────────┬────────┘
         │
         │ Displayed in dashboard
         ▼
┌─────────────────┐
│ Parent Dashboard│
│ /browsing-logs  │
└─────────────────┘
```

---

## Prerequisites

Before setting up browsing logs, ensure:

- ✅ **Raspberry Pi** is configured as WiFi access point
- ✅ **dnsmasq** is installed and running (`sudo systemctl status dnsmasq`)
- ✅ **Laravel application** is installed at `/var/www/parental_wifi`
- ✅ **Queue worker** is running (`sudo systemctl status parental-wifi-queue.service`)
- ✅ **Laravel scheduler** is configured (crontab: `* * * * * php artisan schedule:run`)
- ✅ **Devices** are registered in database with IP addresses

---

## Complete Setup Guide

### Step 1: Enable DNS Query Logging in dnsmasq

Edit dnsmasq configuration:

```bash
sudo nano /etc/dnsmasq.conf
```

Add or uncomment these lines:

```conf
# Enable DNS query logging
log-queries

# Log to syslog facility (local0)
log-facility=local0
```

**Configuration Explanation:**
- **log-queries**: Enables logging of all DNS queries
- **log-facility=local0**: Logs to syslog facility local0 (we'll forward this to a file)

---

### Step 2: Create DNS Log Forwarder Service

Since dnsmasq logs to systemd journal (not directly to a file), we need a service to forward logs:

```bash
sudo nano /etc/systemd/system/dnsmasq-log-forwarder.service
```

Add this content:

```ini
[Unit]
Description=Forward dnsmasq logs from journal to file
After=systemd-journald.service
Requires=systemd-journald.service

[Service]
Type=simple
ExecStart=/bin/bash -c 'journalctl -u dnsmasq -f --no-pager | grep -E "query|reply" >> /var/log/dnsmasq.log'
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**Service Explanation:**
- **journalctl -u dnsmasq -f**: Follows dnsmasq logs from systemd journal
- **grep -E "query|reply"**: Filters for DNS queries and replies
- **>> /var/log/dnsmasq.log**: Appends to log file

---

### Step 3: Create DNS Log File and Set Permissions

```bash
# Create log file
sudo touch /var/log/dnsmasq.log

# Set permissions (service writes as root, Laravel needs to read)
sudo chown root:www-data /var/log/dnsmasq.log
sudo chmod 644 /var/log/dnsmasq.log
```

---

### Step 4: Enable and Start Services

```bash
# Reload systemd to recognize new service
sudo systemctl daemon-reload

# Restart dnsmasq to apply logging configuration
sudo systemctl restart dnsmasq

# Enable and start log forwarder service
sudo systemctl enable dnsmasq-log-forwarder.service
sudo systemctl start dnsmasq-log-forwarder.service

# Verify services are running
sudo systemctl status dnsmasq
sudo systemctl status dnsmasq-log-forwarder.service
```

---

### Step 5: Configure Log Rotation

Prevent log file from filling up disk space:

```bash
sudo nano /etc/logrotate.d/dnsmasq
```

Add this content:

```conf
/var/log/dnsmasq.log {
    daily
    rotate 7
    compress
    delaycompress
    notifempty
    missingok
    create 644 root www-data
    postrotate
        # Restart forwarder service after rotation
        systemctl restart dnsmasq-log-forwarder.service 2>/dev/null || true
    endscript
}
```

**Log Rotation Explanation:**
- **daily**: Rotate logs daily
- **rotate 7**: Keep 7 days of logs
- **compress**: Compress old logs to save space
- **delaycompress**: Don't compress yesterday's log (easier to read)

---

### Step 6: Configure Laravel Application

#### 6.1 Update .env File

```bash
cd /var/www/parental_wifi
nano .env
```

Add or update:

```env
NETWORK_LOG_PATH=/var/log/dnsmasq.log
```

#### 6.2 Create/Update Network Config File

```bash
nano config/network.php
```

Ensure it contains:

```php
<?php

return [
    'log_path' => env('NETWORK_LOG_PATH', '/var/log/dnsmasq.log'),
];
```

#### 6.3 Clear and Cache Configuration

```bash
php artisan config:clear
php artisan config:cache
```

---

### Step 7: Verify Setup

#### 7.1 Check DNS Logging is Working

```bash
# Clear the log
sudo truncate -s 0 /var/log/dnsmasq.log

# Browse to some sites on your device (youtube.com, google.com, etc.)
# Wait 30 seconds

# Check the log
sudo tail -20 /var/log/dnsmasq.log
```

You should see entries like:
```
Dec 22 04:30:15 parentalpi dnsmasq[1234]: query[A] google.com from 192.168.4.31
Dec 22 04:30:16 parentalpi dnsmasq[1234]: query[A] youtube.com from 192.168.4.31
```

#### 7.2 Test ParseNetworkLogs Job

```bash
cd /var/www/parental_wifi
php artisan tinker
```

```php
// Run job synchronously for immediate results
$job = new App\Jobs\ParseNetworkLogs();
$job->handle();

// Check results
App\Models\BrowsingLog::count();
App\Models\BrowsingLog::latest()->take(10)->get(['url', 'domain', 'visited_at']);
exit
```

#### 7.3 Verify in Dashboard

1. Login to parent dashboard
2. Navigate to `/browsing-logs`
3. Filter by device
4. Verify browsing logs appear

---

## Configuration Files

### dnsmasq Configuration

**File:** `/etc/dnsmasq.conf`

**Required Settings:**
```conf
log-queries
log-facility=local0
```

### Log Forwarder Service

**File:** `/etc/systemd/system/dnsmasq-log-forwarder.service`

**Purpose:** Forwards DNS logs from systemd journal to file

### Log Rotation

**File:** `/etc/logrotate.d/dnsmasq`

**Purpose:** Prevents log file from filling disk space

### Laravel Configuration

**File:** `config/network.php`

**Content:**
```php
return [
    'log_path' => env('NETWORK_LOG_PATH', '/var/log/dnsmasq.log'),
];
```

**Environment Variable:** `.env`
```env
NETWORK_LOG_PATH=/var/log/dnsmasq.log
```

---

## Testing & Verification

### Quick Test Checklist

- [ ] dnsmasq is running: `sudo systemctl status dnsmasq`
- [ ] Log forwarder is running: `sudo systemctl status dnsmasq-log-forwarder.service`
- [ ] DNS log file exists: `ls -lh /var/log/dnsmasq.log`
- [ ] DNS log has content: `sudo tail -20 /var/log/dnsmasq.log`
- [ ] ParseNetworkLogs job runs: Check `storage/logs/laravel.log`
- [ ] BrowsingLog records created: `php artisan tinker` → `App\Models\BrowsingLog::count()`
- [ ] Dashboard shows logs: Visit `/browsing-logs`

### Manual Testing Steps

**1. Generate DNS Traffic:**
```bash
# On child device, browse to:
# - youtube.com
# - facebook.com
# - instagram.com
# - google.com
```

**2. Check DNS Log:**
```bash
sudo tail -30 /var/log/dnsmasq.log | grep "query\[A\]"
```

**3. Run Parser Manually:**
```bash
cd /var/www/parental_wifi
php artisan tinker
```

```php
// For immediate results (synchronous)
$job = new App\Jobs\ParseNetworkLogs();
$job->handle();

// Or queue it (asynchronous, few seconds delay)
App\Jobs\ParseNetworkLogs::dispatch();
exit
```

**4. Verify Results:**
```bash
php artisan tinker
```

```php
App\Models\BrowsingLog::count();
App\Models\BrowsingLog::latest()->take(10)->get(['url', 'domain', 'visited_at']);
exit
```

---

## Troubleshooting

### Problem: No DNS queries in log

**Symptoms:**
- `/var/log/dnsmasq.log` is empty
- No DNS queries appearing

**Solutions:**

1. **Check dnsmasq is running:**
   ```bash
   sudo systemctl status dnsmasq
   ```

2. **Check dnsmasq configuration:**
   ```bash
   grep -E "log-queries|log-facility" /etc/dnsmasq.conf
   ```
   Should show:
   ```
   log-queries
   log-facility=local0
   ```

3. **Check log forwarder service:**
   ```bash
   sudo systemctl status dnsmasq-log-forwarder.service
   ```

4. **Check systemd journal for DNS queries:**
   ```bash
   sudo journalctl -u dnsmasq -n 20 --no-pager | grep query
   ```

5. **Restart services:**
   ```bash
   sudo systemctl restart dnsmasq
   sudo systemctl restart dnsmasq-log-forwarder.service
   ```

6. **Clear DNS cache on device:**
   - Turn WiFi off and on
   - Or forget network and reconnect
   - This forces new DNS queries

---

### Problem: Log file not updating

**Symptoms:**
- Log file exists but doesn't grow
- No new entries appearing

**Solutions:**

1. **Check log forwarder service:**
   ```bash
   sudo systemctl status dnsmasq-log-forwarder.service
   sudo journalctl -u dnsmasq-log-forwarder.service -n 20
   ```

2. **Restart log forwarder:**
   ```bash
   sudo systemctl restart dnsmasq-log-forwarder.service
   ```

3. **Check file permissions:**
   ```bash
   ls -la /var/log/dnsmasq.log
   ```
   Should be: `-rw-r--r-- 1 root www-data`

4. **Check disk space:**
   ```bash
   df -h
   ```

---

### Problem: ParseNetworkLogs creates 0 entries

**Symptoms:**
- Job runs successfully but `BrowsingLog::count()` is 0
- Log shows `entries_created: 0`

**Solutions:**

1. **Check device IP addresses:**
   ```bash
   php artisan tinker
   ```
   ```php
   App\Models\Device::all(['id', 'name', 'mac_address', 'ip_address']);
   exit
   ```
   Devices must have `ip_address` values matching IPs in DNS logs.

2. **Update device IP addresses:**
   ```bash
   php artisan tinker
   ```
   ```php
   App\Jobs\MonitorDeviceConnections::dispatch();
   exit
   ```
   This job updates device IP addresses from ARP table.

3. **Check DNS log format:**
   ```bash
   sudo tail -10 /var/log/dnsmasq.log
   ```
   Should see: `query[A] domain.com from 192.168.4.31`

4. **Check parser logs:**
   ```bash
   tail -50 storage/logs/laravel.log | grep ParseNetworkLogs
   ```

5. **Test parser manually:**
   ```bash
   php artisan tinker
   ```
   ```php
   $job = new App\Jobs\ParseNetworkLogs();
   $job->handle();
   App\Models\BrowsingLog::count();
   exit
   ```

---

### Problem: Dashboard doesn't show logs

**Symptoms:**
- BrowsingLog records exist in database
- Dashboard shows empty or no results

**Solutions:**

1. **Check device filter:**
   - Ensure correct device is selected in filter
   - Try "All Devices" option

2. **Check date range:**
   - Ensure date range includes recent logs
   - Try clearing date filters

3. **Check database directly:**
   ```bash
   php artisan tinker
   ```
   ```php
   App\Models\BrowsingLog::count();
   App\Models\BrowsingLog::latest()->take(5)->get(['url', 'domain', 'visited_at', 'device_id']);
   exit
   ```

4. **Check device ownership:**
   - Ensure logged-in user owns the device
   - Check `devices.user_id` matches logged-in user

---

### Problem: Service won't start

**Symptoms:**
- `systemctl status` shows failed
- Service doesn't start on boot

**Solutions:**

1. **Check service file syntax:**
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl status dnsmasq-log-forwarder.service
   ```

2. **Check service logs:**
   ```bash
   sudo journalctl -u dnsmasq-log-forwarder.service -n 50 --no-pager
   ```

3. **Verify service file:**
   ```bash
   cat /etc/systemd/system/dnsmasq-log-forwarder.service
   ```

4. **Test command manually:**
   ```bash
   sudo journalctl -u dnsmasq -f --no-pager | grep -E "query|reply" | head -5
   ```

---

## Maintenance

### Service Management

**Start/Stop/Restart:**
```bash
# Start service
sudo systemctl start dnsmasq-log-forwarder.service

# Stop service
sudo systemctl stop dnsmasq-log-forwarder.service

# Restart service
sudo systemctl restart dnsmasq-log-forwarder.service

# Check status
sudo systemctl status dnsmasq-log-forwarder.service
```

**Enable/Disable on Boot:**
```bash
# Enable on boot
sudo systemctl enable dnsmasq-log-forwarder.service

# Disable on boot
sudo systemctl disable dnsmasq-log-forwarder.service
```

### Log File Management

**Check Log File Size:**
```bash
ls -lh /var/log/dnsmasq.log
```

**Clear Log File:**
```bash
sudo truncate -s 0 /var/log/dnsmasq.log
```

**View Recent Logs:**
```bash
sudo tail -50 /var/log/dnsmasq.log
```

**Follow Logs in Real-Time:**
```bash
sudo tail -f /var/log/dnsmasq.log
```

### Database Maintenance

**Clear Old Browsing Logs:**
```bash
php artisan tinker
```

```php
// Delete all logs
App\Models\BrowsingLog::truncate();

// Or delete logs older than 30 days
App\Models\BrowsingLog::where('visited_at', '<', now()->subDays(30))->delete();
exit
```

**Check Log Statistics:**
```bash
php artisan tinker
```

```php
// Total logs
App\Models\BrowsingLog::count();

// Logs per device
App\Models\BrowsingLog::selectRaw('device_id, count(*) as count')
    ->groupBy('device_id')
    ->get();

// Most visited domains
App\Models\BrowsingLog::selectRaw('domain, count(*) as count')
    ->groupBy('domain')
    ->orderBy('count', 'desc')
    ->take(10)
    ->get();
exit
```

---

## DNS Log Format

### Log Entry Format

dnsmasq logs DNS queries in this format:

```
Dec 22 04:30:15 parentalpi dnsmasq[1234]: query[A] google.com from 192.168.4.31
Dec 22 04:30:16 parentalpi dnsmasq[1234]: query[AAAA] youtube.com from 192.168.4.31
Dec 22 04:30:17 parentalpi dnsmasq[1234]: reply google.com is 142.250.191.14
```

**Format Breakdown:**
- **Timestamp**: `Dec 22 04:30:15`
- **Hostname**: `parentalpi`
- **Service**: `dnsmasq[1234]` (process ID)
- **Query Type**: `query[A]` (A record) or `query[AAAA]` (IPv6) or `query[HTTPS]` (HTTPS record)
- **Domain**: `google.com`
- **Source IP**: `from 192.168.4.31`

### What Gets Parsed

The `ParseNetworkLogs` job extracts:
- **Domain name**: `google.com`, `youtube.com`, etc.
- **Source IP address**: `192.168.4.31` (to match to device)
- **Timestamp**: When the DNS query was made
- **URL**: Constructed as `https://domain.com` (assumes HTTPS for modern sites)

---

## ParseNetworkLogs Job

### Job Details

**File:** `app/Jobs/ParseNetworkLogs.php`

**Schedule:** Every 10 minutes (configured in `routes/console.php`)

**Purpose:** Parses DNS log file and creates BrowsingLog records

### How It Works

1. Reads `/var/log/dnsmasq.log` file
2. Parses each line for DNS query entries
3. Extracts domain and source IP address
4. Matches IP address to device in database
5. Creates BrowsingLog record with domain and timestamp
6. Skips duplicate entries

### Manual Execution

**Queue (Asynchronous - few seconds delay):**
```bash
php artisan tinker
```

```php
App\Jobs\ParseNetworkLogs::dispatch();
exit
```

**Synchronous (Immediate - for testing):**
```bash
php artisan tinker
```

```php
$job = new App\Jobs\ParseNetworkLogs();
$job->handle();
exit
```

### Job Logs

Check job execution:
```bash
tail -50 storage/logs/laravel.log | grep ParseNetworkLogs
```

Expected output:
```
[2025-12-22 05:20:02] production.INFO: ParseNetworkLogs job started - parsing network traffic logs
[2025-12-22 05:20:06] production.INFO: ParseNetworkLogs job completed {"log_path":"/var/log/dnsmasq.log","entries_processed":473,"entries_created":473,"entries_skipped":2148}
```

---

## Performance Considerations

### Resource Usage

- **CPU**: Minimal (DNS logging is lightweight)
- **Memory**: ~10-50MB for log forwarder service
- **Disk**: ~1-5MB per day (with log rotation)
- **Network**: No impact (only reads logs)

### Log File Size

With log rotation (7 days, daily rotation):
- **Daily size**: ~1-5MB depending on traffic
- **Weekly size**: ~7-35MB (compressed: ~2-10MB)
- **Monthly size**: ~30-150MB (compressed: ~10-50MB)

### ParseNetworkLogs Job Performance

- **Processing time**: 100-500ms for typical log files
- **Frequency**: Every 10 minutes
- **Impact**: Minimal (runs in background)

---

## Security Considerations

### Log File Permissions

- **Owner**: `root:www-data`
- **Permissions**: `644` (readable by web server, writable by root)
- **Location**: `/var/log/dnsmasq.log` (system log directory)

### Data Privacy

- **Log Retention**: 7 days by default (configurable in logrotate)
- **Database Retention**: No automatic deletion (manual cleanup required)
- **Access Control**: Only parents can view logs for their own devices

### Recommendations

1. **Regular Cleanup**: Periodically delete old browsing logs from database
2. **Log Rotation**: Keep log rotation enabled to prevent disk space issues
3. **Access Control**: Ensure proper authentication and authorization
4. **Data Encryption**: Consider encrypting database if storing sensitive data

---

## Related Documentation

- **ParseNetworkLogs Job**: `docs/BACKGROUND_JOBS_PARSE_NETWORK_LOGS.md`
- **Background Jobs Overview**: `docs/BACKGROUND_JOBS_OVERVIEW.md`
- **Raspberry Pi Services**: `docs/RASPBERRY_PI_SERVICES_SETUP.md`
- **Queue Worker Setup**: `docs/TEST_PHASE_5_RESULTS.md`

---

## Quick Reference Commands

### Service Management
```bash
# Check status
sudo systemctl status dnsmasq-log-forwarder.service

# Restart service
sudo systemctl restart dnsmasq-log-forwarder.service

# View logs
sudo journalctl -u dnsmasq-log-forwarder.service -n 50
```

### DNS Logging
```bash
# View DNS log
sudo tail -50 /var/log/dnsmasq.log

# Clear log
sudo truncate -s 0 /var/log/dnsmasq.log

# Follow logs in real-time
sudo tail -f /var/log/dnsmasq.log
```

### Testing
```bash
# Run parser manually
cd /var/www/parental_wifi
php artisan tinker
App\Jobs\ParseNetworkLogs::dispatch();
exit

# Check results
php artisan tinker
App\Models\BrowsingLog::count();
exit
```

### Verification
```bash
# Check services
sudo systemctl status dnsmasq
sudo systemctl status dnsmasq-log-forwarder.service

# Check configuration
grep -E "log-queries|log-facility" /etc/dnsmasq.conf
cat /etc/systemd/system/dnsmasq-log-forwarder.service

# Check Laravel config
php artisan tinker
config('network.log_path');
exit
```

---

## Summary

This reference guide covers the complete setup and maintenance of browsing logs using DNS logging. The system:

✅ **Captures all DNS queries** from devices  
✅ **Extracts domain names** reliably (HTTP and HTTPS)  
✅ **Stores browsing history** in database  
✅ **Displays in dashboard** for parent review  
✅ **Runs automatically** every 10 minutes  
✅ **Handles log rotation** to prevent disk space issues  

**For questions or issues, refer to the Troubleshooting section above.**

---

**Last Updated:** December 2025  
**Tested On:** Raspberry Pi OS (Debian-based)  
**Status:** ✅ Production Ready

