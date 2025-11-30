# Sudoers Configuration Guide

## Overview

This guide explains how to configure sudoers on the Raspberry Pi to allow the Laravel application (running as `www-data`) to execute network control scripts without requiring a password prompt.

## Why Do We Need Sudoers Configuration?

### The Problem

- **Network control scripts require root privileges**: Scripts like `block_device.sh` and `unblock_device.sh` need to modify iptables firewall rules, which requires root/administrator privileges.
- **Laravel runs as www-data**: The Laravel application runs as the `www-data` user (standard for web servers), which does not have root privileges.
- **Password prompts don't work**: When PHP executes scripts via `exec()` or `shell_exec()`, there's no way to enter a password interactively.

### The Solution

Sudoers configuration allows `www-data` to execute specific scripts with sudo privileges **without requiring a password**. This is a secure way to grant limited root access to only the scripts that need it.

## Security Considerations

### Why This Is Safe

1. **Whitelist Approach**: Only specific scripts are allowed, not arbitrary commands
2. **Full Path Required**: Scripts must be executed with full absolute paths
3. **No Shell Access**: www-data cannot execute arbitrary commands, only the whitelisted scripts
4. **Audit Trail**: All script executions are logged by ScriptExecutor service
5. **Limited Scope**: Only network control scripts are allowed, not system-wide root access

### Security Best Practices

- **Never use `NOPASSWD: ALL`**: This would give www-data unlimited root access (very dangerous)
- **Always use full paths**: Prevents PATH manipulation attacks
- **Limit to specific scripts**: Only allow the scripts that are actually needed
- **Regular audits**: Review sudoers configuration periodically
- **Monitor logs**: Check for unauthorized script execution attempts

## Step-by-Step Configuration

### Step 1: Determine Your Application Path

First, you need to know where your Laravel application is installed on the Raspberry Pi.

**Common locations:**
- `/var/www/parental_wifi/` (standard Apache/Nginx location)
- `/home/pi/parental_wifi/` (development/testing)
- Custom location based on your setup

**To find your path:**
```bash
# On Raspberry Pi, run this command to find your Laravel root directory
cd /var/www/parental_wifi  # or your installation path
pwd
```

**Note:** Replace `/var/www/parental_wifi` in the examples below with your actual path.

### Step 2: Verify Script Locations

Verify that all scripts exist in the `scripts/` directory:

```bash
# Navigate to your Laravel root directory
cd /var/www/parental_wifi

# List scripts directory
ls -la scripts/

# You should see these files:
# - block_device.sh
# - unblock_device.sh
# - whitelist_device.sh
# - get_connected_devices.sh
# - monitor_traffic.sh
```

**Important:** All scripts must be executable. If they're not, make them executable:

```bash
chmod +x scripts/*.sh
```

### Step 3: Create Sudoers Configuration File

**Never edit `/etc/sudoers` directly!** Instead, create a new file in `/etc/sudoers.d/` directory.

**Why `/etc/sudoers.d/`?**
- Easier to manage (separate file for each application)
- Less risk of breaking system sudoers configuration
- Can be easily removed if needed
- Automatically included by sudoers

**Create the configuration file:**

```bash
# Use sudo to create the file (requires root privileges)
sudo nano /etc/sudoers.d/parental-wifi-scripts
```

### Step 4: Add Sudoers Entries

Add the following lines to the file (replace `/var/www/parental_wifi` with your actual path):

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

**Explanation of the format:**
- `www-data`: The user allowed to execute the script
- `ALL`: Can execute from any host (localhost in this case)
- `(ALL)`: Can execute as any user (root in this case)
- `NOPASSWD`: No password required
- `/var/www/parental_wifi/scripts/block_device.sh`: Full path to the script

**Important Notes:**
- Use **full absolute paths** (not relative paths)
- One script per line
- No trailing slashes
- Scripts must be executable (chmod +x)

### Step 5: Set Correct File Permissions

Sudoers configuration files must have specific permissions for security:

```bash
# Set correct permissions (read-only for root, no write access for others)
sudo chmod 0440 /etc/sudoers.d/parental-wifi-scripts

# Set correct ownership (root user and root group)
sudo chown root:root /etc/sudoers.d/parental-wifi-scripts
```

**Why these permissions?**
- `0440`: Read-only for owner and group, no access for others
- Prevents unauthorized modifications
- Required by sudoers for security

### Step 6: Validate Sudoers Configuration

**Always validate sudoers configuration before testing!** Invalid syntax can lock you out of sudo access.

```bash
# Validate sudoers syntax (checks all files including sudoers.d/)
sudo visudo -c
```

**Expected output:**
```
/etc/sudoers: parsed OK
/etc/sudoers.d/parental-wifi-scripts: parsed OK
```

**If you see errors:**
- Fix the syntax errors immediately
- Do NOT proceed until validation passes
- Invalid sudoers can prevent sudo from working

### Step 7: Test Configuration

Test that www-data can execute scripts without password:

```bash
# Switch to www-data user
sudo su -s /bin/bash www-data

# Test executing a script (should work without password)
sudo /var/www/parental_wifi/scripts/block_device.sh AA:BB:CC:DD:EE:FF

# Exit www-data user
exit
```

**Expected behavior:**
- Script executes without password prompt
- No "password required" errors
- Script runs successfully (or fails with script-specific errors, not permission errors)

**If password is still required:**
- Check file path is correct (use `pwd` to verify)
- Check file permissions (must be 0440)
- Check file ownership (must be root:root)
- Verify sudoers syntax with `sudo visudo -c`

## Verification Checklist

Before considering the configuration complete, verify:

- [ ] All scripts exist in `scripts/` directory
- [ ] All scripts are executable (`chmod +x`)
- [ ] Sudoers file created in `/etc/sudoers.d/`
- [ ] All script paths are correct (full absolute paths)
- [ ] File permissions are 0440
- [ ] File ownership is root:root
- [ ] Sudoers syntax validated (`sudo visudo -c`)
- [ ] Test execution works without password
- [ ] Scripts execute successfully

## Troubleshooting

### Problem: "password is required" error

**Possible causes:**
1. **Incorrect path**: Script path in sudoers doesn't match actual script location
2. **Relative path used**: Must use full absolute path
3. **Script not executable**: Run `chmod +x scripts/*.sh`
4. **Sudoers file permissions**: Must be 0440
5. **Sudoers syntax error**: Run `sudo visudo -c` to check

**Solution:**
```bash
# Verify script exists and is executable
ls -la /var/www/parental_wifi/scripts/block_device.sh

# Verify sudoers entry
sudo cat /etc/sudoers.d/parental-wifi-scripts

# Test manually
sudo -u www-data sudo /var/www/parental_wifi/scripts/block_device.sh AA:BB:CC:DD:EE:FF
```

### Problem: "command not found" error

**Possible causes:**
1. **Script doesn't exist**: Check script file exists
2. **Wrong path**: Verify path in sudoers matches actual location
3. **Script not executable**: Run `chmod +x scripts/*.sh`

**Solution:**
```bash
# Check if script exists
ls -la /var/www/parental_wifi/scripts/block_device.sh

# Make executable if needed
chmod +x /var/www/parental_wifi/scripts/block_device.sh
```

### Problem: "permission denied" error

**Possible causes:**
1. **Script not executable**: Run `chmod +x scripts/*.sh`
2. **Wrong ownership**: Scripts should be owned by www-data or root
3. **Directory permissions**: scripts/ directory must be readable

**Solution:**
```bash
# Make scripts executable
chmod +x /var/www/parental_wifi/scripts/*.sh

# Check ownership (should be www-data or root)
ls -la /var/www/parental_wifi/scripts/

# Fix ownership if needed
sudo chown www-data:www-data /var/www/parental_wifi/scripts/*.sh
```

### Problem: Sudoers syntax error

**Possible causes:**
1. **Typo in file**: Check for spelling errors
2. **Missing path**: Must use full absolute path
3. **Wrong format**: Must follow exact sudoers format

**Solution:**
```bash
# Validate syntax
sudo visudo -c

# Check file contents
sudo cat /etc/sudoers.d/parental-wifi-scripts

# Compare with example above
```

### Problem: Script executes but iptables fails

**This is NOT a sudoers problem** - this is a script execution problem.

**Possible causes:**
1. **iptables not installed**: Install with `sudo apt install iptables`
2. **iptables service not running**: Check with `sudo systemctl status iptables`
3. **Script logic error**: Check script logs and output

**Solution:**
```bash
# Test iptables directly
sudo iptables -L

# Check script output
sudo -u www-data sudo /var/www/parental_wifi/scripts/block_device.sh AA:BB:CC:DD:EE:FF

# Check Laravel logs
tail -f /var/www/parental_wifi/storage/logs/laravel.log
```

## Security Best Practices

### 1. Use Full Absolute Paths

**Good:**
```
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/block_device.sh
```

**Bad:**
```
www-data ALL=(ALL) NOPASSWD: scripts/block_device.sh
www-data ALL=(ALL) NOPASSWD: ./scripts/block_device.sh
```

### 2. Limit to Specific Scripts Only

**Good:**
```
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/block_device.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/unblock_device.sh
```

**Bad:**
```
www-data ALL=(ALL) NOPASSWD: ALL
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/*
```

### 3. Regular Audits

Periodically review sudoers configuration:

```bash
# List all sudoers files
sudo ls -la /etc/sudoers.d/

# Review configuration
sudo cat /etc/sudoers.d/parental-wifi-scripts

# Check for unauthorized changes
sudo grep -r "www-data" /etc/sudoers.d/
```

### 4. Monitor Script Execution

Check Laravel logs for script execution:

```bash
# View recent script executions
tail -f /var/www/parental_wifi/storage/logs/laravel.log | grep ScriptExecutor

# Check for errors
grep -i "script.*fail" /var/www/parental_wifi/storage/logs/laravel.log
```

## Removing Sudoers Configuration

If you need to remove the sudoers configuration:

```bash
# Remove the configuration file
sudo rm /etc/sudoers.d/parental-wifi-scripts

# Validate sudoers still works
sudo visudo -c
```

**Note:** After removing, scripts will require password (and will fail in PHP context).

## Additional Resources

- **Sudoers Manual**: `man sudoers` (on Raspberry Pi)
- **Sudo Documentation**: https://www.sudo.ws/
- **Laravel Documentation**: https://laravel.com/docs
- **iptables Documentation**: `man iptables` (on Raspberry Pi)

## Summary

Sudoers configuration allows the Laravel application to execute network control scripts with root privileges without requiring a password. This is necessary because:

1. **Scripts need root privileges** for iptables operations
2. **Laravel runs as www-data** (non-root user)
3. **Password prompts don't work** in PHP execution context

The configuration is secure because:
- Only specific scripts are allowed (whitelist)
- Full absolute paths are required
- No arbitrary command execution
- All executions are logged

Follow the step-by-step guide above to configure sudoers correctly and securely.

