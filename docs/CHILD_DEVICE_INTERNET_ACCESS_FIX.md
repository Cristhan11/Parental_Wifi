# Child Device Internet Access Fix

**Date:** December 21, 2025  
**Issue:** Child devices with time remaining don't have internet access (still in Preauthenticated state)  
**Status:** ✅ Fixed

---

## Problem

Child devices that have time remaining (e.g., 1 hour) were still being blocked by NoDogSplash, showing "No internet connection" errors. This happened because:

1. **Device connects**: NoDogSplash puts device in `Preauthenticated` state (blocked)
2. **Device has time**: Database shows `remaining_time_minutes > 0` (e.g., 60 minutes)
3. **Still blocked**: Device remains in `Preauthenticated` state (not authenticated)
4. **No internet**: Device cannot access internet even though it has time

**Root Cause:**
- `CheckTimeExpiration` job only handled expired devices (time <= 0)
- No mechanism to authenticate devices with time remaining
- Devices stayed in `Preauthenticated` state even when they had time

---

## Solution

Updated `CheckTimeExpiration` job to also authenticate devices with time remaining.

### Updated: `app/Jobs/CheckTimeExpiration.php`

**Added:**
- Logic to authenticate devices with time remaining
- Checks all active devices with `remaining_time_minutes > 0`
- Authenticates them in NoDogSplash (allows internet access)
- Idempotent - safe to call multiple times

**How it works:**
1. Job runs every 1-2 minutes (existing schedule)
2. First, handles expired devices (blocks them) - existing logic
3. **NEW:** Then, authenticates devices with time remaining - new logic
4. Devices with time can now access internet normally

**Flow:**
```
Device has time → CheckTimeExpiration job runs → 
Authenticate device in NoDogSplash → Device can access internet
```

---

## Changes Made

### File: `app/Jobs/CheckTimeExpiration.php`

**Added Step 3:**
```php
// Authenticate devices with time remaining that are still blocked
$activeDevicesWithTime = Device::where('status', 'active')
    ->where('remaining_time_minutes', '>', 0)
    ->get();

foreach ($activeDevicesWithTime as $device) {
    if (!$device->isWhitelisted()) {
        $noDogSplashService->allowDeviceThrough($device);
    }
}
```

---

## Testing

### Test 1: Immediate Fix (Manual Authentication)

On Raspberry Pi, authenticate your child device immediately:

```bash
cd /var/www/parental_wifi
sudo ./scripts/allow_device_through.sh e6:6a:8f:19:be:b1
```

**Replace `e6:6a:8f:19:be:b1` with your device's MAC address.**

**Verify:**
```bash
sudo ndsctl clients | grep "e6:6a:8f:19:be:b1" -A 5
```

**Expected:** `state=Authenticated` (not `Preauthenticated`)

### Test 2: Auto-Authentication (Job)

1. **Wait 1-2 minutes** (for CheckTimeExpiration job to run)
2. **Check device state:**
   ```bash
   sudo ndsctl clients | grep "e6:6a:8f:19:be:b1" -A 5
   ```
3. **Expected:** `state=Authenticated` (auto-authenticated by job)

### Test 3: Internet Access

1. **On child device**, try accessing a website (e.g., `http://neverssl.com`)
2. **Expected:** Website loads normally (not redirected to portal)
3. **If still blocked:** Wait 1-2 minutes for job to run, or run manual fix

### Test 4: Check Logs

```bash
tail -f storage/logs/laravel.log | grep -i "CheckTimeExpiration\|Authenticated device with time"
```

**Expected:** Log entries showing device authentication

---

## Verification Checklist

- [ ] Device has time remaining in database (`remaining_time_minutes > 0`)
- [ ] Device status is `active` (not `blocked`)
- [ ] Device is authenticated in NoDogSplash (`state=Authenticated`)
- [ ] Device can access internet (websites load normally)
- [ ] CheckTimeExpiration job is running (check logs)
- [ ] Auto-authentication works (device gets authenticated automatically)

---

## Troubleshooting

### Issue: Device Still Blocked After Fix

**Check:**
1. Device state in NoDogSplash:
   ```bash
   sudo ndsctl clients | grep "YOUR_MAC" -A 5
   ```
2. If still `Preauthenticated`, authenticate manually:
   ```bash
   sudo ./scripts/allow_device_through.sh YOUR_MAC
   ```
3. Check device has time:
   ```bash
   php artisan tinker
   >>> App\Models\Device::where('mac_address', 'YOUR_MAC')->first(['name', 'remaining_time_minutes', 'status']);
   ```

### Issue: Auto-Authentication Not Working

**Check:**
1. CheckTimeExpiration job is running:
   ```bash
   php artisan schedule:list | grep CheckTimeExpiration
   ```
2. Queue worker is running:
   ```bash
   sudo systemctl status parental-wifi-queue
   ```
3. Check logs for errors:
   ```bash
   tail -n 100 storage/logs/laravel.log | grep -i "error\|exception"
   ```

### Issue: Device Not in NoDogSplash Client List

**Solution:**
- Device must be connected to WiFi
- Wait a few seconds after connecting
- Check: `sudo ndsctl clients | grep "YOUR_MAC"`

---

## Related Files

- `app/Jobs/CheckTimeExpiration.php` - Main fix
- `app/Services/NoDogSplashService.php` - Authentication service
- `scripts/allow_device_through.sh` - Authentication script

---

## Summary

✅ **Fixed:** Child devices with time remaining are now automatically authenticated  
✅ **Method:** CheckTimeExpiration job authenticates devices with time  
✅ **Result:** Devices can access internet when they have time remaining  
✅ **Future:** All devices with time are automatically authenticated every 1-2 minutes

The system now properly handles devices with time:
- **Expired devices**: Blocked and redirected to portal ✅
- **Devices with time**: Authenticated and allowed internet access ✅
- **Whitelisted devices**: Auto-authenticated on connect (from previous fix) ✅

