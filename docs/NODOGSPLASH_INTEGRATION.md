# NoDogSplash Integration - Implementation Details

## Overview

This document explains the detailed implementation of the NoDogSplash integration for the Parental WiFi Control System. The integration enables the system to redirect devices to a captive portal when their internet time expires and allow them through after completing quizzes or videos.

## Architecture

The NoDogSplash integration follows the same security pattern as the NetworkService:

1. **NoDogSplashService** (PHP) - High-level service that coordinates operations
2. **ScriptExecutor** (PHP) - Secure wrapper that executes bash scripts with validation
3. **Bash Scripts** - System-level scripts that modify NoDogSplash configuration

```
┌─────────────────────────────────────────────────────────────┐
│                  NoDogSplashService (PHP)                    │
│  - redirectDeviceToPortal()                                  │
│  - allowDeviceThrough()                                      │
│  - isDeviceRedirected()                                      │
└────────────────────┬────────────────────────────────────────┘
                     │ Uses
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                ScriptExecutor (PHP)                          │
│  - Validates script is whitelisted                           │
│  - Validates script path                                     │
│  - Escapes arguments                                         │
│  - Executes script securely                                  │
└────────────────────┬────────────────────────────────────────┘
                     │ Executes
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Bash Scripts (System Level)                     │
│  - redirect_device_portal.sh                                 │
│  - allow_device_through.sh                                   │
│  - check_device_redirected.sh                                │
└────────────────────┬────────────────────────────────────────┘
                     │ Modifies
                     ▼
┌─────────────────────────────────────────────────────────────┐
│         NoDogSplash Configuration & Service                  │
│  - /etc/nodogsplash/nodogsplash.conf                        │
│  - nodogsplash.service (systemd)                            │
└─────────────────────────────────────────────────────────────┘
```

---

## File Structure

```
app/Services/
├── NoDogSplashService.php       # Main service (PHP)
└── ScriptExecutor.php            # Script execution wrapper (PHP)

scripts/
├── redirect_device_portal.sh    # Redirect device to portal
├── allow_device_through.sh      # Remove redirect (allow device)
└── check_device_redirected.sh   # Check redirect status
```

---

## 1. NoDogSplashService.php

### Purpose

The `NoDogSplashService` class provides a high-level interface for managing NoDogSplash captive portal redirects. It coordinates with bash scripts via `ScriptExecutor` to configure NoDogSplash.

### Class Structure

```php
class NoDogSplashService
{
    protected ScriptExecutor $scriptExecutor;

    public function __construct(ScriptExecutor $scriptExecutor)
    public function redirectDeviceToPortal(Device $device): bool
    public function allowDeviceThrough(Device $device): bool
    public function isDeviceRedirected(Device $device): bool
}
```

### Dependency Injection

**Syntax Explanation:**
```php
public function __construct(ScriptExecutor $scriptExecutor)
```

- **`public function __construct()`** - PHP constructor method, automatically called when object is created
- **`ScriptExecutor $scriptExecutor`** - Type-hinted parameter that tells PHP/Laravel to automatically inject a ScriptExecutor instance
- **Laravel's Service Container** - Automatically resolves and injects dependencies

**How It Works:**
1. When Laravel creates a `NoDogSplashService` instance, it checks the constructor parameters
2. It sees `ScriptExecutor $scriptExecutor` and automatically creates/finds a ScriptExecutor instance
3. The ScriptExecutor is injected into the constructor
4. We store it as a class property: `$this->scriptExecutor = $scriptExecutor`

### Method: redirectDeviceToPortal()

**Purpose:** Configures NoDogSplash to redirect a device to the portal page.

**Syntax Breakdown:**

```php
public function redirectDeviceToPortal(Device $device): bool
```

- **`public`** - Method can be called from outside the class
- **`function redirectDeviceToPortal()`** - Method name
- **`Device $device`** - Parameter type-hinted as Device model instance
- **`: bool`** - Return type declaration (returns true/false)

**Implementation Logic:**

```php
// Step 1: Extract MAC address from device
$macAddress = $device->mac_address;

// Step 2: Validate MAC address exists
if (empty($macAddress)) {
    Log::error('Cannot redirect device: MAC address is missing');
    return false;
}

// Step 3: Build portal URL using Laravel's route helper
$portalUrl = route('portal.landing', ['mac' => $macAddress]);
```

**Syntax Explanation:**
- **`$device->mac_address`** - Object property access operator (`->`) gets the MAC address
- **`empty($macAddress)`** - PHP function that checks if variable is empty/null/zero
- **`route('portal.landing', ['mac' => $macAddress])`** - Laravel helper that generates URL from named route with query parameters

**Script Execution:**

```php
$result = $this->scriptExecutor->execute('redirect_device_portal.sh', [
    $macAddress,
    $portalUrl,
]);
```

**Syntax Explanation:**
- **`$this->scriptExecutor`** - Access class property (the ScriptExecutor instance)
- **`->execute()`** - Call method on the ScriptExecutor object
- **`'redirect_device_portal.sh'`** - String literal (script name)
- **`[$macAddress, $portalUrl]`** - Array syntax, creates array with 2 elements to pass as arguments

**Result Checking:**

```php
if ($result['success']) {
    Log::info('Device redirected to portal successfully');
    return true;
} else {
    Log::error('Failed to redirect device to portal');
    return false;
}
```

**Syntax Explanation:**
- **`$result['success']`** - Array access operator `[]` gets the 'success' key from result array
- **`Log::info()`** - Laravel facade static method call (writes to log file)
- **`return true`** - Exit function and return boolean value

### Method: allowDeviceThrough()

**Purpose:** Removes redirect configuration, allowing device to access internet normally.

**Syntax Breakdown:**

```php
public function allowDeviceThrough(Device $device): bool
```

**Implementation Logic:**

```php
$result = $this->scriptExecutor->execute('allow_device_through.sh', [
    $macAddress,
]);
```

**Key Difference:** Only passes MAC address (no portal URL needed since we're removing the redirect)

### Method: isDeviceRedirected()

**Purpose:** Checks if device is currently being redirected to portal.

**Syntax Breakdown:**

```php
public function isDeviceRedirected(Device $device): bool
```

**Implementation Logic:**

```php
$result = $this->scriptExecutor->execute('check_device_redirected.sh', [
    $macAddress,
]);

$isRedirected = $result['success'];
return $isRedirected;
```

**Exit Code Logic:**
- Script returns exit code **0** if device IS redirected → `$result['success']` = `true`
- Script returns exit code **1** if device is NOT redirected → `$result['success']` = `false`

**Important:** In Unix/Linux, exit code 0 means "success", but in this context:
- Exit code 0 = "Yes, device IS redirected" (positive result)
- Exit code 1 = "No, device is NOT redirected" (negative result)

---

## 2. ScriptExecutor.php Updates

### Whitelist Addition

**Syntax:**

```php
protected array $allowedScripts = [
    'block_device.sh',
    'unblock_device.sh',
    'whitelist_device.sh',
    'get_connected_devices.sh',
    'monitor_traffic.sh',
    'redirect_device_portal.sh',      // NEW
    'allow_device_through.sh',        // NEW
    'check_device_redirected.sh',     // NEW
];
```

**Syntax Explanation:**
- **`protected array`** - Class property that's an array, accessible within class and subclasses
- **`$allowedScripts`** - Property name
- **`= [...]`** - Array literal syntax, creates array with string elements
- **`'script_name.sh'`** - String literals (script filenames)

**Why Whitelist?**
- **Security:** Only scripts in this list can be executed
- **Prevents:** Arbitrary command execution attacks
- **Whitelist vs Blacklist:** Whitelist is more secure (explicitly allow known-good scripts)

---

## 3. Bash Script: redirect_device_portal.sh

### Purpose

Adds a device to NoDogSplash blocklist, causing all HTTP requests from that device to redirect to the portal page.

### Script Structure

```bash
#!/bin/bash
```

**Syntax Explanation:**
- **`#!`** - Shebang operator, tells system which interpreter to use
- **`/bin/bash`** - Path to bash shell interpreter
- Must be first line of script (tells system to run script with bash)

### Configuration Constants

```bash
NODOGSPLASH_CONFIG="/etc/nodogsplash/nodogsplash.conf"
NODOGSPLASH_SERVICE="nodogsplash"
BACKUP_DIR="/tmp/nodogsplash_backups"
```

**Syntax Explanation:**
- **`VARIABLE_NAME="value"`** - Bash variable assignment (no spaces around `=`)
- **`"..."`** - Double quotes preserve spaces in value
- **`/etc/...`** - Absolute path (starts with `/`)

### Error Handling Setup

```bash
set -e
set -u
```

**Syntax Explanation:**
- **`set -e`** - Exit immediately if any command fails (non-zero exit code)
- **`set -u`** - Exit if any variable is used before being set
- **Purpose:** Makes script more robust (fails fast on errors)

### Function: validate_mac_address()

```bash
validate_mac_address() {
    local mac="$1"
    
    if [ -z "$mac" ]; then
        echo "Error: MAC address is required" >&2
        return 1
    fi
    
    if echo "$mac" | grep -qE '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$'; then
        return 0
    else
        return 1
    fi
}
```

**Syntax Breakdown:**

- **`validate_mac_address()`** - Function definition (no parameters in parentheses)
- **`{`** - Opening brace (function body)
- **`local mac="$1"`** - Local variable assignment
  - **`local`** - Variable scope is only within function
  - **`mac`** - Variable name
  - **`"$1"`** - First function argument (`$1`, `$2`, etc. are positional parameters)
  - **`"..."`** - Quotes preserve value if it contains spaces

- **`if [ -z "$mac" ]; then`** - Conditional statement
  - **`if`** - Conditional keyword
  - **`[ -z "$mac" ]`** - Test command (`[` is alias for `test`)
    - **`-z`** - Test if string is empty (zero length)
    - **`"$mac"`** - Variable expansion (`$mac` gets value, quotes preserve it)
  - **`;`** - Command separator
  - **`then`** - Start of if-then block

- **`echo "Error: MAC address is required" >&2`** - Error output
  - **`echo`** - Print text
  - **`>&2`** - Redirect output to stderr (standard error stream)
  - **Purpose:** Error messages go to stderr, normal output to stdout

- **`return 1`** - Exit function with error code (non-zero = failure)

- **`echo "$mac" | grep -qE '...'`** - Pipe and pattern matching
  - **`echo "$mac"`** - Print MAC address
  - **`|`** - Pipe operator (sends output to next command)
  - **`grep -qE '...'`** - Search for pattern
    - **`-q`** - Quiet mode (don't print matches, just return exit code)
    - **`-E`** - Extended regular expressions
    - **`'...'`** - Regex pattern (single quotes preserve literal meaning)

**Regex Pattern Explanation:**
```
^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$
```

- **`^`** - Start of string
- **`([0-9A-Fa-f]{2}[:-])`** - Capture group: 2 hex digits followed by colon or hyphen
- **`{5}`** - Repeat previous pattern 5 times
- **`([0-9A-Fa-f]{2})`** - Final 2 hex digits
- **`$`** - End of string
- **Matches:** `AA:BB:CC:DD:EE:FF` or `AA-BB-CC-DD-EE-FF`

### Function: normalize_mac_address()

```bash
normalize_mac_address() {
    local mac="$1"
    
    mac=$(echo "$mac" | sed 's/-/:/g')
    mac=$(echo "$mac" | tr '[:lower:]' '[:upper:]')
    
    echo "$mac"
}
```

**Syntax Breakdown:**

- **`mac=$(...)`** - Command substitution
  - **`$(...)`** - Execute command and capture output
  - **`=`** - Assign output to variable

- **`sed 's/-/:/g'`** - Stream editor
  - **`s/-/:/g`** - Substitute pattern
    - **`s/`** - Substitute command
    - **`-/:`** - Replace hyphen with colon
    - **`/g`** - Global (replace all occurrences)

- **`tr '[:lower:]' '[:upper:]'`** - Translate characters
  - **`tr`** - Translate command
  - **`'[:lower:]' '[:upper:]'`** - Replace lowercase with uppercase

- **`echo "$mac"`** - Print normalized MAC address (output of function)

### Function: backup_config_file()

```bash
backup_config_file() {
    if [ ! -f "$NODOGSPLASH_CONFIG" ]; then
        echo "Info: NoDogSplash config file does not exist yet"
        return 0
    fi
    
    if [ ! -d "$BACKUP_DIR" ]; then
        sudo mkdir -p "$BACKUP_DIR"
    fi
    
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_file="$BACKUP_DIR/nodogsplash.conf.backup_$timestamp"
    
    sudo cp "$NODOGSPLASH_CONFIG" "$backup_file"
}
```

**Syntax Breakdown:**

- **`[ ! -f "$NODOGSPLASH_CONFIG" ]`** - File test
  - **`[ ! ... ]`** - Negation (if NOT)
  - **`-f`** - Test if file exists and is a regular file
  - **`"$NODOGSPLASH_CONFIG"`** - Variable expansion with quotes

- **`[ ! -d "$BACKUP_DIR" ]`** - Directory test
  - **`-d`** - Test if directory exists

- **`sudo mkdir -p "$BACKUP_DIR"`** - Create directory
  - **`sudo`** - Run as administrator
  - **`mkdir`** - Make directory command
  - **`-p`** - Create parent directories if needed, don't error if exists

- **`date +%Y%m%d_%H%M%S`** - Date formatting
  - **`date`** - Current date/time command
  - **`+%Y%m%d_%H%M%S`** - Format string
    - **`%Y`** - 4-digit year
    - **`%m`** - Month (01-12)
    - **`%d`** - Day (01-31)
    - **`%H`** - Hour (00-23)
    - **`%M`** - Minute (00-59)
    - **`%S`** - Second (00-59)
  - **Output:** `20250115_143022` (example)

- **`sudo cp "$NODOGSPLASH_CONFIG" "$backup_file"`** - Copy file
  - **`cp`** - Copy command
  - **`"$NODOGSPLASH_CONFIG"`** - Source file
  - **`"$backup_file"`** - Destination file

### Function: add_device_to_blocklist()

```bash
add_device_to_blocklist() {
    local mac="$1"
    local portal_url="$2"
    
    if [ ! -f "$NODOGSPLASH_CONFIG" ]; then
        sudo tee "$NODOGSPLASH_CONFIG" > /dev/null <<EOF
# NoDogSplash Configuration
BlockList $mac
EOF
        return 0
    fi
    
    if sudo grep -qi "BlockList.*$mac" "$NODOGSPLASH_CONFIG"; then
        return 0
    fi
    
    echo "BlockList $mac" | sudo tee -a "$NODOGSPLASH_CONFIG" > /dev/null
}
```

**Syntax Breakdown:**

- **`sudo tee "$NODOGSPLASH_CONFIG" > /dev/null`** - Write to file with sudo
  - **`tee`** - Write to file and stdout
  - **`> /dev/null`** - Redirect stdout to null (discard output)
  - **Purpose:** `tee` can write to protected files, unlike `>` redirect

- **`<<EOF ... EOF`** - Here document (heredoc)
  - **`<<EOF`** - Start of heredoc (EOF is delimiter name)
  - **Content between** - Literal text to write
  - **`EOF`** - End delimiter
  - **Purpose:** Write multi-line text easily

- **`sudo grep -qi "BlockList.*$mac"`** - Search in file
  - **`grep`** - Search command
  - **`-q`** - Quiet (don't print, just return exit code)
  - **`-i`** - Case insensitive
  - **`"BlockList.*$mac"`** - Pattern (BlockList followed by anything then MAC)
    - **`.*`** - Regex: any characters (zero or more)

- **`echo "BlockList $mac" | sudo tee -a "$NODOGSPLASH_CONFIG"`** - Append to file
  - **`-a`** - Append mode (don't overwrite)
  - **`|`** - Pipe output to tee

### Function: restart_nodogsplash_service()

```bash
restart_nodogsplash_service() {
    if ! command -v systemctl > /dev/null 2>&1; then
        return 1
    fi
    
    if ! sudo systemctl list-unit-files | grep -q "$NODOGSPLASH_SERVICE.service"; then
        return 1
    fi
    
    sudo systemctl restart "$NODOGSPLASH_SERVICE"
    sleep 1
    
    if sudo systemctl is-active --quiet "$NODOGSPLASH_SERVICE"; then
        return 0
    else
        return 1
    fi
}
```

**Syntax Breakdown:**

- **`command -v systemctl`** - Check if command exists
  - **`command -v`** - Returns path to command if exists, nothing if not
  - **`> /dev/null 2>&1`** - Redirect both stdout and stderr to null
    - **`> /dev/null`** - Redirect stdout to null
    - **`2>&1`** - Redirect stderr (file descriptor 2) to stdout (file descriptor 1)

- **`sudo systemctl list-unit-files`** - List systemd services
  - **`systemctl`** - Systemd control command
  - **`list-unit-files`** - List all service unit files

- **`sudo systemctl restart "$NODOGSPLASH_SERVICE"`** - Restart service
  - **`restart`** - Restart service command

- **`sleep 1`** - Wait 1 second
  - **Purpose:** Give service time to fully restart

- **`sudo systemctl is-active --quiet`** - Check if service is running
  - **`is-active`** - Check if service is active/running
  - **`--quiet`** - Don't print output, just return exit code

### Main Script Execution

```bash
if [ $# -ne 2 ]; then
    echo "Usage: $0 <MAC_ADDRESS> <PORTAL_URL>" >&2
    exit 1
fi

MAC_ADDRESS="$1"
PORTAL_URL="$2"
```

**Syntax Breakdown:**

- **`$#`** - Special variable: number of arguments passed to script
- **`-ne`** - Not equal (numeric comparison)
- **`$0`** - Special variable: script name itself
- **`$1`, `$2`** - Positional parameters (first and second arguments)

**Execution Flow:**

```bash
# 1. Validate arguments
if [ $# -ne 2 ]; then ... fi

# 2. Validate MAC address
if ! validate_mac_address "$MAC_ADDRESS"; then
    exit 1
fi

# 3. Normalize MAC address
NORMALIZED_MAC=$(normalize_mac_address "$MAC_ADDRESS")

# 4. Backup config
backup_config_file

# 5. Add to blocklist
add_device_to_blocklist "$NORMALIZED_MAC" "$PORTAL_URL"

# 6. Restart service
restart_nodogsplash_service
```

---

## 4. Bash Script: allow_device_through.sh

### Purpose

Removes a device from NoDogSplash blocklist, allowing it to access internet normally.

### Key Function: remove_device_from_blocklist()

```bash
remove_device_from_blocklist() {
    local mac="$1"
    
    if [ ! -f "$NODOGSPLASH_CONFIG" ]; then
        return 0
    fi
    
    if ! sudo grep -qi "BlockList.*$mac" "$NODOGSPLASH_CONFIG"; then
        return 0
    fi
    
    sudo sed -i.bak "/BlockList.*$mac/d" "$NODOGSPLASH_CONFIG"
    sudo rm -f "$NODOGSPLASH_CONFIG.bak"
}
```

**Syntax Breakdown:**

- **`sudo sed -i.bak "/BlockList.*$mac/d"`** - Delete lines matching pattern
  - **`sed`** - Stream editor
  - **`-i.bak`** - In-place edit, create backup with `.bak` extension
  - **`/BlockList.*$mac/d`** - Pattern to match, then delete (`d` command)
  - **Purpose:** Remove the BlockList line containing the MAC address

- **`sudo rm -f "$NODOGSPLASH_CONFIG.bak"`** - Remove backup file
  - **`rm`** - Remove command
  - **`-f`** - Force (don't error if file doesn't exist)

---

## 5. Bash Script: check_device_redirected.sh

### Purpose

Checks if a device is currently in the NoDogSplash blocklist (being redirected).

### Key Function: check_device_in_blocklist()

```bash
check_device_in_blocklist() {
    local mac="$1"
    
    if [ ! -f "$NODOGSPLASH_CONFIG" ]; then
        echo "not_redirected" >&2
        return 1
    fi
    
    if sudo grep -qiE "BlockList[[:space:]]+$mac" "$NODOGSPLASH_CONFIG"; then
        echo "redirected"
        return 0
    else
        echo "not_redirected"
        return 1
    fi
}
```

**Syntax Breakdown:**

- **`[[:space:]]+`** - Character class in regex
  - **`[[:space:]]`** - Matches any whitespace character (space, tab)
  - **`+`** - One or more occurrences
  - **Purpose:** Match "BlockList" followed by one or more spaces, then MAC

**Exit Code Logic:**

- **Exit code 0** = Device IS redirected (found in blocklist)
- **Exit code 1** = Device is NOT redirected (not in blocklist)

**Why this convention?**
- Unix convention: Exit code 0 = success/true, non-zero = failure/false
- In this context:
  - Exit 0 = "Yes, check succeeded and device IS redirected" (positive result)
  - Exit 1 = "No, device is NOT redirected" (successful check, negative result)

**In PHP:**
```php
$result = $scriptExecutor->execute('check_device_redirected.sh', [$mac]);
$isRedirected = $result['success'];  // true if exit code 0, false if exit code 1
```

---

## 6. NoDogSplash Configuration File Format

### Location

`/etc/nodogsplash/nodogsplash.conf`

### Format

```
# NoDogSplash Configuration
# Auto-generated by Laravel Parental WiFi System

# BlockList: Devices that should be redirected to portal
BlockList AA:BB:CC:DD:EE:FF
BlockList 11:22:33:44:55:66
```

### Syntax

- **`# Comment`** - Comment line (ignored by NoDogSplash)
- **`BlockList MAC_ADDRESS`** - Add MAC address to blocklist
  - Each device on its own line
  - MAC address in format: `XX:XX:XX:XX:XX:XX`

### How NoDogSplash Uses It

1. NoDogSplash reads the config file at startup
2. Any device with MAC address in BlockList is intercepted
3. All HTTP requests from blocked device redirect to portal
4. Device cannot access internet until removed from BlockList

---

## 7. Complete Flow Examples

### Flow 1: Device Time Expires → Redirect to Portal

```
1. CheckTimeExpiration Job runs (every 2 minutes)
   ↓
2. Detects device time has expired
   ↓
3. Calls: NoDogSplashService::redirectDeviceToPortal($device)
   ↓
4. Service calls: ScriptExecutor::execute('redirect_device_portal.sh', [mac, url])
   ↓
5. Script validates MAC address
   ↓
6. Script backs up config file
   ↓
7. Script adds "BlockList AA:BB:CC:DD:EE:FF" to config file
   ↓
8. Script restarts nodogsplash service
   ↓
9. Device's next HTTP request → Redirected to portal
```

### Flow 2: Child Completes Quiz → Allow Device Through

```
1. Child completes quiz successfully
   ↓
2. TimeGrantingService grants time to device
   ↓
3. Calls: NoDogSplashService::allowDeviceThrough($device)
   ↓
4. Service calls: ScriptExecutor::execute('allow_device_through.sh', [mac])
   ↓
5. Script validates MAC address
   ↓
6. Script backs up config file
   ↓
7. Script removes "BlockList AA:BB:CC:DD:EE:FF" from config file
   ↓
8. Script restarts nodogsplash service
   ↓
9. Device can now access internet normally
```

### Flow 3: Check if Device is Redirected

```
1. Portal needs to check device status
   ↓
2. Calls: NoDogSplashService::isDeviceRedirected($device)
   ↓
3. Service calls: ScriptExecutor::execute('check_device_redirected.sh', [mac])
   ↓
4. Script reads config file
   ↓
5. Script searches for MAC address in BlockList entries
   ↓
6. Script returns exit code:
   - 0 if found (device is redirected)
   - 1 if not found (device is not redirected)
   ↓
7. PHP interprets exit code:
   - Exit 0 → $result['success'] = true → return true
   - Exit 1 → $result['success'] = false → return false
```

---

## 8. Security Considerations

### 1. Script Whitelisting

Only scripts in `ScriptExecutor::$allowedScripts` can be executed. This prevents arbitrary command execution.

### 2. Path Validation

ScriptExecutor validates:
- Script exists in `scripts/` directory
- No path traversal attempts (`../`)
- Script is executable
- Resolved path is still in scripts directory

### 3. Argument Sanitization

All arguments are escaped using `escapeshellarg()`:
```php
$escapedArgs = array_map(function ($arg) {
    return escapeshellarg($arg);
}, $args);
```

### 4. MAC Address Validation

Scripts validate MAC address format using regex:
- Format: `XX:XX:XX:XX:XX:XX` or `XX-XX-XX-XX-XX-XX`
- Only hexadecimal characters allowed
- Exactly 12 hex digits (6 groups of 2)

### 5. Sudo Privileges

Scripts require sudo for:
- Reading/writing `/etc/nodogsplash/nodogsplash.conf`
- Restarting `nodogsplash` service

**Sudoers Configuration:**
```
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/redirect_device_portal.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/allow_device_through.sh
www-data ALL=(ALL) NOPASSWD: /var/www/parental_wifi/scripts/check_device_redirected.sh
```

---

## 9. Error Handling

### Script Level

- **`set -e`** - Exit on any command failure
- **`set -u`** - Exit on undefined variable
- Validate inputs before processing
- Return appropriate exit codes

### Service Level

- Check script execution result
- Log errors but don't crash
- Return boolean success/failure
- Allow system to continue functioning even if redirect fails

### Example Error Handling

```php
$result = $this->scriptExecutor->execute('redirect_device_portal.sh', [$mac, $url]);

if ($result['success']) {
    Log::info('Redirect successful');
    return true;
} else {
    Log::error('Redirect failed', [
        'error' => $result['error'],
        'output' => $result['output'],
    ]);
    return false;  // Don't throw exception, just return false
}
```

---

## 10. Testing Considerations

### Manual Testing

1. **Test redirect:**
   ```bash
   sudo ./scripts/redirect_device_portal.sh AA:BB:CC:DD:EE:FF "http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF"
   ```

2. **Check config:**
   ```bash
   sudo cat /etc/nodogsplash/nodogsplash.conf
   ```

3. **Test check:**
   ```bash
   sudo ./scripts/check_device_redirected.sh AA:BB:CC:DD:EE:FF
   echo $?  # Should be 0 if redirected, 1 if not
   ```

4. **Test allow through:**
   ```bash
   sudo ./scripts/allow_device_through.sh AA:BB:CC:DD:EE:FF
   ```

### Integration Testing

1. Create a test device in database
2. Call `redirectDeviceToPortal()` via service
3. Verify device is in config file
4. Call `isDeviceRedirected()` - should return true
5. Call `allowDeviceThrough()` - should return true
6. Verify device is removed from config file
7. Call `isDeviceRedirected()` - should return false

---

## 11. Troubleshooting

### Issue: Script execution fails

**Check:**
1. Script is in `scripts/` directory
2. Script is executable: `chmod +x scripts/*.sh`
3. Script is in ScriptExecutor whitelist
4. PHP user (www-data) has execute permission

### Issue: Config file modification fails

**Check:**
1. Sudoers configuration is correct
2. Config file exists: `/etc/nodogsplash/nodogsplash.conf`
3. Config file is readable/writable with sudo
4. NoDogSplash service is installed

### Issue: Service restart fails

**Check:**
1. NoDogSplash service is installed: `systemctl list-unit-files | grep nodogsplash`
2. Service name is correct: `nodogsplash` (not `nodogsplashd`)
3. PHP user has sudo permission to restart service

### Issue: Device not being redirected

**Check:**
1. Device is in config file: `sudo grep "BlockList.*MAC" /etc/nodogsplash/nodogsplash.conf`
2. NoDogSplash service is running: `sudo systemctl status nodogsplash`
3. NoDogSplash is intercepting on correct interface (wlan0)

---

## 12. Summary

### Key Concepts

1. **Separation of Concerns:**
   - PHP handles business logic
   - Bash scripts handle system operations
   - ScriptExecutor provides security layer

2. **Security:**
   - Whitelist approach (only approved scripts)
   - Input validation and sanitization
   - Sudo for privileged operations

3. **Error Handling:**
   - Scripts fail fast with exit codes
   - Services log errors but don't crash
   - System continues functioning even if redirect fails

4. **Idempotency:**
   - Scripts can be run multiple times safely
   - Check before adding/removing (no duplicates)

### File Responsibilities

| File | Responsibility |
|------|---------------|
| `NoDogSplashService.php` | High-level API, coordinates operations |
| `ScriptExecutor.php` | Secure script execution wrapper |
| `redirect_device_portal.sh` | Add device to blocklist |
| `allow_device_through.sh` | Remove device from blocklist |
| `check_device_redirected.sh` | Check if device is in blocklist |

### Integration Points

- **CheckTimeExpiration Job** → Calls `redirectDeviceToPortal()`
- **TimeGrantingService** → Calls `allowDeviceThrough()`
- **Portal Pages** → Call `isDeviceRedirected()` to check status

---

## Conclusion

The NoDogSplash integration follows a secure, maintainable architecture that separates concerns between PHP business logic and bash system operations. All operations are validated, logged, and handled gracefully to ensure the system continues functioning even if individual operations fail.

