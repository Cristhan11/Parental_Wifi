# Raspberry Pi Website Management Testing Guide

## Overview

This comprehensive guide covers testing website management functionality on Raspberry Pi, including both automated and manual testing procedures. The goal is to verify that DNS blocking works correctly with real devices and dnsmasq.

**What is Website Management?**
- Blocking websites/domains for specific devices
- Domain-level blocking (blocks entire domain and optionally subdomains)
- App-level blocking (blocks app with all related domains)
- DNS-based blocking using dnsmasq (works for both browsers and mobile apps)

**Why Test on Raspberry Pi?**
- DNS blocking requires dnsmasq service (only available on Linux)
- Shell scripts need sudo privileges (configured on Raspberry Pi)
- Real device testing requires actual network setup
- Integration with dnsmasq can only be tested on the target platform

---

## Table of Contents

1. [Pre-Testing Setup](#pre-testing-setup)
2. [Automated Testing](#automated-testing)
3. [Manual Testing Procedures](#manual-testing-procedures)
4. [Test Scenarios](#test-scenarios)
5. [Verification Commands](#verification-commands)
6. [Troubleshooting](#troubleshooting)
7. [Test Results Documentation](#test-results-documentation)

---

## Pre-Testing Setup

### 1. Deploy Code to Raspberry Pi

```bash
# On Raspberry Pi
cd /var/www/parental_wifi

# Pull latest code (or copy files)
git pull  # or however you deploy

# Run migrations
php artisan migrate

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 2. Update Sudoers Configuration

DNS blocking scripts need sudo privileges to modify dnsmasq configuration.

**Follow the guide:** `docs/SUDOERS_UPDATE_DNS_BLOCKING.md`

**Quick steps:**
```bash
# Edit sudoers file
sudo nano /etc/sudoers.d/parental-wifi-scripts

# Add these lines (replace /var/www/parental_wifi with your actual path):
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/block_domain.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/unblock_domain.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/update_dnsmasq_blocklist.sh

# Verify syntax
sudo visudo -c -f /etc/sudoers.d/parental-wifi-scripts

# Set correct permissions
sudo chmod 0440 /etc/sudoers.d/parental-wifi-scripts
sudo chown root:root /etc/sudoers.d/parental-wifi-scripts
```

**Note:** If your web server runs as a different user (e.g., `snasna`), replace `www-data` with that user.

### 3. Verify Scripts Are Executable

```bash
chmod +x scripts/block_domain.sh
chmod +x scripts/unblock_domain.sh
chmod +x scripts/update_dnsmasq_blocklist.sh
```

### 4. Verify Services Are Running

```bash
# Check dnsmasq
sudo systemctl status dnsmasq

# If not running, start it:
sudo systemctl start dnsmasq
sudo systemctl enable dnsmasq

# Check Nginx
sudo systemctl status nginx

# Check PHP-FPM (adjust version as needed)
sudo systemctl status php8.3-fpm

# Check database
sudo systemctl status mariadb  # or mysql
```

### 5. Verify dnsmasq Configuration

```bash
# Check dnsmasq config directory exists
ls -la /etc/dnsmasq.d/

# If it doesn't exist, create it:
sudo mkdir -p /etc/dnsmasq.d

# Verify dnsmasq is configured to use this directory
sudo grep -r "conf-dir" /etc/dnsmasq.conf
# Should see: conf-dir=/etc/dnsmasq.d/,*.conf
```

### 6. Create Test Device (if needed)

If you don't have a device in the database:

1. Log in to the Laravel application
2. Navigate to Devices
3. Add a test device with a valid MAC address
4. Note the device ID and MAC address for testing

---

## Automated Testing

### Running the Test Script

The automated test script checks:
- Pre-testing checklist (services, scripts, migrations)
- DNS blocking script syntax and validation
- dnsmasq configuration
- Database schema
- Integration (classes, routes)
- Functional DNS blocking (Phase 6)
- Sudoers configuration (Phase 7)

**Run full test suite:**
```bash
cd /var/www/parental_wifi
./scripts/test-website-management.sh
```

**Run without functional tests (faster, for quick checks):**
```bash
./scripts/test-website-management.sh --skip-functional
```

### Interpreting Test Results

**Phase 1: Pre-Testing Checklist**
- ✅ All checks pass: Ready for testing
- ⚠️ Warnings: Non-critical issues (e.g., service not running)
- ❌ Failures: Critical issues (e.g., scripts missing, migrations not run)

**Phase 2: DNS Blocking Scripts Tests**
- Validates script syntax and input validation
- Should all pass ✅

**Phase 3: dnsmasq Configuration Tests**
- Checks dnsmasq directory and service
- Should all pass ✅

**Phase 4: Database Tests**
- Verifies database connection and schema
- Should all pass ✅

**Phase 5: Integration Tests**
- Checks Laravel classes and routes
- Should all pass ✅

**Phase 6: Functional DNS Blocking Tests** (requires real device)
- Tests actual domain blocking/unblocking
- Verifies dnsmasq config generation
- Tests DNS resolution
- May show warnings if no device available

**Phase 7: Sudoers Configuration Verification**
- Checks sudoers file syntax
- Verifies DNS blocking scripts are in sudoers
- Tests script execution with sudo

### Expected Output

```
🧪 Website Management Testing

Phase 1: Pre-Testing Checklist
   ✅ Laravel application found
   ✅ dnsmasq service is running
   ✅ Scripts directory exists
   ✅ scripts/block_domain.sh exists
   ✅ scripts/block_domain.sh is executable
   ✅ Migration has been run (block_type column exists)
✅ Phase 1: Pre-Testing Checklist

[... other phases ...]

Test Summary
Total tests: 7
Passed: 7
Failed: 0

✅ All automated tests passed!
```

---

## Manual Testing Procedures

### Test 1: Domain-Level Blocking

**Objective:** Verify that blocking a domain prevents access to that domain.

**Steps:**

1. **Log in to Laravel application**
   - Navigate to: `http://[RASPBERRY_PI_IP]/blocked-websites`

2. **Create blocked website**
   - Click "Block Website"
   - Select a test device
   - Blocking Type: **Domain**
   - Domain: `test-blocked-12345.com` (use a unique, non-existent domain)
   - Enable "Block subdomains" (optional)
   - Reason: "Test domain blocking"
   - Click "Block Website"

3. **Verify dnsmasq config**
   ```bash
   # Get device MAC address (from database or UI)
   DEVICE_MAC="AA:BB:CC:DD:EE:FF"  # Replace with actual MAC
   
   # Check config file
   sudo cat /etc/dnsmasq.d/blocked-domains-${DEVICE_MAC}.conf
   ```
   
   **Expected output:**
   ```
   address=/test-blocked-12345.com/127.0.0.1
   address=/.test-blocked-12345.com/127.0.0.1  # If subdomains enabled
   ```

4. **Test DNS resolution** (from test device or Raspberry Pi)
   ```bash
   nslookup test-blocked-12345.com
   # or
   dig test-blocked-12345.com @127.0.0.1
   ```
   
   **Expected output:**
   ```
   Address: 127.0.0.1
   ```

5. **Test browser access** (from test device)
   - Try to access: `http://test-blocked-12345.com`
   - **Expected:** Connection fails or times out

6. **Verify in Laravel**
   - Check blocked websites list
   - Verify domain appears in the list
   - Verify block type is "Domain"

**Success Criteria:**
- ✅ Domain appears in dnsmasq config
- ✅ DNS resolution returns 127.0.0.1
- ✅ Browser cannot access domain
- ✅ Domain appears in blocked websites list

---

### Test 2: App-Level Blocking

**Objective:** Verify that blocking an app blocks all related domains.

**Steps:**

1. **Create blocked website (App type)**
   - Navigate to blocked websites
   - Click "Block Website"
   - Select a test device
   - Blocking Type: **App**
   - Domain: `facebook.com`
   - App Name: `Facebook` (optional)
   - Wait for related domains to appear (AJAX)
   - Verify related domains list includes:
     - `api.facebook.com`
     - `graph.facebook.com`
     - `m.facebook.com`
     - etc.
   - Click "Block Website"

2. **Verify dnsmasq config**
   ```bash
   sudo cat /etc/dnsmasq.d/blocked-domains-${DEVICE_MAC}.conf
   ```
   
   **Expected:** All related domains should be in config:
   ```
   address=/facebook.com/127.0.0.1
   address=/api.facebook.com/127.0.0.1
   address=/graph.facebook.com/127.0.0.1
   address=/m.facebook.com/127.0.0.1
   [... other related domains ...]
   ```

3. **Test DNS resolution for related domains**
   ```bash
   nslookup api.facebook.com
   nslookup graph.facebook.com
   ```
   
   **Expected:** All should return 127.0.0.1

4. **Test mobile app** (from test device)
   - Try to use Facebook app
   - **Expected:** App cannot connect (all API calls blocked)

5. **Test browser** (from test device)
   - Try to access: `http://facebook.com`
   - **Expected:** Connection fails or times out

**Success Criteria:**
- ✅ All related domains detected
- ✅ All related domains in dnsmasq config
- ✅ DNS resolution returns 127.0.0.1 for all domains
- ✅ Mobile app cannot connect
- ✅ Browser cannot access website

---

### Test 3: Unblocking

**Objective:** Verify that unblocking a domain restores access.

**Steps:**

1. **Block a test domain** (follow Test 1)

2. **Verify domain is blocked**
   ```bash
   nslookup test-blocked-12345.com
   # Should return: 127.0.0.1
   ```

3. **Unblock domain**
   - Navigate to blocked websites list
   - Find the test domain
   - Click "Delete" or "Remove"
   - Confirm deletion

4. **Verify dnsmasq config updated**
   ```bash
   sudo cat /etc/dnsmasq.d/blocked-domains-${DEVICE_MAC}.conf
   ```
   
   **Expected:** Domain should be removed from config

5. **Test DNS resolution**
   ```bash
   nslookup test-blocked-12345.com
   ```
   
   **Expected:** Returns normal DNS result (or NXDOMAIN if domain doesn't exist)

6. **Test browser access** (from test device)
   - Try to access: `http://test-blocked-12345.com`
   - **Expected:** Domain is accessible (or shows normal error if domain doesn't exist)

**Success Criteria:**
- ✅ Domain removed from dnsmasq config
- ✅ DNS resolution returns normal result
- ✅ Domain is accessible again

---

### Test 4: Per-Device Blocking

**Objective:** Verify that blocking a domain for one device doesn't affect other devices.

**Steps:**

1. **Ensure you have at least 2 devices in database**

2. **Block domain for Device A only**
   - Create blocked website
   - Select Device A
   - Domain: `test-device-a-only.com`
   - Click "Block Website"

3. **Verify Device A's config**
   ```bash
   DEVICE_A_MAC="AA:BB:CC:DD:EE:FF"  # Device A MAC
   sudo cat /etc/dnsmasq.d/blocked-domains-${DEVICE_A_MAC}.conf
   ```
   
   **Expected:** Domain should be in Device A's config

4. **Verify Device B's config**
   ```bash
   DEVICE_B_MAC="11:22:33:44:55:66"  # Device B MAC
   sudo cat /etc/dnsmasq.d/blocked-domains-${DEVICE_B_MAC}.conf
   ```
   
   **Expected:** Domain should NOT be in Device B's config

5. **Test from Device A**
   - Try to access: `http://test-device-a-only.com`
   - **Expected:** Blocked (cannot access)

6. **Test from Device B**
   - Try to access: `http://test-device-a-only.com`
   - **Expected:** Accessible (or normal error if domain doesn't exist)

**Success Criteria:**
- ✅ Domain only in Device A's config
- ✅ Device A: Domain blocked
- ✅ Device B: Domain accessible
- ✅ Separate config files per device

---

### Test 5: Subdomain Blocking

**Objective:** Verify that subdomain blocking works correctly.

**Steps:**

1. **Create blocked website with subdomains**
   - Blocking Type: **Domain**
   - Domain: `example.com`
   - Enable "Block subdomains" checkbox
   - Click "Block Website"

2. **Verify dnsmasq config**
   ```bash
   sudo cat /etc/dnsmasq.d/blocked-domains-${DEVICE_MAC}.conf
   ```
   
   **Expected:**
   ```
   address=/example.com/127.0.0.1
   address=/.example.com/127.0.0.1  # Note the leading dot (wildcard)
   ```

3. **Test main domain**
   ```bash
   nslookup example.com
   ```
   **Expected:** Returns 127.0.0.1

4. **Test subdomains**
   ```bash
   nslookup www.example.com
   nslookup m.example.com
   nslookup api.example.com
   ```
   
   **Expected:** All should return 127.0.0.1

**Success Criteria:**
- ✅ Main domain blocked
- ✅ All subdomains blocked
- ✅ Wildcard pattern (`.example.com`) in config

---

## Test Scenarios

### Scenario 1: Block Facebook App

**Goal:** Block Facebook app completely (website + mobile app)

**Steps:**
1. Create blocked website: App type, `facebook.com`
2. Verify all related domains detected
3. Verify all domains in dnsmasq config
4. Test Facebook app (should not connect)
5. Test Facebook website (should be blocked)

**Expected Result:** Facebook app and website completely blocked

---

### Scenario 2: Block YouTube for One Child Only

**Goal:** Block YouTube for one device, allow for others

**Steps:**
1. Create blocked website: Domain type, `youtube.com`, Device A only
2. Verify Device A's config contains `youtube.com`
3. Verify Device B's config does NOT contain `youtube.com`
4. Test from Device A (should be blocked)
5. Test from Device B (should be accessible)

**Expected Result:** YouTube blocked for Device A only

---

### Scenario 3: Block All Social Media

**Goal:** Block multiple social media apps for a device

**Steps:**
1. Create blocked websites for:
   - Facebook (App type)
   - Instagram (App type)
   - TikTok (App type)
   - Twitter (App type)
2. Verify all domains in dnsmasq config
3. Test each app (should all be blocked)

**Expected Result:** All social media apps blocked

---

## Verification Commands

### Check dnsmasq Service Status

```bash
sudo systemctl status dnsmasq
```

### View dnsmasq Logs

```bash
sudo journalctl -u dnsmasq -n 50
```

### Check dnsmasq Config for Device

```bash
DEVICE_MAC="AA:BB:CC:DD:EE:FF"  # Replace with actual MAC
sudo cat /etc/dnsmasq.d/blocked-domains-${DEVICE_MAC}.conf
```

### Test DNS Resolution

```bash
# Using nslookup
nslookup blocked-domain.com

# Using dig
dig blocked-domain.com @127.0.0.1

# Using host
host blocked-domain.com 127.0.0.1
```

**Expected for blocked domains:** `127.0.0.1`

### Check All dnsmasq Config Files

```bash
sudo ls -la /etc/dnsmasq.d/blocked-domains-*.conf
```

### Verify dnsmasq Config Syntax

```bash
sudo dnsmasq --test
```

### Restart dnsmasq

```bash
sudo systemctl restart dnsmasq
```

### Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

### Check Database for Blocked Websites

```bash
php artisan tinker
```

```php
// List all blocked websites
App\Models\BlockedWebsite::with('device')->get();

// Check specific device
$device = App\Models\Device::where('mac_address', 'AA:BB:CC:DD:EE:FF')->first();
$device->blockedWebsites;
```

---

## Troubleshooting

### Issue: dnsmasq Config Not Created

**Symptoms:**
- Blocked website created in database
- No dnsmasq config file created
- DNS blocking not working

**Possible Causes:**
1. Sudoers not configured
2. Scripts not executable
3. Script execution failing

**Solutions:**

1. **Check sudoers:**
   ```bash
   sudo cat /etc/sudoers.d/parental-wifi-scripts
   # Should contain DNS blocking scripts
   ```

2. **Check script permissions:**
   ```bash
   ls -la scripts/block_domain.sh
   # Should be executable
   ```

3. **Check Laravel logs:**
   ```bash
   tail -f storage/logs/laravel.log
   # Look for DNS blocking errors
   ```

4. **Test script manually:**
   ```bash
   sudo bash scripts/block_domain.sh test.com AA:BB:CC:DD:EE:FF 0
   ```

---

### Issue: DNS Blocking Not Working

**Symptoms:**
- dnsmasq config exists
- Domain in config file
- DNS resolution still returns real IP

**Possible Causes:**
1. dnsmasq not running
2. Device not using Raspberry Pi as DNS server
3. DNS cache on device
4. dnsmasq config not reloaded

**Solutions:**

1. **Check dnsmasq is running:**
   ```bash
   sudo systemctl status dnsmasq
   ```

2. **Restart dnsmasq:**
   ```bash
   sudo systemctl restart dnsmasq
   ```

3. **Verify device is using Raspberry Pi as DNS:**
   - Check device's network settings
   - DNS server should be Raspberry Pi's IP

4. **Clear DNS cache on device:**
   - Android: Settings > Network > Clear cache
   - iOS: Restart device or forget/reconnect WiFi

5. **Test DNS resolution from Raspberry Pi:**
   ```bash
   nslookup blocked-domain.com 127.0.0.1
   # Should return 127.0.0.1
   ```

---

### Issue: Related Domains Not Detected

**Symptoms:**
- App blocking created
- No related domains shown
- Only main domain blocked

**Possible Causes:**
1. App not in predefined mappings
2. AJAX request failing
3. Domain name mismatch

**Solutions:**

1. **Check predefined mappings:**
   - See `app/Services/DomainBlockingService.php`
   - Check if app domain is in `$appDomainMappings`

2. **Check browser console:**
   - Open DevTools (F12)
   - Check Network tab for AJAX errors

3. **Test manually:**
   ```bash
   php artisan tinker
   ```
   ```php
   $service = app(\App\Services\DomainBlockingService::class);
   $service->detectRelatedDomains('facebook.com', 'Facebook');
   ```

4. **Add domain manually:**
   - If app not in mappings, add related domains manually in UI

---

### Issue: Sudoers Syntax Error

**Symptoms:**
- `sudo visudo -c` shows syntax error
- Scripts cannot execute with sudo

**Solutions:**

1. **Check syntax:**
   ```bash
   sudo visudo -c -f /etc/sudoers.d/parental-wifi-scripts
   ```

2. **Edit sudoers file:**
   ```bash
   sudo visudo -f /etc/sudoers.d/parental-wifi-scripts
   ```

3. **Common issues:**
   - Missing spaces around `=`
   - Incorrect path (must be absolute)
   - Wrong user name
   - Missing `NOPASSWD:`

4. **Verify file permissions:**
   ```bash
   sudo chmod 0440 /etc/sudoers.d/parental-wifi-scripts
   sudo chown root:root /etc/sudoers.d/parental-wifi-scripts
   ```

---

### Issue: Script Execution Permission Denied

**Symptoms:**
- Scripts exist and are executable
- Execution fails with permission denied

**Solutions:**

1. **Check script permissions:**
   ```bash
   ls -la scripts/block_domain.sh
   # Should show: -rwxr-xr-x
   ```

2. **Make scripts executable:**
   ```bash
   chmod +x scripts/*.sh
   ```

3. **Check sudoers:**
   - Verify scripts are in sudoers file
   - Verify paths are correct (absolute paths)

4. **Test with sudo:**
   ```bash
   sudo bash scripts/block_domain.sh test.com AA:BB:CC:DD:EE:FF 0
   ```

---

## Test Results Documentation

After completing tests, document results in `docs/TEST_WEBSITE_MANAGEMENT_RESULTS.md`.

**Include:**
- Date and environment information
- Automated test results
- Manual test results for each scenario
- DNS resolution test results
- dnsmasq config verification
- Issues found and resolutions
- Overall test summary

**Example:**
```markdown
## Raspberry Pi Testing Results

### Test 1: Domain-Level Blocking
- ✅ dnsmasq config created
- ✅ DNS resolution returns 127.0.0.1
- ✅ Browser cannot access domain
- **Result:** ✅ PASSED

### Test 2: App-Level Blocking
- ✅ All related domains detected
- ✅ All domains in dnsmasq config
- ✅ Mobile app cannot connect
- **Result:** ✅ PASSED
```

---

## Quick Reference

### Essential Commands

```bash
# Run automated tests
./scripts/test-website-management.sh

# Check dnsmasq config
sudo cat /etc/dnsmasq.d/blocked-domains-{MAC}.conf

# Test DNS resolution
nslookup blocked-domain.com

# Check dnsmasq service
sudo systemctl status dnsmasq

# View dnsmasq logs
sudo journalctl -u dnsmasq -n 50

# Restart dnsmasq
sudo systemctl restart dnsmasq
```

### Important Files

- **Test script:** `scripts/test-website-management.sh`
- **DNS blocking scripts:** `scripts/block_domain.sh`, `scripts/unblock_domain.sh`, `scripts/update_dnsmasq_blocklist.sh`
- **Sudoers file:** `/etc/sudoers.d/parental-wifi-scripts`
- **dnsmasq config:** `/etc/dnsmasq.d/blocked-domains-{MAC}.conf`
- **Laravel logs:** `storage/logs/laravel.log`
- **Documentation:** `docs/SUDOERS_UPDATE_DNS_BLOCKING.md`

---

## Next Steps

After successful testing:

1. ✅ Document test results
2. ✅ Fix any issues found
3. ✅ Re-test failed scenarios
4. ✅ Deploy to production
5. ✅ Monitor system for issues
6. ✅ Update documentation if needed

---

**Last Updated:** [Date]  
**Status:** Ready for Raspberry Pi testing

