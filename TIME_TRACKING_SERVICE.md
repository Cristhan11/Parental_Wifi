# Time Tracking Service - Complete Guide

## Overview: What TimeTrackingService Does

The **TimeTrackingService** is the **CRITICAL FOUNDATION** for the captive portal system. It tracks how much internet time each device has left and deducts time as devices browse the internet. When time runs out, the device is blocked and redirected to the captive portal where they can earn more time by completing quizzes or watching educational videos.
 
## The Core Concept

Think of it like a **prepaid phone plan**:
- Device starts with X minutes (e.g., 15 minutes)
- As device browses internet, time is deducted
- When time reaches 0, access is blocked
- To get more time, complete quiz or watch video

---

## How It Works: The Complete Flow

### Step 1: Device Gets Approved
```
Parent approves device → DeviceController assigns device → Status becomes 'active'
```

**What happens:**
- Parent logs into dashboard
- Parent adds/assigns child's device (enters MAC address)
- Device status changes from 'blocked' to 'active'
- Device is now ready to browse (if time available)

---

### Step 2: Session Starts
```
Device tries to access internet → startSession() is called
→ Checks if device is approved (status is 'active' or 'whitelisted')
→ If approved: Creates DeviceSession record
→ If NOT approved: Returns null, logs unauthorized attempt (security)
```

**What happens:**
- Device connects to WiFi and tries to access internet
- System calls `TimeTrackingService::startSession($device)`
- Service checks: Is device approved? (status is 'active' or 'whitelisted')
- **If approved:**
  - Creates new `DeviceSession` record
  - Sets `started_at` = current time
  - Sets `ended_at` = NULL (session is active)
  - Device can now browse
- **If NOT approved:**
  - Returns `null` (no session created)
  - Logs unauthorized attempt with MAC address
  - MAC blocking handled by NetworkService (security)

---

### Step 3: Device Browses Internet
```
Device is browsing → Active session is running
→ Background job (TrackActiveSessions) runs every 1-5 minutes
→ Calls trackActiveSessions()
→ Deducts time from device's remaining_time_minutes
```

**What happens:**
- Device is actively browsing websites
- Background job runs periodically (every 1-5 minutes)
- Job calls `TimeTrackingService::trackActiveSessions()`
- Service processes all active sessions:
  - Calculates how long each session has been running
  - Deducts that time from `remaining_time_minutes`
  - Updates `last_seen_at` timestamp
- Example: Device browses 5 minutes → 5 minutes deducted

**Timeline Example:**
```
10:00 AM - Device starts browsing (session created)
10:05 AM - Background job runs → Deducts 5 minutes
         - Device now has: 30 - 5 = 25 minutes remaining
         
10:10 AM - Background job runs → Deducts 5 more minutes
         - Device now has: 25 - 5 = 20 minutes remaining
         
10:15 AM - Background job runs → Deducts 5 more minutes
         - Device now has: 20 - 5 = 15 minutes remaining
```

**Important: Device Disconnection Handling (Option 1)**
- If device disconnects from WiFi → `handleDeviceDisconnection()` is called
- Session automatically ends → Time stops deducting
- IP address is cleared
- This prevents time waste when device is in standby or disconnected

---

### Step 4: Time Expires
```
Time reaches 0 → hasTimeExpired() returns true
→ Background job (CheckTimeExpiration) finds expired devices
→ Device is blocked and redirected to portal
```

**What happens:**
- Device continues browsing
- Background job keeps deducting time
- Eventually `remaining_time_minutes` reaches 0
- Another background job (CheckTimeExpiration) runs
- Job calls `TimeTrackingService::getExpiredDevices()`
- Service finds all devices where `hasTimeExpired()` returns true
- For each expired device:
  - Device status changed to 'blocked'
  - Device blocked at network level (via NetworkService)
  - Device redirected to captive portal (via NoDogSplash)
  - Child sees portal page with quiz/video options

---

### Step 5: Device Completes Quiz/Video
```
Quiz/Video completed → TimeGrantingService grants time
→ Device's remaining_time_minutes increases
→ Device is unblocked and can browse again
```

**What happens:**
- Child completes quiz or watches video
- `TimeGrantingService` grants additional time (e.g., 15 minutes)
- Device's `remaining_time_minutes` increases: 0 + 15 = 15 minutes
- Device status changed back to 'active'
- Device unblocked at network level
- Device can browse again
- Process repeats (Steps 2-4)

---

## Method-by-Method Explanation

### 1. `calculateRemainingTime(Device $device): int`

**What it does:** Calculates how much time a device has left (most accurate calculation).

**The Logic:**
```php
// Step 1: Check if whitelisted (unlimited access)
if (whitelisted) return 999999;

// Step 2: Get time stored in database
baseRemaining = device.remaining_time_minutes  // e.g., 30 minutes

// Step 3: Get active session (if device is currently browsing)
activeSession = device.activeSession()

// Step 4: Calculate accurate remaining time
if (no active session):
    return baseRemaining  // Simple case: 30 minutes

if (has active session):
    sessionDuration = how long session has been running  // e.g., 5 minutes
    accurateRemaining = baseRemaining - sessionDuration  // 30 - 5 = 25 minutes
    return accurateRemaining
```

**Why this is important:**
- Background job runs every 1-5 minutes, so time might not be deducted yet
- If device has been browsing 5 minutes but job hasn't run, we need to account for that
- This ensures time is **as accurate as possible**

**Example:**
- Device has 30 minutes in database
- Session has been running 5 minutes
- Accurate remaining = 30 - 5 = **25 minutes**

---

### 2. `hasTimeExpired(Device $device): bool`

**What it does:** Checks if device's time has run out.

**The Logic:**
```php
// Step 1: Whitelisted devices never expire
if (whitelisted) return false;

// Step 2: Calculate remaining time
remaining = calculateRemainingTime(device)

// Step 3: Check if expired
if (remaining <= 0):
    return true  // Time expired!
else:
    return false  // Still has time
```

**When it's used:**
- Background job calls this to find devices to block
- Portal controllers check this before allowing access

---

### 3. `getExpiredDevices(): Collection`

**What it does:** Finds all devices that have expired (for background job to block).

**The Logic:**
```php
// Step 1: Get all active devices (not blocked, not whitelisted)
activeDevices = Device.where('status', 'active')

// Step 2: Filter to find expired ones
expiredDevices = activeDevices.filter(device => hasTimeExpired(device))

// Step 3: Return collection
return expiredDevices
```

**When it's used:**
- Background job (CheckTimeExpiration) calls this
- Finds all devices that need to be blocked
- Then blocks them and redirects to portal

---

### 4. `startSession(Device $device): ?DeviceSession`

**What it does:** Creates a new session when device starts browsing.

**The Logic:**
```php
// Step 1: Check if device is approved
if (device.status != 'active' AND device.status != 'whitelisted'):
    // NOT approved - security issue!
    Log unauthorized attempt with MAC address
    return null  // No session created

// Step 2: Check if device already has active session
if (device has active session):
    return existing session  // Don't create duplicate

// Step 3: Create new session
session = create DeviceSession:
    started_at = now()
    ended_at = null  // Active
    duration_seconds = null

// Step 4: Log and return
Log session start
return session
```

**Security Feature:**
- If unapproved device tries to start session, logs attempt
- MAC blocking handled by NetworkService (separate concern)
- Prevents unauthorized access

**Example:**
- Parent approves device → status becomes 'active'
- Device tries to browse → `startSession()` called
- Session created → device can browse

---
sd
### 5. `endSession(DeviceSession $session): void`

**What it does:** Ends a session and deducts the final time.

**The Logic:**
```php
// Step 1: Check if already ended
if (session already ended):
    return  // Do nothing

// Step 2: Mark session as ended
session.ended_at = now()

// Step 3: Calculate duration
session.calculateDuration()  // Calculates duration_seconds

// Step 4: Get duration in minutes
durationMinutes = session.getDurationMinutes()  // e.g., 10 minutes

// Step 5: Deduct time (if not whitelisted)
if (device NOT whitelisted AND duration > 0):
    device.deductTime(durationMinutes)  // Deduct 10 minutes

// Step 6: Update last seen
device.last_seen_at = now()
```

**When it's used:**
- When device disconnects
- When device stops browsing
- Final time deduction for the session

---

### 6. `trackActiveSessions(): void` ⭐ **MAIN METHOD**

**What it does:** Processes all active sessions and deducts time periodically.

**The Logic:**
```php
// Step 1: Get all active sessions
activeSessions = DeviceSession.where('ended_at', null)

// Step 2: For each active session
foreach (session in activeSessions):
    device = session.device
    
    // Step 3: Skip whitelisted devices
    if (device is whitelisted):
        continue  // Skip to next session
    
    // Step 4: Calculate session duration
    durationMinutes = session.getDurationMinutes()  // e.g., 5 minutes
    
    // Step 5: Deduct time (if >= 1 minute)
    if (durationMinutes >= 1):
        minutesToDeduct = ceil(durationMinutes)  // Round up: 5.1 = 6
        device.deductTime(minutesToDeduct)  // Deduct 6 minutes
        device.last_seen_at = now()
        Log deduction
```

**When it's used:**
- Called by background job (TrackActiveSessions)
- Runs every 1-5 minutes
- This is the **main method** that keeps time tracking accurate

**Example Timeline:**
```
10:00 AM - Device starts browsing (session created)
10:05 AM - Background job runs → Deducts 5 minutes
10:10 AM - Background job runs → Deducts 5 more minutes
10:15 AM - Background job runs → Deducts 5 more minutes
         - Device now has 0 minutes → Blocked!
```

---

### 7. `getActiveSessions(?Device $device = null): Collection`

**What it does:** Gets all active sessions (for monitoring/dashboard).

**The Logic:**
```php
// Step 1: Query active sessions
query = DeviceSession.where('ended_at', null)

// Step 2: Optional device filter
if (device provided):
    query.where('device_id', device.id)

// Step 3: Return results
return query.get()
```

**When it's used:**
- Dashboard to show which devices are currently online
- Monitoring views
- Parent can see active browsing sessions

---

### 8. `getSessionDuration(DeviceSession $session): int`

**What it does:** Helper method to get session duration in minutes.

**The Logic:**
```php
// Use model's method and round up
duration = session.getDurationMinutes()  // e.g., 5.3 minutes
return ceil(duration)  // Returns 6 minutes
```

**When it's used:**
- Helper method used by other methods
- Converts float to integer (rounded up)

---

### 9. `shouldTrackTime(Device $device): bool`

**What it does:** Checks if device should have time tracked.

**The Logic:**
```php
// Whitelisted devices skip all tracking
return !device.isWhitelisted()
```

**When it's used:**
- Helper method used throughout service
- Quick check before time operations

---

### 10. `handleDeviceDisconnection(string $macAddress): bool` ⭐ **NEW - Option 1**

**What it does:** Automatically ends session when device disconnects from WiFi.

**The Logic:**
```php
// Step 1: Find device by MAC address
device = Device.where('mac_address', macAddress)

// Step 2: Get active session
activeSession = device.activeSession()

// Step 3: If session exists, end it
if (activeSession):
    endSession(activeSession)  // Deducts time
    device.ip_address = null   // Clear IP
    return true
else:
    return false  // No session to end
```

**When it's used:**
- Called by network monitoring when device disconnects
- Prevents time waste when device is in standby
- Automatically handles disconnection cleanup

**Example:**
- Device connects → Session starts
- Device goes to standby → Still connected, time deducts
- Device disconnects → `handleDeviceDisconnection()` called → Session ends → Time stops deducting

---

### 11. `handleMultipleDeviceDisconnections(array $macAddresses): array`

**What it does:** Batch process multiple device disconnections.

**The Logic:**
```php
// Process each MAC address
foreach (macAddress in macAddresses):
    results[macAddress] = handleDeviceDisconnection(macAddress)

return results
```

**When it's used:**
- Network monitoring detects multiple disconnections at once
- Efficient batch processing

---

### 12. `endSessionsForDisconnectedDevices(): int` ⭐ **Safety Mechanism**

**What it does:** Safety check to end sessions for devices without IP addresses.

**The Logic:**
```php
// Step 1: Get all active sessions
activeSessions = DeviceSession.where('ended_at', null)

// Step 2: Check each session's device
foreach (session in activeSessions):
    device = session.device
    
    // Step 3: If device has no IP, it's disconnected
    if (device.ip_address is null):
        endSession(session)  // End session
        sessionsEnded++

return sessionsEnded
```

**When it's used:**
- Periodic background job (every 5-10 minutes)
- Fallback mechanism if disconnection wasn't detected
- Catches edge cases where device disconnected but wasn't detected

**Example:**
- Background job runs every 5 minutes
- Finds devices with active sessions but no IP address
- Automatically ends those sessions
- Prevents time waste from "zombie" sessions

---

## Complete Example: Real-World Scenario

### Scenario: Child's Device with 15 Minutes Initial Time

**1. Parent Approves Device**
- Device status: 'active'
- `remaining_time_minutes`: 15
- Device ready to browse

**2. Device Starts Browsing**
- `startSession($device)` called
- Session created: `started_at = 10:00 AM`
- Device can now access internet

**3. Device Browses for 5 Minutes**
- Background job runs at 10:05 AM
- `trackActiveSessions()` called
- Deducts 5 minutes
- `remaining_time_minutes`: 15 - 5 = **10 minutes**

**4. Device Browses for 5 More Minutes**
- Background job runs at 10:10 AM
- Deducts 5 more minutes
- `remaining_time_minutes`: 10 - 5 = **5 minutes**

**5. Device Browses for 5 More Minutes**
- Background job runs at 10:15 AM
- Deducts 5 more minutes
- `remaining_time_minutes`: 5 - 5 = **0 minutes**

**6. Time Expires**
- Background job (CheckTimeExpiration) runs
- `hasTimeExpired($device)` returns `true`
- Device blocked and redirected to portal
- Child sees quiz/video options

**7. Child Completes Quiz**
- TimeGrantingService grants 15 minutes
- `remaining_time_minutes`: 0 + 15 = **15 minutes**
- Device unblocked
- Can browse again (process repeats)

---

## Key Design Decisions

### 1. ✅ Accurate Time Calculation
- **Formula:** `remaining_time_minutes - active_session_duration`
- **Why:** Accounts for time not yet deducted by background job
- **Example:** 30 min in DB, 5 min session = 25 min remaining

### 2. ✅ Periodic Deduction (Not Real-Time)
- **Method:** Background job runs every 1-5 minutes
- **Why:** More efficient than real-time, prevents database overload
- **Trade-off:** Slight delay in time deduction (acceptable)

### 3. ✅ Whitelisted Devices Skip All Tracking
- **Behavior:** Never deduct time, never expire
- **Why:** Unrestricted access for trusted devices
- **Implementation:** All methods check `isWhitelisted()`

### 4. ✅ Security: Unapproved Device Handling
- **Behavior:** Unapproved devices can't start sessions
- **Why:** Prevents unauthorized access/hackers
- **Implementation:** Logs attempt, MAC blocking handled separately

---

## How Methods Work Together

```
Device Approved
    ↓
startSession() → Creates session
    ↓
Device Browses
    ↓
trackActiveSessions() → Deducts time periodically
    ↓
calculateRemainingTime() → Calculates accurate remaining time
    ↓
hasTimeExpired() → Checks if time ran out
    ↓
getExpiredDevices() → Finds expired devices
    ↓
Device Blocked → Redirected to Portal
```

---

## Integration with Other Components

### Used By:
- **Background Job: TrackActiveSessions** - Calls `trackActiveSessions()`
- **Background Job: CheckTimeExpiration** - Calls `getExpiredDevices()`, `hasTimeExpired()`
- **DeviceController** - Calls `startSession()` when device approved
- **Portal Controllers** - Calls `hasTimeExpired()` before allowing access
- **TimeGrantingService** - After granting time, verifies it worked

### Uses:
- **Device Model** - Methods: `deductTime()`, `isWhitelisted()`, `activeSession()`, `sessions()`
- **DeviceSession Model** - Methods: `isActive()`, `getDurationMinutes()`, `calculateDuration()`

---

## Common Questions

### Q: Why not deduct time in real-time?
**A:** Real-time deduction would require constant database updates, which is inefficient. Periodic deduction (every 1-5 minutes) is more efficient and still accurate enough.

### Q: What if background job doesn't run?
**A:** `calculateRemainingTime()` accounts for active sessions, so time is still accurate even if job hasn't run yet.

### Q: Can a device have multiple active sessions?
**A:** No. `startSession()` prevents duplicates by checking for existing active session.

### Q: What happens if device disconnects suddenly?
**A:** The service automatically handles disconnections via `handleDeviceDisconnection()`. When a device disconnects:
- Active session is automatically ended
- Final time is deducted
- IP address is cleared
- This prevents time waste when device is in standby or disconnected

### Q: Does time get deducted if device is in standby (not browsing)?
**A:** **No!** With Option 1 implementation, time is only deducted while device is actually connected. When device disconnects:
- Session automatically ends
- Time deduction stops
- Device can reconnect later without wasting time

### Q: How accurate is the time tracking?
**A:** Very accurate. Uses formula: `remaining_time_minutes - active_session_duration`, which accounts for time not yet deducted.

---

## Summary

The **TimeTrackingService** is the foundation that:
1. ✅ Tracks device internet time accurately
2. ✅ Deducts time as devices browse
3. ✅ Detects when time expires
4. ✅ Enables portal redirect when time runs out
5. ✅ Handles whitelisted devices (unrestricted access)
6. ✅ Provides security (logs unauthorized attempts)
7. ✅ **Automatically ends sessions on device disconnect (Option 1)** - Prevents time waste when device is in standby or disconnected

Without this service, the captive portal system cannot function. It's the **critical foundation** that makes everything else possible.

---

## Option 1 Implementation: Automatic Session Ending on Disconnect

### Problem Solved
Previously, if a device connected to WiFi and then went to standby (not browsing), time would still be deducted because the session remained active. This wasted the child's internet time.

### Solution
**Option 1** automatically ends sessions when devices disconnect from WiFi:
- ✅ **`handleDeviceDisconnection($macAddress)`** - Called when network monitoring detects disconnection
- ✅ **`endSessionsForDisconnectedDevices()`** - Safety check for devices without IP addresses
- ✅ **`handleMultipleDeviceDisconnections($macAddresses)`** - Batch processing

### How It Works

**Scenario 1: Device Disconnects**
```
10:00 AM - Device connects → Session starts
10:05 AM - Device goes to standby (screen off, not browsing)
10:10 AM - Device disconnects from WiFi
         → handleDeviceDisconnection() called
         → Session ended automatically
         → Time stops deducting
         → IP address cleared
```

**Scenario 2: Device in Standby (Still Connected)**
```
10:00 AM - Device connects → Session starts
10:05 AM - Device goes to standby (still connected to WiFi)
10:10 AM - Background job runs → Time still deducts (device is connected)
         → This is expected behavior (device is still connected)
```

**Scenario 3: Safety Check (Fallback)**
```
10:00 AM - Device connects → Session starts
10:05 AM - Device disconnects (but not detected by monitoring)
10:10 AM - endSessionsForDisconnectedDevices() runs
         → Finds device with active session but no IP address
         → Automatically ends session
         → Prevents "zombie" sessions
```

### Integration Points

1. **Network Monitoring** - Call `handleDeviceDisconnection()` when device disconnects
2. **Background Job** - Call `endSessionsForDisconnectedDevices()` every 5-10 minutes as safety check
3. **Device Controller** - Can manually call `handleDeviceDisconnection()` if needed

### Benefits

- ✅ **No time waste** - Sessions end when device disconnects
- ✅ **Automatic** - No manual intervention needed
- ✅ **Safe** - Can be called multiple times without double-deducting
- ✅ **Redundant** - Safety check catches missed disconnections
- ✅ **Logged** - All disconnections are logged for monitoring

