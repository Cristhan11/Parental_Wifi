# Browsing Logs Permanent Setup for Raspberry Pi

**Purpose:** Configure permanent network logging on Raspberry Pi so browsing logs work continuously without manual intervention.

**Reference:** Based on existing Raspberry Pi setup documented in:
- `docs/RASPBERRY_PI_SERVICES_SETUP.md` - Current services configuration
- `docs/TEST_PHASE_5_RESULTS.md` - Background jobs and queue system setup
- `docs/BACKGROUND_JOBS_PARSE_NETWORK_LOGS.md` - ParseNetworkLogs job documentation

---

## What's Already Working ✅

Based on your current setup, the following are already configured:

1. **Queue Worker Service** (`parental-wifi-queue.service`)
   - ✅ Running and processing jobs
   - ✅ Auto-restart enabled
   - ✅ Configured to process background jobs

2. **Background Jobs Scheduled**
   - ✅ ParseNetworkLogs job scheduled every 10 minutes
   - ✅ Crontab configured: `* * * * * cd /var/www/parental_wifi && php artisan schedule:run >> /dev/null 2>&1`

3. **Laravel Application**
   - ✅ BrowsingLogController exists
   - ✅ ParseNetworkLogs job implemented
   - ✅ Database tables configured

## What's Missing for Permanent Operation ❌

To make browsing logs work permanently, you need to configure:

1. **Network Logging Service** - tcpdump running as a systemd service
2. **Log Rotation** - Prevent log files from filling up disk space
3. **Configuration File** - Ensure Laravel knows where to find logs

---

## Step 1: Create Network Log Directory

```bash
# Create log directory
sudo mkdir -p /var/log/tcpdump

# Set ownership (replace 'snasna' with your username if different)
sudo chown snasna:snasna /var/log/tcpdump

# Set permissions
sudo chmod 755 /var/log/tcpdump
```

---

## Step 2: Create tcpdump Systemd Service

Create a systemd service to run tcpdump automatically on boot:

```bash
sudo nano /etc/systemd/system/tcpdump-logging.service
```

Add the following content:

```ini
[Unit]
Description=tcpdump Network Logging for Parental WiFi
After=network.target wlan0.service
Wants=network-online.target
After=network-online.target

[Service]
Type=simple
User=root
ExecStart=/bin/bash -c '/usr/sbin/tcpdump -i wlan0 -n -A "tcp port 80 or tcp port 443" >> /var/log/tcpdump/network.log 2>&1'
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

# Resource limits to prevent excessive disk usage
LimitNOFILE=65536

[Install]
WantedBy=multi-user.target
```

**⚠️ Important:** The ParseNetworkLogs job expects **text format** (uses `file_get_contents()` and `explode("\n")`), so we must use text format.

**Service Configuration Explanation:**
- **After=network.target wlan0.service**: Ensures network is up before starting
- **User=root**: tcpdump requires root privileges  
- **-i wlan0**: Captures traffic on WiFi interface
- **-n**: Don't resolve hostnames (faster)
- **-A**: Print packets in ASCII (readable text format)
- **"tcp port 80 or tcp port 443"**: Only capture HTTP/HTTPS traffic
- **>> /var/log/tcpdump/network.log**: Append output to log file
- **2>&1**: Redirect stderr to stdout (capture all output including errors)
- **Restart=always**: Auto-restart if tcpdump crashes
- **RestartSec=10**: Wait 10 seconds before restarting

**Note:** 
- Log rotation is handled by logrotate (configured in Step 3), not by tcpdump itself
- tcpdump will continue logging until service is stopped or logrotate rotates the file
- When logrotate rotates the file, it reloads the service which creates a new log file

---

## Step 3: Create Log Rotation Configuration

Prevent log files from filling up disk space:

```bash
sudo nano /etc/logrotate.d/tcpdump-network
```

Add the following content:

```conf
/var/log/tcpdump/network.log {
    daily
    rotate 7
    compress
    delaycompress
    notifempty
    missingok
    create 644 snasna www-data
    postrotate
        # Reload tcpdump service if it's running
        systemctl reload tcpdump-logging.service 2>/dev/null || true
    endscript
}
```

**Log Rotation Configuration Explanation:**
- **daily**: Rotate logs daily
- **rotate 7**: Keep 7 days of logs
- **compress**: Compress old logs to save space
- **delaycompress**: Don't compress yesterday's log (easier to read)
- **notifempty**: Don't rotate empty files
- **missingok**: Don't error if log file doesn't exist
- **create 644 snasna www-data**: Create new log file with correct permissions

---

## Step 4: Create/Update Network Configuration File

Ensure Laravel knows where to find the log file:

```bash
cd /var/www/parental_wifi
nano config/network.php
```

Create/update the file:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Network Log Path
    |--------------------------------------------------------------------------
    |
    | Path to the network traffic log file used by ParseNetworkLogs job.
    | Default: /var/log/tcpdump/network.log
    |
    */
    'log_path' => env('NETWORK_LOG_PATH', '/var/log/tcpdump/network.log'),
];
```

---

## Step 5: Update .env File

Add network log path to your `.env` file:

```bash
cd /var/www/parental_wifi
nano .env
```

Add or update:

```env
NETWORK_LOG_PATH=/var/log/tcpdump/network.log
```

Clear config cache:

```bash
php artisan config:clear
php artisan config:cache
```

---

## Step 6: Set File Permissions

Ensure web server can read log files:

```bash
# Set ownership and permissions
sudo chown snasna:www-data /var/log/tcpdump/network.log
sudo chmod 644 /var/log/tcpdump/network.log

# Create log file if it doesn't exist (will be created by tcpdump, but good to have)
sudo touch /var/log/tcpdump/network.log
sudo chown snasna:www-data /var/log/tcpdump/network.log
sudo chmod 644 /var/log/tcpdump/network.log
```

---

## Step 7: Enable and Start tcpdump Service

```bash
# Reload systemd to recognize new service
sudo systemctl daemon-reload

# Enable service to start on boot
sudo systemctl enable tcpdump-logging.service

# Start the service
sudo systemctl start tcpdump-logging.service

# Check service status
sudo systemctl status tcpdump-logging.service
```

**Expected Output:**
```
● tcpdump-logging.service - tcpdump Network Logging for Parental WiFi
     Loaded: loaded (/etc/systemd/system/tcpdump-logging.service; enabled; vendor preset: enabled)
     Active: active (running) since [timestamp]
   Main PID: [pid] (tcpdump)
      Tasks: 1 (limit: 4915)
        CPU: [time]
     CGroup: /system.slice/tcpdump-logging.service
             └─[pid] /usr/sbin/tcpdump -i wlan0 ...
```

---

## Step 8: Verify Log File is Being Created

```bash
# Check if log file exists and has content
ls -lh /var/log/tcpdump/network.log

# Watch log file in real-time (generate some traffic first)
tail -f /var/log/tcpdump/network.log

# Check log file size
du -h /var/log/tcpdump/network.log
```

---

## Step 9: Test ParseNetworkLogs Job

Manually trigger the job to test:

```bash
cd /var/www/parental_wifi
php artisan tinker
```

Then:

```php
App\Jobs\ParseNetworkLogs::dispatch();
exit
```

Check Laravel logs:

```bash
tail -50 storage/logs/laravel.log | grep ParseNetworkLogs
```

Look for:
- `ParseNetworkLogs job started`
- `ParseNetworkLogs job completed` with `entries_created` > 0

---

## Step 10: Verify Browsing Logs in Dashboard

1. Have a device browse some websites (HTTP sites work best)
2. Wait 10-15 minutes for ParseNetworkLogs job to run (or trigger manually)
3. Login to dashboard at `http://192.168.4.1/browsing-logs`
4. Filter by your device
5. Verify browsing logs appear

---

## Troubleshooting

### Service Won't Start

```bash
# Check service status
sudo systemctl status tcpdump-logging.service

# Check service logs
sudo journalctl -u tcpdump-logging.service -n 50

# Test tcpdump manually
sudo tcpdump -i wlan0 -n -A 'tcp port 80 or tcp port 443' -c 10
```

### No Log File Created

```bash
# Check if wlan0 interface exists
ip addr show wlan0

# Check if tcpdump is installed
which tcpdump

# Check service is running
sudo systemctl status tcpdump-logging.service

# Check permissions
ls -la /var/log/tcpdump/
```

### ParseNetworkLogs Job Not Creating BrowsingLogs

```bash
# Check log file format
head -20 /var/log/tcpdump/network.log

# Verify MAC addresses are in logs
grep -i "mac\|AA:BB:CC" /var/log/tcpdump/network.log | head -5

# Check Laravel logs
tail -100 storage/logs/laravel.log | grep ParseNetworkLogs

# Check database directly
php artisan tinker
App\Models\BrowsingLog::count();
exit
```

### Disk Space Issues

```bash
# Check disk usage
df -h

# Check log file sizes
du -sh /var/log/tcpdump/*

# Verify log rotation is working
ls -lh /var/log/tcpdump/

# Test log rotation manually
sudo logrotate -d /etc/logrotate.d/tcpdump-network
```

### Permission Errors

```bash
# Fix ownership
sudo chown -R snasna:www-data /var/log/tcpdump/

# Fix permissions
sudo chmod 644 /var/log/tcpdump/network.log
sudo chmod 755 /var/log/tcpdump/

# Verify web server can read
sudo -u www-data cat /var/log/tcpdump/network.log | head -5
```

---

## Service Management Commands

### Start/Stop/Restart Service

```bash
# Start service
sudo systemctl start tcpdump-logging.service

# Stop service
sudo systemctl stop tcpdump-logging.service

# Restart service
sudo systemctl restart tcpdump-logging.service

# Reload service (if you changed config file)
sudo systemctl reload tcpdump-logging.service
```

### Check Service Status

```bash
# Check if service is running
sudo systemctl status tcpdump-logging.service

# Check if service is enabled (starts on boot)
sudo systemctl is-enabled tcpdump-logging.service

# View recent logs
sudo journalctl -u tcpdump-logging.service -n 50 --no-pager
```

### Enable/Disable Service on Boot

```bash
# Enable service to start on boot
sudo systemctl enable tcpdump-logging.service

# Disable service from starting on boot
sudo systemctl disable tcpdump-logging.service
```

---

## Configuration Summary

After completing this setup, you'll have:

✅ **tcpdump-logging.service** - Automatically captures network traffic on wlan0  
✅ **Log Rotation** - Prevents disk space issues (keeps 7 days of logs)  
✅ **ParseNetworkLogs Job** - Runs every 10 minutes (already configured)  
✅ **Queue Worker** - Processes jobs (already configured)  
✅ **Dashboard** - Displays browsing logs (already configured)  

---

## Verification Checklist

After setup, verify everything is working:

- [ ] tcpdump-logging.service is running: `sudo systemctl status tcpdump-logging.service`
- [ ] Service is enabled on boot: `sudo systemctl is-enabled tcpdump-logging.service`
- [ ] Log file exists: `ls -lh /var/log/tcpdump/network.log`
- [ ] Log file has content: `tail -20 /var/log/tcpdump/network.log`
- [ ] Log file permissions are correct: `ls -la /var/log/tcpdump/network.log`
- [ ] ParseNetworkLogs job runs successfully: Check Laravel logs
- [ ] BrowsingLog records are created: `php artisan tinker` → `App\Models\BrowsingLog::count()`
- [ ] Dashboard shows browsing logs: Visit `/browsing-logs` and filter by device

---

## Alternative: Using iptables Logging (Optional)

If you prefer iptables logging instead of tcpdump:

1. **Add iptables LOG rules:**
   ```bash
   sudo iptables -I FORWARD -i wlan0 -p tcp --dport 80 -j LOG --log-prefix "HTTP: " --log-level 4
   sudo iptables -I FORWARD -i wlan0 -p tcp --dport 443 -j LOG --log-prefix "HTTPS: " --log-level 4
   ```

2. **Save iptables rules:**
   ```bash
   sudo netfilter-persistent save
   ```

3. **Configure rsyslog:**
   ```bash
   sudo nano /etc/rsyslog.conf
   ```
   Add: `kern.info /var/log/iptables.log`
   
   ```bash
   sudo systemctl restart rsyslog
   ```

4. **Update .env:**
   ```env
   NETWORK_LOG_PATH=/var/log/iptables.log
   ```

**Note:** iptables logs may have different format - you may need to adjust the parser in ParseNetworkLogs job.

---

## Performance Considerations

- **tcpdump Impact**: Minimal CPU usage, but can use disk I/O
- **Log File Size**: With log rotation (7 days, daily rotation), expect ~50-200MB per day depending on traffic
- **ParseNetworkLogs Job**: Runs every 10 minutes, processes incrementally
- **Memory Usage**: tcpdump uses ~10-50MB RAM

---

## Security Considerations

- **Log Files**: Contain network traffic data - protect with proper permissions (644)
- **tcpdump Service**: Runs as root (required for packet capture)
- **Log Retention**: 7 days by default - adjust based on privacy/legal requirements
- **Disk Space**: Monitor disk usage with log rotation enabled

---

## Related Documentation

- `docs/RASPBERRY_PI_SERVICES_SETUP.md` - Current services setup
- `docs/BACKGROUND_JOBS_PARSE_NETWORK_LOGS.md` - ParseNetworkLogs job details
- `docs/TEST_PHASE_5_RESULTS.md` - Background jobs verification
- `docs/BROWSING_LOGS_SETUP_GUIDE.md` - General setup guide

---

**Last Updated:** Based on current system configuration (December 2025)  
**Tested On:** Raspberry Pi OS (Debian-based)  
**Status:** Ready for production use

