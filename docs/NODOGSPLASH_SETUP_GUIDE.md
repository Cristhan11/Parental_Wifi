# NoDogSplash Installation and Setup Guide

## Overview

This guide provides step-by-step instructions for installing and configuring NoDogSplash on your Raspberry Pi 4B running Raspberry Pi OS Lite (64-bit). NoDogSplash is the captive portal software that will intercept HTTP requests and redirect devices to our portal page when their internet time expires.

## Prerequisites

Before starting, ensure you have:

- ✅ Raspberry Pi 4B with Raspberry Pi OS Lite (64-bit) installed
- ✅ WiFi Access Point configured and running (hostapd, dnsmasq, dhcpcd)
- ✅ Laravel application deployed at `/var/www/parental_wifi`
- ✅ Network configuration completed (wlan0 interface configured as Access Point)
- ✅ SSH access to Raspberry Pi (or direct terminal access)

## Network Configuration Reference

Based on your existing setup:
- **WiFi Interface:** `wlan0`
- **Access Point IP:** `192.168.4.1/24`
- **SSID:** `Parental_WiFi`
- **DHCP Range:** `192.168.4.2` to `192.168.4.51`
- **Gateway:** `192.168.4.1` (the Pi itself)
- **DNS Server:** `192.168.4.1` (the Pi itself, via dnsmasq)

---

## Step 1: Install NoDogSplash

### 1.1 Update Package List

```bash
sudo apt update
```

**What this does:**
- Updates the list of available packages from repositories
- Ensures you get the latest version information

### 1.2 Install NoDogSplash

```bash
sudo apt install nodogsplash -y
```

**What this does:**
- **`sudo apt install`** - Package manager install command with administrator privileges
- **`nodogsplash`** - The captive portal software package
- **`-y`** - Automatically answer "yes" to prompts (non-interactive)

**Expected Output:**
```
Reading package lists... Done
Building dependency tree... Done
...
Setting up nodogsplash (X.X.X) ...
```

### 1.3 Verify Installation

```bash
which nodogsplash
nodogsplash --version
```

**What to check:**
- **`which nodogsplash`** - Should return: `/usr/sbin/nodogsplash` or similar path
- **`nodogsplash --version`** - Should display version number (e.g., `4.11.0`)

**If commands fail:**
- Installation may have failed, re-run `sudo apt install nodogsplash -y`
- Check for error messages in the installation output

---

## Step 2: Stop NoDogSplash Service (Temporary)

NoDogSplash starts automatically after installation. We need to stop it temporarily while we configure it.

```bash
sudo systemctl stop nodogsplash
sudo systemctl disable nodogsplash
```

**What this does:**
- **`systemctl stop nodogsplash`** - Stops the running service immediately
- **`systemctl disable nodogsplash`** - Prevents service from starting automatically on boot
- We'll enable it again after configuration is complete

**Why stop it now?**
- We need to modify configuration files
- We want to test configuration before enabling it permanently
- Avoids conflicts with existing network setup

**Verify it's stopped:**
```bash
sudo systemctl status nodogsplash
```

**Expected output:** Should show `inactive (dead)` or `disabled`

---

## Step 3: Backup Default Configuration

Always backup default configurations before modifying them.

```bash
sudo cp /etc/nodogsplash/nodogsplash.conf /etc/nodogsplash/nodogsplash.conf.orig
```

**What this does:**
- **`cp`** - Copy command
- Creates backup file with `.orig` extension
- Allows you to restore defaults if needed

**Verify backup:**
```bash
ls -la /etc/nodogsplash/
```

You should see both `nodogsplash.conf` and `nodogsplash.conf.orig`

---

## Step 4: Configure NoDogSplash

### 4.1 Open Configuration File

```bash
sudo nano /etc/nodogsplash/nodogsplash.conf
```

**What this does:**
- **`sudo`** - Opens file with administrator privileges (required for system config files)
- **`nano`** - Simple text editor (alternative: `vi` or `vim`)
- Opens the configuration file for editing

### 4.2 Configure Basic Settings

Find and modify these settings in the config file:

```ini
# Gateway Interface (WiFi interface)
GatewayInterface wlan0

# Gateway Address (Access Point IP)
GatewayAddress 192.168.4.1

# Internet Interface (Ethernet interface for internet access)
InternetInterface eth0

# Gateway Name (SSID)
GatewayName Parental_WiFi

# Gateway FQDN (Fully Qualified Domain Name)
GatewayFQDN parentalwifi.local

# Max Clients (maximum number of connected devices)
MaxClients 50

# AuthIdleTimeout (timeout for portal authentication in seconds)
AuthIdleTimeout 480

# ClientIdleTimeout (timeout for client session in seconds)
ClientIdleTimeout 480
```

### 4.3 Configuration Explained

**GatewayInterface wlan0**
- Specifies which network interface NoDogSplash monitors
- Should match your WiFi Access Point interface

**GatewayAddress 192.168.4.1**
- The IP address of your Raspberry Pi on the WiFi network
- Must match your Access Point IP configuration

**InternetInterface eth0**
- The interface connected to the internet (Ethernet)
- NoDogSplash will forward traffic through this interface

**GatewayName Parental_WiFi**
- Display name for the captive portal
- Can be customized to match your SSID

**MaxClients 50**
- Maximum number of devices that can be connected simultaneously
- Should match your DHCP range capacity

**AuthIdleTimeout 480**
- How long (in seconds) a device can remain on portal page without action
- 480 seconds = 8 minutes

**ClientIdleTimeout 480**
- How long (in seconds) an authenticated client can remain idle
- 480 seconds = 8 minutes

### 4.4 Save Configuration

In nano editor:
1. Press `Ctrl + O` to save
2. Press `Enter` to confirm filename
3. Press `Ctrl + X` to exit

---

## Step 5: Configure Portal Pages Location

### 5.1 Create Portal Directory

```bash
sudo mkdir -p /etc/nodogsplash/htdocs
```

**What this does:**
- Creates directory for custom portal pages
- NoDogSplash will serve portal pages from this location

### 5.2 Configure Portal Path in Config

Open config file again:
```bash
sudo nano /etc/nodogsplash/nodogsplash.conf
```

Add or modify this setting:
```ini
# Portal Pages Directory
PortalPagesPath /etc/nodogsplash/htdocs

# OR use your Laravel application portal (recommended)
# We'll redirect to Laravel portal instead
```

**Note:** For our implementation, we'll redirect to Laravel's portal routes rather than using static portal pages. However, we still need to configure the redirect.

### 5.3 Configure Redirect to Laravel Portal

Add this configuration:
```ini
# Redirect authenticated clients to Laravel portal
# This will be managed by our scripts, but we need basic redirect configuration
RedirectURL http://192.168.4.1/portal
```

---

## Step 6: Configure Firewall Rules (IPTables Integration)

NoDogSplash needs to work with your existing iptables configuration. It will add its own rules to manage captive portal traffic.

### 6.1 Check Current IPTables Rules

```bash
sudo iptables -L -n -v
```

**What this does:**
- **`iptables -L`** - List all rules
- **`-n`** - Show numeric addresses (don't resolve hostnames)
- **`-v`** - Verbose (show packet/byte counts)

**Review output:** Note any existing rules for wlan0 and eth0 interfaces

### 6.2 NoDogSplash IPTables Integration

NoDogSplash automatically adds iptables rules when it starts. It uses these chains:
- **nodogsplash_authed** - Authenticated clients (allowed through)
- **nodogsplash_preauth** - Unauthenticated clients (redirected to portal)

**Important:** NoDogSplash must be started AFTER your existing iptables rules are set up, or it may conflict.

### 6.3 Configure IPTables Script Order

If you have a script that sets up iptables on boot, ensure NoDogSplash starts after it:

```bash
sudo systemctl list-dependencies nodogsplash.service
```

Check what NoDogSplash depends on and ensure network is fully configured before it starts.

---

## Step 7: Test Configuration (Before Starting Service)

### 7.1 Validate Configuration File Syntax

```bash
sudo nodogsplash -c /etc/nodogsplash/nodogsplash.conf -d 7 -f
```

**What this does:**
- **`-c /etc/nodogsplash/nodogsplash.conf`** - Specify config file to use
- **`-d 7`** - Debug level 7 (maximum verbosity)
- **`-f`** - Run in foreground (don't daemonize)

**Expected output:** Should show configuration loaded successfully, no errors

**If you see errors:**
- Check configuration file syntax
- Verify all paths exist
- Check interface names are correct

**To stop the test:** Press `Ctrl + C`

### 7.2 Check for Port Conflicts

NoDogSplash uses port 2050 by default for its web interface. Check if it's available:

```bash
sudo netstat -tulpn | grep 2050
```

**Expected output:** Should be empty (port not in use) or show nodogsplash if it's running

**If port is in use:**
- Another service may be using it
- Check what's using it: `sudo lsof -i :2050`

---

## Step 8: Enable and Start NoDogSplash Service

### 8.1 Enable Service

```bash
sudo systemctl enable nodogsplash
```

**What this does:**
- Enables the service to start automatically on boot
- Creates systemd service links

### 8.2 Start Service

```bash
sudo systemctl start nodogsplash
```

**What this does:**
- Starts the NoDogSplash service immediately
- Begins intercepting HTTP requests

### 8.3 Check Service Status

```bash
sudo systemctl status nodogsplash
```

**Expected output:**
```
● nodogsplash.service - NoDogSplash Captive Portal
   Loaded: loaded (/lib/systemd/system/nodogsplash.service; enabled)
   Active: active (running) since ...
   ...
```

**Check for:**
- ✅ **Active: active (running)** - Service is running
- ✅ **enabled** - Service will start on boot
- ❌ No error messages

### 8.4 View Service Logs

```bash
sudo journalctl -u nodogsplash -f
```

**What this does:**
- **`journalctl`** - Systemd log viewer
- **`-u nodogsplash`** - Filter logs for nodogsplash service
- **`-f`** - Follow (live updates, like `tail -f`)

**To exit:** Press `Ctrl + C`

**What to look for:**
- No error messages
- "NoDogSplash started" or similar success message
- Interface binding successful

---

## Step 9: Configure Sudoers for Scripts

Our Laravel scripts need sudo privileges to modify NoDogSplash configuration. Configure sudoers to allow this.

### 9.1 Edit Sudoers File

```bash
sudo visudo
```

**Important:** Always use `visudo` to edit sudoers file - it validates syntax and prevents configuration errors.

### 9.2 Add Script Permissions

Add these lines at the end of the file:

```
# NoDogSplash management scripts for Laravel application
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/redirect_device_portal.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/allow_device_through.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/check_device_redirected.sh
```

**Syntax Explanation:**
- **`www-data`** - PHP web server user (runs Laravel)
- **`ALL=(ALL)`** - Can run as any user (first ALL), from any host (second ALL)
- **`NOPASSWD:`** - Run without password prompt
- **`/path/to/script.sh`** - Full path to script that can be executed

### 9.3 Save and Exit

In visudo:
1. Save: `Ctrl + O`, `Enter`
2. Exit: `Ctrl + X`

**Verify syntax:**
- Visudo will warn you if syntax is invalid
- It won't save if there are errors

### 9.4 Test Sudo Access

Test that www-data can run scripts without password:

```bash
sudo -u www-data sudo /var/www/parental_wifi/scripts/check_device_redirected.sh AA:BB:CC:DD:EE:FF
```

**Expected:** Should run without password prompt

---

## Step 10: Make Scripts Executable

Ensure our NoDogSplash scripts are executable:

```bash
cd /var/www/parental_wifi
chmod +x scripts/redirect_device_portal.sh
chmod +x scripts/allow_device_through.sh
chmod +x scripts/check_device_redirected.sh
```

**What this does:**
- **`chmod +x`** - Add execute permission
- Makes scripts executable by users

**Verify permissions:**
```bash
ls -l scripts/*.sh
```

**Expected output:**
```
-rwxr-xr-x 1 user user ... redirect_device_portal.sh
-rwxr-xr-x 1 user user ... allow_device_through.sh
-rwxr-xr-x 1 user user ... check_device_redirected.sh
```

The `x` in `-rwxr-xr-x` indicates executable permission.

---

## Step 11: Manual Testing Procedures

### Test 1: Verify NoDogSplash is Running

```bash
# Check service status
sudo systemctl status nodogsplash

# Check if process is running
ps aux | grep nodogsplash

# Check if port 2050 is listening
sudo netstat -tulpn | grep 2050
```

**Expected Results:**
- Service shows `active (running)`
- Process appears in `ps aux` output
- Port 2050 is listening

---

### Test 2: Verify Configuration File Location

```bash
# Check config file exists
ls -la /etc/nodogsplash/nodogsplash.conf

# View current configuration
sudo cat /etc/nodogsplash/nodogsplash.conf | grep -E "GatewayInterface|GatewayAddress|InternetInterface"
```

**Expected Output:**
```
GatewayInterface wlan0
GatewayAddress 192.168.4.1
InternetInterface eth0
```

---

### Test 3: Test Redirect Script (Manual)

**Prerequisites:**
- Have a test MAC address ready (or use a real device's MAC)
- Know the portal URL format

**Test Steps:**

1. **Check current config (should be empty or no BlockList entries):**
   ```bash
   sudo cat /etc/nodogsplash/nodogsplash.conf | grep -i blocklist
   ```

2. **Run redirect script:**
   ```bash
   sudo ./scripts/redirect_device_portal.sh AA:BB:CC:DD:EE:FF "http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF"
   ```

   **Replace `AA:BB:CC:DD:EE:FF` with a real MAC address from a connected device**

3. **Verify device was added to config:**
   ```bash
   sudo cat /etc/nodogsplash/nodogsplash.conf | grep -i "AA:BB:CC:DD:EE:FF"
   ```

   **Expected:** Should show `BlockList AA:BB:CC:DD:EE:FF` or similar

4. **Check NoDogSplash service restarted:**
   ```bash
   sudo systemctl status nodogsplash
   ```

   **Expected:** Service should show recent restart time

5. **View logs for any errors:**
   ```bash
   sudo journalctl -u nodogsplash -n 20
   ```

---

### Test 4: Test Check Script (Manual)

```bash
# Check if device is redirected (should return exit code 0 if redirected)
sudo ./scripts/check_device_redirected.sh AA:BB:CC:DD:EE:FF

# Check exit code
echo $?
```

**Expected Results:**
- **Exit code 0** = Device IS redirected (found in BlockList)
- **Exit code 1** = Device is NOT redirected (not in BlockList)
- Script outputs "redirected" or "not_redirected" to stderr

**Test both scenarios:**
1. After running redirect script → should return 0
2. After running allow script → should return 1

---

### Test 5: Test Allow Through Script (Manual)

1. **Verify device is currently in BlockList:**
   ```bash
   sudo cat /etc/nodogsplash/nodogsplash.conf | grep "AA:BB:CC:DD:EE:FF"
   ```

2. **Run allow through script:**
   ```bash
   sudo ./scripts/allow_device_through.sh AA:BB:CC:DD:EE:FF
   ```

3. **Verify device was removed from config:**
   ```bash
   sudo cat /etc/nodogsplash/nodogsplash.conf | grep "AA:BB:CC:DD:EE:FF"
   ```

   **Expected:** Should return nothing (device not in BlockList)

4. **Verify service restarted:**
   ```bash
   sudo systemctl status nodogsplash
   ```

---

### Test 6: Test End-to-End Redirect (With Real Device)

**Prerequisites:**
- Have a device connected to the WiFi network
- Know the device's MAC address

**Test Steps:**

1. **Connect a test device to WiFi network (Parental_WiFi)**
   - Device should get IP from DHCP (e.g., 192.168.4.2)

2. **Find device's MAC address:**
   ```bash
   # On Raspberry Pi, check ARP table
   arp -a | grep 192.168.4
   
   # Or use our get_connected_devices script
   sudo ./scripts/get_connected_devices.sh
   ```

3. **Add device to BlockList using redirect script:**
   ```bash
   sudo ./scripts/redirect_device_portal.sh [DEVICE_MAC] "http://192.168.4.1/portal?mac=[DEVICE_MAC]"
   ```

4. **On the test device, try to browse to a website:**
   - Open browser
   - Go to http://google.com or any website
   - **Expected:** Should redirect to `http://192.168.4.1/portal?mac=[DEVICE_MAC]`

5. **Remove redirect using allow script:**
   ```bash
   sudo ./scripts/allow_device_through.sh [DEVICE_MAC]
   ```

6. **On test device, try browsing again:**
   - **Expected:** Should access internet normally (no redirect)

---

### Test 7: Test from Laravel (PHP)

**Prerequisites:**
- Laravel application is running
- Database is set up
- Device exists in database

**Test Steps:**

1. **Create a test device in database:**
   ```bash
   php artisan tinker
   ```
   
   Then in tinker:
   ```php
   $device = App\Models\Device::create([
       'name' => 'Test Device',
       'mac_address' => 'AA:BB:CC:DD:EE:FF',
       'user_id' => 1,  // Your user ID
       'remaining_time_minutes' => 0,
       'status' => 'blocked'
   ]);
   ```

2. **Test redirect from Laravel:**
   ```php
   $service = app(App\Services\NoDogSplashService::class);
   $result = $service->redirectDeviceToPortal($device);
   var_dump($result);  // Should return true
   ```

3. **Check config file was modified:**
   ```bash
   sudo cat /etc/nodogsplash/nodogsplash.conf | grep "AA:BB:CC:DD:EE:FF"
   ```

4. **Test check function:**
   ```php
   $isRedirected = $service->isDeviceRedirected($device);
   var_dump($isRedirected);  // Should return true
   ```

5. **Test allow through:**
   ```php
   $result = $service->allowDeviceThrough($device);
   var_dump($result);  // Should return true
   ```

6. **Verify device removed from config:**
   ```bash
   sudo cat /etc/nodogsplash/nodogsplash.conf | grep "AA:BB:CC:DD:EE:FF"
   ```
   **Expected:** Should return nothing

---

## Step 12: Verify Integration with Existing Services

### Check Service Dependencies

```bash
# Check what services NoDogSplash depends on
systemctl list-dependencies nodogsplash.service

# Check startup order
systemctl list-unit-files | grep -E "nodogsplash|hostapd|dnsmasq|dhcpcd"
```

**Ensure proper startup order:**
1. Network interfaces configured first
2. hostapd (Access Point)
3. dnsmasq (DHCP)
4. iptables rules
5. NoDogSplash (last, so it can add its rules)

### Check IPTables Rules Added by NoDogSplash

```bash
# List all iptables rules
sudo iptables -L -n -v

# Check NoDogSplash chains specifically
sudo iptables -L nodogsplash_authed -n -v
sudo iptables -L nodogsplash_preauth -n -v
```

**Expected:** Should see chains created by NoDogSplash

---

## Troubleshooting

### Issue 1: NoDogSplash Service Won't Start

**Symptoms:**
- `systemctl status nodogsplash` shows `failed` or `inactive`

**Debug Steps:**

1. **Check logs:**
   ```bash
   sudo journalctl -u nodogsplash -n 50
   ```

2. **Check configuration syntax:**
   ```bash
   sudo nodogsplash -c /etc/nodogsplash/nodogsplash.conf -d 7 -f
   ```

3. **Common issues:**
   - **Interface not found:** Check `GatewayInterface` matches actual interface name
   - **Port already in use:** Check what's using port 2050
   - **Permission denied:** Check file permissions on config directory

**Solutions:**
- Verify interface names: `ip addr show`
- Check for port conflicts: `sudo lsof -i :2050`
- Fix permissions: `sudo chmod 644 /etc/nodogsplash/nodogsplash.conf`

---

### Issue 2: Scripts Fail to Execute

**Symptoms:**
- Script execution returns error
- Permission denied errors

**Debug Steps:**

1. **Check script permissions:**
   ```bash
   ls -l scripts/*.sh
   ```

2. **Test script manually:**
   ```bash
   sudo ./scripts/redirect_device_portal.sh AA:BB:CC:DD:EE:FF "http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF"
   ```

3. **Check sudoers configuration:**
   ```bash
   sudo visudo -c
   ```

4. **Test sudo access:**
   ```bash
   sudo -u www-data sudo /var/www/parental_wifi/scripts/check_device_redirected.sh AA:BB:CC:DD:EE:FF
   ```

**Solutions:**
- Make scripts executable: `chmod +x scripts/*.sh`
- Fix sudoers syntax errors
- Verify script paths are correct in sudoers file

---

### Issue 3: Devices Not Being Redirected

**Symptoms:**
- Device in BlockList but not redirecting
- Device can access internet even after redirect script

**Debug Steps:**

1. **Verify device is in config:**
   ```bash
   sudo cat /etc/nodogsplash/nodogsplash.conf | grep -i blocklist
   ```

2. **Check NoDogSplash is running:**
   ```bash
   sudo systemctl status nodogsplash
   ```

3. **Check iptables rules:**
   ```bash
   sudo iptables -L -n -v | grep nodogsplash
   ```

4. **Check logs:**
   ```bash
   sudo journalctl -u nodogsplash -f
   ```

5. **Test on device:**
   - Try accessing http://captive.apple.com (captive portal detection)
   - Try accessing http://192.168.4.1 directly

**Solutions:**
- Restart NoDogSplash: `sudo systemctl restart nodogsplash`
- Verify MAC address format (should be uppercase with colons)
- Check NoDogSplash is intercepting on correct interface

---

### Issue 4: Config File Modifications Not Applied

**Symptoms:**
- Script runs successfully but device still not redirected
- Config file changes disappear

**Debug Steps:**

1. **Check if service is overwriting config:**
   - Some NoDogSplash configurations may reset on restart
   - Check if config file is read-only

2. **Verify script actually modified file:**
   ```bash
   sudo cat /etc/nodogsplash/nodogsplash.conf
   ```

3. **Check backup files:**
   ```bash
   ls -la /tmp/nodogsplash_backups/
   ```

**Solutions:**
- Ensure scripts have write permission
- Check if NoDogSplash is overwriting config (may need different approach)
- Verify service restart actually reloaded config

---

### Issue 5: Conflict with Existing IPTables Rules

**Symptoms:**
- NoDogSplash starts but doesn't intercept traffic
- IPTables rules conflict

**Debug Steps:**

1. **Check all iptables rules:**
   ```bash
   sudo iptables -L -n -v
   sudo iptables -t nat -L -n -v
   ```

2. **Check rule order:**
   - NoDogSplash rules should come after existing rules
   - Check FORWARD chain order

**Solutions:**
- Ensure NoDogSplash starts after iptables are configured
- Check rule priorities in FORWARD chain
- May need to adjust startup order of services

---

## Verification Checklist

Use this checklist to verify installation is complete:

- [ ] NoDogSplash installed (`nodogsplash --version` works)
- [ ] Configuration file exists and is configured
- [ ] NoDogSplash service is running (`systemctl status nodogsplash`)
- [ ] Scripts are executable (`ls -l scripts/*.sh` shows `-rwxr-xr-x`)
- [ ] Sudoers configured (test with `sudo -u www-data sudo ...`)
- [ ] Redirect script works manually
- [ ] Check script works manually
- [ ] Allow through script works manually
- [ ] Real device redirect test successful
- [ ] Laravel integration test successful
- [ ] Service starts on boot (enabled)
- [ ] Logs show no errors

---

## Next Steps

After successful setup and testing:

1. **Test with Laravel Application:**
   - Test `NoDogSplashService` methods from Laravel
   - Verify integration with `CheckTimeExpiration` job
   - Test full workflow: time expiration → redirect → quiz/video → allow through

2. **Monitor Logs:**
   - Watch for any errors in NoDogSplash logs
   - Monitor Laravel logs for script execution issues

3. **Performance Testing:**
   - Test with multiple devices
   - Check response times
   - Monitor resource usage

4. **Documentation:**
   - Note any custom configurations
   - Document any issues encountered
   - Update configuration if needed

---

## Additional Resources

- **NoDogSplash Documentation:** https://nodogsplashdocs.readthedocs.io/
- **NoDogSplash Configuration Reference:** `/etc/nodogsplash/nodogsplash.conf` (contains inline documentation)
- **System Logs:** `sudo journalctl -u nodogsplash -f`
- **NoDogSplash Status:** `sudo nodogsplash -s` (if available)

---

## Summary

This guide covered:
1. ✅ Installing NoDogSplash
2. ✅ Configuring basic settings
3. ✅ Setting up portal integration
4. ✅ Configuring script permissions
5. ✅ Manual testing procedures
6. ✅ Troubleshooting common issues

Your NoDogSplash installation should now be ready for integration with your Laravel application. The scripts can manage device redirects, and the service will intercept HTTP requests as configured.

