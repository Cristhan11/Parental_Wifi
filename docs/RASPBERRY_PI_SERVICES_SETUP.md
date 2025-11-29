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
- ✅ All services are active and running
- ✅ Git repository is properly configured

---

## Required Services

The following services must be running for the Laravel application to function properly:

1. **Nginx** - Web server
2. **PHP-FPM 8.4** - PHP FastCGI Process Manager (php8.4-fpm)
3. **MariaDB** - Database server

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

### Enable Services to Start on Boot

```bash
# Enable Nginx
sudo systemctl enable nginx

# Enable PHP-FPM
sudo systemctl enable php8.4-fpm

# Enable MariaDB
sudo systemctl enable mariadb
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

- [x] Nginx is running: `systemctl is-active nginx` → `active` ✅ (nginx/1.26.3)
- [x] PHP-FPM 8.4 is running: `systemctl is-active php8.4-fpm` → `active` ✅
- [x] MariaDB is running: `systemctl is-active mariadb` → `active` ✅
- [x] Nginx config points to PHP 8.4-FPM: `grep "fastcgi_pass" /etc/nginx/sites-available/parental_wifi` shows `php8.4-fpm.sock` ✅
- [x] PHP-FPM socket exists: `ls -la /var/run/php/php8.4-fpm.sock` → socket file exists ✅
- [ ] Repository is up to date: `git pull` completed successfully
- [ ] Laravel is accessible: `curl http://localhost/` returns HTML (not error)

**Status:** All services verified and running correctly. Ready for testing.

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

---

## Quick Reference Commands

### Service Management

```bash
# Check all services
systemctl is-active nginx php8.4-fpm mariadb

# Start all services
sudo systemctl start nginx php8.4-fpm mariadb

# Enable all services
sudo systemctl enable nginx php8.4-fpm mariadb

# Restart all services
sudo systemctl restart nginx php8.4-fpm mariadb
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
- Always test Nginx configuration before reloading: `sudo nginx -t`
- All commands assume you're in the project directory: `/var/www/parental_wifi`
- All services are confirmed active and properly configured

---

## Related Documentation

- **Test Phase 3 Execution**: `docs/TEST_PHASE_3_EXECUTION.md`
- **Video System Testing**: `docs/VIDEO_SYSTEM_TESTING.md`
- **Raspberry Pi Setup**: `docs/VIDEO_SYSTEM_TESTING.md` (Steps 1-10)

