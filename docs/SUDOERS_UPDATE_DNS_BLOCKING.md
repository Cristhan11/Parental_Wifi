# Sudoers Configuration Update for DNS Blocking Scripts

## Overview

This document explains how to update the sudoers configuration on Raspberry Pi to allow the DNS blocking scripts to run with sudo privileges.

**Why This is Needed:**
- DNS blocking scripts need to modify `/etc/dnsmasq.d/` directory (requires root)
- Scripts need to restart dnsmasq service (requires root)
- Scripts are executed by PHP/web server user (www-data or snasna)

**Important:** This must be done on Raspberry Pi, not on local development machine.

---

## Scripts That Need Sudo Access

The following scripts need to be added to sudoers:

1. `block_domain.sh` - Adds domain to dnsmasq blocklist
2. `unblock_domain.sh` - Removes domain from dnsmasq blocklist
3. `update_dnsmasq_blocklist.sh` - Regenerates complete blocklist from database

---

## Current Sudoers File

The existing sudoers file is located at:
```
/etc/sudoers.d/parental-wifi-scripts
```

**Current Contents (Example):**
```
# Parental WiFi Control System - Script Permissions
# This file allows specific scripts to run with sudo privileges

# Network control scripts
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/block_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/unblock_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/whitelist_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/get_connected_devices.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/monitor_traffic.sh
```

---

## Update Instructions

### Step 1: Backup Current Sudoers File

```bash
sudo cp /etc/sudoers.d/parental-wifi-scripts /etc/sudoers.d/parental-wifi-scripts.backup
```

### Step 2: Edit Sudoers File

```bash
sudo nano /etc/sudoers.d/parental-wifi-scripts
```

### Step 3: Add DNS Blocking Scripts

Add the following lines to the file (replace `/var/www/parental_wifi` with your actual project path):

```
# DNS blocking scripts
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/block_domain.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/unblock_domain.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/update_dnsmasq_blocklist.sh
```

**Note:** If your web server runs as a different user (e.g., `snasna`), replace `www-data` with that user.

### Step 4: Verify Sudoers Syntax

```bash
sudo visudo -c -f /etc/sudoers.d/parental-wifi-scripts
```

**Expected Output:**
```
/etc/sudoers.d/parental-wifi-scripts: parsed OK
```

If there are syntax errors, fix them before proceeding.

### Step 5: Verify File Permissions

```bash
sudo chmod 0440 /etc/sudoers.d/parental-wifi-scripts
sudo chown root:root /etc/sudoers.d/parental-wifi-scripts
```

### Step 6: Test Script Execution

Test that scripts can be executed with sudo:

```bash
# Test block_domain.sh
sudo -u www-data sudo /var/www/parental_wifi/scripts/block_domain.sh test.com AA:BB:CC:DD:EE:FF 0

# Test unblock_domain.sh
sudo -u www-data sudo /var/www/parental_wifi/scripts/unblock_domain.sh test.com AA:BB:CC:DD:EE:FF

# Test update_dnsmasq_blocklist.sh
echo "test.com:0" | sudo -u www-data sudo /var/www/parental_wifi/scripts/update_dnsmasq_blocklist.sh AA:BB:CC:DD:EE:FF
```

---

## Complete Sudoers File Example

Here's what the complete file should look like after adding DNS blocking scripts:

```
# Parental WiFi Control System - Script Permissions
# This file allows specific scripts to run with sudo privileges

# Network control scripts
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/block_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/unblock_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/whitelist_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/get_connected_devices.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/monitor_traffic.sh

# DNS blocking scripts
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/block_domain.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/unblock_domain.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/update_dnsmasq_blocklist.sh
```

---

## Alternative: Using Different User

If your web server runs as a different user (e.g., `snasna`), use this format:

```
# DNS blocking scripts
snasna ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/block_domain.sh
snasna ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/unblock_domain.sh
snasna ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/update_dnsmasq_blocklist.sh
```

**To find your web server user:**
```bash
ps aux | grep php-fpm | grep -v grep
# Look for the user in the output
```

---

## Verification Checklist

After updating sudoers, verify:

- [ ] Sudoers syntax is valid (`sudo visudo -c`)
- [ ] File permissions are correct (0440, root:root)
- [ ] Scripts can be executed with sudo
- [ ] DNS blocking works from Laravel application

---

## Troubleshooting

### Issue: "sudo: no tty present and no askpass program specified"

**Solution:** The `NOPASSWD` directive should fix this. Verify it's in the sudoers file.

### Issue: "command not found"

**Solution:** Verify script paths are correct and absolute (not relative).

### Issue: "permission denied"

**Solution:** 
- Check file permissions: `ls -la /etc/sudoers.d/parental-wifi-scripts`
- Verify user matches web server user
- Check script paths are correct

### Issue: Sudoers syntax error

**Solution:**
- Use `sudo visudo -c` to check syntax
- Ensure no typos in paths
- Ensure proper formatting (tabs/spaces)

---

## Security Notes

**Important Security Considerations:**

1. **Absolute Paths Only:** Always use absolute paths in sudoers (never relative paths)
2. **Specific Scripts:** Only allow specific scripts, not entire directories
3. **NOPASSWD:** Using `NOPASSWD` is necessary for web server execution but reduces security
4. **File Permissions:** Sudoers file must be owned by root and have 0440 permissions
5. **Regular Audits:** Periodically review sudoers file for unnecessary permissions

---

## Rollback

If you need to rollback the changes:

```bash
# Restore backup
sudo cp /etc/sudoers.d/parental-wifi-scripts.backup /etc/sudoers.d/parental-wifi-scripts

# Verify syntax
sudo visudo -c -f /etc/sudoers.d/parental-wifi-scripts
```

---

## Testing After Update

After updating sudoers, test DNS blocking from Laravel:

1. **Create Blocked Website via UI:**
   - Navigate to blocked websites
   - Create new blocked website
   - Select device and domain
   - Click "Block Website"

2. **Verify dnsmasq Config:**
   ```bash
   sudo cat /etc/dnsmasq.d/blocked-domains-{MAC}.conf
   ```

3. **Verify DNS Blocking:**
   ```bash
   nslookup blocked-domain.com
   ```

If all steps succeed, sudoers configuration is correct.

---

**Last Updated:** [Date]  
**Status:** Ready for Raspberry Pi deployment

