# Portal Core Implementation - Complete Guide

## Overview

This document explains how the **Portal Core System** works. This is the heart of the captive portal - it automatically detects when a child's internet time expires, blocks them from the internet, redirects them to the portal, and unblocks them after they complete quizzes or videos.

**What is a Captive Portal?**
Think of a captive portal like a "toll booth" on the internet highway. When a child's time runs out, they're stopped at the toll booth (portal page) and must complete an activity (quiz or video) to earn more time and continue browsing.

## Table of Contents

1. [System Architecture](#system-architecture)
2. [Key Components](#key-components)
3. [How Time Expiration Works](#how-time-expiration-works)
4. [How Device Blocking Works](#how-device-blocking-works)
5. [How Portal Redirects Work](#how-portal-redirects-work)
6. [How Device Unblocking Works](#how-device-unblocking-works)
7. [Complete Flow Diagrams](#complete-flow-diagrams)
8. [File Reference](#file-reference)

---

## System Architecture

### The Three-Layer System

The portal core uses a **three-layer blocking system** to control device access:

```
┌─────────────────────────────────────────────────────────┐
│ Layer 1: Database Status                                 │
│ - Tracks device state: 'active' or 'blocked'            │
│ - Source of truth for our application                    │
│ - Updated first when blocking/unblocking                 │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ Layer 2: Network Blocking (iptables/firewall)           │
│ - Physically prevents device from accessing internet     │
│ - Uses firewall rules to block device's MAC address      │
│ - Like a security guard stopping someone at the gate     │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ Layer 3: Portal Redirect (NoDogSplash)                 │
│ - Intercepts HTTP requests and redirects to portal       │
│ - Even if device bypasses firewall, they see portal      │
│ - Like a detour sign redirecting traffic                 │
└─────────────────────────────────────────────────────────┘
```

**Why Three Layers?**
- **Layer 1 (Database)**: Tracks state for our application (UI, reports, etc.)
- **Layer 2 (Network)**: Physically blocks internet access (security)
- **Layer 3 (Redirect)**: Ensures device sees portal page (user experience)

All three must work together for complete control.

---

## Key Components

### 1. CheckTimeExpiration Job
**File:** `app/Jobs/CheckTimeExpiration.php`

**What it does:**
- Runs automatically every 2 minutes in the background
- Checks all devices to find whose time has expired
- Automatically blocks and redirects expired devices

**Think of it as:** A security guard that patrols every 2 minutes, checking if anyone's time has run out and needs to be stopped.

**Key Methods:**
- `handle()` - Main method that runs when job executes

### 2. NetworkService
**File:** `app/Services/NetworkService.php`

**What it does:**
- Handles network-level blocking/unblocking using firewall (iptables)
- Blocks device's MAC address at the network level
- Prevents device from physically accessing internet

**Think of it as:** The security guard who actually stops people at the gate.

**Key Methods:**
- `blockDevice($device)` - Blocks device at network level
- `unblockDevice($device)` - Unblocks device at network level
- `isDeviceBlocked($device)` - Checks if device is blocked

**Current Status:** Stub implementation (logs operations, doesn't actually block yet)
- Will be fully implemented in TODO #12 (Shell Scripts)

### 3. NoDogSplashService
**File:** `app/Services/NoDogSplashService.php`

**What it does:**
- Handles captive portal redirects using NoDogSplash
- Manages device authentication state using `ndsctl` commands
- Ensures device sees portal instead of requested websites

**Think of it as:** The detour sign that redirects traffic to the portal.

**Key Methods:**
- `redirectDeviceToPortal($device)` - Redirects device to portal by deauthenticating it
- `allowDeviceThrough($device)` - Allows device through by authenticating it
- `isDeviceRedirected($device)` - Checks if device is redirected (Preauthenticated state)

**Current Status:** ✅ **Fully Implemented**
- Uses `ndsctl deauth` to put devices in Preauthenticated state (redirected)
- Uses `ndsctl auth` to put devices in Authenticated state (allowed through)
- Queries `ndsctl clients` to check device state
- Integrates with ScriptExecutor for secure script execution
- See `docs/NODOGSPLASH_SETUP.md` for complete setup details

### 4. TimeGrantingService (Updated)
**File:** `app/Services/TimeGrantingService.php`

**What it does:**
- Grants time to devices after quiz/video completion
- Unblocks devices at all three layers (database, network, portal)
- Ensures device can access internet after earning time

**Key Methods:**
- `grantTimeFromQuiz($device, $quizAttempt)` - Grants time after quiz
- `grantTimeFromVideo($device, $videoCompletion)` - Grants time after video
- `unblockDevice($device)` - Unblocks device at all three layers

---

## How Time Expiration Works

### Step-by-Step Process

```
1. Device Uses Internet
   └─> Time is deducted from remaining_time_minutes
   └─> TimeTrackingService tracks active sessions

2. Time Reaches Zero
   └─> remaining_time_minutes = 0 (or negative)
   └─> Device has used all allocated time

3. CheckTimeExpiration Job Runs (every 2 minutes)
   └─> Calls TimeTrackingService::getExpiredDevices()
   └─> Finds all devices with time <= 0

4. For Each Expired Device:
   └─> Update database status: 'active' → 'blocked'
   └─> Call NetworkService::blockDevice() (Layer 2)
   └─> Call NoDogSplashService::redirectDeviceToPortal() (Layer 3)
   └─> Log all operations

5. Device is Now Blocked
   └─> Cannot access internet (network blocking)
   └─> All HTTP requests redirect to portal (redirect)
   └─> Child sees portal page with quiz/video options
```

### Code Flow

```php
// 1. CheckTimeExpiration job runs automatically (every 2 minutes)
CheckTimeExpiration::handle()
    ↓
// 2. Find expired devices
TimeTrackingService::getExpiredDevices()
    ↓
// 3. For each expired device:
foreach ($expiredDevices as $device) {
    // 3a. Update database status
    $device->update(['status' => 'blocked']);
    
    // 3b. Block at network level
    NetworkService::blockDevice($device);
    
    // 3c. Redirect to portal
    NoDogSplashService::redirectDeviceToPortal($device);
}
```

### Example Scenario

**Timeline:**
- 10:00 AM - Child starts browsing (30 minutes allocated)
- 10:25 AM - Child has used 25 minutes (5 minutes remaining)
- 10:30 AM - Child has used all 30 minutes (0 minutes remaining)
- 10:30 AM - CheckTimeExpiration job runs, detects expired device
- 10:30 AM - Device is blocked and redirected to portal
- 10:30 AM - Child tries to visit google.com → sees portal page instead

---

## How Device Blocking Works

### The Blocking Process

When a device's time expires, it's blocked at three levels:

#### Layer 1: Database Status
```php
// Update device status in database
$device->update(['status' => 'blocked']);
```
- Changes status from 'active' to 'blocked'
- Other parts of system check this status
- UI shows device as blocked

#### Layer 2: Network Blocking (iptables)
```php
// Block device at network level
NetworkService::blockDevice($device);
```
**What happens (future implementation):**
- Executes iptables command: `iptables -A INPUT -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP`
- Adds firewall rule that blocks device's MAC address
- Device cannot physically access internet (all packets dropped)

**Current implementation:**
- Only updates database status (already done in Layer 1)
- Logs the operation
- Actual iptables blocking will be added in TODO #12

#### Layer 3: Portal Redirect (NoDogSplash)
```php
// Redirect device to portal
NoDogSplashService::redirectDeviceToPortal($device);
```
**What happens:**
1. Service finds device's token using `ndsctl clients`
2. Service calls `redirect_device_portal.sh` script via ScriptExecutor
3. Script executes `ndsctl deauth <token>` to put device in Preauthenticated state
4. NoDogSplash intercepts all HTTP requests from Preauthenticated devices
5. NoDogSplash redirects to `RedirectURL` (configured in `/etc/nodogsplash/nodogsplash.conf`)
6. Device sees portal page instead of requested website

**Implementation details:**
- Uses `ndsctl` (NoDogSplash control command) to manage device states
- Portal URL uses gateway IP (`192.168.4.1`) from config
- Firewall rule allows Preauthenticated users to access portal (prevents redirect loop)
- See `docs/NODOGSPLASH_INTEGRATION.md` for technical details

### Why Three Layers?

1. **Database Layer**: Tracks state for application logic
2. **Network Layer**: Physical security (can't bypass)
3. **Redirect Layer**: User experience (always see portal)

If one layer fails, the others still work.

---

## How Portal Redirects Work

### What is a Captive Portal Redirect?

When a device tries to visit any website, NoDogSplash intercepts the request and redirects it to the portal page instead.

**Example:**
```
Child tries to visit: google.com
                    ↓
NoDogSplash intercepts request
                    ↓
Redirects to: /portal?mac=AA:BB:CC:DD:EE:FF
                    ↓
Child sees portal page (quiz/video selection)
```

### The Redirect Process

#### When Time Expires:
```php
NoDogSplashService::redirectDeviceToPortal($device);
```

**Implementation:**
1. Service builds portal URL: `http://192.168.4.1/portal?mac=AA:BB:CC:DD:EE:FF`
2. Service calls `redirect_device_portal.sh` script via ScriptExecutor
3. Script finds device token using `ndsctl clients`
4. Script executes `ndsctl deauth <token>` to put device in Preauthenticated state
5. Device's next HTTP request → NoDogSplash intercepts → Redirects to `RedirectURL`
6. Device sees splash page → Splash page redirects to `/portal?tok=TOKEN`
7. PortalController looks up MAC from token → Shows portal page

#### When Time is Granted:
```php
NoDogSplashService::allowDeviceThrough($device);
```

**Implementation:**
1. Service calls `allow_device_through.sh` script via ScriptExecutor
2. Script finds device token using `ndsctl clients`
3. Script executes `ndsctl auth <token>` to put device in Authenticated state
4. Device can now access internet normally (no redirect)

### Current Status

- ✅ **Fully implemented**: Uses `ndsctl` commands to manage device authentication state
- ✅ **Scripts working**: All three NoDogSplash scripts are functional
- ✅ **Integration complete**: Works with ScriptExecutor and Laravel services
- ✅ **Configuration documented**: See `docs/NODOGSPLASH_SETUP.md` for setup

---

## How Device Unblocking Works

### The Unblocking Process

After a child completes a quiz or video and earns time, the device must be unblocked at all three layers.

#### Step-by-Step Unblocking

```
1. Child Completes Quiz/Video
   └─> PortalController::submitQuiz() or submitVideoWords()
   └─> Validates answers/words
   └─> Calculates score

2. Time is Granted
   └─> TimeGrantingService::grantTimeFromQuiz() or grantTimeFromVideo()
   └─> Adds time to device's remaining_time_minutes
   └─> Creates DeviceTimeGrant record

3. Device is Unblocked (if was blocked)
   └─> TimeGrantingService::unblockDevice()
   └─> Unblocks at all three layers:
       ├─> Layer 1: Database status 'blocked' → 'active'
       ├─> Layer 2: NetworkService::unblockDevice() (remove iptables rule)
       └─> Layer 3: NoDogSplashService::allowDeviceThrough() (remove redirect)

4. Device Can Browse Again
   └─> Status is 'active' in database
   └─> Network blocking removed (if implemented)
   └─> Portal redirect removed (if implemented)
   └─> Child can access internet with newly granted time
```

### Code Flow

```php
// 1. Child completes quiz/video
PortalController::submitQuiz() or submitVideoWords()
    ↓
// 2. Time is granted
TimeGrantingService::grantTimeFromQuiz() or grantTimeFromVideo()
    ↓
// 3. Check if device should be unblocked
if ($this->shouldUnblockDevice($device)) {
    // 4. Unblock at all three layers
    $this->unblockDevice($device);
        ↓
    // 4a. Update database status
    $device->update(['status' => 'active']);
    
    // 4b. Unblock at network level
    NetworkService::unblockDevice($device);
    
    // 4c. Remove portal redirect
    NoDogSplashService::allowDeviceThrough($device);
}
```

### When Unblocking Happens

Unblocking only happens if:
1. Device status is 'blocked' (was blocked due to expired time)
2. Device now has remaining_time_minutes > 0 (time was just granted)

If device was already 'active', no unblocking is needed.

---

## Complete Flow Diagrams

### Flow 1: Time Expiration → Blocking → Redirect

```
┌─────────────────────────────────────────────────────────────┐
│ Device Uses Internet                                        │
│ - Time deducted: 30 min → 25 min → 20 min → ... → 0 min    │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ CheckTimeExpiration Job Runs (every 2 minutes)             │
│ - Checks all devices for expired time                       │
│ - Finds device with remaining_time_minutes = 0              │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Block Device at All Three Layers                            │
│                                                              │
│ Layer 1: Database                                           │
│   $device->update(['status' => 'blocked'])                  │
│                                                              │
│ Layer 2: Network (iptables)                                 │
│   NetworkService::blockDevice($device)                      │
│   → Adds firewall rule blocking MAC address                │
│                                                              │
│ Layer 3: Portal Redirect (NoDogSplash)                     │
│   NoDogSplashService::redirectDeviceToPortal($device)       │
│   → Configures redirect to /portal?mac=XX:XX:XX:XX:XX:XX   │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Device is Blocked                                           │
│ - Cannot access internet (network blocking)                 │
│ - All HTTP requests redirect to portal (redirect)          │
│ - Child sees portal page with quiz/video options            │
└─────────────────────────────────────────────────────────────┘
```

### Flow 2: Quiz/Video Completion → Time Grant → Unblocking

```
┌─────────────────────────────────────────────────────────────┐
│ Child Completes Quiz/Video                                  │
│ - Answers questions or enters dictionary words              │
│ - Submits form                                              │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Validate and Score                                          │
│ - Check answers/words                                       │
│ - Calculate score                                            │
│ - Determine if passed (score >= passing_score)             │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Grant Time (if passed)                                      │
│ TimeGrantingService::grantTimeFromQuiz() or                 │
│ TimeGrantingService::grantTimeFromVideo()                    │
│                                                              │
│ - Add time to remaining_time_minutes                         │
│ - Create DeviceTimeGrant record                              │
│ - Example: 0 min → 15 min (if quiz rewards 15 minutes)      │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Unblock Device at All Three Layers                          │
│                                                              │
│ Layer 1: Database                                           │
│   $device->update(['status' => 'active'])                   │
│                                                              │
│ Layer 2: Network (iptables)                                 │
│   NetworkService::unblockDevice($device)                    │
│   → Removes firewall rule blocking MAC address             │
│                                                              │
│ Layer 3: Portal Redirect (NoDogSplash)                     │
│   NoDogSplashService::allowDeviceThrough($device)            │
│   → Removes redirect configuration                           │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Device Can Browse Again                                     │
│ - Status is 'active' in database                             │
│ - Network blocking removed                                  │
│ - Portal redirect removed                                   │
│ - Child can access internet with newly granted time         │
└─────────────────────────────────────────────────────────────┘
```

### Flow 3: Complete End-to-End Cycle

```
┌─────────────────────────────────────────────────────────────┐
│ START: Device Has Time                                      │
│ - Status: 'active'                                           │
│ - remaining_time_minutes: 30                                │
│ - Can browse internet                                       │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Device Uses Internet                                         │
│ - Time deducted: 30 → 25 → 20 → ... → 0                     │
│ - Status remains 'active'                                   │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Time Expires                                                │
│ - remaining_time_minutes: 0                                 │
│ - CheckTimeExpiration job detects expiration                │
│ - Device blocked and redirected                            │
│ - Status: 'blocked'                                         │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Child Sees Portal                                            │
│ - All HTTP requests redirect to portal                      │
│ - Child sees quiz/video options                             │
│ - Child selects activity                                    │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ Child Completes Activity                                     │
│ - Takes quiz or watches video                               │
│ - Passes validation                                         │
│ - Time is granted: 0 → 15 min                               │
│ - Device is unblocked                                       │
│ - Status: 'active'                                          │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│ BACK TO START: Device Has Time Again                        │
│ - Status: 'active'                                           │
│ - remaining_time_minutes: 15                                │
│ - Can browse internet again                                 │
└─────────────────────────────────────────────────────────────┘
```

---

## File Reference

### 1. CheckTimeExpiration Job
**File:** `app/Jobs/CheckTimeExpiration.php`

**Purpose:** Automatically checks for expired devices and blocks/redirects them.

**Key Code:**
```php
public function handle(
    TimeTrackingService $timeTrackingService,
    NetworkService $networkService,
    NoDogSplashService $noDogSplashService
): void {
    // Find expired devices
    $expiredDevices = $timeTrackingService->getExpiredDevices();
    
    // Process each expired device
    foreach ($expiredDevices as $device) {
        // Update database status
        $device->update(['status' => 'blocked']);
        
        // Block at network level
        $networkService->blockDevice($device);
        
        // Redirect to portal
        $noDogSplashService->redirectDeviceToPortal($device);
    }
}
```

**Scheduled:** Every 2 minutes via `routes/console.php`

---

### 2. NetworkService
**File:** `app/Services/NetworkService.php`

**Purpose:** Handles network-level blocking/unblocking using firewall (iptables).

**Key Methods:**

#### `blockDevice(Device $device): bool`
- Blocks device at network level
- Updates database status to 'blocked'
- Logs operation
- **Future:** Will execute iptables command to block MAC address

#### `unblockDevice(Device $device): bool`
- Unblocks device at network level
- Updates database status to 'active'
- Logs operation
- **Future:** Will remove iptables rule blocking MAC address

#### `isDeviceBlocked(Device $device): bool`
- Checks if device is blocked at network level
- **Current:** Checks database status
- **Future:** Will check actual iptables rules

**Current Status:** Stub (database updates + logging only)
**Full Implementation:** TODO #12 (Shell Scripts)

---

### 3. NoDogSplashService
**File:** `app/Services/NoDogSplashService.php`

**Purpose:** Handles captive portal redirects using NoDogSplash.

**Key Methods:**

#### `redirectDeviceToPortal(Device $device): bool`
- Configures NoDogSplash to redirect device to portal
- Logs operation
- **Future:** Will modify NoDogSplash config file and restart service

#### `allowDeviceThrough(Device $device): bool`
- Removes portal redirect, allows device internet access
- Logs operation
- **Future:** Will remove redirect rule from NoDogSplash config

#### `isDeviceRedirected(Device $device): bool`
- Checks if device is currently redirected
- **Current:** Checks database status
- **Future:** Will check actual NoDogSplash config file

**Current Status:** Stub (logging only)
**Full Implementation:** TODO #15 (NoDogSplash Integration)

---

### 4. TimeGrantingService (Updated)
**File:** `app/Services/TimeGrantingService.php`

**Purpose:** Grants time to devices and unblocks them after quiz/video completion.

**Key Method:**

#### `unblockDevice(Device $device): void`
- Unblocks device at all three layers
- Updates database status: 'blocked' → 'active'
- Calls NetworkService::unblockDevice() (Layer 2)
- Calls NoDogSplashService::allowDeviceThrough() (Layer 3)
- Handles errors gracefully (continues even if one layer fails)

**Code:**
```php
protected function unblockDevice(Device $device): void
{
    // Layer 1: Database
    $device->update(['status' => 'active']);
    
    // Layer 2: Network
    try {
        $this->networkService->unblockDevice($device);
    } catch (\Exception $e) {
        Log::error('Network unblocking failed', [...]);
    }
    
    // Layer 3: Portal Redirect
    try {
        $this->noDogSplashService->allowDeviceThrough($device);
    } catch (\Exception $e) {
        Log::error('Redirect removal failed', [...]);
    }
}
```

---

### 5. Scheduler Registration
**File:** `routes/console.php`

**Purpose:** Registers CheckTimeExpiration job to run automatically.

**Code:**
```php
Schedule::job(new CheckTimeExpiration)
    ->everyTwoMinutes()      // Run every 2 minutes
    ->name('check-time-expiration')  // Name for logging
    ->withoutOverlapping()    // Prevent multiple instances
    ->runInBackground();      // Run in background (non-blocking)
```

**How to Run:**
- Add to crontab: `* * * * * cd /path-to-project && php artisan schedule:run`
- Or test manually: `php artisan schedule:test`

---

## Key Concepts Explained

### What is a Background Job?

A background job is code that runs automatically without user interaction. Think of it like a "robot assistant" that works in the background.

**Example:**
- CheckTimeExpiration job runs every 2 minutes
- It checks for expired devices automatically
- No one needs to click a button - it just works

### What is MAC Address?

MAC address is like a "fingerprint" for each device. Every device has a unique MAC address.

**Example:**
- Device 1: `AA:BB:CC:DD:EE:FF`
- Device 2: `11:22:33:44:55:66`

We use MAC address to identify which device to block/unblock.

### What is iptables?

iptables is a firewall tool in Linux. It controls which devices can access the internet.

**How it works:**
- Add rule: Block MAC address `AA:BB:CC:DD:EE:FF` → Device can't access internet
- Remove rule: Allow MAC address `AA:BB:CC:DD:EE:FF` → Device can access internet

### What is NoDogSplash?

NoDogSplash is a captive portal solution. It intercepts HTTP requests and redirects them to a custom page.

**How it works:**
- Device tries to visit `google.com`
- NoDogSplash intercepts the request
- Redirects to `/portal?mac=AA:BB:CC:DD:EE:FF` instead
- Device sees portal page, not google.com

---

## Testing the System

### Manual Testing

1. **Test Time Expiration:**
   ```bash
   # Set device time to 0
   php artisan tinker
   >>> $device = Device::find(1);
   >>> $device->update(['remaining_time_minutes' => 0]);
   
   # Run CheckTimeExpiration job manually
   >>> dispatch(new \App\Jobs\CheckTimeExpiration);
   
   # Check logs
   tail -f storage/logs/laravel.log
   ```

2. **Test Time Granting:**
   ```bash
   # Complete a quiz/video via portal
   # Check that device is unblocked
   # Verify logs show unblocking operations
   ```

3. **Test Scheduler:**
   ```bash
   # Test scheduler manually
   php artisan schedule:test
   
   # Or run scheduler once
   php artisan schedule:run
   ```

### What to Look For

- **Logs:** Check `storage/logs/laravel.log` for:
  - "CheckTimeExpiration job started"
  - "Expired device processed successfully"
  - "Device unblocked after time grant"
  
- **Database:** Check `devices` table:
  - Status changes: 'active' → 'blocked' → 'active'
  - `remaining_time_minutes` updates correctly

---

## Current Limitations

### What Works Now

✅ Time expiration detection  
✅ Database status updates  
✅ Automatic job scheduling  
✅ Time granting after quiz/video  
✅ Complete unblocking flow (all three layers)  
✅ Logging and error handling  
✅ **NoDogSplash redirects (fully implemented)**
  - Device redirection to portal using `ndsctl deauth`
  - Device authentication using `ndsctl auth`
  - State checking using `ndsctl clients`
  - Token-based MAC address lookup in portal

### What's Stub (Not Fully Implemented)

⚠️ **Network-level blocking (iptables)**
- Currently: Only updates database, logs operation
- Future: Will actually block device using iptables
- Implementation: TODO #12 (Shell Scripts)

### Implementation Status

**NoDogSplash Integration (Layer 3):** ✅ **Complete**
- Fully implemented using `ndsctl` commands
- All scripts working and tested
- Portal redirects functioning correctly
- See `docs/NODOGSPLASH_SETUP.md` for setup details

**Network Blocking (Layer 2):** ⚠️ **Pending**
- Currently: Only updates database, logs operation
- Future: Will actually block device using iptables
- Implementation: TODO #12 (Shell Scripts)

---

## Next Steps

1. **Test Phase 6 (TODO #11)**
   - Test complete workflow on Raspberry Pi
   - Verify time expiration → blocking → redirect → unblocking

2. **Shell Scripts (TODO #12)**
   - Implement actual iptables blocking
   - Complete NetworkService implementation

3. **NoDogSplash Integration (TODO #15)** ✅ **COMPLETE**
   - ✅ Implemented redirect configuration using `ndsctl`
   - ✅ Completed NoDogSplashService implementation
   - ✅ All scripts working and tested
   - ✅ Documentation complete

---

## Summary

The Portal Core System is the heart of the captive portal. It:

1. **Detects** when device time expires (CheckTimeExpiration job)
2. **Blocks** device at network level (NetworkService)
3. **Redirects** device to portal (NoDogSplashService)
4. **Grants** time after quiz/video completion (TimeGrantingService)
5. **Unblocks** device at all three layers (TimeGrantingService)

The system uses a three-layer approach (database, network, redirect) for complete control. Currently, database and redirect layers are fully implemented, while network layer (iptables blocking) is pending implementation in TODO #12.

All code is well-documented and beginner-friendly, making it easy to understand and maintain.

