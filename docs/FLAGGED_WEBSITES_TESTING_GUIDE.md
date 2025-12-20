# Flagged Websites Manual Testing Guide

**Date:** December 20, 2025  
**System:** Parental WiFi Control System  
**Feature:** Flagged Website Management (Monitoring, Not Blocking)  
**Status:** ✅ Fully Implemented & Tested

---

## Quick Start

1. **Access the application**: `http://[PI_IP]/flagged-websites`
2. **Create a flagged website**: Click "Flag Website" → Fill form → Submit
3. **Verify it works**: Check database, test access from device

---

## Overview

This guide provides step-by-step instructions to manually test flagged website functionality. **Flagged websites are different from blocked websites:**

- **Blocked Websites**: ❌ Prevented from accessing (DNS blocking via dnsmasq)
- **Flagged Websites**: ✅ **Allowed but monitored** - access is permitted but logged for parent review

**Key Difference:**
- Flagged websites should be **accessible** (not blocked)
- Visits to flagged websites should be **logged** (monitoring functionality - part of TODO21)
- No DNS blocking is applied to flagged websites

---

## Prerequisites Checklist

Before testing, ensure:

- [ ] Laravel application running on Raspberry Pi
- [ ] Database migrations run successfully (`php artisan migrate`)
- [ ] At least one child device registered in database
- [ ] User authenticated as parent (logged in)
- [ ] Web browser accessible to Pi's IP address
- [ ] Terminal/SSH access to Raspberry Pi (for database checks)

---

## Prerequisites

- ✅ Laravel application running on Raspberry Pi
- ✅ Database migrations run successfully
- ✅ At least one child device registered in database
- ✅ User authenticated as parent
- ✅ FlaggedWebsiteController routes accessible

---

## Test 1: Create Flagged Website

**Objective:** Verify that creating a flagged website works correctly and domain is extracted properly.

### Step 1: Access Create Form

1. **Log in** to the application as a parent user
2. Navigate to: `http://[PI_IP]/flagged-websites`
   - Replace `[PI_IP]` with your Pi's IP address (e.g., `192.168.1.100`)
   - Or use `http://localhost/flagged-websites` if testing locally

3. Click the **"Flag Website"** button (red button in top right)

### Step 2: Fill in the Form

1. **Device:** Select a child device from dropdown (e.g., `CP_ChildDev01`)
   - If no devices appear, create one first via `/accounts/create`

2. **URL:** Enter a test URL:
   - Example: `https://example.com/page`
   - Or: `https://www.facebook.com`
   - Or: `http://test-site.com/some-page`

3. **Reason (optional):** Enter a reason:
   - Example: "Test monitoring"
   - Or leave blank (reason is optional)

### Step 3: Submit and Verify

1. Click **"Flag Website"** button

2. **Expected Result:**
   - ✅ Redirected to flagged websites list page
   - ✅ Green success message: "Website flagged successfully."
   - ✅ New flagged website appears in the list

3. **Verify in List:**
   - URL should be displayed
   - Domain should be extracted (e.g., `example.com` from `https://example.com/page`)
   - Device name should be shown
   - Reason should be displayed (or "-" if empty)

### Step 4: Verify Database Record

**Option A: Using Tinker (Recommended)**

On your Pi terminal, run:

```bash
cd /var/www/parental_wifi  # or your project path
php artisan tinker
```

Then in tinker, run:

```php
// Get the latest flagged website
$flagged = App\Models\FlaggedWebsite::latest()->first();

// Display information
echo "ID: " . $flagged->id . "\n";
echo "URL: " . $flagged->url . "\n";
echo "Domain: " . $flagged->domain . "\n";
echo "Device: " . $flagged->device->name . "\n";
echo "Device MAC: " . $flagged->device->mac_address . "\n";
echo "Reason: " . ($flagged->reason ?? 'None') . "\n";
echo "Created: " . $flagged->created_at . "\n";
```

**Expected Output:**
```
ID: 1
URL: https://example.com/page
Domain: example.com
Device: CP_ChildDev01
Device MAC: AA:BB:CC:DD:EE:FF
Reason: Test monitoring
Created: 2025-12-20 19:30:00
```

**Key Verification:**
- ✅ Domain is correctly extracted from URL (`example.com` from `https://example.com/page`)
- ✅ Database record created successfully
- ✅ Device relationship works (can access `$flagged->device`)

Type `exit` to leave tinker.

**Option B: Using MySQL/MariaDB directly**

```bash
mysql -u root -p parental_wifi
```

```sql
SELECT 
    fw.id,
    fw.url,
    fw.domain,
    d.name AS device_name,
    fw.reason,
    fw.created_at
FROM flagged_websites fw
JOIN devices d ON fw.device_id = d.id
ORDER BY fw.created_at DESC
LIMIT 5;
```

### Step 3: Verify Website is NOT Blocked

**Important:** Flagged websites should be **accessible** (not blocked like blocked websites).

On your Pi terminal, run:

```bash
# Test DNS resolution (should return REAL IP, not 127.0.0.1)
dig @127.0.0.1 example.com +short
```

**Expected Output:**
```
93.184.216.34
# (or other real IP addresses - NOT 127.0.0.1)
```

**Key Verification:**
- ✅ DNS returns real IP addresses (not 127.0.0.1)
- ✅ Website is NOT blocked (unlike blocked websites)
- ✅ No dnsmasq config entry for flagged websites

### Step 4: Test from Device

On your test device:
- Try accessing the flagged website: `http://example.com`
- **Expected:** Should load normally (website is accessible)
- **Note:** Visit should be logged (monitoring functionality - part of TODO21)

---

## Test 2: List Flagged Websites

**Objective:** Verify that listing flagged websites works correctly with filtering.

### Step 1: View Flagged Websites List

1. Navigate to: `http://[PI_IP]/flagged-websites`

2. **Expected:**
   - List shows all flagged websites for your devices
   - Each entry shows: URL, Domain, Device, Reason, Created Date
   - Pagination works if more than 20 entries

### Step 2: Filter by Device

1. On the flagged websites list page, select a device from the filter dropdown
2. Click "Filter" or submit the form

**Expected:**
- List shows only flagged websites for the selected device
- Filter persists in URL parameters

### Step 3: Search by Domain/URL

1. On the flagged websites list page, enter a search term in the search box
2. Click "Search" or submit the form

**Expected:**
- List shows only flagged websites matching the search term (domain or URL)
- Search is case-insensitive

---

## Test 3: Edit Flagged Website

**Objective:** Verify that editing a flagged website works correctly, including domain re-extraction.

### Step 1: Edit Flagged Website

1. Navigate to: `http://[PI_IP]/flagged-websites`
2. Click **"Edit"** button for a flagged website
3. Modify the form:
   - Change URL to: `https://test.com/different-page`
   - Change Reason to: "Updated reason"
4. Click **"Update Website"** button

### Step 2: Verify Changes

On your Pi terminal, run:

```bash
php artisan tinker
```

```php
$flagged = App\Models\FlaggedWebsite::latest()->first();
echo "URL: " . $flagged->url . "\n";
echo "Domain: " . $flagged->domain . "\n";
echo "Reason: " . $flagged->reason . "\n";
```

**Expected Output:**
```
URL: https://test.com/different-page
Domain: test.com
Reason: Updated reason
```

**Key Verification:**
- ✅ URL updated correctly
- ✅ Domain re-extracted from new URL (`test.com` from `https://test.com/different-page`)
- ✅ Reason updated correctly

---

## Test 4: Delete Flagged Website

**Objective:** Verify that deleting a flagged website works correctly.

### Step 1: Delete Flagged Website

1. Navigate to: `http://[PI_IP]/flagged-websites`
2. Click **"Delete"** button for a flagged website
3. Confirm deletion

### Step 2: Verify Deletion

On your Pi terminal, run:

```bash
php artisan tinker
```

```php
// Check if flagged website was deleted
$flagged = App\Models\FlaggedWebsite::where('domain', 'example.com')->first();
if ($flagged) {
    echo "Still exists in database\n";
} else {
    echo "Successfully removed from database\n";
}

// Or check all flagged websites
App\Models\FlaggedWebsite::select('id','domain','device_id')->get();
```

**Expected:**
- ✅ Flagged website removed from database
- ✅ No longer appears in list
- ✅ Device relationship cascade works (if device deleted, flagged websites deleted)

---

## Test 5: Domain Extraction Edge Cases

**Objective:** Verify that domain extraction works correctly for various URL formats.

### Test Cases

Create flagged websites with different URL formats and verify domain extraction:

| Input URL | Expected Domain |
|-----------|----------------|
| `https://example.com` | `example.com` |
| `http://www.example.com` | `example.com` |
| `https://subdomain.example.com/page` | `subdomain.example.com` |
| `https://example.com:8080/path` | `example.com` |
| `https://user:pass@example.com` | `example.com` |

**Steps:**
1. Create flagged website with each URL format
2. Verify domain extraction in database:

```bash
php artisan tinker
```

```php
$flagged = App\Models\FlaggedWebsite::where('url', 'YOUR_URL')->first();
echo "URL: " . $flagged->url . "\n";
echo "Domain: " . $flagged->domain . "\n";
```

**Expected:**
- ✅ Domain correctly extracted for all URL formats
- ✅ No protocol, port, path, or credentials in domain field
- ✅ Subdomains preserved when present

---

## Test 6: Authorization

**Objective:** Verify that users can only manage flagged websites for their own devices.

### Step 1: Test Authorization

1. Create a flagged website for Device A (owned by User A)
2. Log in as User B (different user)
3. Try to edit/delete the flagged website

**Expected:**
- ✅ Access denied (403 Forbidden or redirect)
- ✅ User B cannot see/edit/delete flagged websites for User A's devices
- ✅ Authorization policy works correctly

---

## Test 7: Multiple Devices

**Objective:** Verify that flagged websites work correctly for multiple devices.

### Step 1: Create Flagged Websites for Different Devices

1. Create flagged website for Device A: `example.com`
2. Create flagged website for Device B: `test.com`

### Step 2: Verify Separation

On your Pi terminal, run:

```bash
php artisan tinker
```

```php
// Check flagged websites for Device A
$deviceA = App\Models\Device::where('name', 'DeviceA')->first();
$flaggedA = App\Models\FlaggedWebsite::where('device_id', $deviceA->id)->get();
echo "Device A flagged websites: " . $flaggedA->pluck('domain')->implode(', ') . "\n";

// Check flagged websites for Device B
$deviceB = App\Models\Device::where('name', 'DeviceB')->first();
$flaggedB = App\Models\FlaggedWebsite::where('device_id', $deviceB->id)->get();
echo "Device B flagged websites: " . $flaggedB->pluck('domain')->implode(', ') . "\n";
```

**Expected:**
- ✅ Each device has separate flagged websites
- ✅ Flagged websites are device-specific
- ✅ No cross-device interference

---

## Comparison: Blocked vs Flagged Websites

| Feature | Blocked Websites | Flagged Websites |
|---------|------------------|------------------|
| **Access** | ❌ Blocked (DNS redirects to 127.0.0.1) | ✅ Allowed (normal DNS resolution) |
| **DNS Blocking** | ✅ Yes (dnsmasq config) | ❌ No (no dnsmasq config) |
| **Purpose** | Prevent access | Monitor access |
| **Logging** | Access attempts logged | Visits logged (TODO21) |
| **Block Types** | URL/Domain/App | URL only |
| **Related Domains** | ✅ Yes (for apps) | ❌ No |
| **Subdomain Blocking** | ✅ Yes | ❌ No |

---

## Troubleshooting

### Issue: Domain Not Extracted Correctly

**Symptoms:** Domain field is empty or contains full URL

**Solution:**
- Check `DomainBlockingService::normalizeDomain()` method
- Verify URL format is valid
- Check Laravel logs for errors

### Issue: Flagged Website Appears Blocked

**Symptoms:** Flagged website returns 127.0.0.1 (should be accessible)

**Possible Causes:**
1. Same domain is also in blocked websites list
2. DNS cache issue

**Solution:**
```bash
# Check if domain is also blocked
php artisan tinker
```

```php
$domain = 'example.com';
$blocked = App\Models\BlockedWebsite::where('domain', $domain)->first();
if ($blocked) {
    echo "Domain is also blocked - that's why it's not accessible\n";
} else {
    echo "Domain is not blocked - check DNS cache\n";
}
```

### Issue: Authorization Not Working

**Symptoms:** User can edit/delete flagged websites for other users' devices

**Solution:**
- Check `FlaggedWebsitePolicy` exists
- Verify policy is registered in `AuthServiceProvider`
- Check `$this->authorize()` calls in controller

---

## Quick Reference Commands

### Check Database
```bash
php artisan tinker
>>> App\Models\FlaggedWebsite::with('device')->get();
```

### Test DNS Resolution
```bash
dig @127.0.0.1 example.com +short
# Should return REAL IP (not 127.0.0.1 for flagged websites)
```

### Check Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep -i "flagged"
```

---

## Test Results Checklist

- [ ] Test 1: Create flagged website - Domain extraction works
- [ ] Test 1: Create flagged website - Website is NOT blocked (accessible)
- [ ] Test 2: List flagged websites - Filtering and search work
- [ ] Test 3: Edit flagged website - Domain re-extraction works
- [ ] Test 4: Delete flagged website - Removal works
- [ ] Test 5: Domain extraction - Edge cases handled correctly
- [ ] Test 6: Authorization - Users can only manage their own devices' flagged websites
- [ ] Test 7: Multiple devices - Separation works correctly

---

## Next Steps

After completing CRUD testing:

1. **TODO21 - Monitoring & Logging**: Implement detection and logging of flagged website visits
   - ParseNetworkLogs job should create AccessAttempt records
   - Dashboard should show flagged website visits
   - Real-time notifications (WebSockets)

2. **Integration Testing**: Test flagged website visit detection when monitoring is implemented

---

## Related Documentation

- `docs/WEBSITE_MANAGEMENT_IMPLEMENTATION.md` - Complete implementation details
- `docs/DOMAIN_BLOCKING_TESTING_GUIDE.md` - Blocked websites testing (for comparison)
- `docs/DEVICE_MANAGEMENT_IMPLEMENTATION.md` - Device management system

