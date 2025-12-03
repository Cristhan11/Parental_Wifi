# NoDogSplash Installation and Setup Guide

## Overview

This guide provides step-by-step instructions for installing and configuring NoDogSplash on your Raspberry Pi 4B running Raspberry Pi OS Lite (64-bit). NoDogSplash is the captive portal software that will intercept HTTP requests and redirect devices to our portal page when their internet time expires.

**Note:** For the complete, verified final setup documentation, see `docs/NODOGSPLASH_SETUP.md`. This guide provides the installation steps, while the setup doc provides the complete working configuration.

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

**Important Note:** NoDogSplash is not available in the default Raspberry Pi package repositories. We need to compile and install it from source.

### 1.1 Update Package List and Install Dependencies

```bash
sudo apt update
sudo apt install -y build-essential git libmicrohttpd-dev libnl-3-dev libnl-genl-3-dev libjson-c-dev
```

**What this does:**
- **`sudo apt update`** - Updates package list from repositories
- **`build-essential`** - Compiler tools (gcc, make, etc.)
- **`git`** - To clone the NoDogSplash repository
- **`libmicrohttpd-dev`** - HTTP library (required by NoDogSplash)
- **`libnl-3-dev`** - Netlink library (for network management)
- **`libnl-genl-3-dev`** - Netlink generic netlink library
- **`libjson-c-dev`** - JSON library (required for state file support)

### 1.2 Clone NoDogSplash Repository

```bash
cd ~
git clone https://github.com/nodogsplash/nodogsplash.git
cd nodogsplash
```

**What this does:**
- **`cd ~`** - Navigate to home directory
- **`git clone`** - Clones the official NoDogSplash repository from GitHub
- **`cd nodogsplash`** - Navigates into the source directory

**Expected Output:**
```
Cloning into 'nodogsplash'...
remote: Enumerating objects: 5604, done.
...
Resolving deltas: 100% (3547/3547), done.
```

### 1.3 Compile NoDogSplash

```bash
make
```

**What this does:**
- **`make`** - Compiles the source code using the Makefile
- This may take a few minutes depending on your Raspberry Pi's speed

**Expected Output:**
- Compilation messages showing object files being built
- Should complete without errors

**If compilation fails:**
- Check that all dependencies were installed correctly
- Look for error messages indicating missing libraries
- Common issue: Missing `libjson-c-dev` (will show error about `json-c/json.h`)

### 1.4 Install NoDogSplash

```bash
sudo make install
```

**What this does:**
- **`sudo make install`** - Installs the compiled binaries and configuration files
- Copies `nodogsplash` binary to `/usr/bin/`
- Copies configuration files to `/etc/nodogsplash/`

**Expected Output:**
```
strip nodogsplash
strip ndsctl
mkdir -p /usr/bin/
cp ndsctl /usr/bin/
cp nodogsplash /usr/bin/
mkdir -p /etc/nodogsplash/htdocs/images
cp resources/nodogsplash.conf /etc/nodogsplash/
...
```

### 1.5 Verify Installation

```bash
which nodogsplash
nodogsplash -version
```

**What to check:**
- **`which nodogsplash`** - Should return: `/usr/bin/nodogsplash`
- **`nodogsplash -version`** - Should display version number (e.g., `5.0.2`)

**Note:** The version flag is `-version` (not `--version`) in version 5.0.2

---

## Step 2: Create Systemd Service File

Since we installed from source, NoDogSplash may not have created a systemd service automatically. We'll create one now, but won't enable it until after configuration.

### 2.1 Create Service File

```bash
sudo nano /etc/systemd/system/nodogsplash.service
```

Add this content:

```ini
[Unit]
Description=NoDogSplash Captive Portal
After=network.target hostapd.service dnsmasq.service

[Service]
Type=simple
ExecStart=/usr/bin/nodogsplash -f
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**What this does:**
- **`[Unit]`** - Service unit configuration
  - **`After=network.target hostapd.service dnsmasq.service`** - Ensures NoDogSplash starts after network, hostapd, and dnsmasq are ready
- **`[Service]`** - Service execution configuration
  - **`Type=simple`** - Service runs in foreground (process is the main service)
  - **`ExecStart=/usr/bin/nodogsplash -f`** - Command to start NoDogSplash with `-f` flag (foreground mode)
  - **`Restart=always`** - Automatically restart if service crashes
  - **`RestartSec=5`** - Wait 5 seconds before restarting
- **`[Install]`** - Installation configuration
  - **`WantedBy=multi-user.target`** - Start service when system reaches multi-user target (normal boot)

Save: `Ctrl+O`, `Enter`, `Ctrl+X`

### 2.2 Reload Systemd Configuration

```bash
sudo systemctl daemon-reload
```

**What this does:**
- **`daemon-reload`** - Reloads systemd configuration files
- Required after creating or modifying service files

**Important:** Do NOT start the service yet. We'll configure it first, then start it.

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
# ============================================
# BASIC CONFIGURATION - REQUIRED SETTINGS
# ============================================

# Gateway Interface (WiFi interface)
# Must match your WiFi Access Point interface name
# Use 'ip addr show' to verify your interface name
GatewayInterface wlan0

# Gateway Address (Access Point IP)
# Must match the IP address of your wlan0 interface
# This is the IP address that NoDogSplash listens on
GatewayAddress 192.168.4.1

# Gateway Name (SSID) - Optional
# Display name for the captive portal
# Can be customized to match your SSID
GatewayName Parental_WiFi

# Max Clients (maximum number of connected devices)
# Should match your DHCP range capacity
MaxClients 50

# AuthIdleTimeout (timeout for portal authentication in seconds)
# How long a device can remain on portal page without action
# 480 seconds = 8 minutes
AuthIdleTimeout 480

# ClientIdleTimeout (timeout for client session in seconds)
# How long an authenticated client can remain idle
# 480 seconds = 8 minutes
ClientIdleTimeout 480
```

**Important Notes:**
- **`InternetInterface`** is NOT a valid option in NoDogSplash version 5.0.2 - Do NOT add this line
- If you see `InternetInterface` in commented form, leave it commented out
- Only uncomment and set `GatewayInterface` and `GatewayAddress`

### 4.3 Configuration Explained

**GatewayInterface wlan0**
- Specifies which network interface NoDogSplash monitors
- Should match your WiFi Access Point interface
- **Required:** Must be set to the correct interface name

**GatewayAddress 192.168.4.1**
- The IP address of your Raspberry Pi on the WiFi network
- Must match your Access Point IP configuration
- **Required:** Must match the IP address of your wlan0 interface

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
# ============================================
# REDIRECT CONFIGURATION - CRITICAL
# ============================================

# Redirect URL - Where Preauthenticated devices are redirected
# This is where NoDogSplash redirects all HTTP requests from Preauthenticated devices
# Must use the gateway IP (192.168.4.1) so devices on WiFi network can access it
# Do NOT use the server's IP address (e.g., 192.168.1.173) - devices on WiFi can't access it
RedirectURL http://192.168.4.1/portal
```

**Important Notes:**
- The `RedirectURL` must use the gateway IP (`192.168.4.1`), not the server's IP
- This is the URL that Preauthenticated devices will be redirected to
- The portal path (`/portal`) must match your Laravel route

---

## Step 6: Configure Firewall Rules (Prevent Redirect Loop)

**Critical:** You must configure firewall rules to allow Preauthenticated users to access the portal. Without this, accessing the portal will cause an infinite redirect loop.

### 6.1 Configure Preauthenticated Users Firewall Rules

Open the config file:
```bash
sudo nano /etc/nodogsplash/nodogsplash.conf
```

Find the `FirewallRuleSet preauthenticated-users` section and add the portal access rule:

```ini
FirewallRuleSet preauthenticated-users {
# For preauthenticated users to resolve IP addresses in their
# initial request not using the router itself as a DNS server.
# Leave commented to help prevent DNS tunnelling
FirewallRule allow tcp port 53
FirewallRule allow udp port 53

# CRITICAL: Allow access to portal on gateway (prevents redirect loop)
# This allows Preauthenticated users to access http://192.168.4.1/portal
# without being redirected again. Without this rule, accessing the portal
# causes an infinite redirect loop because NoDogSplash intercepts the
# request and redirects to RedirectURL (which is the same URL).
# This rule must be added to prevent the redirect loop issue.
FirewallRule allow tcp port 80 to 192.168.4.1

# For splash page content not hosted on the router, you
# will want to allow port 80 tcp to the remote host here.
# Doing so circumvents the usual capture and redirect of
# any port 80 request to this remote host.
# Note that the remote host's numerical IP address must be known
# and used here.
#  FirewallRule allow tcp port 80 to 123.321.123.321
}
```

**Why this is needed:**
- Without this rule, when a Preauthenticated device tries to access `http://192.168.4.1/portal`, NoDogSplash intercepts it
- NoDogSplash redirects to `RedirectURL` which is `http://192.168.4.1/portal` (the same URL)
- This creates an infinite redirect loop
- The firewall rule allows Preauthenticated users to access port 80 on the gateway IP, bypassing the redirect

### 6.2 Save and Restart

After adding the firewall rule:
1. Save the file: `Ctrl+O`, `Enter`, `Ctrl+X`
2. Restart NoDogSplash:
   ```bash
   sudo systemctl restart nodogsplash
   ```

### 6.3 Verify Firewall Rule

Check that the rule was added:
```bash
sudo grep -A 5 "preauthenticated-users" /etc/nodogsplash/nodogsplash.conf | grep "192.168.4.1"
```

**Expected output:** `FirewallRule allow tcp port 80 to 192.168.4.1`

---

## Step 7: Configure Firewall Rules (IPTables Integration)

NoDogSplash needs to work with your existing iptables configuration. It will add its own rules to manage captive portal traffic.

### 7.1 Check Current IPTables Rules

```bash
sudo iptables -L -n -v
```

**What this does:**
- **`iptables -L`** - List all rules
- **`-n`** - Show numeric addresses (don't resolve hostnames)
- **`-v`** - Verbose (show packet/byte counts)

**Review output:** Note any existing rules for wlan0 and eth0 interfaces

### 7.2 NoDogSplash IPTables Integration

NoDogSplash automatically adds iptables rules when it starts. It uses these chains:
- **nodogsplash_authed** - Authenticated clients (allowed through)
- **nodogsplash_preauth** - Unauthenticated clients (redirected to portal)

**Important:** NoDogSplash must be started AFTER your existing iptables rules are set up, or it may conflict.

### 7.3 Configure IPTables Script Order

If you have a script that sets up iptables on boot, ensure NoDogSplash starts after it:

```bash
sudo systemctl list-dependencies nodogsplash.service
```

Check what NoDogSplash depends on and ensure network is fully configured before it starts.

---

## Step 8: Test Configuration (Before Starting Service)

### 8.1 Validate Configuration File Syntax

```bash
sudo /usr/bin/nodogsplash -c /etc/nodogsplash/nodogsplash.conf -f -d 3
```

**What this does:**
- **`/usr/bin/nodogsplash`** - Full path to NoDogSplash binary (installed from source)
- **`-c /etc/nodogsplash/nodogsplash.conf`** - Specify config file to use
- **`-f`** - Run in foreground (don't daemonize)
- **`-d 3`** - Debug level 3 (shows important messages)

**Expected output:** Should show:
- Configuration file loaded successfully
- Gateway detected: `Detected gateway wlan0 at 192.168.4.1`
- Web server created: `Created web server on 192.168.4.1:2050`
- Firewall rules initialized
- Process continues running (doesn't exit)

**If you see errors:**
- **"Bad configuration option: InternetInterface"** - Remove or comment out `InternetInterface` line (not valid in v5.0.2)
- **"No such device"** - Check `GatewayInterface` matches actual interface name (use `ip addr show` to verify)
- **Port conflicts** - Another service may be using port 2050

**To stop the test:** Press `Ctrl + C`

### 8.2 Check for Port Conflicts

NoDogSplash uses port 2050 by default for its web interface. Check if it's available:

```bash
sudo netstat -tulpn | grep 2050
```

**Expected output:** Should be empty (port not in use) or show nodogsplash if it's running

**If port is in use:**
- Another service may be using it
- Check what's using it: `sudo lsof -i :2050`

---

## Step 9: Enable and Start NoDogSplash Service

### 9.1 Enable Service

```bash
sudo systemctl enable nodogsplash
```

**What this does:**
- Enables the service to start automatically on boot
- Creates systemd service links

### 9.2 Start Service

```bash
sudo systemctl start nodogsplash
```

**What this does:**
- Starts the NoDogSplash service immediately
- Begins intercepting HTTP requests

### 9.3 Check Service Status

```bash
sudo systemctl status nodogsplash
```

**Expected output:**
```
● nodogsplash.service - NoDogSplash Captive Portal
   Loaded: loaded (/etc/systemd/system/nodogsplash.service; enabled)
   Active: active (running) since ...
   Main PID: XXXX (nodogsplash)
   ...
```

**Check for:**
- ✅ **Active: active (running)** - Service is running
- ✅ **enabled** - Service will start on boot
- ✅ **Main PID** - Shows process ID of running nodogsplash
- ❌ No error messages or "activating (auto-restart)" status

**If service fails to start:**
- Check logs: `sudo journalctl -u nodogsplash -n 50`
- Verify config file: `sudo /usr/bin/nodogsplash -c /etc/nodogsplash/nodogsplash.conf -f -d 3`
- Check for interface issues: `ip addr show wlan0`

### 9.4 View Service Logs

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

## Step 10: Configure Sudoers for Scripts

Our Laravel scripts need sudo privileges to modify NoDogSplash configuration. Configure sudoers to allow this.

### 10.1 Edit Sudoers File

```bash
sudo visudo
```

**Important:** Always use `visudo` to edit sudoers file - it validates syntax and prevents configuration errors.

### 10.2 Add Script Permissions

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

### 10.3 Save and Exit

In visudo:
1. Save: `Ctrl + O`, `Enter`
2. Exit: `Ctrl + X`

**Verify syntax:**
- Visudo will warn you if syntax is invalid
- It won't save if there are errors

### 10.4 Test Sudo Access

Test that www-data can run scripts without password:

```bash
sudo -u www-data sudo /var/www/parental_wifi/scripts/check_device_redirected.sh AA:BB:CC:DD:EE:FF
```

**Expected:** Should run without password prompt

---

## Step 11: Make Scripts Executable

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

## Step 12: Manual Testing Procedures

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
```

**Note:** `InternetInterface` is not a valid option in NoDogSplash version 5.0.2 and should not appear in active configuration lines.

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

## Step 13: Verify Integration with Existing Services

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
- `systemctl status nodogsplash` shows `failed`, `inactive`, or `activating (auto-restart)`
- Service keeps restarting in a loop

**Debug Steps:**

1. **Check logs:**
   ```bash
   sudo journalctl -u nodogsplash -n 50
   ```

2. **Check configuration syntax:**
   ```bash
   sudo /usr/bin/nodogsplash -c /etc/nodogsplash/nodogsplash.conf -f -d 3
   ```

3. **Common issues:**
   - **"Bad configuration option: InternetInterface"** - Remove this line (not valid in v5.0.2)
   - **"No such device"** - Check `GatewayInterface` matches actual interface name
   - **Port already in use:** Check what's using port 2050
   - **Service exits immediately:** Service file may need `-f` flag (foreground mode)

**Solutions:**
- Remove `InternetInterface` line from config file (comment it out or delete it)
- Verify interface names: `ip addr show`
- Check for port conflicts: `sudo lsof -i :2050`
- Fix service file: Ensure `ExecStart=/usr/bin/nodogsplash -f` includes `-f` flag
- Check service type: Use `Type=simple` with `-f` flag, or `Type=forking` if NoDogSplash daemonizes

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

## Important Note: BlockList Configuration in NoDogSplash 5.0.2

**Testing Required:** Our scripts use `BlockList MAC_ADDRESS` entries in the config file to redirect devices. NoDogSplash version 5.0.2 may handle blocklists differently than older versions. 

**After completing setup:**
1. Test the redirect scripts manually to verify BlockList entries work
2. If BlockList doesn't work as expected, we may need to use alternative methods:
   - `ndsctl` command-line tool to block/unblock devices
   - Firewall rules directly (iptables)
   - NoDogSplash's API/control interface

**If BlockList doesn't work:** We can modify the scripts to use `ndsctl` commands instead, which is the recommended method for NoDogSplash 5.0+.

## Next Steps

After successful setup and testing:

### Immediate Next Steps:

1. **Make scripts executable:**
   ```bash
   cd /var/www/parental_wifi
   chmod +x scripts/*.sh
   ```

2. **Configure sudoers:**
   - Add script permissions to sudoers file (Step 9 above)
   - Test sudo access from www-data user

3. **Test scripts manually:**
   - Test redirect script with a real MAC address
   - Verify BlockList entry is added to config file
   - Test check script to verify it detects the entry
   - Test allow through script to remove the entry
   - **Important:** Verify devices actually get redirected (may need adjustment if BlockList doesn't work)

4. **Test from Laravel:**
   - Test `NoDogSplashService` methods from Laravel tinker
   - Verify integration with `CheckTimeExpiration` job
   - Test full workflow: time expiration → redirect → quiz/video → allow through

### Future Steps:

5. **Monitor Logs:**
   - Watch for any errors in NoDogSplash logs
   - Monitor Laravel logs for script execution issues

6. **Performance Testing:**
   - Test with multiple devices
   - Check response times
   - Monitor resource usage

7. **Documentation:**
   - Note any custom configurations
   - Document any issues encountered
   - Update configuration if needed
   - Document if BlockList method works or if alternative method is needed

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

