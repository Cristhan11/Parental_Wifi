# Parent Device Redirect Fix

**Date:** December 20, 2025  
**Issue:** Parent devices are being redirected to portal even though they're whitelisted  
**Status:** ✅ Fixed

---

## Problem

Parent devices registered with `status='whitelisted'` and `role='parent'` were still being redirected to the portal when connecting to the WiFi network. This happened because:

1. **NoDogSplash State**: When devices connect, they start in `Preauthenticated` state (redirected)
2. **Database vs NoDogSplash**: Device status in database (`whitelisted`) doesn't automatically sync with NoDogSplash state
3. **Missing Auto-Authentication**: No mechanism to automatically authenticate whitelisted devices when they connect

---

## Solution

### 1. Immediate Fix: Authenticate Parent Device Manually

Run this command on your Raspberry Pi to authenticate your parent device immediately:

```bash
cd /var/www/parental_wifi
sudo ./scripts/allow_device_through.sh 30:03:C8:0A:45:AF
```

Replace `30:03:C8:0A:45:AF` with your parent device's MAC address.

**Verify it worked:**
```bash
sudo ndsctl clients | grep "30:03:C8:0A:45:AF"
```

Should show `state=Authenticated` (not `Preauthenticated`).

### 2. Long-Term Fix: Auto-Authentication for Whitelisted Devices

**Updated:** `app/Jobs/MonitorDeviceConnections.php`

The `MonitorDeviceConnections` job now automatically authenticates whitelisted devices when they connect:

- **Runs every 2 minutes** (via scheduler)
- **Detects whitelisted devices** when they connect
- **Automatically authenticates them** in NoDogSplash
- **Allows internet access** without portal redirect

**How it works:**
1. Job detects device connection
2. Checks if device status is `whitelisted`
3. Calls `NoDogSplashService::allowDeviceThrough($device)`
4. Device is authenticated in NoDogSplash
5. Device can access internet normally

---

## Changes Made

### File: `app/Jobs/MonitorDeviceConnections.php`

**Added:**
- `NoDogSplashService` dependency injection
- Auto-authentication logic for whitelisted devices
- Error handling for authentication failures

**Code Added:**
```php
// Auto-authenticate whitelisted devices in NoDogSplash
if ($device->isWhitelisted()) {
    try {
        $noDogSplashService->allowDeviceThrough($device);
        Log::debug('Auto-authenticated whitelisted device in NoDogSplash', [...]);
    } catch (\Exception $e) {
        Log::debug('Could not auto-authenticate whitelisted device', [...]);
    }
}
```

---

## Testing

### Test 1: Manual Authentication

1. Connect parent device to WiFi
2. Run authentication script:
   ```bash
   sudo ./scripts/allow_device_through.sh YOUR_MAC_ADDRESS
   ```
3. Verify state:
   ```bash
   sudo ndsctl clients | grep "YOUR_MAC"
   ```
4. **Expected:** `state=Authenticated`
5. Try accessing a website - should work normally

### Test 2: Auto-Authentication

1. Disconnect parent device from WiFi
2. Wait 2 minutes (for MonitorDeviceConnections job to run)
3. Reconnect parent device to WiFi
4. Wait up to 2 minutes (for next job run)
5. Check state:
   ```bash
   sudo ndsctl clients | grep "YOUR_MAC"
   ```
6. **Expected:** `state=Authenticated` (auto-authenticated by job)

### Test 3: Verify No Redirect

1. Connect parent device to WiFi
2. Open browser
3. Try accessing `http://neverssl.com` or any HTTP site
4. **Expected:** Website loads normally (not redirected to portal)

---

## Verification Checklist

- [ ] Parent device status is `whitelisted` in database
- [ ] Parent device role is `parent` in database
- [ ] Parent device is authenticated in NoDogSplash (`state=Authenticated`)
- [ ] Parent device can access internet without redirect
- [ ] MonitorDeviceConnections job is running (check logs)
- [ ] Auto-authentication works on reconnect

---

## Troubleshooting

### Issue: Device Still Redirected After Authentication

**Check:**
1. Device state in NoDogSplash:
   ```bash
   sudo ndsctl clients | grep "YOUR_MAC"
   ```
2. If still `Preauthenticated`, authenticate again:
   ```bash
   sudo ./scripts/allow_device_through.sh YOUR_MAC
   ```

### Issue: Auto-Authentication Not Working

**Check:**
1. MonitorDeviceConnections job logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "MonitorDeviceConnections"
   ```
2. Verify job is running:
   ```bash
   php artisan schedule:list
   ```
3. Check queue worker:
   ```bash
   sudo systemctl status parental-wifi-queue
   ```

### Issue: Token Not in URL

**This is a separate issue** - the token should be passed by the splash page. Check:

1. Splash page exists: `/etc/nodogsplash/htdocs/splash.html`
2. Splash page redirects with token: `window.location.href = "http://192.168.4.1/portal?tok=" + token;`
3. NoDogSplash config: `RedirectURL` should be commented out

---

## Related Files

- `app/Jobs/MonitorDeviceConnections.php` - Auto-authentication logic
- `app/Services/NoDogSplashService.php` - Authentication service
- `scripts/allow_device_through.sh` - Authentication script
- `scripts/fix_parent_device.sh` - Quick fix script

---

## Summary

✅ **Fixed:** Parent devices are now automatically authenticated when they connect  
✅ **Result:** Parent devices can access internet without portal redirect  
✅ **Future:** All whitelisted devices (parent or otherwise) are auto-authenticated

The system now properly handles parent devices:
- Database status: `whitelisted` ✅
- NoDogSplash state: `Authenticated` ✅
- Internet access: Normal (no redirect) ✅

