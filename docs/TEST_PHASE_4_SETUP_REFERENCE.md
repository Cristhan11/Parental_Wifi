# Test Phase 4 Setup Reference

**Purpose:** Quick reference for Test Phase 4 (Shell Script Execution) system configuration on Raspberry Pi.

**Note:** This is a reference document, not a setup guide. For detailed setup instructions, see the referenced documentation.

---

## System Information

### Operating System
- **OS:** Debian GNU/Linux 13 (trixie)
- **Kernel:** Linux 6.12.47+rpt-rpi-v8 #1 SMP PREEMPT Debian 1:6.12.47-1+rpt1 (2025-09-16)
- **Architecture:** aarch64 (64-bit ARM)

### Hardware
- **Device:** Raspberry Pi (exact model to be confirmed)
- **User:** snasna
- **Project Directory:** `/var/www/parental_wifi`

### Git Configuration
- **Remote:** `git@github.com:Cristhan11/Parental_Wifi.git`
- **Branch:** `main`

---

## Service Configuration

### Web Application Services
- **PHP Version:** 8.4.11 (cli)
- **PHP-FPM Service:** `php8.4-fpm`
- **Web Server:** nginx 1.26.3
- **Database:** MariaDB

**Service Status:** All active
```bash
systemctl is-active nginx php8.4-fpm mariadb
# Output: active, active, active
```

### WiFi Access Point Services
- **hostapd:** active (WiFi Access Point daemon)
- **dnsmasq:** active (DHCP and DNS server)
- **dhcpcd:** active (Network interface manager)

**Service Status:** All active
```bash
systemctl is-active hostapd dnsmasq dhcpcd
# Output: active, active, active
```

**Reference:** See `docs/RASPBERRY_PI_SERVICES_SETUP.md` for detailed service management.

---

## Network Configuration

### WiFi Access Point
- **Interface:** `wlan0`
- **Access Point IP:** `192.168.4.1/24`
- **Broadcast:** `192.168.4.255`
- **SSID:** `Parental_WiFi`
- **DHCP Range:** `192.168.4.2` to `192.168.4.51` (50 devices)
- **Subnet Mask:** `255.255.255.0` (`/24`)
- **Lease Time:** `24h`

### Network Verification
```bash
# Check wlan0 IP
ip addr show wlan0 | grep "inet "
# Output: inet 192.168.4.1/24 brd 192.168.4.255 scope global noprefixroute wlan0

# Check SSID
sudo grep "^ssid=" /etc/hostapd/hostapd.conf
# Output: ssid=Parental_WiFi

# Check DHCP range
sudo grep "^dhcp-range=" /etc/dnsmasq.conf
# Output: dhcp-range=192.168.4.2,192.168.4.51,255.255.255.0,24h
```

---

## Sudoers Configuration

### Configuration File
- **Location:** `/etc/sudoers.d/parental-wifi-scripts`
- **Permissions:** `0440` (read-only for owner and group)
- **Ownership:** `root:root`
- **User Allowed:** `www-data`

### Configured Scripts
All scripts configured with `NOPASSWD` for `www-data` user:

```
# Parental WiFi Network Control Scripts
# Allow www-data to execute network control scripts without password
# These scripts require root privileges for iptables operations

www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/block_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/unblock_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/whitelist_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/get_connected_devices.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/monitor_traffic.sh
```

### Verification Commands
```bash
# Check file exists
ls -la /etc/sudoers.d/parental-wifi-scripts

# Validate syntax
sudo visudo -c
# Expected: /etc/sudoers.d/parental-wifi-scripts: parsed OK

# Test execution as www-data
sudo -u www-data sudo /var/www/parental_wifi/scripts/get_connected_devices.sh
# Should execute without password prompt
```

**Reference:** See `docs/SUDOERS_CONFIGURATION.md` for detailed setup instructions.

---

## Scripts Information

### Scripts Directory
- **Location:** `/var/www/parental_wifi/scripts/`
- **Permissions:** All scripts are executable (`755` or `rwxrwxr-x`)

### Required Scripts
- `block_device.sh` - Blocks device MAC address
- `unblock_device.sh` - Unblocks device MAC address
- `whitelist_device.sh` - Whitelists device (bypasses restrictions)
- `get_connected_devices.sh` - Gets list of connected devices
- `monitor_traffic.sh` - Gets traffic statistics

### Script Verification
```bash
# List all scripts
ls -la /var/www/parental_wifi/scripts/*.sh

# Check permissions (all should be executable)
ls -la /var/www/parental_wifi/scripts/*.sh | grep -E "block_device|unblock_device|whitelist_device|get_connected_devices|monitor_traffic"
# All should show 'x' (executable) in permissions

# Make scripts executable if needed
chmod +x /var/www/parental_wifi/scripts/*.sh
```

---

## PHP Execution Functions

### Required Functions
- `exec()` - Enabled
- `shell_exec()` - Enabled

### Verification
```bash
# Check if functions are disabled
php -i | grep -E "disable_functions|exec|shell_exec"
# Functions should NOT be in disable_functions list
```

---

## Quick Reference Commands

### Service Status
```bash
# Check all web services
systemctl is-active nginx php8.4-fpm mariadb

# Check access point services
systemctl is-active hostapd dnsmasq dhcpcd

# Check all at once
systemctl is-active nginx php8.4-fpm mariadb hostapd dnsmasq dhcpcd
```

### Network Verification
```bash
# Check wlan0 interface
ip addr show wlan0

# Check connected devices
ip neigh show dev wlan0

# Check iptables rules
sudo iptables -L FORWARD -n -v
sudo iptables -L INPUT -n -v
```

### Script Testing
```bash
# Test get_connected_devices.sh
./scripts/get_connected_devices.sh

# Test monitor_traffic.sh
./scripts/monitor_traffic.sh

# Test block_device.sh (with test MAC)
sudo ./scripts/block_device.sh AA:BB:CC:DD:EE:FF

# Test unblock_device.sh
sudo ./scripts/unblock_device.sh AA:BB:CC:DD:EE:FF
```

### Sudoers Verification
```bash
# Validate syntax
sudo visudo -c

# View configuration
sudo cat /etc/sudoers.d/parental-wifi-scripts

# Test execution
sudo -u www-data sudo /var/www/parental_wifi/scripts/get_connected_devices.sh
```

### PHP/Laravel Testing
```bash
# Run Test Phase 4 verification
php artisan test:phase4

# Test via tinker
php artisan tinker
# Then: $service = app(\App\Services\NetworkService::class);
#       $devices = $service->getConnectedDevices();
```

---

## Test Phase 4 Requirements Checklist

### Prerequisites
- [x] PHP 8.4.11 installed
- [x] PHP-FPM service running (php8.4-fpm)
- [x] Nginx web server running
- [x] MariaDB database running
- [x] Access point services running (hostapd, dnsmasq, dhcpcd)
- [x] Scripts directory exists: `/var/www/parental_wifi/scripts/`
- [x] All scripts are executable
- [x] PHP execution functions enabled (exec, shell_exec)
- [x] Sudoers configuration created and validated
- [x] Network interface (wlan0) configured

### Test Results
- [x] ScriptExecutor service working
- [x] NetworkService working
- [x] getConnectedDevices() working (after fix)
- [x] Blocking/unblocking tested and working
- [x] All automated tests passed
- [x] Security tests passed

---

## Known Issues and Fixes

### Issue: NetworkService.getConnectedDevices() Returns Empty Array
**Problem:** Script outputs `mac` and `ip` keys, but NetworkService expected `mac_address` and `ip_address`.

**Fix Applied:** Updated `app/Services/NetworkService.php` line ~1004 to map script output keys:
```php
// Changed from:
if (isset($device['mac_address']) && isset($device['ip_address'])) {
    $validDevices[] = [
        'mac_address' => $device['mac_address'],
        'ip_address' => $device['ip_address'],
        ...
    ];
}

// To:
if (isset($device['mac']) && isset($device['ip'])) {
    $validDevices[] = [
        'mac_address' => $device['mac'],      // Map 'mac' to 'mac_address'
        'ip_address' => $device['ip'],        // Map 'ip' to 'ip_address'
        ...
    ];
}
```

**Status:** ✅ Fixed and tested

---

## Related Documentation

- **Service Management:** `docs/RASPBERRY_PI_SERVICES_SETUP.md` - Detailed service setup and management
- **Sudoers Setup:** `docs/SUDOERS_CONFIGURATION.md` - Complete sudoers configuration guide
- **Testing Guide:** `docs/TESTING.md` - Test Phase 4 procedures and checklist
- **Test Results:** `docs/TEST_PHASE_4_RESULTS.md` - Test execution results
- **Network Architecture:** `docs/NETWORK_CONTROL_SYSTEM_ARCHITECTURE.md` - System architecture overview

---

## Last Updated

**Date:** 2025-11-30  
**Tester:** snasna  
**Status:** Test Phase 4 Complete ✅

---

## Notes

- All services verified and running correctly
- Sudoers configuration tested and working
- Scripts tested and functional
- NetworkService fix applied and verified
- Blocking/unblocking tested successfully with device `E6:6A:8F:19:BE:B1`
- All automated tests passed
- System ready for production use

