# Domain Blocking Manual Test Results

**Test Date:** December 8, 2025  
**Tester:** [Your Name]  
**Pi Hostname:** parentalpi  
**Pi IP:** [Pi IP Address]

---

## Pre-Testing Checklist

### Pi Configuration Verification

- [x] dnsmasq is installed: `sudo systemctl status dnsmasq` ✅
- [x] dnsmasq is running: Active (running) ✅
- [x] dnsmasq config directory exists: `/etc/dnsmasq.d/` ✅
- [x] dnsmasq is configured to read from `/etc/dnsmasq.d/`: `conf-dir=/etc/dnsmasq.d/,*.conf` ✅

### Scripts Location

- [x] Blocking scripts exist: `scripts/block_domain.sh`, `scripts/unblock_domain.sh` ✅
- [x] Scripts are executable: `-rwxrwxr-x` ✅
- [x] ScriptExecutor can find scripts: Scripts in whitelist ✅

### Network Configuration

- [ ] Pi is acting as DHCP server (dnsmasq handles this)
- [ ] Pi is acting as DNS server (dnsmasq handles this)
- [ ] Devices use Pi as DNS server (check device network settings)

### Database

- [x] Devices registered in database ✅
  - CHILD Device: `e6:6a:8f:19:be:b1` - CP_ChildDev01 (Active)
  - PARENT Device: `30:03:C8:0A:45:AF` - Laptop_Parentl (Whitelisted)
- [x] Device has valid MAC address ✅
- [x] Device status is "active" (CHILD device) ✅

---

## Test 1: Basic Domain Blocking (Single Domain)

**Test Date/Time:** [Date/Time]  
**Test Device MAC:** `e6:6a:8f:19:be:b1` (CP_ChildDev01)  
**Blocked Domain(s):** `example.com`  
**Block Type:** Domain  
**Subdomains Blocked:** No

### Steps Performed:

1. **Created Blocked Website via Web Interface:**
   - Navigated to `/blocked-websites/create`
   - Selected device: CP_ChildDev01
   - Entered domain: `example.com`
   - Selected block type: "Domain"
   - Did NOT check "Block subdomains"
   - Clicked "Block Website"

2. **Verified Database Record:**
   ```bash
   php artisan tinker
   >>> $blocked = App\Models\BlockedWebsite::latest()->first();
   >>> $blocked->domain;
   >>> $blocked->block_type;
   ```
   **Result:** [Document result]

3. **Verified dnsmasq Configuration:**
   ```bash
   sudo cat /etc/dnsmasq.d/blocked-domains-e6:6a:8f:19:be:b1.conf
   ```
   **Result:** [Document result]

4. **Verified dnsmasq Service:**
   ```bash
   sudo systemctl status dnsmasq
   sudo journalctl -u dnsmasq -n 50
   ```
   **Result:** [Document result]

5. **Tested from Device:**
   - Tried to access `http://example.com`
   - Result: [Blocked/Allowed]
   - Tried `nslookup example.com` on device
   - Result: [127.0.0.1/Real IP]

6. **Verified Other Domains Still Work:**
   - Tried accessing `http://google.com`
   - Result: [Works/Blocked]

### Test Results:

- [ ] Blocked domain returns 127.0.0.1
- [ ] Other domains work normally
- [ ] dnsmasq config file created correctly
- [ ] dnsmasq service running without errors

### Issues Found:
[Document any issues]

### Resolution:
[Document how issues were resolved]

---

## Test 2: Subdomain Blocking

**Test Date/Time:** [Date/Time]  
**Test Device MAC:** `e6:6a:8f:19:be:b1` (CP_ChildDev01)  
**Blocked Domain(s):** `test.com`  
**Block Type:** Domain  
**Subdomains Blocked:** Yes

### Steps Performed:

1. **Created Blocked Website with Subdomains:**
   - Navigated to `/blocked-websites/create`
   - Selected device: CP_ChildDev01
   - Entered domain: `test.com`
   - Selected block type: "Domain"
   - **Checked "Block subdomains"**
   - Clicked "Block Website"

2. **Verified dnsmasq Config:**
   ```bash
   sudo cat /etc/dnsmasq.d/blocked-domains-e6:6a:8f:19:be:b1.conf | grep test.com
   ```
   **Result:** [Document result - should show `address=/.test.com/127.0.0.1`]

3. **Tested from Device:**
   - `http://test.com` → [Blocked/Allowed]
   - `http://www.test.com` → [Blocked/Allowed]
   - `http://api.test.com` → [Blocked/Allowed]
   - `http://subdomain.test.com` → [Blocked/Allowed]

### Test Results:

- [ ] All subdomains of test.com are blocked
- [ ] dnsmasq config uses wildcard pattern (leading dot)

### Issues Found:
[Document any issues]

### Resolution:
[Document how issues were resolved]

---

## Test 3: App-Level Blocking (Multiple Domains)

**Test Date/Time:** [Date/Time]  
**Test Device MAC:** `e6:6a:8f:19:be:b1` (CP_ChildDev01)  
**Blocked Domain(s):** `facebook.com` + related domains  
**Block Type:** App  
**Subdomains Blocked:** [Yes/No]

### Steps Performed:

1. **Created App Block:**
   - Navigated to `/blocked-websites/create`
   - Selected device: CP_ChildDev01
   - Entered domain: `facebook.com`
   - Selected block type: "App"
   - System auto-suggested related domains: [List domains]
   - Clicked "Block Website"

2. **Verified Related Domains:**
   ```bash
   php artisan tinker
   >>> $blocked = App\Models\BlockedWebsite::where('domain', 'facebook.com')->first();
   >>> $blocked->related_domains;
   >>> $blocked->getDomainsToBlock();
   ```
   **Result:** [Document result]

3. **Verified dnsmasq Config:**
   ```bash
   sudo cat /etc/dnsmasq.d/blocked-domains-e6:6a:8f:19:be:b1.conf | grep facebook
   ```
   **Result:** [Document result - should show multiple entries]

4. **Tested from Device:**
   - `http://facebook.com` → [Blocked/Allowed]
   - `http://api.facebook.com` → [Blocked/Allowed]
   - `http://graph.facebook.com` → [Blocked/Allowed]
   - Facebook mobile app → [Works/Blocked]

### Test Results:

- [ ] Main domain blocked
- [ ] All related domains blocked
- [ ] Mobile app cannot connect (all API domains blocked)

### Issues Found:
[Document any issues]

### Resolution:
[Document how issues were resolved]

---

## Test 4: Unblock Domain

**Test Date/Time:** [Date/Time]  
**Test Device MAC:** `e6:6a:8f:19:be:b1` (CP_ChildDev01)  
**Unblocked Domain(s):** [Domain name]

### Steps Performed:

1. **Unblocked via Web Interface:**
   - Navigated to `/blocked-websites`
   - Found blocked website: [Domain]
   - Clicked "Delete" or "Unblock"

2. **Verified Database:**
   ```bash
   php artisan tinker
   >>> $blocked = App\Models\BlockedWebsite::find([ID]);
   >>> $blocked; // Should be null after deletion
   ```
   **Result:** [Document result]

3. **Verified dnsmasq Config:**
   ```bash
   sudo cat /etc/dnsmasq.d/blocked-domains-e6:6a:8f:19:be:b1.conf
   ```
   **Result:** [Document result - domain should be removed]

4. **Tested from Device:**
   - Tried accessing previously blocked domain
   - Result: [Works/Blocked]

### Test Results:

- [ ] Domain removed from database
- [ ] Domain removed from dnsmasq config
- [ ] Domain accessible again

### Issues Found:
[Document any issues]

### Resolution:
[Document how issues were resolved]

---

## Test 5: Multiple Devices (Different Blocklists)

**Test Date/Time:** [Date/Time]  
**Device A MAC:** `e6:6a:8f:19:be:b1` (CP_ChildDev01)  
**Device B MAC:** `30:03:C8:0A:45:AF` (Laptop_Parentl)

### Steps Performed:

1. **Registered Blocking for Two Devices:**
   - Device A: Blocked `example.com`
   - Device B: Blocked `test.com`

2. **Verified Separate Config Files:**
   ```bash
   ls -la /etc/dnsmasq.d/blocked-domains-*.conf
   ```
   **Result:** [Document result - should show separate files]

3. **Tested from Each Device:**
   - Device A: `example.com` → [Blocked/Allowed], `test.com` → [Blocked/Allowed]
   - Device B: `test.com` → [Blocked/Allowed], `example.com` → [Blocked/Allowed]

### Test Results:

- [ ] Each device has separate blocklist
- [ ] Blocking on one device doesn't affect others

### Issues Found:
[Document any issues]

### Resolution:
[Document how issues were resolved]

---

## Summary

### Tests Completed:
- [ ] Test 1: Basic Domain Blocking
- [ ] Test 2: Subdomain Blocking
- [ ] Test 3: App-Level Blocking
- [ ] Test 4: Unblock Domain
- [ ] Test 5: Multiple Devices

### Overall Status:
- **Passing:** [Number] tests
- **Failing:** [Number] tests
- **Issues Found:** [Number] issues

### Known Issues:
[List any known issues that need to be addressed]

### Recommendations:
[Any recommendations for improvements or fixes]

---

## Debugging Log

### Commands Run:
[Document any debugging commands run]

### Error Messages:
[Document any error messages encountered]

### Solutions Applied:
[Document solutions that were applied]

