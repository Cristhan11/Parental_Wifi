# NoDogSplash Final Setup Documentation

## Overview

This document provides the complete, verified setup for NoDogSplash integration in the Parental WiFi Control System. This setup has been tested and confirmed working on Raspberry Pi 4B running Raspberry Pi OS Lite (64-bit).

**Note:** For DNS interception setup (HTTPS support), see `docs/DNS_INTERCEPTION_SETUP.md`.

## System Configuration

### Raspberry Pi Setup

- **OS**: Raspberry Pi OS Lite (64-bit)
- **Username**: `snasna`
- **Project Directory**: `/var/www/parental_wifi`
- **Web Server User**: `www-data`
- **PHP Version**: 8.4.11
- **PHP-FPM Service**: `php8.4-fpm`

### Network Configuration

- **WiFi Interface**: `wlan0`
- **Access Point IP**: `192.168.4.1/24`
- **SSID**: `Parental_WiFi`
- **DHCP Range**: `192.168.4.2` to `192.168.4.51`
- **Gateway**: `192.168.4.1`
- **DNS Server**: `192.168.4.1`

---

## NoDogSplash Installation

### Installation Method

NoDogSplash was installed from source (not available in default repositories):

```bash
# Install dependencies
sudo apt update
sudo apt install -y build-essential git libmicrohttpd-dev libnl-3-dev libnl-genl-3-dev libjson-c-dev

# Clone repository
cd ~
git clone https://github.com/nodogsplash/nodogsplash.git
cd nodogsplash

# Compile and install
make
sudo make install
```

### Verification

```bash
# Check installation
which nodogsplash
nodogsplash -version

# Expected output: /usr/bin/nodogsplash and version number (e.g., 5.0.2)
```

---

## NoDogSplash Configuration

### Configuration File Location

`/etc/nodogsplash/nodogsplash.conf`

### Required Settings

```ini
# Gateway Interface (WiFi interface)
GatewayInterface wlan0

# Gateway Address (Access Point IP)
GatewayAddress 192.168.4.1

# Redirect URL - Where Preauthenticated devices are redirected
RedirectURL http://192.168.4.1/portal
```

### Firewall Rules Configuration

**Critical:** To prevent redirect loops, you must allow Preauthenticated users to access the portal on the gateway IP.

Edit the `FirewallRuleSet preauthenticated-users` section in `/etc/nodogsplash/nodogsplash.conf`:

```ini
FirewallRuleSet preauthenticated-users {
# For preauthenticated users to resolve IP addresses in their
# initial request not using the router itself as a DNS server.
# Leave commented to help prevent DNS tunnelling
FirewallRule allow tcp port 53
FirewallRule allow udp port 53

# Allow access to portal on gateway (prevents redirect loop)
# This allows Preauthenticated users to access http://192.168.4.1/portal
# without being redirected again. Without this rule, accessing the portal
# causes an infinite redirect loop because NoDogSplash intercepts the
# request and redirects to RedirectURL (which is the same URL).
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

**Why this is needed:** Without this rule, when a Preauthenticated device tries to access `http://192.168.4.1/portal`, NoDogSplash intercepts it and redirects to `RedirectURL` (which is the same URL), creating an infinite redirect loop.

**After making changes:**
```bash
sudo systemctl restart nodogsplash
```

### Important Notes

- **`InternetInterface`** is NOT a valid option in NoDogSplash version 5.0.2 - Do NOT add this line
- **`RedirectURL`** must be set correctly - this is where devices are redirected when in Preauthenticated state
- **No `BlockList` entries** - We use `ndsctl` commands instead (see below)
- **Firewall rule for portal access** - Must allow port 80 to gateway IP for Preauthenticated users (prevents redirect loop)

### Complete Configuration Example

Here's a complete example of the critical configuration sections in `/etc/nodogsplash/nodogsplash.conf`:

```ini
# ============================================
# BASIC CONFIGURATION
# ============================================

# Gateway Interface (WiFi interface)
# Must match your WiFi Access Point interface name
GatewayInterface wlan0

# Gateway Address (Access Point IP)
# Must match the IP address of your wlan0 interface
GatewayAddress 192.168.4.1

# Redirect URL - Where Preauthenticated devices are redirected
# This is where NoDogSplash redirects all HTTP requests from Preauthenticated devices
# Must use the gateway IP (192.168.4.1) so devices on WiFi network can access it
RedirectURL http://192.168.4.1/portal

# ============================================
# FIREWALL RULES - CRITICAL FOR PORTAL ACCESS
# ============================================

# FirewallRuleSet: preauthenticated-users
# Control access for users before authentication.
# These rules apply to devices in Preauthenticated state.
FirewallRuleSet preauthenticated-users {
# For preauthenticated users to resolve IP addresses in their
# initial request not using the router itself as a DNS server.
FirewallRule allow tcp port 53
FirewallRule allow udp port 53

# CRITICAL: Allow access to portal on gateway (prevents redirect loop)
# This allows Preauthenticated users to access http://192.168.4.1/portal
# without being redirected again. Without this rule, accessing the portal
# causes an infinite redirect loop because NoDogSplash intercepts the
# request and redirects to RedirectURL (which is the same URL).
# This rule MUST be present for the portal to work correctly.
FirewallRule allow tcp port 80 to 192.168.4.1
}
```

### Verify Configuration

```bash
# Check basic settings
sudo grep -E "GatewayInterface|GatewayAddress|RedirectURL" /etc/nodogsplash/nodogsplash.conf

# Check firewall rule (critical for portal access)
sudo grep -A 5 "preauthenticated-users" /etc/nodogsplash/nodogsplash.conf | grep "192.168.4.1"
```

**Expected output:**
```
GatewayInterface wlan0
GatewayAddress 192.168.4.1
RedirectURL http://192.168.4.1/portal
FirewallRule allow tcp port 80 to 192.168.4.1
```

---

## Systemd Service Configuration

### Service File Location

`/etc/systemd/system/nodogsplash.service`

### Service Configuration

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

### Service Management

```bash
# Enable service (start on boot)
sudo systemctl enable nodogsplash

# Start service
sudo systemctl start nodogsplash

# Check status
sudo systemctl status nodogsplash

# View logs
sudo journalctl -u nodogsplash -f
```

**Expected Status:**
- `Active: active (running)`
- `enabled` (starts on boot)

---

## IP Forwarding Configuration

### Systemd Service for IP Forwarding

IP forwarding is managed by a systemd service to ensure it persists after reboot.

### Service File Location

`/etc/systemd/system/ip-forward.service`

### Service Configuration

```ini
[Unit]
Description=Enable IP Forwarding
After=network.target

[Service]
Type=oneshot
ExecStart=/bin/sh -c 'echo 1 > /proc/sys/net/ipv4/ip_forward'
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target
```

### Enable and Start

```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable service (start on boot)
sudo systemctl enable ip-forward.service

# Start service
sudo systemctl start ip-forward.service

# Verify IP forwarding is enabled
sysctl net.ipv4.ip_forward
```

**Expected output:** `net.ipv4.ip_forward = 1`

---

## Splash Page Configuration

### Splash Page Location

`/etc/nodogsplash/htdocs/splash.html`

### Splash Page Content

The splash page redirects devices to the Laravel portal with the token parameter:

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecting...</title>
    <script>
        // Get token from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('tok') || '';
        
        // Redirect to portal with token
        if (token) {
            window.location.href = "http://192.168.4.1/portal?tok=" + token;
        } else {
            // Fallback: redirect to portal without token
            window.location.href = "http://192.168.4.1/portal";
        }
    </script>
    <meta http-equiv="refresh" content="0; url=http://192.168.4.1/portal">
</head>
<body>
    <p>Redirecting to portal...</p>
    <p>If you are not redirected, <a href="http://192.168.4.1/portal">click here</a>.</p>
</body>
</html>
```

### How It Works

1. NoDogSplash redirects Preauthenticated devices to `http://192.168.4.1:2050/splash.html?tok=TOKEN`
2. The splash page JavaScript extracts the `tok` parameter
3. The splash page redirects to `http://192.168.4.1/portal?tok=TOKEN`
4. The Laravel portal controller looks up the MAC address from the token using `ndsctl clients`

---

## Laravel Configuration

### Portal URL Configuration

**File:** `config/portal.php`

```php
<?php

return [
    'url' => env('PORTAL_URL', 'http://192.168.4.1'),
];
```

### Environment Variable (Optional)

Add to `.env` if you need to override:

```env
PORTAL_URL=http://192.168.4.1
```

### NoDogSplashService Configuration

The `NoDogSplashService` uses the portal URL from config:

```php
$portalBaseUrl = config('portal.url', 'http://192.168.4.1');
$portalPath = route('portal.landing', ['mac' => $macAddress], false);
$portalUrl = $portalBaseUrl . $portalPath;
```

This ensures the portal URL uses `192.168.4.1` (WiFi AP IP) instead of the server's IP address.

---

## Portal Controller Token Support

### Token-Based MAC Address Lookup

The `PortalController` has been updated to support NoDogSplash tokens:

**File:** `app/Http/Controllers/PortalController.php`

### How It Works

1. Portal receives request with `?tok=TOKEN` parameter
2. `getDevice()` method checks for token first
3. If token found, calls `getMacFromToken()` method
4. `getMacFromToken()` executes `sudo ndsctl clients` to look up MAC address
5. Device is found by MAC address in database

### Implementation

```php
protected function getDevice(Request $request): ?Device
{
    // First, check if we have a NoDogSplash token parameter
    $token = $request->query('tok');
    if ($token) {
        // Look up MAC address from token using ndsctl
        $macAddress = $this->getMacFromToken($token);
        if ($macAddress) {
            // Store MAC in session for subsequent requests
            session(['device_mac' => $macAddress]);
            // Look up device in database
            $device = Device::where('mac_address', $macAddress)->first();
            if ($device) {
                return $device;
            }
        }
    }

    // Fallback to original method (MAC from query, POST, or session)
    // ...
}

protected function getMacFromToken(string $token): ?string
{
    // Execute ndsctl clients command
    $output = @shell_exec("sudo ndsctl clients 2>/dev/null");
    
    if (!$output) {
        return null;
    }
    
    // Parse output to find token and extract MAC
    $lines = explode("\n", trim($output));
    
    foreach ($lines as $line) {
        if (strpos($line, "token=$token") !== false) {
            // Extract MAC address using regex
            if (preg_match('/mac=([a-fA-F0-9:]{17})/', $line, $matches)) {
                return strtolower($matches[1]);
            }
        }
    }
    
    return null;
}
```

---

## Bash Scripts

### Script Location

All scripts are located in: `/var/www/parental_wifi/scripts/`

### Required Scripts

1. **`redirect_device_portal.sh`** - Redirects device to portal
2. **`allow_device_through.sh`** - Allows device through (removes redirect)
3. **`check_device_redirected.sh`** - Checks if device is redirected

### Script Permissions

All scripts must be executable:

```bash
cd /var/www/parental_wifi
sudo chmod +x scripts/redirect_device_portal.sh
sudo chmod +x scripts/allow_device_through.sh
sudo chmod +x scripts/check_device_redirected.sh
```

### Verify Permissions

```bash
ls -la scripts/*.sh
```

**Expected output:** All scripts should show `-rwxr-xr-x` (executable)

---

## Sudoers Configuration

### Required Sudo Permissions

The `www-data` user needs sudo permissions to:
1. Execute NoDogSplash scripts
2. Run `ndsctl clients` command

### Sudoers Configuration

**File:** `/etc/sudoers` or `/etc/sudoers.d/www-data-nodogsplash`

Add these lines:

```
# NoDogSplash management scripts for Laravel application
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/redirect_device_portal.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/allow_device_through.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/check_device_redirected.sh

# NoDogSplash control command for MAC address lookup
www-data ALL=(ALL) NOPASSWD: /usr/bin/ndsctl clients
```

### Edit Sudoers

```bash
sudo visudo
```

**Important:** Always use `visudo` to edit sudoers - it validates syntax.

### Verify Configuration

```bash
sudo grep -i "www-data\|ndsctl" /etc/sudoers /etc/sudoers.d/*
```

**Expected output:** Should show all four permission lines above.

---

## How NoDogSplash Works

### Device States

NoDogSplash manages devices in two states:

1. **Preauthenticated** - Device is redirected to portal on HTTP requests
2. **Authenticated** - Device can access internet normally

### How Redirection Works

1. Device connects to WiFi → NoDogSplash detects it → Device is in **Preauthenticated** state
2. Device tries to access `http://google.com` → NoDogSplash intercepts request
3. NoDogSplash redirects to `RedirectURL` (configured in `nodogsplash.conf`)
4. Device sees portal page instead of requested website

### Managing Device States

We use `ndsctl` (NoDogSplash control command) to manage device states:

- **To redirect device**: `ndsctl deauth <token>` - Puts device in Preauthenticated state
- **To allow device through**: `ndsctl auth <token>` - Puts device in Authenticated state

### Finding Device Token

Each device has a unique token assigned by NoDogSplash. To find it:

```bash
sudo ndsctl clients
```

**Output format:**
```
1
client_id=0
ip=192.168.4.32
mac=e6:6a:8f:19:be:b1
token=c8e54152
state=Preauthenticated
```

---

## Script Implementation Details

### redirect_device_portal.sh

**Purpose:** Redirects a device to the portal by putting it in Preauthenticated state.

**How it works:**
1. Validates MAC address format
2. Finds device token using `ndsctl clients`
3. Calls `ndsctl deauth <token>` to put device in Preauthenticated state
4. Device will be redirected on next HTTP request

**Usage:**
```bash
sudo ./scripts/redirect_device_portal.sh AA:BB:CC:DD:EE:FF "http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF"
```

**Note:** The portal URL parameter is currently not used (we use `RedirectURL` from config), but it's kept for compatibility.

### allow_device_through.sh

**Purpose:** Allows a device through by putting it in Authenticated state.

**How it works:**
1. Validates MAC address format
2. Finds device token using `ndsctl clients`
3. Calls `ndsctl auth <token>` to put device in Authenticated state
4. Device can now access internet normally

**Usage:**
```bash
sudo ./scripts/allow_device_through.sh AA:BB:CC:DD:EE:FF
```

### check_device_redirected.sh

**Purpose:** Checks if a device is currently redirected (in Preauthenticated state).

**How it works:**
1. Validates MAC address format
2. Finds device token using `ndsctl clients`
3. Checks device state (Preauthenticated = redirected, Authenticated = not redirected)
4. Returns exit code 0 if redirected, 1 if not redirected

**Usage:**
```bash
sudo ./scripts/check_device_redirected.sh AA:BB:CC:DD:EE:FF
echo $?  # 0 = redirected, 1 = not redirected
```

---

## Complete Flow

### Flow 1: Device Time Expires → Redirect to Portal

```
1. CheckTimeExpiration Job runs (every 2 minutes)
   ↓
2. Detects device time has expired
   ↓
3. Calls: NoDogSplashService::redirectDeviceToPortal($device)
   ↓
4. Service builds portal URL: http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF
   ↓
5. Service calls: ScriptExecutor::execute('redirect_device_portal.sh', [mac, url])
   ↓
6. Script finds device token using: ndsctl clients
   ↓
7. Script calls: ndsctl deauth <token>
   ↓
8. Device is put in Preauthenticated state
   ↓
9. Device's next HTTP request → NoDogSplash intercepts → Redirects to RedirectURL
   ↓
10. Device sees splash page → Splash page redirects to /portal?tok=TOKEN
    ↓
11. PortalController looks up MAC from token → Shows portal page
```

### Flow 2: Child Completes Quiz/Video → Allow Device Through

```
1. Child completes quiz/video successfully
   ↓
2. TimeGrantingService grants time to device
   ↓
3. Calls: NoDogSplashService::allowDeviceThrough($device)
   ↓
4. Service calls: ScriptExecutor::execute('allow_device_through.sh', [mac])
   ↓
5. Script finds device token using: ndsctl clients
   ↓
6. Script calls: ndsctl auth <token>
   ↓
7. Device is put in Authenticated state
   ↓
8. Device can now access internet normally
```

---

## Testing Procedures

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

### Test 2: Verify Configuration

```bash
# Check config file
sudo grep -E "GatewayInterface|GatewayAddress|RedirectURL" /etc/nodogsplash/nodogsplash.conf

# Check IP forwarding
sysctl net.ipv4.ip_forward

# Check scripts are executable
ls -la /var/www/parental_wifi/scripts/*.sh
```

**Expected Results:**
- Config shows correct values
- IP forwarding = 1
- All scripts are executable

### Test 3: Test Device Connection

```bash
# Connect a device to WiFi
# Then check if it appears in NoDogSplash
sudo ndsctl clients
```

**Expected Output:**
```
1
client_id=0
ip=192.168.4.32
mac=e6:6a:8f:19:be:b1
token=c8e54152
state=Preauthenticated
```

### Test 4: Test Redirect Script

```bash
# Get device MAC address from ndsctl clients output
# Then run redirect script
sudo ./scripts/redirect_device_portal.sh e6:6a:8f:19:be:b1 "http://192.168.4.1/portal?mac=e6:6a:8f:19:be:b1"

# Verify device is in Preauthenticated state
sudo ndsctl clients | grep "e6:6a:8f:19:be:b1"
```

**Expected:** Device state should be `Preauthenticated`

### Test 5: Test Redirect on Device

1. Connect device to WiFi
2. Run redirect script (from Test 4)
3. On device browser, try to access `http://neverssl.com`
4. **Expected:** Device should be redirected to `http://192.168.4.1:2050/splash.html?tok=...`
5. Splash page should redirect to `http://192.168.4.1/portal?tok=...`
6. Portal should load and show device information

### Test 6: Test Allow Through Script

```bash
# After redirect test, allow device through
sudo ./scripts/allow_device_through.sh e6:6a:8f:19:be:b1

# Verify device is in Authenticated state
sudo ndsctl clients | grep "e6:6a:8f:19:be:b1"
```

**Expected:** Device state should be `Authenticated`

### Test 7: Test from Laravel

```bash
cd /var/www/parental_wifi
php artisan tinker
```

Then:

```php
// Get a device
$device = App\Models\Device::where('mac_address', 'e6:6a:8f:19:be:b1')->first();

// Test redirect
$service = app(App\Services\NoDogSplashService::class);
$result = $service->redirectDeviceToPortal($device);
var_dump($result);  // Should return true

// Test check
$isRedirected = $service->isDeviceRedirected($device);
var_dump($isRedirected);  // Should return true

// Test allow through
$result = $service->allowDeviceThrough($device);
var_dump($result);  // Should return true
```

---

## Troubleshooting

### Issue: NoDogSplash Service Won't Start

**Check:**
1. Service logs: `sudo journalctl -u nodogsplash -n 50`
2. Configuration syntax: `sudo /usr/bin/nodogsplash -c /etc/nodogsplash/nodogsplash.conf -f -d 3`
3. Interface exists: `ip addr show wlan0`
4. Port available: `sudo lsof -i :2050`

**Common Issues:**
- **"Bad configuration option: InternetInterface"** - Remove this line (not valid in v5.0.2)
- **"No such device"** - Check `GatewayInterface` matches actual interface name
- **Port already in use** - Another service may be using port 2050

### Issue: Devices Not Redirecting

**Check:**
1. RedirectURL is configured: `sudo grep RedirectURL /etc/nodogsplash/nodogsplash.conf`
2. NoDogSplash is running: `sudo systemctl status nodogsplash`
3. Device is in client list: `sudo ndsctl clients`
4. Device state is Preauthenticated: `sudo ndsctl clients | grep "state="`
5. iptables rules: `sudo iptables -t nat -L PREROUTING -n -v | grep nds`

**Solution:**
- Ensure `RedirectURL` is set correctly
- Restart NoDogSplash: `sudo systemctl restart nodogsplash`
- Verify device is connected to WiFi

### Issue: "Device not found in NoDogSplash client list"

**Cause:** Device is not connected to WiFi or NoDogSplash hasn't detected it yet.

**Solution:**
1. Make sure device is connected to the WiFi network
2. Wait a few seconds for NoDogSplash to detect the device
3. Try accessing a website to trigger NoDogSplash detection
4. Check client list: `sudo ndsctl clients`

### Issue: Portal Shows "Device not found"

**Cause:** Token lookup failed or device not in database.

**Check:**
1. Token is being passed: Check URL has `?tok=...` parameter
2. Sudo permission for `ndsctl clients`: `sudo grep "ndsctl clients" /etc/sudoers.d/*`
3. Device exists in database: Check device MAC address matches

**Solution:**
- Verify sudo permission is configured correctly
- Check device MAC address in database matches NoDogSplash client list
- Check Laravel logs: `tail -f storage/logs/laravel.log`

### Issue: Redirect Loop When Accessing Portal

**Symptoms:**
- Device tries to access `http://192.168.4.1/portal` but page keeps reloading
- Browser shows redirect loop error
- Portal page never loads
- URL keeps redirecting between `http://192.168.4.1/portal` and `http://192.168.4.1:2050/splash.html`

**Cause:** NoDogSplash is intercepting requests to the portal and redirecting them again, creating an infinite loop. This happens because Preauthenticated users don't have permission to access port 80 on the gateway IP.

**Solution:**
Add firewall rule to allow Preauthenticated users to access the gateway IP:

```bash
sudo nano /etc/nodogsplash/nodogsplash.conf
```

Find `FirewallRuleSet preauthenticated-users` section and add:

```ini
# Allow access to portal on gateway (prevents redirect loop)
FirewallRule allow tcp port 80 to 192.168.4.1
```

The complete section should look like:

```ini
FirewallRuleSet preauthenticated-users {
FirewallRule allow tcp port 53
FirewallRule allow udp port 53
FirewallRule allow tcp port 80 to 192.168.4.1
}
```

Then restart NoDogSplash:

```bash
sudo systemctl restart nodogsplash
```

**Verify the fix:**
```bash
# Check the rule is present
sudo grep -A 5 "preauthenticated-users" /etc/nodogsplash/nodogsplash.conf | grep "192.168.4.1"
```

Should show: `FirewallRule allow tcp port 80 to 192.168.4.1`

**Test:** On a device, try accessing `http://192.168.4.1/portal` directly. It should load without looping.

### Issue: IP Forwarding Resets to 0

**Cause:** IP forwarding not persisted after reboot.

**Solution:**
- Ensure `ip-forward.service` is enabled: `sudo systemctl is-enabled ip-forward.service`
- Check service is running: `sudo systemctl status ip-forward.service`
- Verify after reboot: `sysctl net.ipv4.ip_forward`

### Issue: Script Execution Fails

**Check:**
1. Script permissions: `ls -la scripts/*.sh`
2. Sudoers configuration: `sudo visudo -c`
3. Test manually: `sudo ./scripts/redirect_device_portal.sh AA:BB:CC:DD:EE:FF "http://192.168.4.1/portal"`

**Solution:**
- Make scripts executable: `chmod +x scripts/*.sh`
- Fix sudoers syntax errors
- Verify script paths are correct in sudoers file

---

## Verification Checklist

Use this checklist to verify your setup is complete:

- [ ] NoDogSplash installed (`nodogsplash -version` works)
- [ ] Configuration file exists and is configured correctly
- [ ] NoDogSplash service is running (`systemctl status nodogsplash`)
- [ ] IP forwarding service is enabled and running
- [ ] Splash page exists at `/etc/nodogsplash/htdocs/splash.html`
- [ ] Scripts are executable (`ls -la scripts/*.sh` shows `-rwxr-xr-x`)
- [ ] Sudoers configured (all 5 permissions present, including DNS interception)
- [ ] Portal config exists (`config/portal.php` with correct URL)
- [ ] PortalController supports token lookup
- [ ] Firewall rule allows portal access (prevents redirect loop)
- [ ] DNS interception configured (dnsmasq conf-dir, script executable, sudoers)
- [ ] Redirect script works manually
- [ ] Check script works manually
- [ ] Allow through script works manually
- [ ] Real device redirect test successful
- [ ] Laravel integration test successful
- [ ] Service starts on boot (enabled)
- [ ] Logs show no errors

---

## Summary

This setup provides a complete NoDogSplash integration that:

1. **Redirects devices** to the portal when their time expires
2. **Allows devices through** after they complete quizzes/videos
3. **Uses token-based lookup** to identify devices from NoDogSplash
4. **Intercepts HTTPS requests** via DNS interception (enables HTTPS support)
5. **Persists configuration** across reboots (IP forwarding, services)
6. **Integrates seamlessly** with Laravel application

**Key Components:**
- NoDogSplash service (captive portal)
- Bash scripts (device state management, DNS interception)
- Laravel service (NoDogSplashService)
- Portal controller (token support)
- Systemd services (IP forwarding, NoDogSplash)
- Sudoers configuration (script execution)
- DNS interception (HTTPS support via dnsmasq)

**Key Files:**
- `/etc/nodogsplash/nodogsplash.conf` - NoDogSplash configuration
- `/etc/nodogsplash/htdocs/splash.html` - Splash page
- `/etc/systemd/system/nodogsplash.service` - NoDogSplash service
- `/etc/systemd/system/ip-forward.service` - IP forwarding service
- `/var/www/parental_wifi/scripts/*.sh` - Management scripts
- `/var/www/parental_wifi/config/portal.php` - Portal URL config
- `/var/www/parental_wifi/app/Services/NoDogSplashService.php` - Laravel service
- `/var/www/parental_wifi/app/Http/Controllers/PortalController.php` - Portal controller

---

## DNS Interception for HTTPS Support

### Overview

DNS interception enables HTTPS request interception by redirecting all DNS queries to the gateway IP (192.168.4.1). This allows NoDogSplash to intercept HTTPS requests that would otherwise fail.

### How It Works

1. **When device is redirected:**
   - DNS interception is automatically enabled
   - All DNS queries resolve to `192.168.4.1` (gateway IP)
   - HTTPS requests go to gateway, where NoDogSplash intercepts them

2. **When device is authenticated:**
   - DNS interception is automatically disabled
   - Normal DNS resolution is restored
   - Device can access websites normally

### Configuration

See `docs/DNS_INTERCEPTION_SETUP.md` for complete setup instructions.

**Quick Setup:**
1. Add `conf-dir=/etc/dnsmasq.d/,*.conf` to `/etc/dnsmasq.conf`
2. Create `/etc/dnsmasq.d/` directory
3. Add sudoers permission for `manage_dns_interception.sh`
4. Make script executable: `chmod +x scripts/manage_dns_interception.sh`

### Important Notes

- DNS interception is **global** (affects all devices when enabled)
- Whitelisted devices **never** have DNS interception enabled
- Managed automatically by `NoDogSplashService`
- See `docs/DNS_INTERCEPTION_SETUP.md` for details

## Related Documentation

- **NoDogSplash Integration Details**: `docs/NODOGSPLASH_INTEGRATION.md`
- **NoDogSplash Setup Guide**: `docs/NODOGSPLASH_SETUP_GUIDE.md`
- **NoDogSplash Redirect Fix**: `docs/NODOGSPLASH_REDIRECT_FIX.md`
- **DNS Interception Setup**: `docs/DNS_INTERCEPTION_SETUP.md`
- **Network Control System**: `docs/NETWORK_CONTROL_SYSTEM_ARCHITECTURE.md`
- **Raspberry Pi Services Setup**: `docs/RASPBERRY_PI_SERVICES_SETUP.md`

