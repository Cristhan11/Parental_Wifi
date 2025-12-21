# Browsing Logs Setup Guide

## Overview

This guide explains how to set up network logging so that browsing logs can be captured and displayed in the dashboard. **Network logging must be configured before browsing logs will appear in the dashboard.**

## How It Works

1. **Network Traffic Capture**: tcpdump or iptables logs network traffic to a file
2. **Log File**: Traffic is written to `/var/log/tcpdump/network.log` (default)
3. **ParseNetworkLogs Job**: Runs every 10 minutes, reads the log file, extracts browsing information
4. **Database Storage**: Creates BrowsingLog records in the database
5. **Dashboard Display**: Browsing logs appear in the dashboard at `/browsing-logs`

## Setup Required: YES ✅

**You MUST set up network logging first.** The system does NOT automatically capture network traffic. Without this setup, the log file won't exist, and the ParseNetworkLogs job will skip processing (no errors, but no logs created).

## Step 1: Choose Logging Method

You have two options:

### Option A: tcpdump (Recommended for Testing)

tcpdump captures network packets and can log HTTP traffic.

### Option B: iptables Logging

iptables can log network traffic using LOG rules.

## Step 2: Setup tcpdump Logging (Recommended)

### 2.1 Create Log Directory

```bash
sudo mkdir -p /var/log/tcpdump
sudo chown pi:pi /var/log/tcpdump
```

### 2.2 Start tcpdump Capture

**Method 1: Continuous logging (text format)**

```bash
# Start tcpdump to capture HTTP/HTTPS traffic on wlan0 interface
sudo tcpdump -i wlan0 -n -A 'tcp port 80 or tcp port 443' > /var/log/tcpdump/network.log 2>&1
```

**Method 2: Run as background service**

Create a systemd service to run tcpdump automatically:

```bash
sudo nano /etc/systemd/system/tcpdump-logging.service
```

Add this content:

```ini
[Unit]
Description=tcpdump Network Logging
After=network.target

[Service]
Type=simple
ExecStart=/usr/sbin/tcpdump -i wlan0 -n -A 'tcp port 80 or tcp port 443' -w /var/log/tcpdump/network.log
Restart=always
User=root

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl enable tcpdump-logging.service
sudo systemctl start tcpdump-logging.service
```

**Important Notes:**
- tcpdump captures raw packets, which may not always include full URLs
- For better URL capture, consider using iptables logging instead
- Log files can grow large - implement log rotation

### 2.3 Set File Permissions

```bash
sudo chmod 644 /var/log/tcpdump/network.log
sudo chown pi:www-data /var/log/tcpdump/network.log
```

## Step 3: Setup iptables Logging (Alternative)

### 3.1 Add iptables LOG Rules

```bash
# Log HTTP traffic (port 80)
sudo iptables -I FORWARD -i wlan0 -p tcp --dport 80 -j LOG --log-prefix "HTTP: " --log-level 4

# Log HTTPS traffic (port 443)
sudo iptables -I FORWARD -i wlan0 -p tcp --dport 443 -j LOG --log-prefix "HTTPS: " --log-level 4
```

### 3.2 Configure rsyslog

```bash
# Edit rsyslog configuration
sudo nano /etc/rsyslog.conf
```

Add this line:

```
kern.info /var/log/iptables.log
```

Restart rsyslog:

```bash
sudo systemctl restart rsyslog
```

### 3.3 Update Laravel Config

If using iptables logging, update the log path in `.env`:

```env
NETWORK_LOG_PATH=/var/log/iptables.log
```

**Note**: iptables logs may not include full URLs, only IP addresses and MAC addresses. The parser will attempt to extract domains where possible.

## Step 4: Configure Laravel Application

### 4.1 Set Log Path in .env

```env
NETWORK_LOG_PATH=/var/log/tcpdump/network.log
```

### 4.2 Create Config File (if not exists)

Create `config/network.php`:

```php
<?php

return [
    'log_path' => env('NETWORK_LOG_PATH', '/var/log/tcpdump/network.log'),
];
```

### 4.3 Verify Queue Worker is Running

```bash
sudo systemctl status parental-wifi-queue
```

If not running:

```bash
sudo systemctl start parental-wifi-queue
sudo systemctl enable parental-wifi-queue
```

## Step 5: Test the Setup

### 5.1 Verify Log File is Being Created

```bash
# Check if log file exists and has content
ls -lh /var/log/tcpdump/network.log
tail -20 /var/log/tcpdump/network.log
```

### 5.2 Generate Test Traffic

On the child device connected to WiFi:
- Browse to some HTTP websites (e.g., `http://example.com`, `http://www.google.com`)
- Wait 1-2 minutes

### 5.3 Manually Trigger Log Parsing (Optional)

Instead of waiting 10 minutes, manually run the job:

```bash
cd /path-to-your-project
php artisan tinker
```

Then in tinker:

```php
App\Jobs\ParseNetworkLogs::dispatch();
exit
```

### 5.4 Check Application Logs

```bash
tail -50 storage/logs/laravel.log | grep ParseNetworkLogs
```

Look for:
- `ParseNetworkLogs job started`
- `ParseNetworkLogs job completed` with `entries_created` count

### 5.5 Verify Browsing Logs in Dashboard

1. Login to dashboard
2. Navigate to `/browsing-logs`
3. Filter by your device
4. Verify browsing logs appear

## Troubleshooting

### Problem: No logs appear in dashboard

**Check 1: Log file exists?**
```bash
ls -la /var/log/tcpdump/network.log
```

If file doesn't exist:
- Verify tcpdump/iptables is running
- Check for errors in system logs

**Check 2: Log file has content?**
```bash
tail -50 /var/log/tcpdump/network.log
```

If empty:
- Verify device is browsing websites
- Check tcpdump/iptables is capturing traffic

**Check 3: Job is running?**
```bash
tail -100 storage/logs/laravel.log | grep ParseNetworkLogs
```

Look for "job completed" messages with entry counts.

**Check 4: MAC address matches?**
```bash
# Get device MAC address
ip link show wlan0 | grep -oP '(?<=link/ether )[^ ]+'

# Check database
php artisan tinker
App\Models\Device::where('mac_address', 'YOUR_MAC')->first();
```

MAC addresses must match exactly (format is normalized).

**Check 5: Database has logs?**
```bash
php artisan tinker
App\Models\BrowsingLog::count();
App\Models\BrowsingLog::latest()->take(5)->get(['url', 'domain', 'visited_at']);
```

### Problem: Job runs but creates no logs

**Possible causes:**
1. Log format doesn't match parser expectations
2. MAC addresses not found in log entries
3. URLs not extractable from log format
4. Device not found in database (MAC address mismatch)

**Solution:**
- Check log format matches parser expectations
- Verify log entries contain MAC addresses and URLs
- Test parser directly with sample log entries

### Problem: Permission denied errors

```bash
# Fix log file permissions
sudo chmod 644 /var/log/tcpdump/network.log
sudo chown pi:www-data /var/log/tcpdump/network.log

# Verify web server user can read
sudo -u www-data cat /var/log/tcpdump/network.log
```

## Important Notes

1. **Log Format**: The parser expects log entries with MAC addresses and URLs. Different log formats may require parser adjustments.

2. **MAC Address Format**: MAC addresses are normalized (colons or dashes accepted). Ensure device MAC address in database matches format in logs.

3. **HTTPS Limitations**: HTTPS traffic is encrypted, so full URLs may not be captured. Only domain names (from SNI) might be available.

4. **Log Rotation**: Log files can grow large. Implement log rotation to prevent disk space issues:

```bash
sudo nano /etc/logrotate.d/tcpdump
```

Add:
```
/var/log/tcpdump/network.log {
    daily
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 pi www-data
}
```

5. **Performance**: Large log files can slow parsing. Consider log rotation or incremental parsing.

## Expected Results

After setup:
- ✅ Log file exists and contains network traffic entries
- ✅ ParseNetworkLogs job runs every 10 minutes (or manually triggered)
- ✅ BrowsingLog records created in database
- ✅ Dashboard shows browsing logs when filtering by device
- ✅ Device detail page shows recent website visits

## Quick Test Checklist

- [ ] Log file exists: `/var/log/tcpdump/network.log`
- [ ] Log file has content (traffic entries)
- [ ] Queue worker is running
- [ ] ParseNetworkLogs job executes (check Laravel logs)
- [ ] BrowsingLog records exist in database
- [ ] Dashboard shows browsing logs for device

## Summary

**Answer: YES, you need to set up network logging first.**

The system does NOT automatically capture network traffic. You must:
1. Configure tcpdump or iptables to log network traffic
2. Ensure log file is created and has content
3. Wait for ParseNetworkLogs job to process logs (runs every 10 minutes)
4. Check dashboard for browsing logs

Without network logging setup, browsing logs will NOT appear in the dashboard, even if devices are connected and browsing websites.

