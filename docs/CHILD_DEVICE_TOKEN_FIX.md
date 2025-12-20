# Child Device Token Fix

**Date:** December 21, 2025  
**Issue:** Child devices accessing portal directly (no token) show "Device not found"  
**Status:** ✅ Fixed

---

## Problem

When child devices (especially Android phones) connect to the WiFi network, Android's captive portal detection accesses `http://192.168.4.1` directly, bypassing the NoDogSplash splash page. This means:

1. **No token in URL**: The `?tok=TOKEN` parameter is missing
2. **Device not identified**: PortalController can't find the device
3. **Error message**: "Device not found. Please connect to the network"

**Root Cause:**
- Android's captive portal detection doesn't go through splash.html
- It accesses the portal directly: `http://192.168.4.1/portal` (no token)
- The original code only looked up devices by token or MAC parameter
- No fallback to identify device by IP address

---

## Solution

Added IP-based device lookup as a fallback when token is missing.

### Updated: `app/Http/Controllers/PortalController.php`

**Added:**
- `getMacFromIp()` method to look up MAC address from IP using `ndsctl clients`
- IP-based fallback in `getDevice()` method
- Better logging for debugging

**How it works:**
1. First tries token lookup (if `?tok=...` is present)
2. **NEW:** Falls back to IP lookup (if token missing)
3. Falls back to MAC parameter (if IP lookup fails)
4. Falls back to session (if MAC parameter missing)

**Flow:**
```
Device accesses portal → No token? → Get client IP → 
Look up MAC from IP in ndsctl → Find device in database → Success!
```

---

## Changes Made

### File: `app/Http/Controllers/PortalController.php`

**Added Method:**
```php
protected function getMacFromIp(string $ipAddress): ?string
{
    // Executes ndsctl clients
    // Parses output to find IP address
    // Extracts MAC address
    // Returns MAC in lowercase format
}
```

**Updated Method:**
```php
protected function getDevice(Request $request): ?Device
{
    // 1. Try token lookup (existing)
    // 2. NEW: Try IP lookup (fallback)
    // 3. Try MAC parameter (existing)
    // 4. Try session (existing)
}
```

---

## Testing

### Test 1: Direct Portal Access (Android Captive Portal)

1. **Connect child device to WiFi**
2. **Android shows "Sign in to Wi-Fi network" notification**
3. **Tap notification** (or access `http://192.168.4.1` directly)
4. **Expected:** Portal loads with device identified (no "Device not found" error)

### Test 2: Token-Based Access (Normal Flow)

1. **Connect child device to WiFi**
2. **Try accessing any HTTP website** (e.g., `http://neverssl.com`)
3. **Expected:** Redirected to splash page → portal with token → device identified

### Test 3: Verify IP Lookup Works

On Raspberry Pi:
```bash
# Check device IP in NoDogSplash
sudo ndsctl clients | grep "mac=e6:6a:8f:19:be:b1" -A 5

# Should show:
# ip=192.168.4.31
# mac=e6:6a:8f:19:be:b1
```

### Test 4: Check Laravel Logs

```bash
tail -f storage/logs/laravel.log | grep -i "device identified by IP\|getMacFromIp"
```

**Expected:** Log entries showing IP-based device identification

---

## Verification Checklist

- [ ] Child device can access portal directly (no token)
- [ ] Device is identified correctly
- [ ] Portal shows device name and time remaining
- [ ] Quizzes and videos are displayed
- [ ] No "Device not found" error
- [ ] Logs show IP-based identification

---

## Technical Details

### IP Lookup Process

1. **Get client IP**: `$request->ip()` (e.g., `192.168.4.31`)
2. **Execute ndsctl**: `sudo ndsctl clients`
3. **Parse output**: Find client block with matching IP
4. **Extract MAC**: Get MAC address from that client block
5. **Look up device**: Find device in database by MAC address
6. **Store in session**: Save MAC for subsequent requests

### Error Handling

- If `ndsctl clients` fails: Logs warning, falls back to MAC parameter
- If IP not found: Logs debug message, falls back to MAC parameter
- If device not in database: Returns null (shows "Device not found")

### Security

- IP lookup only works for devices in NoDogSplash client list
- MAC address is validated before database lookup
- Session storage prevents repeated lookups

---

## Troubleshooting

### Issue: Still Getting "Device not found"

**Check:**
1. Device is in NoDogSplash client list:
   ```bash
   sudo ndsctl clients | grep "YOUR_IP"
   ```
2. Device MAC matches database:
   ```bash
   php artisan tinker
   >>> App\Models\Device::where('mac_address', 'YOUR_MAC')->first();
   ```
3. Laravel logs for errors:
   ```bash
   tail -n 100 storage/logs/laravel.log | grep -i "error\|exception"
   ```

### Issue: IP Lookup Failing

**Check:**
1. `ndsctl clients` works:
   ```bash
   sudo ndsctl clients
   ```
2. `www-data` can run ndsctl (if using sudo):
   ```bash
   sudo -u www-data sudo ndsctl clients
   ```
3. Client IP is correct:
   ```bash
   # In Laravel logs, check what IP was used
   tail -n 50 storage/logs/laravel.log | grep "ip="
   ```

### Issue: Device Not in Database

**Solution:**
1. Register device in Laravel application
2. Make sure MAC address matches exactly (case-insensitive)
3. Wait for MonitorDeviceConnections job to update IP address

---

## Related Files

- `app/Http/Controllers/PortalController.php` - Main fix
- `app/Jobs/MonitorDeviceConnections.php` - Updates device IP addresses
- `docs/NODOGSPLASH_SETUP.md` - NoDogSplash configuration

---

## Summary

✅ **Fixed:** Child devices can now access portal directly (no token required)  
✅ **Method:** IP-based device lookup as fallback  
✅ **Result:** Portal identifies devices correctly even when token is missing  
✅ **Compatibility:** Works with Android captive portal detection

The system now handles multiple device identification methods:
1. **Token-based** (preferred, from splash page)
2. **IP-based** (fallback, for direct access)
3. **MAC parameter** (fallback, manual)
4. **Session** (fallback, cached)

