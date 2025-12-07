# NoDogSplash Functionality in Parental WiFi System

## Overview

NoDogSplash is a captive portal solution that intercepts HTTP requests from devices on the WiFi network and redirects them to a custom portal page. It acts as a "toll booth" that stops devices and shows them the portal before allowing internet access.

## Core Function

NoDogSplash manages device authentication states to control when devices see the portal versus when they can access the internet normally.

### Device States

- **Preauthenticated**: Device is redirected to portal on all HTTP requests
- **Authenticated**: Device can access internet normally

## Implementation

### Service: `NoDogSplashService`

Located at `app/Services/NoDogSplashService.php`, this service provides three main methods:

#### 1. `redirectDeviceToPortal(Device $device)`

**Purpose**: Redirects a device to the portal when time expires.

**Logic**:
1. Gets device MAC address
2. Builds portal URL: `http://192.168.4.1/portal?mac=XX:XX:XX:XX:XX:XX`
3. Executes `redirect_device_portal.sh` script via ScriptExecutor
4. Script finds device token using `ndsctl clients`
5. Script deauthenticates device using `ndsctl deauth <token>`
6. Device enters Preauthenticated state
7. NoDogSplash intercepts HTTP requests and redirects to portal

**When Called**:
- When device time expires (via `CheckTimeExpiration` job)
- When parent manually blocks device
- When device violates rules

#### 2. `allowDeviceThrough(Device $device)`

**Purpose**: Allows device to access internet after completing quiz/video.

**Logic**:
1. Gets device MAC address
2. Executes `allow_device_through.sh` script via ScriptExecutor
3. Script finds device token using `ndsctl clients`
4. Script authenticates device using `ndsctl auth <token>`
5. Device enters Authenticated state
6. Device can access internet normally

**When Called**:
- After child completes quiz/video (via `TimeGrantingService`)
- When parent manually unblocks device
- When time is granted for any reason

#### 3. `isDeviceRedirected(Device $device)`

**Purpose**: Checks if device is currently being redirected to portal.

**Logic**:
1. Gets device MAC address
2. Executes `check_device_redirected.sh` script
3. Script checks NoDogSplash client list for device state
4. Returns `true` if device is Preauthenticated, `false` if Authenticated

## Technical Details

### NoDogSplash Control Commands (`ndsctl`)

The system uses `ndsctl` commands to manage device authentication:

- **`ndsctl clients`**: Lists all connected devices with their tokens, MAC addresses, and states
- **`ndsctl deauth <token>`**: Deauthenticates device (puts in Preauthenticated state)
- **`ndsctl auth <token>`**: Authenticates device (puts in Authenticated state)

### Scripts

All operations are executed through secure bash scripts:

- **`redirect_device_portal.sh`**: Handles device deauthentication
  - Validates MAC address format
  - Finds device token from client list
  - Executes `ndsctl deauth` to redirect device

- **`allow_device_through.sh`**: Handles device authentication
  - Validates MAC address format
  - Finds device token from client list
  - Executes `ndsctl auth` to allow device through

- **`check_device_redirected.sh`**: Checks device redirect status
  - Queries NoDogSplash client list
  - Returns device authentication state

### Security

- All scripts executed via `ScriptExecutor` service
- Scripts are whitelisted and validated before execution
- Arguments are sanitized to prevent command injection
- Scripts require sudo privileges (configured via `/etc/sudoers.d/parental-wifi-scripts`)

## Integration Points

### Time Expiration Flow

1. `CheckTimeExpiration` job detects expired devices
2. Calls `NoDogSplashService::redirectDeviceToPortal()`
3. Device is deauthenticated and redirected to portal
4. Child sees portal page instead of internet

### Time Granting Flow

1. Child completes quiz/video
2. `TimeGrantingService` grants time
3. Calls `NoDogSplashService::allowDeviceThrough()`
4. Device is authenticated and can access internet

## Limitations

- **HTTPS Not Intercepted**: NoDogSplash only intercepts HTTP requests. HTTPS requests bypass the portal (acceptable limitation for this use case).
- **Device Must Be Connected**: Device must be connected to WiFi and appear in NoDogSplash client list for operations to work.

## Configuration

- Portal URL configured in `config/portal.php` (default: `http://192.168.4.1`)
- NoDogSplash configuration files typically located at `/etc/nodogsplash/`
- Service uses WiFi AP IP address (192.168.4.1) for portal redirects

