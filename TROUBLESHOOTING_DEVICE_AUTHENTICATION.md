# Device Authentication Troubleshooting Guide

## Overview
This guide provides commands for troubleshooting device registration and authentication in NoDogSplash, specifically for moving devices from **Preauthenticated** to **Authenticated** state.

---

## 🔍 Step 1: Check Device Status in NoDogSplash

### List All Connected Devices
```bash
sudo ndsctl clients
```

**Output Format:**
```
client_id=0
ip=192.168.4.32
mac=e6:6a:8f:19:be:b1
token=74b99472
state=Preauthenticated

client_id=1
ip=192.168.4.33
mac=aa:bb:cc:dd:ee:ff
token=abc12345
state=Authenticated
```

**What to Look For:**
- `state=Preauthenticated` = Device is redirected to portal (no internet access)
- `state=Authenticated` = Device can access internet normally
- `mac=` = Device's MAC address
- `token=` = Device's authentication token (needed for auth commands)

---

## 🔐 Step 2: Authenticate Device (Preauthenticated → Authenticated)

### Method 1: Using the Script (Recommended)
```bash
# Navigate to scripts directory
cd /path/to/parental_wifi/scripts

# Make script executable (if not already)
chmod +x allow_device_through.sh

# Authenticate device by MAC address
sudo ./allow_device_through.sh AA:BB:CC:DD:EE:FF
```

**What This Does:**
1. Finds device token from MAC address
2. Checks current state (skips if already Authenticated)
3. Authenticates device using `ndsctl auth <token>`
4. Device can now access internet

**Expected Output:**
```
Authenticating device in NoDogSplash
  MAC Address: AA:BB:CC:DD:EE:FF
Info: Looking for device in NoDogSplash client list...
Info: Found device token: 74b99472
Info: Device authenticated successfully (token: 74b99472)
Device authenticated successfully
Device AA:BB:CC:DD:EE:FF can now access internet normally
Info: Device is now in Authenticated state - can access internet
```

---

### Method 2: Manual Authentication (Direct ndsctl)

#### Step 2a: Find Device Token
```bash
# Get device token from MAC address
sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "token=" | cut -d= -f2
```

**Example Output:**
```
74b99472
```

#### Step 2b: Authenticate Using Token
```bash
# Authenticate device (replace TOKEN with actual token from Step 2a)
sudo ndsctl auth 74b99472
```

**Expected Output:**
```
OK
```

#### Step 2c: Verify Authentication
```bash
# Check device state again
sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "state="
```

**Expected Output:**
```
state=Authenticated
```

---

## 🔄 Step 3: Deauthenticate Device (Authenticated → Preauthenticated)

### Method 1: Using the Script (Recommended)
```bash
# Navigate to scripts directory
cd /path/to/parental_wifi/scripts

# Make script executable (if not already)
chmod +x redirect_device_portal.sh

# Deauthenticate device (redirect to portal)
sudo ./redirect_device_portal.sh AA:BB:CC:DD:EE:FF
```

**What This Does:**
1. Finds device token from MAC address
2. Checks current state (skips if already Preauthenticated)
3. Deauthenticates device using `ndsctl deauth <token>`
4. Device will be redirected to portal on next HTTP request

---

### Method 2: Manual Deauthentication (Direct ndsctl)

#### Step 3a: Find Device Token
```bash
sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "token=" | cut -d= -f2
```

#### Step 3b: Deauthenticate Using Token
```bash
# Deauthenticate device (replace TOKEN with actual token)
sudo ndsctl deauth 74b99472
```

**Expected Output:**
```
OK
```

---

## 📋 Complete Troubleshooting Workflow

### Scenario: New Device Registration

```bash
# 1. Check if device is in NoDogSplash client list
sudo ndsctl clients | grep -i "AA:BB:CC:DD:EE:FF"

# 2. If device is found but in Preauthenticated state, authenticate it
cd /path/to/parental_wifi/scripts
sudo ./allow_device_through.sh AA:BB:CC:DD:EE:FF

# 3. Verify authentication worked
sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "state="
```

### Scenario: Device Stuck in Preauthenticated State

```bash
# 1. Check current state
sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF"

# 2. Get token
TOKEN=$(sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "token=" | cut -d= -f2)

# 3. Authenticate device
sudo ndsctl auth $TOKEN

# 4. Verify state changed
sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "state="
```

### Scenario: Device Not Found in NoDogSplash

```bash
# 1. Check if device is connected to WiFi
sudo ndsctl clients

# 2. If device not in list, ensure:
#    - Device is connected to the WiFi network
#    - Device has made at least one HTTP request
#    - NoDogSplash service is running: sudo systemctl status nodogsplash

# 3. Restart NoDogSplash if needed
sudo systemctl restart nodogsplash

# 4. Check again after device makes HTTP request
sudo ndsctl clients
```

---

## 🛠️ Useful One-Liner Commands

### Get Device State by MAC Address
```bash
sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "state=" | cut -d= -f2
```

### Get Device Token by MAC Address
```bash
sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "token=" | cut -d= -f2
```

### Get Device IP by MAC Address
```bash
sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "ip=" | cut -d= -f2
```

### Authenticate Device by MAC (One-Liner)
```bash
TOKEN=$(sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "token=" | cut -d= -f2) && sudo ndsctl auth $TOKEN
```

### Deauthenticate Device by MAC (One-Liner)
```bash
TOKEN=$(sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "token=" | cut -d= -f2) && sudo ndsctl deauth $TOKEN
```

### Check All Preauthenticated Devices
```bash
sudo ndsctl clients | grep -B 2 -A 2 "state=Preauthenticated"
```

### Check All Authenticated Devices
```bash
sudo ndsctl clients | grep -B 2 -A 2 "state=Authenticated"
```

---

## 🔧 NoDogSplash Service Management

### Check NoDogSplash Status
```bash
sudo systemctl status nodogsplash
```

### Restart NoDogSplash
```bash
sudo systemctl restart nodogsplash
```

### View NoDogSplash Logs
```bash
sudo journalctl -u nodogsplash -f
```

### Check NoDogSplash Configuration
```bash
cat /etc/nodogsplash/nodogsplash.conf
```

---

## 📝 Database Verification Commands

### Check Device in Database (Laravel Tinker)
```bash
php artisan tinker
```

Then in tinker:
```php
// Find device by MAC address
$device = App\Models\Device::where('mac_address', 'AA:BB:CC:DD:EE:FF')->first();

// Check device status
$device->status; // Should be 'active' or 'whitelisted'

// Check remaining time
$device->remaining_time_minutes;

// Check IP address
$device->ip_address;
```

### List All Devices
```bash
php artisan tinker --execute="App\Models\Device::all(['id', 'name', 'mac_address', 'status', 'remaining_time_minutes'])->toArray();"
```

---

## 🐛 Common Issues & Solutions

### Issue 1: Device Not Found in NoDogSplash Client List

**Symptoms:**
- `ndsctl clients` doesn't show the device
- Script returns "Device not found in NoDogSplash client list"

**Solutions:**
1. **Ensure device is connected to WiFi**
   ```bash
   # Check WiFi connections
   iw dev wlan0 station dump
   ```

2. **Device must make an HTTP request first**
   - Device needs to try accessing a website
   - NoDogSplash only tracks devices after they make HTTP requests

3. **Restart NoDogSplash**
   ```bash
   sudo systemctl restart nodogsplash
   ```

4. **Check NoDogSplash is running**
   ```bash
   sudo systemctl status nodogsplash
   ```

---

### Issue 2: Authentication Fails

**Symptoms:**
- `ndsctl auth` returns error
- Device stays in Preauthenticated state

**Solutions:**
1. **Verify token is correct**
   ```bash
   # Get token again and verify
   sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF"
   ```

2. **Check NoDogSplash service**
   ```bash
   sudo systemctl restart nodogsplash
   ```

3. **Try authentication again**
   ```bash
   TOKEN=$(sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "token=" | cut -d= -f2)
   sudo ndsctl auth $TOKEN
   ```

---

### Issue 3: Device State Not Changing

**Symptoms:**
- Command succeeds but state doesn't change
- Device still shows Preauthenticated after auth

**Solutions:**
1. **Wait a few seconds** - State change may take a moment

2. **Check state again**
   ```bash
   sudo ndsctl clients | grep -A 5 "mac=AA:BB:CC:DD:EE:FF" | grep "state="
   ```

3. **Restart NoDogSplash**
   ```bash
   sudo systemctl restart nodogsplash
   ```

4. **Device may need to make new HTTP request** - State change applies to new requests

---

### Issue 4: Multiple Devices with Same MAC

**Symptoms:**
- Multiple entries in `ndsctl clients` with same MAC
- Authentication affects wrong device

**Solutions:**
1. **Use most recent token** (highest client_id)
   ```bash
   # Get all entries for MAC
   sudo ndsctl clients | grep -B 2 -A 5 "mac=AA:BB:CC:DD:EE:FF"
   
   # Use token from most recent entry (highest client_id)
   ```

2. **Deauthenticate old entries**
   ```bash
   # Deauthenticate old tokens
   sudo ndsctl deauth OLD_TOKEN
   ```

---

## 📊 Quick Reference Table

| Command | Purpose | Example |
|---------|---------|---------|
| `sudo ndsctl clients` | List all connected devices | Shows all devices with states |
| `sudo ndsctl auth <token>` | Authenticate device | `sudo ndsctl auth 74b99472` |
| `sudo ndsctl deauth <token>` | Deauthenticate device | `sudo ndsctl deauth 74b99472` |
| `sudo ./allow_device_through.sh <MAC>` | Authenticate via script | `sudo ./allow_device_through.sh AA:BB:CC:DD:EE:FF` |
| `sudo ./redirect_device_portal.sh <MAC>` | Deauthenticate via script | `sudo ./redirect_device_portal.sh AA:BB:CC:DD:EE:FF` |
| `sudo systemctl status nodogsplash` | Check service status | Verify NoDogSplash is running |
| `sudo systemctl restart nodogsplash` | Restart service | Apply configuration changes |

---

## ✅ Verification Checklist

After authenticating a device, verify:

- [ ] Device appears in `ndsctl clients` output
- [ ] Device state shows `state=Authenticated`
- [ ] Device can access internet (test from device)
- [ ] Device IP address is updated in database
- [ ] Device status in database is 'active' or 'whitelisted'

---

## 🔗 Related Files

- **Scripts:**
  - `scripts/allow_device_through.sh` - Authenticate device
  - `scripts/redirect_device_portal.sh` - Deauthenticate device
  - `scripts/check_device_redirected.sh` - Check redirect status

- **Services:**
  - `app/Services/NoDogSplashService.php` - PHP service for NoDogSplash operations
  - `app/Jobs/MonitorDeviceConnections.php` - Auto-authenticates devices

---

**Note:** All commands require `sudo` privileges. Replace `AA:BB:CC:DD:EE:FF` with your actual device MAC address.

