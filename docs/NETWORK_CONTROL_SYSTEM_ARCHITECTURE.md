# Network Control System Architecture

## Overview

This document explains how the network control system works, including the shell scripts, PHP services, and how they interact to provide device blocking, unblocking, whitelisting, monitoring, and captive portal redirect capabilities.

## System Components

The network control system consists of:

1. **Shell Scripts** (in `scripts/` directory): Execute iptables commands and NoDogSplash control commands
2. **ScriptExecutor Service** (PHP): Secure wrapper for executing shell scripts
3. **NetworkService** (PHP): High-level interface for network operations (iptables blocking)
4. **NoDogSplashService** (PHP): High-level interface for captive portal redirects
5. **Laravel Application**: Uses both services to control devices

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Application                      │
│  (CheckTimeExpiration Job, TimeGrantingService, etc.)      │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Calls methods
                       ▼
        ┌──────────────┴──────────────┐
        │                             │
        ▼                             ▼
┌──────────────────────┐   ┌──────────────────────────────┐
│  NetworkService       │   │  NoDogSplashService          │
│  (PHP)                │   │  (PHP)                       │
│  - blockDevice()      │   │  - redirectDeviceToPortal()  │
│  - unblockDevice()    │   │  - allowDeviceThrough()      │
│  - whitelistDevice()  │   │  - isDeviceRedirected()      │
│  - getConnectedDevices│   └──────────────┬───────────────┘
│  - getTrafficStats()  │                  │
│  - isDeviceBlocked()  │                  │
└──────────┬────────────┘                  │
           │                                │
           │ Uses ScriptExecutor            │ Uses ScriptExecutor
           ▼                                ▼
┌─────────────────────────────────────────────────────────────┐
│                ScriptExecutor Service (PHP)                  │
│  - Validates script whitelist                               │
│  - Validates script paths                                   │
│  - Sanitizes arguments                                      │
│  - Executes scripts with sudo                               │
│  - Captures output and return codes                         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Executes
                       ▼
        ┌──────────────┴──────────────┐
        │                             │
        ▼                             ▼
┌──────────────────────┐   ┌──────────────────────────────┐
│  Network Scripts      │   │  NoDogSplash Scripts         │
│  (Bash)               │   │  (Bash)                      │
│  - block_device.sh    │   │  - redirect_device_portal.sh │
│  - unblock_device.sh  │   │  - allow_device_through.sh   │
│  - whitelist_device.sh│   │  - check_device_redirected.sh│
│  - get_connected_     │   └──────────────┬───────────────┘
│    devices.sh         │                  │
│  - monitor_traffic.sh │                  │
└──────────┬────────────┘                  │
           │                                │
           │ Modifies/Queries                │ Controls
           ▼                                ▼
┌──────────────────────┐   ┌──────────────────────────────┐
│  iptables            │   │  NoDogSplash                 │
│  (Linux Firewall)    │   │  (Captive Portal)            │
│  - INPUT chain       │   │  - ndsctl auth/deauth        │
│  - FORWARD chain     │   │  - Client state management   │
│  - MAC-based rules   │   │  - HTTP request interception│
└──────────────────────┘   └──────────────────────────────┘
```
 
## Component Details

### 1. ScriptExecutor Service (`app/Services/ScriptExecutor.php`)

**Purpose**: Secure wrapper for executing shell scripts from PHP.

**Key Responsibilities**:
- **Security**: Whitelist validation, path validation, input sanitization
- **Execution**: Runs scripts with sudo privileges
- **Error Handling**: Captures output, return codes, and errors
- **Logging**: Logs all script executions for audit trail

**How It Works**:

1. **Whitelist Check**: Verifies script is in allowed list
   ```php
   $allowedScripts = [
       // Network control scripts (iptables)
       'block_device.sh',
       'unblock_device.sh',
       'whitelist_device.sh',
       'get_connected_devices.sh',
       'monitor_traffic.sh',
       // NoDogSplash control scripts (captive portal)
       'redirect_device_portal.sh',
       'allow_device_through.sh',
       'check_device_redirected.sh',
   ];
   ```

2. **Path Validation**: 
   - Checks for path traversal attacks (`../`)
   - Verifies script exists and is executable
   - Ensures script is within `scripts/` directory
   - Resolves symlinks and verifies final path

3. **Argument Sanitization**:
   - Escapes all arguments using `escapeshellarg()`
   - Prevents command injection attacks

4. **Execution**:
   - Builds command: `sudo /full/path/to/script.sh 'arg1' 'arg2'`
   - Executes via `exec()` and captures output
   - Returns structured result array

**Security Features**:
- Only whitelisted scripts can be executed
- Full path validation prevents directory traversal
- Argument escaping prevents command injection
- All executions are logged for auditing

---

### 2. NetworkService (`app/Services/NetworkService.php`)

**Purpose**: High-level interface for network operations. Provides methods that other parts of the application can call to control devices.

**Key Methods**:

#### `blockDevice(Device $device): bool`
- **Purpose**: Block a device from accessing the internet
- **Flow**:
  1. Validates device has MAC address
  2. Calls ScriptExecutor to run `block_device.sh` with MAC address
  3. Updates database status to 'blocked'
  4. Logs operation (success or failure)
  5. Returns true if script succeeded, false otherwise

#### `unblockDevice(Device $device): bool`
- **Purpose**: Allow a device to access the internet again
- **Flow**:
  1. Validates device has MAC address
  2. Calls ScriptExecutor to run `unblock_device.sh` with MAC address
  3. Updates database status to 'active'
  4. Logs operation
  5. Returns success status

#### `whitelistDevice(Device $device): bool`
- **Purpose**: Give device unrestricted access (bypasses all restrictions)
- **Flow**:
  1. Validates device has MAC address
  2. Calls ScriptExecutor to run `whitelist_device.sh` with MAC address
  3. Logs operation
  4. Returns success status

#### `isDeviceBlocked(Device $device): bool`
- **Purpose**: Check if device is actually blocked at network level
- **Flow**:
  1. Gets device MAC address
  2. Normalizes MAC address format
  3. Queries iptables FORWARD and INPUT chains directly
  4. Searches for DROP rules containing the MAC address
  5. Returns true if blocking rule found, false otherwise

#### `getConnectedDevices(): array`
- **Purpose**: Get list of all devices currently connected to access point
- **Flow**:
  1. Calls ScriptExecutor to run `get_connected_devices.sh`
  2. Parses JSON output from script
  3. Validates and filters device data
  4. Returns array of devices with MAC, IP, and hostname

#### `getTrafficStats(?string $macAddress = null): array`
- **Purpose**: Get network traffic statistics for devices
- **Flow**:
  1. Calls ScriptExecutor to run `monitor_traffic.sh` (with optional MAC address)
  2. Parses JSON output from script
  3. Validates and filters statistics data
  4. Returns array with bytes_sent and bytes_received

**Error Handling**:
- All methods handle errors gracefully
- Database status is updated even if script fails (partial success)
- Detailed error logging for debugging
- System continues to function even if network operations fail

---

### 3. Shell Scripts

All scripts follow a similar pattern:
1. Validate input (MAC address format, etc.)
2. Normalize MAC address to standard format (uppercase, colons)
3. Execute iptables commands
4. Return appropriate exit codes
5. Output to stdout (JSON for query scripts, messages for action scripts)

#### `block_device.sh`

**Purpose**: Block a device's MAC address using iptables.

**What It Does**:
1. **Validates MAC Address**: Checks format (XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX)
2. **Normalizes MAC Address**: Converts to uppercase with colons
3. **Adds Blocking Rules**: 
   - Adds DROP rule to INPUT chain (blocks traffic to Pi)
   - Adds DROP rule to FORWARD chain (blocks traffic through Pi to internet)
4. **Idempotent**: Safe to run multiple times (checks if rule exists first)

**iptables Commands Used**:
```bash
# Add blocking rule to INPUT chain
sudo iptables -A INPUT -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP

# Add blocking rule to FORWARD chain
sudo iptables -A FORWARD -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP
```

**Command Breakdown**:
- `-A INPUT/FORWARD`: Append rule to chain
- `-i wlan0`: Match packets on WiFi interface
- `-m mac`: Use MAC address matching module
- `--mac-source`: Match this specific MAC address
- `-j DROP`: Action: Drop (block) the packet

**Exit Codes**:
- `0`: Success (device blocked)
- `1`: Validation error (invalid MAC address)
- `2`: iptables error (failed to add rules)

---

#### `unblock_device.sh`

**Purpose**: Remove blocking rules for a device's MAC address.

**What It Does**:
1. **Validates MAC Address**: Same validation as block_device.sh
2. **Normalizes MAC Address**: Converts to standard format
3. **Removes Blocking Rules**:
   - Removes DROP rules from INPUT chain
   - Removes DROP rules from FORWARD chain
4. **Idempotent**: Safe to run even if rules don't exist

**iptables Commands Used**:
```bash
# Remove blocking rule from INPUT chain
sudo iptables -D INPUT -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP

# Remove blocking rule from FORWARD chain
sudo iptables -D FORWARD -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP
```

**Command Breakdown**:
- `-D INPUT/FORWARD`: Delete rule from chain
- Other parameters same as block_device.sh
- Loops to remove all matching rules (in case duplicates exist)

**Exit Codes**:
- `0`: Success (device unblocked, or already unblocked)
- `1`: Validation error
- `2`: iptables error

---

#### `whitelist_device.sh`

**Purpose**: Whitelist a device to bypass all restrictions.

**What It Does**:
1. **Validates and Normalizes MAC Address**: Same as other scripts
2. **Removes Blocking Rules**: Calls unblock logic to remove any DROP rules
3. **Adds ACCEPT Rules at High Priority**:
   - Adds ACCEPT rule at position 1 in INPUT chain (checked first)
   - Adds ACCEPT rule at position 1 in FORWARD chain (checked first)
4. **Ensures Unrestricted Access**: Device bypasses all blocking rules

**iptables Commands Used**:
```bash
# Remove any existing blocking rules (same as unblock)
sudo iptables -D INPUT -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP
sudo iptables -D FORWARD -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP

# Add high-priority ACCEPT rule to INPUT chain
sudo iptables -I INPUT 1 -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j ACCEPT

# Add high-priority ACCEPT rule to FORWARD chain
sudo iptables -I FORWARD 1 -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j ACCEPT
```

**Command Breakdown**:
- `-I INPUT/FORWARD 1`: Insert rule at position 1 (highest priority)
- `-j ACCEPT`: Action: Accept (allow) the packet
- Position 1 means this rule is checked BEFORE any blocking rules

**Why Position 1?**:
- iptables checks rules in order (top to bottom)
- First matching rule wins
- Position 1 = checked first, before any DROP rules
- Guarantees whitelisted devices always pass through

**Exit Codes**:
- `0`: Success (device whitelisted)
- `1`: Validation error
- `2`: iptables error

---

#### `get_connected_devices.sh`

**Purpose**: Get list of devices currently connected to the access point.

**What It Does**:
1. **Queries ARP Table**: Uses `ip neigh show dev wlan0` to get connected devices
2. **Extracts Information**: 
   - IP address (from ARP table)
   - MAC address (from ARP table)
   - Hostname (via reverse DNS lookup)
3. **Normalizes MAC Addresses**: Converts to standard format
4. **Outputs JSON**: Returns array of device objects

**ARP Table Query**:
```bash
ip neigh show dev wlan0
```

**Output Format**:
```
192.168.4.2 dev wlan0 lladdr AA:BB:CC:DD:EE:FF REACHABLE
```

**Parsing**:
- IP address: First field
- MAC address: 5th field (after "lladdr")
- Hostname: Reverse DNS lookup using `getent hosts`

**JSON Output Format**:
```json
[
  {
    "mac": "AA:BB:CC:DD:EE:FF",
    "ip": "192.168.4.2",
    "hostname": "device-name"
  },
  {
    "mac": "11:22:33:44:55:66",
    "ip": "192.168.4.3",
    "hostname": "unknown"
  }
]
```

**Exit Codes**:
- `0`: Success (even if no devices found - returns empty array)
- `1`: System error (interface not found, etc.)

---

#### `monitor_traffic.sh`

**Purpose**: Get network traffic statistics (bytes sent/received) for devices.

**What It Does**:
1. **Optional MAC Address Filter**: If provided, returns stats for that device only
2. **Queries iptables Statistics**: Uses `iptables -L FORWARD -v -n -x` to get traffic stats
3. **Correlates with MAC Addresses**: Matches traffic stats with MAC addresses
4. **Outputs JSON**: Returns array of traffic statistics

**iptables Statistics Query**:
```bash
sudo iptables -L FORWARD -v -n -x
```

**Command Breakdown**:
- `-L FORWARD`: List FORWARD chain rules
- `-v`: Verbose (show byte and packet counts)
- `-n`: Numeric (don't resolve hostnames)
- `-x`: Exact byte counts (not abbreviated)

**How It Works**:
1. Gets list of connected devices from ARP table
2. For each device, queries iptables for traffic statistics
3. Extracts bytes_sent and bytes_received from iptables output
4. Combines into JSON array

**JSON Output Format**:
```json
[
  {
    "mac": "AA:BB:CC:DD:EE:FF",
    "bytes_sent": 1048576,
    "bytes_received": 2097152
  },
  {
    "mac": "11:22:33:44:55:66",
    "bytes_sent": 512000,
    "bytes_received": 1024000
  }
]
```

**Usage**:
```bash
# Get stats for all devices
./monitor_traffic.sh

# Get stats for specific device
./monitor_traffic.sh AA:BB:CC:DD:EE:FF
```

**Exit Codes**:
- `0`: Success (even if no traffic found - returns empty array or zeros)
- `1`: Validation error (invalid MAC address, if provided)
- `2`: System error (iptables or network commands failed)

---

## Complete Flow Examples

### Example 1: Blocking a Device When Time Expires

```
1. CheckTimeExpiration Job runs
   ↓
2. Finds device with expired time
   ↓
3. Calls NetworkService::blockDevice($device)
   ↓
4. NetworkService validates MAC address exists
   ↓
5. NetworkService calls ScriptExecutor::execute('block_device.sh', [MAC])
   ↓
6. ScriptExecutor validates script is whitelisted
   ↓
7. ScriptExecutor validates script path exists and is executable
   ↓
8. ScriptExecutor sanitizes MAC address argument
   ↓
9. ScriptExecutor executes: sudo /path/to/block_device.sh 'AA:BB:CC:DD:EE:FF'
   ↓
10. block_device.sh validates MAC address format
    ↓
11. block_device.sh normalizes MAC address
    ↓
12. block_device.sh adds iptables DROP rules to INPUT and FORWARD chains
    ↓
13. block_device.sh returns exit code 0 (success)
    ↓
14. ScriptExecutor captures output and return code
    ↓
15. ScriptExecutor logs execution
    ↓
16. NetworkService updates database status to 'blocked'
    ↓
17. NetworkService logs operation
    ↓
18. Device is now blocked at network level (cannot access internet)
```

### Example 2: Unblocking a Device After Time is Granted

```
1. Child completes quiz/video
   ↓
2. TimeGrantingService grants time to device
   ↓
3. TimeGrantingService calls NetworkService::unblockDevice($device)
   ↓
4. NetworkService validates MAC address exists
   ↓
5. NetworkService calls ScriptExecutor::execute('unblock_device.sh', [MAC])
   ↓
6. ScriptExecutor performs security checks
   ↓
7. ScriptExecutor executes: sudo /path/to/unblock_device.sh 'AA:BB:CC:DD:EE:FF'
   ↓
8. unblock_device.sh validates and normalizes MAC address
   ↓
9. unblock_device.sh removes iptables DROP rules from INPUT and FORWARD chains
   ↓
10. unblock_device.sh returns exit code 0 (success)
    ↓
11. ScriptExecutor captures result
    ↓
12. NetworkService updates database status to 'active'
    ↓
13. NetworkService logs operation
    ↓
14. Device can now access internet again
```

### Example 3: Getting Connected Devices

```
1. Admin panel requests list of connected devices
   ↓
2. Controller calls NetworkService::getConnectedDevices()
   ↓
3. NetworkService calls ScriptExecutor::execute('get_connected_devices.sh', [])
   ↓
4. ScriptExecutor performs security checks
   ↓
5. ScriptExecutor executes: sudo /path/to/get_connected_devices.sh
   ↓
6. get_connected_devices.sh queries ARP table: ip neigh show dev wlan0
   ↓
7. get_connected_devices.sh extracts IP and MAC addresses
   ↓
8. get_connected_devices.sh performs reverse DNS lookup for hostnames
   ↓
9. get_connected_devices.sh normalizes MAC addresses
   ↓
10. get_connected_devices.sh outputs JSON array to stdout
    ↓
11. ScriptExecutor captures JSON output
    ↓
12. NetworkService parses JSON output
    ↓
13. NetworkService validates and filters device data
    ↓
14. NetworkService returns array of devices to controller
    ↓
15. Controller displays devices in admin panel
```

---

## iptables Chain Explanation

### INPUT Chain
- **Purpose**: Handles traffic coming TO the Raspberry Pi itself
- **Use Case**: Blocks device from accessing the Pi's services
- **Example**: Device tries to SSH into Pi → blocked by INPUT chain rule

### FORWARD Chain
- **Purpose**: Handles traffic being FORWARDED through the Pi (to internet)
- **Use Case**: Blocks device from accessing the internet (primary use case)
- **Example**: Device tries to visit google.com → blocked by FORWARD chain rule

### Rule Priority
- Rules are checked in order (top to bottom)
- First matching rule wins
- Position 1 = highest priority (checked first)
- Position N = lower priority (checked later)

### Rule Types Used

**DROP Rules** (blocking):
```bash
iptables -A FORWARD -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP
```
- Blocks all traffic from this MAC address
- Used by `block_device.sh`

**ACCEPT Rules** (whitelisting):
```bash
iptables -I FORWARD 1 -i wlan0 -m mac --mac-source AA:BB:CC:DD:EE:FF -j ACCEPT
```
- Allows all traffic from this MAC address
- Position 1 ensures it's checked before DROP rules
- Used by `whitelist_device.sh`

---

## Security Considerations

### ScriptExecutor Security
1. **Whitelist**: Only approved scripts can be executed
2. **Path Validation**: Prevents directory traversal attacks
3. **Argument Sanitization**: Prevents command injection
4. **Logging**: All executions logged for audit trail

### Script Security
1. **Input Validation**: All scripts validate MAC address format
2. **Error Handling**: Scripts handle errors gracefully
3. **Idempotent**: Scripts can be run multiple times safely
4. **Exit Codes**: Clear exit codes for success/failure

### Sudoers Configuration
- www-data user can only execute specific scripts
- No password required (NOPASSWD)
- Full absolute paths required
- Cannot execute arbitrary commands

### Network Security
- iptables rules are based on MAC addresses (harder to spoof than IPs)
- Rules are persistent (survive reboots if configured)
- Both INPUT and FORWARD chains protected

---

## Error Handling Strategy

### Script Level
- Scripts validate input before executing commands
- Scripts return appropriate exit codes
- Error messages go to stderr (don't interfere with JSON output)

### ScriptExecutor Level
- Validates script before execution
- Captures all output and return codes
- Logs all executions (success and failure)
- Returns structured result array

### NetworkService Level
- Validates device has MAC address before calling scripts
- Updates database even if script fails (partial success)
- Logs detailed error information
- Returns boolean success status

### Application Level
- System continues to function even if network operations fail
- Database status tracks intent (may differ from network status)
- Can retry failed operations
- Detailed logs help with debugging

---

## Data Flow Summary

### Blocking Flow
```
Device Time Expires
  → NetworkService::blockDevice()
    → ScriptExecutor::execute('block_device.sh')
      → block_device.sh adds iptables DROP rules
        → Device cannot access internet
```

### Unblocking Flow
```
Time Granted to Device
  → NetworkService::unblockDevice()
    → ScriptExecutor::execute('unblock_device.sh')
      → unblock_device.sh removes iptables DROP rules
        → Device can access internet
```

### Whitelisting Flow
```
Admin Whitelists Device
  → NetworkService::whitelistDevice()
    → ScriptExecutor::execute('whitelist_device.sh')
      → whitelist_device.sh adds iptables ACCEPT rules (position 1)
        → Device bypasses all restrictions
```

### Query Flow
```
Get Connected Devices
  → NetworkService::getConnectedDevices()
    → ScriptExecutor::execute('get_connected_devices.sh')
      → get_connected_devices.sh queries ARP table
        → Returns JSON array of devices
```

### Monitoring Flow
```
Get Traffic Statistics
  → NetworkService::getTrafficStats()
    → ScriptExecutor::execute('monitor_traffic.sh')
      → monitor_traffic.sh queries iptables statistics
        → Returns JSON array with bytes sent/received
```

---

## Key Concepts

### MAC Address
- **What**: Unique identifier for network interface
- **Format**: XX:XX:XX:XX:XX:XX (6 pairs of hexadecimal characters)
- **Why**: More reliable than IP address (IPs can change via DHCP)
- **Usage**: Used in iptables rules to identify devices

### iptables Chains
- **INPUT**: Traffic coming TO the Pi
- **FORWARD**: Traffic going THROUGH the Pi (to internet)
- **OUTPUT**: Traffic going FROM the Pi (not used in this system)

### Rule Priority
- Rules checked in order (top to bottom)
- First matching rule wins
- Position 1 = highest priority
- ACCEPT rules at position 1 bypass all DROP rules

### Idempotency
- Scripts can be run multiple times safely
- Won't create duplicate rules
- Won't fail if rules already exist/don't exist
- Important for reliability and retry logic

### JSON Output
- Query scripts (get_connected_devices.sh, monitor_traffic.sh) output JSON
- Makes parsing easy in PHP
- Error messages go to stderr (don't interfere with JSON)
- Empty results return empty array `[]`

---

## Troubleshooting

### Script Execution Fails
1. Check sudoers configuration (see `SUDOERS_CONFIGURATION.md`)
2. Verify script exists and is executable: `ls -la scripts/`
3. Check script permissions: `chmod +x scripts/*.sh`
4. Test script manually: `sudo scripts/block_device.sh AA:BB:CC:DD:EE:FF`
5. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Device Not Blocking
1. Check if iptables rule exists: `sudo iptables -L FORWARD -n -v | grep MAC`
2. Verify MAC address format is correct
3. Check if device is whitelisted (ACCEPT rule at position 1)
4. Verify wlan0 interface is correct
5. Check script output in Laravel logs

### JSON Parsing Errors
1. Verify script outputs valid JSON: `sudo scripts/get_connected_devices.sh`
2. Check for error messages in stderr
3. Validate JSON format: `echo 'JSON' | jq .`
4. Check Laravel logs for parsing errors

### Permission Errors
1. Verify sudoers configuration
2. Check www-data user can execute scripts
3. Verify script ownership and permissions
4. Test with: `sudo -u www-data sudo scripts/block_device.sh AA:BB:CC:DD:EE:FF`

---

## NoDogSplash Integration

### Overview

NoDogSplash provides the **captive portal redirect layer** of the three-layer blocking system:

1. **Database Layer**: Tracks device state (active/blocked)
2. **Network Layer**: iptables blocking (physical security)
3. **Redirect Layer**: NoDogSplash redirects (user experience)

### How NoDogSplash Works

NoDogSplash manages devices in two states:
- **Preauthenticated**: Device is redirected to portal on HTTP requests
- **Authenticated**: Device can access internet normally

### NoDogSplash Scripts

#### `redirect_device_portal.sh`
- **Purpose**: Redirects device to portal by putting it in Preauthenticated state
- **How**: Uses `ndsctl deauth <token>` to deauthenticate device
- **Result**: Device's HTTP requests redirect to `splash.html?tok=TOKEN` (when `RedirectURL` is not set), which then redirects to the portal with token

#### `allow_device_through.sh`
- **Purpose**: Allows device through by putting it in Authenticated state
- **How**: Uses `ndsctl auth <token>` to authenticate device
- **Result**: Device can access internet normally

#### `check_device_redirected.sh`
- **Purpose**: Checks if device is currently redirected
- **How**: Queries `ndsctl clients` and checks device state
- **Returns**: Exit code 0 if Preauthenticated (redirected), 1 if Authenticated (not redirected)


### Integration with NetworkService

Both services work together:
- **NetworkService**: Blocks device at network level (iptables)
- **NoDogSplashService**: Redirects device to portal (captive portal)

When time expires:
1. NetworkService blocks device (Layer 2)
2. NoDogSplashService redirects device (Layer 3)

When time is granted:
1. NetworkService unblocks device (Layer 2)
2. NoDogSplashService allows device through (Layer 3)

### HTTP-Only Interception

**Current Limitation:** The system only intercepts HTTP requests. HTTPS requests are not intercepted.

**What This Means:**
- HTTP sites (e.g., `http://google.com`) are intercepted and redirected to portal ✅
- HTTPS sites (e.g., `https://google.com`) are NOT intercepted ❌

**Why This Limitation:**
- HTTPS interception would require DNS interception, SSL certificate management, and more complex configuration
- HTTP interception is sufficient for the use case, as most browsers attempt HTTP first for captive portal detection
- This keeps the system simple and maintainable

### Configuration

NoDogSplash requires:
- Configuration file: `/etc/nodogsplash/nodogsplash.conf` (with `RedirectURL` commented out)
- Splash page: `/etc/nodogsplash/htdocs/splash.html` (redirects to portal with token)
- Firewall rule: Allow port 80 to gateway IP for Preauthenticated users (prevents redirect loop)
- Systemd service: `nodogsplash.service`
- Sudo permissions: `www-data` can execute `ndsctl` commands (including `ndsctl clients` for token lookup)

**Important:** `RedirectURL` should be commented out in the config file. When not set, NoDogSplash uses the splash page which passes the token parameter to the portal.

For complete setup details, see `docs/NODOGSPLASH_SETUP.md`.

---

## Summary

The network control system provides a secure, reliable way to control device access to the internet through:

1. **Shell Scripts**: Execute iptables commands and NoDogSplash control commands
2. **ScriptExecutor**: Provides secure execution with validation and error handling
3. **NetworkService**: High-level interface for network-level blocking (iptables)
4. **NoDogSplashService**: High-level interface for captive portal redirects
5. **Laravel Integration**: Seamlessly integrates with the rest of the application

All components work together to provide:
- Device blocking/unblocking (iptables)
- Device whitelisting (iptables)
- Connected device discovery
- Traffic monitoring
- Captive portal redirects (NoDogSplash)
- Secure execution with proper error handling

The system uses a **three-layer approach**:
- **Layer 1 (Database)**: Tracks device state
- **Layer 2 (Network)**: iptables blocking for physical security
- **Layer 3 (Redirect)**: NoDogSplash redirects for user experience

The system is designed to be:
- **Secure**: Multiple layers of validation and sanitization
- **Reliable**: Idempotent operations, graceful error handling
- **Maintainable**: Clear separation of concerns, detailed logging
- **Extensible**: Easy to add new scripts or modify existing ones

