# Raspberry Pi Services Setup Guide

This document provides a quick reference for managing required services and pulling updates from Git on your Raspberry Pi.

## Your Current Setup

**Username:** `snasna`  
**Project Directory:** `/var/www/parental_wifi`  
**PHP Version:** `8.4.11 (cli) (built: Aug 3 2025 07:32:21) (NTS)`  
**PHP Major.Minor:** `8.4`  
**PHP-FPM Service:** `php8.4-fpm`  
**PHP-FPM Status:** `active (running)`  
**Web Server:** `nginx`  
**Nginx Version:** `nginx/1.26.3`  
**Nginx Status:** `active (running)`  
**Database:** `MariaDB` (mariadb.service)  
**Database Status:** `active (running)`  
**Nginx Config:** `/etc/nginx/sites-available/parental_wifi`  
**PHP-FPM Socket:** `unix:/var/run/php/php8.4-fpm.sock`  
**Socket Path:** `/var/run/php/php8.4-fpm.sock` (exists and accessible)  
**Git Remote:** `git@github.com:Cristhan11/Parental_Wifi.git`  
**Git Branch:** `main`

### Verified Configuration

All services are confirmed running and properly configured:
- ✅ PHP-FPM socket exists and is accessible
- ✅ Nginx is configured to use PHP 8.4-FPM
- ✅ All web application services are active and running
- ✅ WiFi Access Point services are active and running (hostapd, dnsmasq, dhcpcd)
- ✅ Access Point is operational (devices can connect and access internet)
- ✅ Git repository is properly configured

---

## Required Services

The following services must be running for the Laravel application to function properly:

1. **Nginx** - Web server
2. **PHP-FPM 8.4** - PHP FastCGI Process Manager (php8.4-fpm)
3. **MariaDB** - Database server

### WiFi Access Point Services

The following services are required for the WiFi Access Point functionality:

1. **hostapd** - WiFi Access Point daemon (creates and manages WiFi network)
2. **dnsmasq** - DHCP and DNS server (assigns IP addresses and resolves domain names)
3. **dhcpcd** - DHCP client daemon (manages static IP for wlan0 interface)

**Current Status:**
- ✅ hostapd: `active (running)`
- ✅ dnsmasq: `active (running)`
- ✅ dhcpcd: `active (running)`

### Captive Portal Services

The following service is required for the captive portal functionality:

1. **nodogsplash** - NoDogSplash Captive Portal (intercepts HTTP requests and redirects devices to portal)

**Current Status:**
- ✅ nodogsplash: `active (running)` (when configured)

**NoDogSplash Configuration:**
- **Config File:** `/etc/nodogsplash/nodogsplash.conf`
- **Splash Page:** `/etc/nodogsplash/htdocs/splash.html`
- **Service File:** `/etc/systemd/system/nodogsplash.service`
- **Gateway Interface:** `wlan0`
- **Gateway Address:** `192.168.4.1`
- **RedirectURL:** Commented out (uses splash page instead)
- **Port:** `2050` (NoDogSplash web server)

**Network Configuration:**
- **WiFi Interface:** `wlan0`
- **Access Point IP:** `192.168.4.1/24`
- **SSID (Network Name):** `Parental_WiFi`
- **DHCP Range:** `192.168.4.2` to `192.168.4.51` (50 devices)
- **Gateway:** `192.168.4.1` (the Pi itself)
- **DNS Server:** `192.168.4.1` (the Pi itself, via dnsmasq)
- **Subnet Mask:** `255.255.255.0` (`/24`)
- **IP Forwarding:** `enabled` (1)

**Configuration Files:**
- **hostapd config:** `/etc/hostapd/hostapd.conf`
- **dnsmasq config:** `/etc/dnsmasq.conf` (backup: `/etc/dnsmasq.conf.orig`)
- **dhcpcd config:** `/etc/dhcpcd.conf`
- **NetworkManager config:** `/etc/NetworkManager/conf.d/99-unmanaged-devices.conf`
- **IP forwarding:** `/etc/sysctl.conf`
- **NoDogSplash config:** `/etc/nodogsplash/nodogsplash.conf`
- **NoDogSplash splash page:** `/etc/nodogsplash/htdocs/splash.html`
- **IP forwarding service:** `/etc/systemd/system/ip-forward.service`

**NetworkManager Configuration:**
- wlan0 is configured as unmanaged by NetworkManager
- This allows dhcpcd to manage the interface for access point mode

**iptables NAT Configuration:**
- MASQUERADE rule active on eth0 interface
- FORWARD rules configured for wlan0 ↔ eth0 traffic
- Rules saved persistently via netfilter-persistent

---

## Check Service Status

### Check All Services at Once

```bash
echo "=== Service Status ==="
echo "Nginx: $(systemctl is-active nginx)"
echo "PHP-FPM: $(systemctl is-active php8.4-fpm)"
echo "MariaDB: $(systemctl is-active mariadb)"
```

**Expected output when all services are running:**
```
=== Service Status ===
Nginx: active
PHP-FPM: active
MariaDB: active
```

### Check Access Point Services

```bash
echo "=== Access Point Services ==="
echo "hostapd: $(systemctl is-active hostapd)"
echo "dnsmasq: $(systemctl is-active dnsmasq)"
echo "dhcpcd: $(systemctl is-active dhcpcd)"
```

**Expected output when all services are running:**
```
=== Access Point Services ===
hostapd: active
dnsmasq: active
dhcpcd: active
```

### Check Captive Portal Services

```bash
echo "=== Captive Portal Services ==="
echo "nodogsplash: $(systemctl is-active nodogsplash)"
echo "ip-forward: $(systemctl is-active ip-forward.service)"
```

**Expected output when all services are running:**
```
=== Captive Portal Services ===
nodogsplash: active
ip-forward: active
```

### Check Individual Services

```bash
# Check Nginx
sudo systemctl status nginx
# OR
systemctl is-active nginx

# Check PHP-FPM 8.4
sudo systemctl status php8.4-fpm
# OR
systemctl is-active php8.4-fpm

# Check MariaDB
sudo systemctl status mariadb
# OR
systemctl is-active mariadb

# Check hostapd (WiFi Access Point)
sudo systemctl status hostapd
# OR
systemctl is-active hostapd

# Check dnsmasq (DHCP/DNS)
sudo systemctl status dnsmasq
# OR
systemctl is-active dnsmasq

# Check dhcpcd (Network Manager)
sudo systemctl status dhcpcd
# OR
systemctl is-active dhcpcd

# Check nodogsplash (Captive Portal)
sudo systemctl status nodogsplash
# OR
systemctl is-active nodogsplash

# Check ip-forward (IP Forwarding Service)
sudo systemctl status ip-forward.service
# OR
systemctl is-active ip-forward.service
```

---

## Start Services

### Start All Services

```bash
# Start Nginx
sudo systemctl start nginx

# Start PHP-FPM 8.4
sudo systemctl start php8.4-fpm

# Start MariaDB
sudo systemctl start mariadb
```

### Start Access Point Services

```bash
# Start dnsmasq (DHCP/DNS) - start this first
sudo systemctl start dnsmasq

# Start hostapd (WiFi Access Point)
sudo systemctl start hostapd

# Start dhcpcd (Network Manager)
sudo systemctl start dhcpcd
```

**Note:** Start dnsmasq before hostapd to ensure DHCP is ready when devices connect.

### Start Captive Portal Services

```bash
# Start ip-forward service (IP forwarding)
sudo systemctl start ip-forward.service

# Start nodogsplash (Captive Portal) - start after network services
sudo systemctl start nodogsplash
```

**Note:** Start NoDogSplash after network services (hostapd, dnsmasq) are running.

### Enable Services to Start on Boot

```bash
# Enable Nginx
sudo systemctl enable nginx

# Enable PHP-FPM
sudo systemctl enable php8.4-fpm

# Enable MariaDB
sudo systemctl enable mariadb

# Enable Access Point Services
sudo systemctl enable dnsmasq
sudo systemctl enable hostapd
sudo systemctl enable dhcpcd

# Enable Captive Portal Services
sudo systemctl enable ip-forward.service
sudo systemctl enable nodogsplash
```

---

## Stop Services (if needed)

```bash
# Stop Nginx
sudo systemctl stop nginx

# Stop PHP-FPM
sudo systemctl stop php8.4-fpm

# Stop MariaDB
sudo systemctl stop mariadb

# Stop Access Point Services
sudo systemctl stop hostapd
sudo systemctl stop dnsmasq
# Note: Usually don't stop dhcpcd as it manages network interfaces

# Stop Captive Portal Services
sudo systemctl stop nodogsplash
# Note: Usually don't stop ip-forward.service as it's needed for routing
```

---

## Restart Services (if needed)

```bash
# Restart Nginx
sudo systemctl restart nginx

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm

# Restart MariaDB
sudo systemctl restart mariadb

# Restart Access Point Services
sudo systemctl restart dnsmasq
sudo systemctl restart hostapd
sudo systemctl restart dhcpcd

# Restart Captive Portal Services
sudo systemctl restart ip-forward.service
sudo systemctl restart nodogsplash
```

**Note:** When restarting access point services, restart dnsmasq first, then hostapd. Restart NoDogSplash after network services are running.

---

## Verify Access Point Configuration

### Check WiFi Interface Status

```bash
# Check wlan0 IP address
ip addr show wlan0 | grep "inet "

# Expected output:
#     inet 192.168.4.1/24 brd 192.168.4.255 scope global noprefixroute wlan0
```

### Check Access Point Configuration

```bash
# Check SSID (WiFi network name)
sudo grep "^ssid=" /etc/hostapd/hostapd.conf

# Check WiFi password (wpa_passphrase)
sudo grep "^wpa_passphrase=" /etc/hostapd/hostapd.conf

# Check DHCP range
sudo grep "^dhcp-range=" /etc/dnsmasq.conf

# Check gateway and DNS settings
sudo grep "^dhcp-option=" /etc/dnsmasq.conf
```

**Your Current Configuration:**
- **SSID:** `Parental_WiFi`
- **DHCP Range:** `192.168.4.2` to `192.168.4.51`
- **Gateway:** `192.168.4.1`
- **DNS:** `192.168.4.1`

### Check Connected Devices

```bash
# See connected devices and their IP addresses
ip neigh show dev wlan0

# Or check DHCP leases
cat /var/lib/misc/dnsmasq.leases

# Check active connections
sudo iptables -t nat -L POSTROUTING -v -n
```

### Verify IP Forwarding

```bash
# Check if IP forwarding is enabled (should output: 1)
cat /proc/sys/net/ipv4/ip_forward

# Check iptables NAT rules
sudo iptables -t nat -L POSTROUTING -v -n

# Check FORWARD rules
sudo iptables -L FORWARD -v -n
```

### Check NetworkManager Configuration

```bash
# Verify wlan0 is unmanaged by NetworkManager
sudo cat /etc/NetworkManager/conf.d/99-unmanaged-devices.conf
```

**Expected output:**
```
[keyfile]
unmanaged-devices=interface-name:wlan0
```

---

## Find Your PHP Version and PHP-FPM Service

### Check PHP Version

```bash
php -v
```

**Your output:**
```
PHP 8.4.11 (cli) (built: Aug  3 2025 07:32:21) (NTS)
Copyright (c) The PHP Group
Built by Debian
Zend Engine v4.4.11, Copyright (c) Zend Technologies
    with Zend OPcache v8.4.11, Copyright (c), by Zend Technologies
```

### Find PHP-FPM Service Name

```bash
# List all PHP-related services
systemctl list-units --type=service | grep php

# Or search for PHP-FPM services
systemctl list-units --all | grep php

# Check installed PHP-FPM packages
dpkg -l | grep php.*fpm
```

**Your PHP-FPM Service:** `php8.4-fpm` (confirmed active and running)

### PHP-FPM Socket Locations

Your PHP-FPM sockets are located at:
- `/var/run/php/php8.4-fpm.sock` (primary socket)
- `/run/php/php8.4-fpm.sock` (symlink)

Both sockets exist and are accessible. Socket ownership: `www-data:www-data`

---

## Verify Nginx Configuration

### Check PHP-FPM Socket in Nginx Config

```bash
# Check which PHP-FPM socket Nginx is using
grep "fastcgi_pass" /etc/nginx/sites-available/parental_wifi
```

### Verify Nginx Config is Correct

Your Nginx configuration is confirmed correct. Verify it:

```bash
# Check PHP-FPM socket in Nginx config
grep "fastcgi_pass" /etc/nginx/sites-available/parental_wifi
```

**Your actual output:**
```
fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
```

✅ **Confirmed:** Nginx is correctly configured to use PHP 8.4-FPM socket.

If it shows a different version (e.g., php8.2-fpm), update it:

```bash
# Edit Nginx configuration
sudo nano /etc/nginx/sites-available/parental_wifi
```

Change the line to:
```nginx
fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
```

Then test and reload:
```bash
# Test configuration
sudo nginx -t

# If test passes, reload Nginx
sudo systemctl reload nginx
```

---

## Git Operations

### Pull Latest Changes from GitHub

#### Step 1: Navigate to Project Directory

```bash
cd /var/www/parental_wifi
```

#### Step 2: Check Current Status

```bash
# Check if you have local changes
git status

# See what files are modified
git diff --stat
```

#### Step 3: Handle Local Changes

**Option A: Discard Local Changes (if you don't need them)**

```bash
# Discard all local changes
git restore .

# Then pull
git pull
```

**Option B: Stash Local Changes (if you want to keep them)**

```bash
# Stash your changes
git stash

# Pull latest changes
git pull

# Reapply your changes later (if needed)
git stash pop
```

**Option C: Commit Local Changes First**

```bash
# Add all changes
git add .

# Commit them
git commit -m "Local changes before pulling"

# Pull (may need to merge)
git pull
```

#### Step 4: Pull Latest Changes

```bash
git pull
```

### Fix Permission Issues

If you get permission errors when using git:

```bash
# Fix ownership of the repository
sudo chown -R snasna:snasna /var/www/parental_wifi

# Then use git without sudo
git pull
```

### Fix "Dubious Ownership" Error

If Git complains about repository ownership:

```bash
# Add exception (run as your regular user, not sudo)
git config --global --add safe.directory /var/www/parental_wifi

# Then use git commands normally
git pull
```

### Verify Pulled Changes

After pulling, verify new files:

```bash
# Check if new files exist
ls -la scripts/test-phase3.sh
ls -la app/Console/Commands/TestPhase3Verification.php

# See recent commits
git log --oneline -5
```

### Your Git Configuration

**Remote URL:** `git@github.com:Cristhan11/Parental_Wifi.git`  
**Current Branch:** `main`  
**Repository Status:** Configured and accessible via SSH

To check your Git configuration:
```bash
# Check remote URL
git remote get-url origin

# Check current branch
git branch --show-current

# Check repository status
git status
```

---

## Quick Setup Checklist

Before running tests or using the application:

### Web Application Services
- [x] Nginx is running: `systemctl is-active nginx` → `active` ✅ (nginx/1.26.3)
- [x] PHP-FPM 8.4 is running: `systemctl is-active php8.4-fpm` → `active` ✅
- [x] MariaDB is running: `systemctl is-active mariadb` → `active` ✅
- [x] Nginx config points to PHP 8.4-FPM: `grep "fastcgi_pass" /etc/nginx/sites-available/parental_wifi` shows `php8.4-fpm.sock` ✅
- [x] PHP-FPM socket exists: `ls -la /var/run/php/php8.4-fpm.sock` → socket file exists ✅

### WiFi Access Point Services
- [x] hostapd is running: `systemctl is-active hostapd` → `active` ✅
- [x] dnsmasq is running: `systemctl is-active dnsmasq` → `active` ✅
- [x] dhcpcd is running: `systemctl is-active dhcpcd` → `active` ✅
- [x] wlan0 has static IP: `ip addr show wlan0` shows `192.168.4.1/24` ✅
- [x] IP forwarding is enabled: `cat /proc/sys/net/ipv4/ip_forward` → `1` ✅
- [x] iptables NAT rules configured: `sudo iptables -t nat -L POSTROUTING` shows MASQUERADE rule ✅

### Captive Portal Services
- [ ] nodogsplash is running: `systemctl is-active nodogsplash` → `active`
- [ ] ip-forward service is running: `systemctl is-active ip-forward.service` → `active`
- [ ] NoDogSplash config exists: `ls -la /etc/nodogsplash/nodogsplash.conf` → file exists
- [ ] Splash page exists: `ls -la /etc/nodogsplash/htdocs/splash.html` → file exists
- [ ] RedirectURL is commented out: `sudo grep "^RedirectURL" /etc/nodogsplash/nodogsplash.conf` → returns nothing
- [ ] Firewall rule allows portal access: `sudo grep -A 5 "preauthenticated-users" /etc/nodogsplash/nodogsplash.conf | grep "192.168.4.1"` → shows firewall rule

### General
- [ ] Repository is up to date: `git pull` completed successfully
- [ ] Laravel is accessible: `curl http://localhost/` returns HTML (not error)
- [ ] WiFi network is visible: Devices can see "Parental_WiFi" network
- [ ] Devices can connect: Test device successfully connects and gets IP address
- [ ] Internet access works: Connected devices can browse the internet

**Status:** All services verified and running correctly. Access point is operational. Ready for testing.

---

## Troubleshooting

### Service Won't Start

```bash
# Check service status for errors
sudo systemctl status [service-name]

# Check service logs
sudo journalctl -u [service-name] -n 50

# Example for Nginx
sudo journalctl -u nginx -n 50
```

### Git Permission Issues

```bash
# Fix ownership
sudo chown -R snasna:snasna /var/www/parental_wifi

# Fix permissions for web server
sudo chown -R snasna:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Laravel Not Accessible

```bash
# Test Nginx configuration
sudo nginx -t

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Check PHP-FPM is running
ps aux | grep php-fpm

# Check PHP-FPM socket exists (should show the socket file)
ls -la /var/run/php/php8.4-fpm.sock
```

### Access Point Not Visible

```bash
# Check hostapd status
sudo systemctl status hostapd

# Check hostapd logs for errors
sudo journalctl -u hostapd -n 50

# Test hostapd configuration
sudo hostapd -dd /etc/hostapd/hostapd.conf
# (Press Ctrl+C to stop)

# Restart hostapd
sudo systemctl restart hostapd

# Check if WiFi is blocked (RF-kill)
rfkill list

# Unblock WiFi if needed
sudo rfkill unblock wifi
```

### Devices Can't Get IP Address

```bash
# Check dnsmasq status
sudo systemctl status dnsmasq

# Check dnsmasq logs
sudo journalctl -u dnsmasq -n 50

# Check DHCP leases
cat /var/lib/misc/dnsmasq.leases

# Verify wlan0 has correct IP
ip addr show wlan0

# Restart dnsmasq
sudo systemctl restart dnsmasq
```

### No Internet Access for Connected Devices

```bash
# Check IP forwarding is enabled
cat /proc/sys/net/ipv4/ip_forward
# Should output: 1

# Check iptables NAT rules
sudo iptables -t nat -L POSTROUTING -v -n

# Check FORWARD rules
sudo iptables -L FORWARD -v -n

# Check Ethernet connection
ip addr show eth0
# Should show an IP address from your router

# Test internet from Pi
ping -c 3 8.8.8.8

# Restore iptables rules if needed
sudo netfilter-persistent reload
```

### NoDogSplash Not Redirecting Devices

```bash
# Check NoDogSplash service status
sudo systemctl status nodogsplash

# Check NoDogSplash logs
sudo journalctl -u nodogsplash -n 50

# Verify configuration
sudo grep -E "GatewayInterface|GatewayAddress|^RedirectURL" /etc/nodogsplash/nodogsplash.conf

# Check if RedirectURL is commented out (should be)
sudo grep "^RedirectURL" /etc/nodogsplash/nodogsplash.conf
# Should return nothing (RedirectURL should be commented out)

# Check splash page exists
ls -la /etc/nodogsplash/htdocs/splash.html

# Check firewall rule for portal access
sudo grep -A 5 "preauthenticated-users" /etc/nodogsplash/nodogsplash.conf | grep "192.168.4.1"

# Check connected clients
sudo ndsctl clients

# Restart NoDogSplash
sudo systemctl restart nodogsplash

# Test configuration syntax
sudo /usr/bin/nodogsplash -c /etc/nodogsplash/nodogsplash.conf -f -d 3
# (Press Ctrl+C to stop)
```

### IP Forwarding Resets to 0

```bash
# Check ip-forward service status
sudo systemctl status ip-forward.service

# Check if service is enabled
sudo systemctl is-enabled ip-forward.service

# Enable and start service if needed
sudo systemctl enable ip-forward.service
sudo systemctl start ip-forward.service

# Verify IP forwarding is enabled
cat /proc/sys/net/ipv4/ip_forward
# Should output: 1
```

### hostapd Service is Masked

If you see "Unit hostapd.service is masked":

```bash
# Unmask the service
sudo systemctl unmask hostapd

# Enable and start
sudo systemctl enable hostapd
sudo systemctl start hostapd
```

---

## Quick Reference Commands

### Service Management

```bash
# Check all web application services
systemctl is-active nginx php8.4-fpm mariadb

# Check all access point services
systemctl is-active hostapd dnsmasq dhcpcd

# Check all captive portal services
systemctl is-active nodogsplash ip-forward.service

# Start all web application services
sudo systemctl start nginx php8.4-fpm mariadb

# Start all access point services (order matters: dnsmasq first, then hostapd)
sudo systemctl start dnsmasq hostapd dhcpcd

# Start all captive portal services
sudo systemctl start ip-forward.service nodogsplash

# Enable all web application services
sudo systemctl enable nginx php8.4-fpm mariadb

# Enable all access point services
sudo systemctl enable dnsmasq hostapd dhcpcd

# Enable all captive portal services
sudo systemctl enable ip-forward.service nodogsplash

# Restart all web application services
sudo systemctl restart nginx php8.4-fpm mariadb

# Restart all access point services
sudo systemctl restart dnsmasq hostapd dhcpcd

# Restart all captive portal services
sudo systemctl restart ip-forward.service nodogsplash
```

### Access Point Quick Checks

```bash
# Check all access point services at once
echo "hostapd: $(systemctl is-active hostapd)"
echo "dnsmasq: $(systemctl is-active dnsmasq)"
echo "dhcpcd: $(systemctl is-active dhcpcd)"

# Check WiFi interface
ip addr show wlan0

# Check connected devices
ip neigh show dev wlan0
# OR
cat /var/lib/misc/dnsmasq.leases

# Check WiFi password
sudo grep "^wpa_passphrase=" /etc/hostapd/hostapd.conf
```

### Captive Portal Quick Checks

```bash
# Check NoDogSplash service
echo "nodogsplash: $(systemctl is-active nodogsplash)"
echo "ip-forward: $(systemctl is-active ip-forward.service)"

# Check NoDogSplash configuration
sudo grep -E "GatewayInterface|GatewayAddress|^RedirectURL" /etc/nodogsplash/nodogsplash.conf

# Check connected clients
sudo ndsctl clients

# Check IP forwarding
cat /proc/sys/net/ipv4/ip_forward
```

### Git Operations

```bash
# Quick pull (discard local changes)
git restore . && git pull

# Check status
git status

# See recent commits
git log --oneline -5
```

---

## Diagnostic Script

To verify your setup or check another Raspberry Pi, run:

```bash
# Make script executable (first time only)
chmod +x scripts/check-raspberry-pi-setup.sh

# Run diagnostic
./scripts/check-raspberry-pi-setup.sh
```

This script will check:
- Current user and project directory
- PHP version and PHP-FPM service
- Nginx installation and status
- Database server (MariaDB/MySQL)
- Nginx PHP-FPM configuration
- PHP-FPM socket locations
- Git repository configuration

## Notes

- **This document is customized for your verified setup:**
  - Username: `snasna`
  - PHP Version: `8.4.11` (PHP 8.4)
  - PHP-FPM Service: `php8.4-fpm` (active and running)
  - Nginx Version: `1.26.3` (active and running)
  - Database: `mariadb.service` (active and running)
  - Project Directory: `/var/www/parental_wifi`
  - Git Remote: `git@github.com:Cristhan11/Parental_Wifi.git`
  - Git Branch: `main`
- **WiFi Access Point Configuration:**
  - SSID: `Parental_WiFi`
  - Access Point IP: `192.168.4.1/24`
  - DHCP Range: `192.168.4.2` to `192.168.4.51` (50 devices)
  - Gateway/DNS: `192.168.4.1` (the Pi itself)
  - WiFi Password: Stored in `/etc/hostapd/hostapd.conf` (wpa_passphrase)
  - NetworkManager: wlan0 is unmanaged (configured in `/etc/NetworkManager/conf.d/99-unmanaged-devices.conf`)
- **NoDogSplash Configuration:**
  - Gateway Interface: `wlan0`
  - Gateway Address: `192.168.4.1`
  - RedirectURL: Commented out (uses splash page instead)
  - Splash Page: `/etc/nodogsplash/htdocs/splash.html` (redirects to portal with token)
  - Port: `2050` (NoDogSplash web server)
  - IP Forwarding: Enabled via `ip-forward.service`
- Always test Nginx configuration before reloading: `sudo nginx -t`
- All commands assume you're in the project directory: `/var/www/parental_wifi`
- All services are confirmed active and properly configured
- When restarting access point services, restart dnsmasq before hostapd
- Access point requires Ethernet connection (eth0) for internet access

---

## Related Documentation

- **WiFi Access Point Setup**: `docs/RASPBERRY_PI_ACCESS_POINT_SETUP.md` - Complete guide for setting up the Raspberry Pi as a WiFi access point
- **NoDogSplash Setup**: `docs/NODOGSPLASH_SETUP.md` - Complete NoDogSplash installation and configuration guide
- **NoDogSplash Integration**: `docs/NODOGSPLASH_INTEGRATION.md` - Detailed implementation of NoDogSplash integration
- **Network Control Architecture**: `docs/NETWORK_CONTROL_SYSTEM_ARCHITECTURE.md` - System architecture and how network control works
- **Video System Testing (Test Phase 3)**: `docs/VIDEO_SYSTEM_TESTING.md`
- **Test Phase 3 Results**: `docs/TEST_PHASE_3_RESULTS.md`
- **General Testing Guide**: `docs/TESTING.md`
- **Raspberry Pi Setup**: `docs/VIDEO_SYSTEM_TESTING.md` (Steps 1-10)

