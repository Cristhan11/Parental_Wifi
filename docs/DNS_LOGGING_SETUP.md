# DNS Logging Setup for Browsing History

**Purpose:** Configure DNS logging to capture all domain names visited by devices. This is more reliable than extracting SNI from encrypted HTTPS traffic.

**Why DNS Logging?**
- ✅ Captures all domain lookups (HTTP and HTTPS)
- ✅ Simple and reliable (plain text logs)
- ✅ Works with existing dnsmasq setup
- ✅ Shows actual domains visited (google.com, youtube.com, etc.)
- ✅ No need to parse encrypted traffic

---

## Step 1: Enable DNS Query Logging in dnsmasq

Edit the dnsmasq configuration:

```bash
sudo nano /etc/dnsmasq.conf
```

Add these lines (or uncomment if they exist):

```conf
# Enable DNS query logging
log-queries

# Log file location
log-facility=/var/log/dnsmasq.log
```

**Configuration Explanation:**
- **log-queries**: Enables logging of all DNS queries
- **log-facility**: Specifies log file location (use file path, not syslog facility)

---

## Step 2: Create DNS Log Directory and File

```bash
# Create log directory (if it doesn't exist)
sudo mkdir -p /var/log

# Create log file
sudo touch /var/log/dnsmasq.log

# Set permissions (dnsmasq runs as root, but Laravel needs to read it)
sudo chown root:root /var/log/dnsmasq.log
sudo chmod 644 /var/log/dnsmasq.log
```

---

## Step 3: Restart dnsmasq Service

```bash
# Restart dnsmasq to apply configuration
sudo systemctl restart dnsmasq

# Check if service is running
sudo systemctl status dnsmasq

# Check if logging is working
tail -f /var/log/dnsmasq.log
```

You should see DNS queries appearing in the log file.

---

## Step 4: Configure Log Rotation

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
    create 644 root root
    postrotate
        # Reload dnsmasq if it's running
        systemctl reload dnsmasq 2>/dev/null || true
    endscript
}
```

---

## Step 5: Update Laravel Configuration

Update the network log path to use DNS logs:

```bash
cd /var/www/parental_wifi
nano .env
```

Add or update:

```env
NETWORK_LOG_PATH=/var/log/dnsmasq.log
```

Then clear and cache config:

```bash
php artisan config:clear
php artisan config:cache
```

---

## Step 6: Test DNS Logging

```bash
# Clear the log
sudo truncate -s 0 /var/log/dnsmasq.log

# Browse to some sites on your device (youtube.com, google.com, etc.)
# Wait 30 seconds

# Check the log
tail -20 /var/log/dnsmasq.log
```

You should see entries like:
```
Dec 22 04:30:15 dnsmasq[1234]: query[A] google.com from 192.168.4.31
Dec 22 04:30:16 dnsmasq[1234]: query[A] youtube.com from 192.168.4.31
```

---

## DNS Log Format

dnsmasq logs DNS queries in this format:

```
Dec 22 04:30:15 dnsmasq[1234]: query[A] google.com from 192.168.4.31
Dec 22 04:30:16 dnsmasq[1234]: query[AAAA] youtube.com from 192.168.4.31
Dec 22 04:30:17 dnsmasq[1234]: cached google.com is 142.250.191.14
```

**Format Breakdown:**
- **Timestamp**: `Dec 22 04:30:15`
- **Service**: `dnsmasq[1234]`
- **Query Type**: `query[A]` (A record) or `query[AAAA]` (IPv6)
- **Domain**: `google.com`
- **Source IP**: `from 192.168.4.31`

**Note:** The ParseNetworkLogs job will parse this format and extract:
- Domain name (google.com, youtube.com)
- Source IP address (to match to device)
- Timestamp

---

## Advantages Over tcpdump SNI Extraction

| Feature | DNS Logging | tcpdump SNI |
|---------|-------------|-------------|
| **Reliability** | ✅ Very reliable | ❌ May not work with all HTTPS |
| **Setup Complexity** | ✅ Simple (just enable logging) | ❌ Complex (needs SNI parsing) |
| **Captures HTTP** | ✅ Yes | ✅ Yes |
| **Captures HTTPS** | ✅ Yes (via DNS lookup) | ⚠️ Maybe (depends on SNI visibility) |
| **Performance** | ✅ Low overhead | ⚠️ Higher overhead |
| **Domain Accuracy** | ✅ 100% accurate | ⚠️ May miss some domains |

---

## Next Steps

After setting up DNS logging:

1. **Update ParseNetworkLogs Job**: The job will automatically parse DNS logs (it already supports multiple log formats)
2. **Test**: Browse some sites and run `App\Jobs\ParseNetworkLogs::dispatch()`
3. **Verify**: Check `App\Models\BrowsingLog::count()` to see captured domains

---

## Troubleshooting

**Problem: No DNS queries in log**
- Check dnsmasq is running: `sudo systemctl status dnsmasq`
- Check config: `grep log-queries /etc/dnsmasq.conf`
- Check permissions: `ls -la /var/log/dnsmasq.log`

**Problem: Log file not updating**
- Restart dnsmasq: `sudo systemctl restart dnsmasq`
- Check dnsmasq logs: `sudo journalctl -u dnsmasq -n 50`

**Problem: ParseNetworkLogs not finding domains**
- Check log format matches expected format
- Check MAC address matching (DNS logs have IP, need to match to device via ARP table)

