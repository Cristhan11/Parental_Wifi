# DNS Interception Setup for HTTPS Support

## Overview

This document explains how to configure DNS interception to enable HTTPS request interception in the captive portal system. DNS interception redirects all DNS queries to the gateway IP (192.168.4.1), allowing NoDogSplash to intercept HTTPS requests that would otherwise fail.

## Why DNS Interception is Needed

**The Problem:**
- NoDogSplash can only intercept HTTP requests directly
- HTTPS requests are encrypted and cannot be intercepted cleanly
- When a device tries to access `https://google.com`, it fails or shows certificate errors
- Devices may not automatically detect the captive portal

**The Solution:**
- Redirect all DNS queries to the gateway IP (192.168.4.1)
- All domains resolve to the gateway, so all requests (HTTP and HTTPS) go to the gateway
- NoDogSplash intercepts these requests and redirects to the portal
- This works for both HTTP and HTTPS sites

## Prerequisites

- dnsmasq installed and running
- NoDogSplash installed and configured
- Root/sudo access to modify system configuration

## Configuration Steps

### Step 1: Configure dnsmasq to use config directory

**File:** `/etc/dnsmasq.conf`

Add this line to enable additional config files:

```ini
# Load additional configuration files from /etc/dnsmasq.d/
conf-dir=/etc/dnsmasq.d/,*.conf
```

**What this does:**
- Allows dnsmasq to load additional config files from `/etc/dnsmasq.d/`
- Files with `.conf` extension are automatically loaded
- This allows us to dynamically manage DNS interception without modifying the main config

**After making changes:**
```bash
sudo systemctl reload dnsmasq
```

### Step 2: Create config directory (if it doesn't exist)

```bash
sudo mkdir -p /etc/dnsmasq.d
```

### Step 3: Verify script permissions

The `manage_dns_interception.sh` script must be executable:

```bash
cd /var/www/parental_wifi
sudo chmod +x scripts/manage_dns_interception.sh
```

### Step 4: Configure sudoers permission

**File:** `/etc/sudoers` or `/etc/sudoers.d/www-data-nodogsplash`

Add this line to allow www-data to execute the DNS interception script:

```
# DNS interception script for HTTPS support
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/manage_dns_interception.sh
```

**Edit sudoers:**
```bash
sudo visudo
```

**Verify configuration:**
```bash
sudo grep "manage_dns_interception" /etc/sudoers /etc/sudoers.d/*
```

## How It Works

### When Device is Redirected (Preauthenticated)

1. **NoDogSplash deauthenticates device:**
   - `ndsctl deauth <token>` puts device in Preauthenticated state
   - NoDogSplash will intercept HTTP requests

2. **DNS interception is enabled:**
   - `manage_dns_interception.sh` adds `address=/#/192.168.4.1` to dnsmasq config
   - All DNS queries resolve to gateway IP (192.168.4.1)
   - dnsmasq is reloaded to apply changes

3. **Device tries to access website:**
   - Device queries DNS for `google.com`
   - dnsmasq returns `192.168.4.1` (gateway IP)
   - Device connects to `https://192.168.4.1` or `http://192.168.4.1`
   - NoDogSplash intercepts the request
   - Device is redirected to portal page

### When Device is Authenticated

1. **NoDogSplash authenticates device:**
   - `ndsctl auth <token>` puts device in Authenticated state
   - Device can access internet normally

2. **DNS interception is disabled:**
   - `manage_dns_interception.sh` removes `address=/#/192.168.4.1` from dnsmasq config
   - Normal DNS resolution is restored
   - dnsmasq is reloaded to apply changes

3. **Device can access websites normally:**
   - DNS queries resolve to real IP addresses
   - Device connects to actual websites
   - Normal internet access

## Important Notes

### Global DNS Interception

**Current Implementation:**
- DNS interception is **global** - when enabled, it affects ALL devices
- This is because dnsmasq doesn't support per-source-IP filtering for the `address` directive
- When any device is Preauthenticated, DNS interception is enabled for all devices

**Impact:**
- Authenticated devices will also have DNS queries redirected to gateway
- However, NoDogSplash allows Authenticated devices through, so they can still access the internet
- The DNS interception just ensures all requests go through the gateway first

**Future Improvement:**
- Could use iptables to redirect DNS queries from specific IPs
- Or use dnsmasq's `dhcp-host` with tags for per-device filtering
- Current implementation is simpler and works for the use case

### Whitelisted Devices

- Whitelisted devices **never** have DNS interception enabled
- The system checks `$device->isWhitelisted()` before enabling interception
- Whitelisted devices always get normal DNS resolution

### IP Address Changes

- Devices may get different IP addresses when reconnecting
- The script queries `ndsctl clients` to get the current IP address
- This ensures DNS interception uses the correct IP

## Testing

### Test 1: Verify DNS Interception Script

```bash
# Test with a connected device
sudo /var/www/parental_wifi/scripts/manage_dns_interception.sh AA:BB:CC:DD:EE:FF add

# Check dnsmasq config
sudo cat /etc/dnsmasq.d/captive-portal.conf

# Should show: address=/#/192.168.4.1
```

### Test 2: Test DNS Resolution

On a device with DNS interception enabled:

```bash
# Query DNS for google.com
nslookup google.com 192.168.4.1

# Should return: 192.168.4.1 (gateway IP)
```

### Test 3: Test HTTPS Interception

1. Connect device to WiFi
2. Redirect device to portal (time expires)
3. Try to access `https://google.com` on the device
4. **Expected:** Device should be redirected to portal page

### Test 4: Test Normal DNS After Authentication

1. Authenticate device (complete quiz/video)
2. DNS interception should be disabled
3. Query DNS: `nslookup google.com 192.168.4.1`
4. **Expected:** Should return real Google IP (not 192.168.4.1)

## Troubleshooting

### Issue: DNS interception not working

**Check:**
1. dnsmasq config directory exists: `ls -la /etc/dnsmasq.d/`
2. Config file exists: `cat /etc/dnsmasq.d/captive-portal.conf`
3. dnsmasq is using conf-dir: `grep conf-dir /etc/dnsmasq.conf`
4. dnsmasq is running: `systemctl status dnsmasq`

**Solution:**
- Ensure `conf-dir=/etc/dnsmasq.d/,*.conf` is in `/etc/dnsmasq.conf`
- Reload dnsmasq: `sudo systemctl reload dnsmasq`

### Issue: Script execution fails

**Check:**
1. Script is executable: `ls -la scripts/manage_dns_interception.sh`
2. Sudoers permission: `sudo grep "manage_dns_interception" /etc/sudoers.d/*`
3. Test manually: `sudo scripts/manage_dns_interception.sh AA:BB:CC:DD:EE:FF add`

**Solution:**
- Make script executable: `chmod +x scripts/manage_dns_interception.sh`
- Add sudoers permission (see Step 4 above)

### Issue: DNS still resolves to real IPs

**Check:**
1. Config file has rule: `grep "address=/#/" /etc/dnsmasq.d/captive-portal.conf`
2. dnsmasq was reloaded: Check logs `journalctl -u dnsmasq -n 20`

**Solution:**
- Ensure rule is present in config file
- Reload dnsmasq: `sudo systemctl reload dnsmasq`
- Restart dnsmasq if reload doesn't work: `sudo systemctl restart dnsmasq`

## Configuration Files

### dnsmasq Main Config

**File:** `/etc/dnsmasq.conf`

Required line:
```ini
conf-dir=/etc/dnsmasq.d/,*.conf
```

### DNS Interception Config

**File:** `/etc/dnsmasq.d/captive-portal.conf`

This file is automatically managed by the script. Content:
```ini
# Captive Portal DNS Interception
# This file is automatically managed by Laravel scripts
# DO NOT EDIT MANUALLY

address=/#/192.168.4.1
```

## Integration with NoDogSplashService

The DNS interception is automatically managed by `NoDogSplashService`:

- **When redirecting device:** `redirectDeviceToPortal()` calls `enableDnsInterception()`
- **When allowing device through:** `allowDeviceThrough()` calls `disableDnsInterception()`
- **Whitelisted devices:** DNS interception is never enabled

## Summary

DNS interception enables HTTPS request interception by redirecting all DNS queries to the gateway IP. This allows NoDogSplash to intercept both HTTP and HTTPS requests, solving the HTTPS limitation.

**Key Points:**
- DNS interception is global (affects all devices when enabled)
- Automatically enabled when device is redirected
- Automatically disabled when device is authenticated
- Whitelisted devices never have DNS interception enabled
- Managed automatically by NoDogSplashService

