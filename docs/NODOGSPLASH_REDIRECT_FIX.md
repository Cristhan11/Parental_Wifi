# NoDogSplash Redirect Fix - Troubleshooting Guide

**Note:** This document describes the fix that was applied. For the complete, verified final setup documentation, see `docs/NODOGSPLASH_SETUP.md`.

## Problem

NoDogSplash was not redirecting HTTP requests to the portal. When devices tried to access `http://google.com`, they would see "This site can't be reached" instead of being redirected to the portal.

## Root Cause

The original implementation used `BlockList` in the NoDogSplash configuration file, which **blocks** devices entirely instead of redirecting them. This is incorrect.

## Solution

NoDogSplash works differently:
1. **Preauthenticated state**: Devices are automatically in this state when they connect. NoDogSplash intercepts HTTP requests from Preauthenticated devices and redirects them to the `RedirectURL` configured in `nodogsplash.conf`.
2. **Authenticated state**: Devices in this state can access the internet normally.

The fix uses `ndsctl` (NoDogSplash control command) to manage device authentication state:
- **To redirect**: Use `ndsctl deauthenticate <token>` to put device back in Preauthenticated state
- **To allow through**: Use `ndsctl authenticate <token>` to authenticate the device

## What Was Changed

All three scripts have been rewritten:

1. **`redirect_device_portal.sh`**: Now uses `ndsctl deauthenticate` instead of adding to BlockList
2. **`allow_device_through.sh`**: Now uses `ndsctl authenticate` instead of removing from BlockList
3. **`check_device_redirected.sh`**: Now checks device state (Preauthenticated vs Authenticated) instead of checking BlockList

## Required Configuration

### 1. Verify NoDogSplash Configuration File

On your Raspberry Pi, check that `/etc/nodogsplash/nodogsplash.conf` has the correct `RedirectURL`:

```bash
sudo nano /etc/nodogsplash/nodogsplash.conf
```

**Required settings:**
```ini
# Gateway Interface (WiFi interface)
GatewayInterface wlan0

# Gateway Address (Access Point IP)
GatewayAddress 192.168.4.1

# Redirect URL - THIS IS CRITICAL!
# This is where Preauthenticated devices will be redirected
RedirectURL http://192.168.4.1/portal
```

**Important**: The `RedirectURL` must be set correctly. This is where NoDogSplash redirects all HTTP requests from Preauthenticated devices.

### 2. Remove BlockList Entries

If you have any `BlockList` entries in your config file, remove them:

```bash
sudo nano /etc/nodogsplash/nodogsplash.conf
```

Remove any lines like:
```
BlockList AA:BB:CC:DD:EE:FF
```

**Why?** BlockList blocks devices entirely - they can't access anything, not even the portal. We don't want that.

### 3. Restart NoDogSplash Service

After making changes:

```bash
sudo systemctl restart nodogsplash
sudo systemctl status nodogsplash
```

## Testing the Fix

### 1. Test Device Connection

Connect a device to the WiFi network and check if it appears in NoDogSplash:

```bash
sudo ndsctl clients
```

You should see output like:
```
1
client_id=0
ip=192.168.4.31
mac=42:b8:77:ae:74:12
token=d864b6e8
state=Preauthenticated
```

### 2. Test Redirect

1. Connect a device to WiFi
2. Open a browser and try to access `http://google.com`
3. **Expected**: Device should be redirected to `http://192.168.4.1/portal?mac=XX:XX:XX:XX:XX:XX`
4. **If not working**: Check NoDogSplash logs:
   ```bash
   sudo journalctl -u nodogsplash -f
   ```

### 3. Test Authentication

After a device completes a quiz/video and earns time, it should be authenticated:

```bash
sudo ndsctl clients
```

The device's state should change from `Preauthenticated` to `Authenticated`.

## Troubleshooting

### Issue: Devices still not redirecting

**Check 1: RedirectURL is configured**
```bash
sudo grep RedirectURL /etc/nodogsplash/nodogsplash.conf
```

Should show: `RedirectURL http://192.168.4.1/portal`

**Check 2: NoDogSplash is running**
```bash
sudo systemctl status nodogsplash
```

Should show: `Active: active (running)`

**Check 3: Device is in client list**
```bash
sudo ndsctl clients
```

Device should appear with `state=Preauthenticated`

**Check 4: iptables rules**
```bash
sudo iptables -t nat -L PREROUTING -n -v | grep nds
```

Should show NoDogSplash rules.

### Issue: "Device not found in NoDogSplash client list"

This means the device is not connected to WiFi or NoDogSplash hasn't detected it yet.

**Solution:**
1. Make sure device is connected to the WiFi network
2. Wait a few seconds for NoDogSplash to detect the device
3. Try accessing a website to trigger NoDogSplash detection

### Issue: Redirect works but shows wrong URL

The `RedirectURL` in `nodogsplash.conf` is what NoDogSplash uses. Our scripts don't modify this - they only manage authentication state.

**To customize the redirect URL:**
1. Edit `/etc/nodogsplash/nodogsplash.conf`
2. Set `RedirectURL http://192.168.4.1/portal`
3. Restart NoDogSplash: `sudo systemctl restart nodogsplash`

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
# CRITICAL: Allow access to portal on gateway (prevents redirect loop)
# This allows Preauthenticated users to access http://192.168.4.1/portal
# without being redirected again. Without this rule, accessing the portal
# causes an infinite redirect loop because NoDogSplash intercepts the
# request and redirects to RedirectURL (which is the same URL).
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

## How It Works Now

### When Device Time Expires:

1. Laravel calls `NoDogSplashService::redirectDeviceToPortal($device)`
2. Script finds device's token using `ndsctl clients`
3. Script calls `ndsctl deauthenticate <token>`
4. Device is put in **Preauthenticated** state
5. Next HTTP request → NoDogSplash intercepts → Redirects to `RedirectURL`

### When Device Completes Quiz/Video:

1. Laravel calls `NoDogSplashService::allowDeviceThrough($device)`
2. Script finds device's token using `ndsctl clients`
3. Script calls `ndsctl authenticate <token>`
4. Device is put in **Authenticated** state
5. Device can now access internet normally

## Alternative Solutions

If NoDogSplash continues to have issues, consider these alternatives:

1. **openNDS**: Fork of NoDogSplash with enhanced features
2. **CoovaChilli**: More advanced captive portal with RADIUS support
3. **iptables + custom redirect**: Use iptables rules to redirect traffic directly

However, the current fix should work correctly if the configuration is correct.

## Summary

The fix changes the approach from using `BlockList` (which blocks) to using `ndsctl` to manage authentication state (which redirects). This is the correct way to use NoDogSplash for captive portal redirects.

**Key Points:**
- ✅ Use `ndsctl deauth` to redirect devices (put in Preauthenticated state)
- ✅ Use `ndsctl auth` to allow devices through (put in Authenticated state)
- ✅ Ensure `RedirectURL` is configured in `nodogsplash.conf`
- ✅ Remove any `BlockList` entries
- ✅ Devices must be in NoDogSplash client list (connected to WiFi)
- ✅ **Firewall rule must allow port 80 to gateway IP for Preauthenticated users** (prevents redirect loop)

