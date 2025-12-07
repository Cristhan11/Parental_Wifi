# Device Management System - Beginner's Guide (TODO 18)

**Date:** December 2025  
**Status:** ✅ Complete and Tested

## Table of Contents

1. [What is Device Management?](#what-is-device-management)
2. [System Overview](#system-overview)
3. [File Structure and Purpose](#file-structure-and-purpose)
4. [How Files Work Together](#how-files-work-together)
5. [Complete Workflow Examples](#complete-workflow-examples)
6. [Key Concepts Explained](#key-concepts-explained)

---

## What is Device Management?

Device Management is the system that allows parents to:
- **Add devices** (like their child's phone, tablet, or laptop) to the system
- **Control device access** (block, allow, or whitelist devices)
- **Set time limits** (how much internet time each device gets)
- **Monitor device activity** (see what websites were visited, quiz scores, etc.)
- **Manage device roles** (CHILD, GUEST, or PARENT device types)

Think of it like a **digital gatekeeper** - parents register devices, set rules, and the system enforces those rules automatically.

---

## System Overview

### The Big Picture

```
Parent (User)
    ↓
    Registers Device (via Web Interface)
    ↓
DeviceController (Handles Requests)
    ↓
DeviceService (Business Logic)
    ↓
Device Model (Database)
    ↓
NetworkService (Network Control)
    ↓
Raspberry Pi (Blocks/Allows Device)
```

### Main Components

1. **Frontend (Views)** - What parents see and interact with
2. **Controller** - Handles requests from the browser
3. **Service** - Contains business logic (MAC address handling, statistics)
4. **Model** - Represents a device in the database
5. **Request Validation** - Ensures data is correct before saving
6. **Policy** - Security (ensures parents only manage their own devices)

---

## File Structure and Purpose

### 📁 Frontend Files (What Parents See)

#### 1. `resources/views/devices/accounts.blade.php`
**What it does:** Main device list page showing all registered devices

**Why it exists:** Parents need to see all their devices in one place (like a phone book)

**Key Features:**
- Shows device table with MAC addresses, names, roles, and status
- Has buttons to create new devices, view blocklist, whitelist
- Displays device status (Active/Blocked/Whitelisted) with color badges

**How it works:**
- User visits `/accounts`
- `DeviceController@accounts()` fetches devices from database
- View displays devices in a table format

---

#### 2. `resources/views/devices/device_create.blade.php`
**What it does:** Form for adding a new device

**Why it exists:** Parents need an easy way to register new devices

**Key Features:**
- Form fields: Device name, MAC address, role, status, time allocation
- Shows connected devices on network (helper feature)
- Validates input before submission
- Normalizes MAC address automatically (converts `aa-bb-cc` to `AA:BB:CC`)

**How it works:**
- User clicks "+ New" button
- Form submits to `DeviceController@store()`
- Controller validates and saves device

---

#### 3. `resources/views/devices/device_edit.blade.php`
**What it does:** Form for editing existing device information

**Why it exists:** Parents need to update device settings (change name, time, status)

**Key Features:**
- Pre-filled form with existing device data
- Shows device statistics (sessions, logs, connection status)
- Can update all device properties

**How it works:**
- User clicks "Edit" on a device
- `DeviceController@edit()` loads device data
- Form submits to `DeviceController@update()`

---

#### 4. `resources/views/devices/child_devices.blade.php`
**What it does:** Statistics dashboard showing device activity

**Why it exists:** Parents want to see how their child's device is being used

**Key Features:**
- Time usage graph (hours per month)
- Quiz scores list
- Website history (recently visited sites)
- Device selector dropdown

**How it works:**
- User visits `/child_devices`
- `DeviceController@index()` calculates statistics
- View displays data in cards with charts

---

#### 5. `resources/views/devices/device_blocklist.blade.php`
**What it does:** Shows all blocked devices

**Why it exists:** Parents need to see and manage blocked devices separately

**Key Features:**
- Lists only devices with "blocked" status
- Quick unblock button
- Edit device option

---

#### 6. `resources/views/devices/device_whitelist.blade.php`
**What it does:** Shows all whitelisted devices

**Why it exists:** Parents need to see devices with unrestricted access

**Key Features:**
- Lists only devices with "whitelisted" status
- Remove from whitelist button

---

#### 7. `resources/views/layouts/navigation.blade.php`
**What it does:** Navigation menu for the entire application

**Why it exists:** Users need to navigate between different sections

**Key Features:**
- Links to Dashboard, Quizzes, Accounts, Child Devices
- User profile dropdown
- Responsive mobile menu

---

### 📁 Backend Files (The Logic)

#### 8. `app/Http/Controllers/DeviceController.php`
**What it does:** The "brain" that handles all device-related requests

**Why it exists:** Controllers coordinate between the frontend (views) and backend (database/services)

**Key Responsibilities:**
- **CRUD Operations:**
  - `accounts()` - Show device list
  - `create()` - Show create form
  - `store()` - Save new device
  - `edit()` - Show edit form
  - `update()` - Update device
  - `destroy()` - Delete device

- **Status Management:**
  - `updateStatus()` - Change device status (active/blocked/whitelisted)
  - `updateRole()` - Change device role (child/guest/parent)
  - `updateTimeAllocation()` - Change time limits

- **Statistics:**
  - `index()` - Show child devices stats
  - `getTimeUsageData()` - Calculate time usage graph data
  - `getQuizScores()` - Get quiz attempt scores
  - `getWebsiteHistory()` - Get visited websites

- **Network Integration:**
  - `getConnectedDevices()` - Get devices currently on network
  - Integrates with `NetworkService` to block/unblock devices

**How it works:**
```
User clicks button → Route → Controller method → Service/Model → Database → Response → View
```

**Example Flow:**
1. User clicks "Save Device" on create form
2. Route `/accounts` (POST) calls `DeviceController@store()`
3. Controller validates data using `StoreDeviceRequest`
4. Controller normalizes MAC address using `DeviceService`
5. Controller saves device to database using `Device` model
6. Controller calls `NetworkService` to block device if needed
7. Controller redirects to accounts page with success message

---

#### 9. `app/Services/DeviceService.php`
**What it does:** Helper service containing reusable device management logic

**Why it exists:** Keeps business logic separate from controllers, making code cleaner and easier to test

**Key Methods:**

1. **`normalizeMacAddress($mac)`**
   - **What:** Converts MAC address to standard format
   - **Why:** MAC addresses can be entered in different formats (`aa-bb-cc` or `AA:BB:CC`)
   - **Example:** `'aa-bb-cc-dd-ee-ff'` → `'AA:BB:CC:DD:EE:FF'`

2. **`validateMacAddress($mac)`**
   - **What:** Checks if MAC address format is valid
   - **Why:** Prevents invalid MAC addresses from being saved
   - **Returns:** `true` if valid, `false` if invalid

3. **`checkMacExists($mac, $excludeDeviceId)`**
   - **What:** Checks if MAC address already exists in database
   - **Why:** Prevents duplicate device registrations
   - **Example:** When updating device, exclude current device from check

4. **`getDeviceStats($device)`**
   - **What:** Calculates device statistics (sessions, logs, attempts)
   - **Why:** Provides data for statistics dashboard
   - **Returns:** Array with counts (sessions_count, logs_count, etc.)

5. **`syncDeviceStatus($device)`**
   - **What:** Syncs database status with network status
   - **Why:** Ensures database matches actual network state

**How it's used:**
- Called by `DeviceController` when needed
- Also used by form requests for validation

---

#### 10. `app/Models/Device.php`
**What it does:** Represents a device in the database (like a blueprint)

**Why it exists:** Laravel uses models to interact with database tables easily

**Key Features:**

1. **Database Relationships:**
   - `user()` - Device belongs to a parent (User)
   - `timeGrants()` - Device has many time grants
   - `quizAttempts()` - Device has many quiz attempts
   - `videoCompletions()` - Device has many video completions
   - `browsingLogs()` - Device has many browsing logs
   - `sessions()` - Device has many internet sessions

2. **Helper Methods:**
   - `hasRemainingTime()` - Check if device has time left
   - `hasTimeExpired()` - Check if time expired
   - `grantTime()` - Add time after quiz/video completion
   - `deductTime()` - Remove time when device is browsing
   - `isBlocked()` - Check if device is blocked
   - `isWhitelisted()` - Check if device is whitelisted

**How it works:**
```php
// Create a device
$device = Device::create([
    'name' => 'John\'s iPhone',
    'mac_address' => 'AA:BB:CC:DD:EE:FF',
    // ...
]);

// Access relationships
$device->user; // Get the parent user
$device->browsingLogs; // Get all browsing logs
$device->hasRemainingTime(); // Check if time left
```

---

#### 11. `app/Http/Requests/StoreDeviceRequest.php`
**What it does:** Validates data when creating a new device

**Why it exists:** Ensures only valid, safe data is saved to database

**Validation Rules:**
- `name` - Required, max 255 characters
- `mac_address` - Required, valid format, must be unique
- `role` - Required, must be: child, guest, or parent
- `status` - Required, must be: active, blocked, or whitelisted
- `remaining_time_minutes` - Optional, integer, 0-9999
- `total_time_allocated` - Optional, integer, 0-9999

**How it works:**
1. User submits form
2. Laravel automatically validates using rules in this file
3. If valid: Controller method is called
4. If invalid: User sees error messages, form is not submitted

**Example:**
```php
// In DeviceController@store()
public function store(StoreDeviceRequest $request)
{
    // At this point, data is already validated!
    $validated = $request->validated();
    // Safe to use - all validation passed
}
```

---

#### 12. `app/Http/Requests/UpdateDeviceRequest.php`
**What it does:** Validates data when updating an existing device

**Why it exists:** Same as StoreDeviceRequest, but allows keeping same MAC address

**Key Difference:**
- MAC address uniqueness check **excludes current device**
- This allows updating other fields without changing MAC address

**Example:**
```php
// Updating device name but keeping same MAC
// UpdateDeviceRequest allows this (StoreDeviceRequest would reject it)
```

---

#### 13. `app/Rules/ValidMacAddress.php`
**What it does:** Custom validation rule for MAC address format

**Why it exists:** Standard Laravel rules don't validate MAC address format specifically

**What it checks:**
- Format: `XX:XX:XX:XX:XX:XX` or `XX-XX-XX-XX-XX-XX`
- Exactly 6 pairs of 2 hexadecimal characters
- Accepts uppercase and lowercase

**How it works:**
- Used in `StoreDeviceRequest` and `UpdateDeviceRequest`
- Automatically called by Laravel during validation
- Returns error message if format is invalid

---

#### 14. `app/Policies/DevicePolicy.php`
**What it does:** Security - ensures users can only manage their own devices

**Why it exists:** Prevents parents from accessing or modifying other parents' devices

**Key Methods:**
- `view()` - Can user view this device? (checks ownership)
- `create()` - Can user create devices? (all authenticated users can)
- `update()` - Can user update this device? (checks ownership)
- `delete()` - Can user delete this device? (checks ownership)

**How it works:**
```php
// In DeviceController
$this->authorize('update', $device);
// Automatically calls DevicePolicy::update()
// If user doesn't own device, throws 403 Forbidden error
```

**Security Example:**
- User 1 tries to edit User 2's device
- `DevicePolicy::update()` checks: `$device->user_id === $user->id`
- Returns `false` → Laravel throws 403 Forbidden
- User 1 cannot edit the device

---

### 📁 Database Files

#### 15. `database/migrations/2025_12_07_170515_add_role_to_devices_table.php`
**What it does:** Adds `role` column to devices table

**Why it exists:** Devices need roles (child/guest/parent) to determine access levels

**What it does:**
- Adds `role` column (string, default: 'child')
- Placed after `status` column in database

**How it works:**
- Run `php artisan migrate` to apply
- Adds column to existing devices table
- All existing devices get default value 'child'

---

#### 16. `database/migrations/2025_11_29_071610_add_manual_source_to_device_time_grants_table.php`
**What it does:** Adds 'manual' as a valid source for time grants

**Why it exists:** Allows parents to manually grant time (not just from quiz/video)

**What it does:**
- Updates enum column to include 'manual' option
- Only runs on MySQL/MariaDB (skips SQLite for testing)

---

#### 17. `database/factories/DeviceFactory.php`
**What it does:** Creates fake device data for testing

**Why it exists:** Tests need realistic device data without manual creation

**How it works:**
```php
// In tests
$device = Device::factory()->create(); // Creates fake device
$device = Device::factory()->blocked()->create(); // Creates blocked device
$device = Device::factory()->withTime(60)->create(); // Creates device with 60 minutes
```

---

### 📁 Routes

#### 18. `routes/web.php` (Device Management Routes)
**What it does:** Defines URLs and which controller methods handle them

**Why it exists:** Maps browser URLs to controller methods

**Key Routes:**

```php
// Accounts routes (main device management)
GET  /accounts              → DeviceController@accounts (list devices)
GET  /accounts/create       → DeviceController@create (show form)
POST /accounts              → DeviceController@store (save device)
GET  /accounts/{device}/edit → DeviceController@edit (show edit form)
PUT  /accounts/{device}     → DeviceController@update (update device)
DELETE /accounts/{device}   → DeviceController@destroy (delete device)
GET  /accounts/blocklist    → DeviceController@blocklist (show blocked)
GET  /accounts/whitelist    → DeviceController@whitelist (show whitelisted)

// Child Devices routes (statistics)
GET  /child_devices         → DeviceController@index (show stats)
GET  /child_devices/{device} → DeviceController@index (show stats for device)
GET  /child_devices/api/connected → DeviceController@getConnectedDevices (API)
```

**How it works:**
```
User visits /accounts
    ↓
Route matches 'accounts.index'
    ↓
Calls DeviceController@accounts()
    ↓
Returns view with devices
```

---

### 📁 Tests

#### 19. `tests/Feature/DeviceManagementTest.php`
**What it does:** Tests all device management functionality

**Why it exists:** Ensures everything works correctly before deploying

**What it tests:**
- CRUD operations (create, read, update, delete)
- Validation (invalid data is rejected)
- Authorization (users can only manage their own devices)
- Status management (active/blocked/whitelisted)
- Time allocation
- Role management

**Test Examples:**
```php
test_can_create_device_with_valid_data() // ✅ Passes
test_mac_address_must_be_unique()        // ✅ Passes
test_users_cannot_edit_other_users_devices() // ✅ Passes
```

---

#### 20. `tests/Unit/DeviceServiceTest.php`
**What it does:** Tests DeviceService methods in isolation

**Why it exists:** Ensures service logic works correctly

**What it tests:**
- MAC address normalization
- MAC address validation
- MAC address existence checking
- Device statistics calculation

---

## How Files Work Together

### Complete Flow: Creating a Device

```
1. USER ACTION
   User clicks "+ New" button on accounts page
   ↓

2. ROUTE
   Browser requests: GET /accounts/create
   Route matches: accounts.create → DeviceController@create()
   ↓

3. CONTROLLER
   DeviceController@create():
   - Checks authorization (DevicePolicy::create())
   - Gets connected devices (NetworkService)
   - Returns view: device_create.blade.php
   ↓

4. VIEW
   device_create.blade.php displays form
   User fills in: name, MAC address, role, status, time
   ↓

5. USER SUBMITS FORM
   Browser sends: POST /accounts
   Route matches: accounts.store → DeviceController@store()
   ↓

6. VALIDATION
   StoreDeviceRequest validates:
   - name: required, string, max 255
   - mac_address: required, ValidMacAddress rule, unique
   - role: required, in:child,guest,parent
   - status: required, in:active,blocked,whitelisted
   - remaining_time_minutes: optional, integer, 0-9999
   ↓

7. IF VALIDATION PASSES
   DeviceController@store():
   - Authorizes (DevicePolicy::create())
   - Normalizes MAC address (DeviceService::normalizeMacAddress())
   - Sets default time (15 minutes if not provided)
   - Creates device (Device::create())
   - Applies network blocking if status is 'blocked' (NetworkService)
   - Redirects to accounts page with success message
   ↓

8. IF VALIDATION FAILS
   User sees error messages
   Form is redisplayed with old values
   User can correct and resubmit
   ↓

9. DATABASE
   Device saved to 'devices' table
   - user_id: Current user's ID
   - name: Device name
   - mac_address: Normalized MAC (AA:BB:CC:DD:EE:FF)
   - role: child/guest/parent
   - status: active/blocked/whitelisted
   - remaining_time_minutes: Time allocated
   ↓

10. RESPONSE
    User redirected to /accounts
    Sees success message: "Device created successfully!"
    New device appears in table
```

---

### Complete Flow: Updating Device Status

```
1. USER ACTION
   User clicks "Block" button on device
   ↓

2. ROUTE
   Browser sends: POST /accounts/{device}/status
   Route matches: accounts.status.update → DeviceController@updateStatus()
   ↓

3. CONTROLLER
   DeviceController@updateStatus():
   - Authorizes (DevicePolicy::update())
   - Validates status value
   - Updates device status in database
   - Calls NetworkService to block device at network level
   - Returns JSON (AJAX) or redirects
   ↓

4. NETWORK SERVICE
   NetworkService::blockDevice():
   - Executes block_device.sh script
   - Adds iptables rule to block MAC address
   - Device is now blocked from internet
   ↓

5. RESPONSE
   Device status updated
   Network blocking applied
   User sees updated status
```

---

### Complete Flow: Viewing Device Statistics

```
1. USER ACTION
   User visits /child_devices
   ↓

2. ROUTE
   Route matches: child_devices.index → DeviceController@index()
   ↓

3. CONTROLLER
   DeviceController@index():
   - Gets all user's devices
   - Selects first device (or from query parameter)
   - Authorizes (DevicePolicy::view())
   - Calculates statistics:
     * getTimeUsageData() - Time usage graph
     * getQuizScores() - Quiz attempt scores
     * getWebsiteHistory() - Visited websites
   - Returns view: child_devices.blade.php
   ↓

4. VIEW
   child_devices.blade.php displays:
   - Device selector dropdown
   - Time usage graph (Chart.js)
   - Quiz scores list
   - Website history list
   ↓

5. USER SEES
   Visual dashboard with all device statistics
```

---

## Key Concepts Explained

### 1. MVC Pattern (Model-View-Controller)

**What it is:** A way to organize code into three parts

**How it works in Device Management:**

- **Model** (`Device.php`): Represents device data in database
- **View** (`accounts.blade.php`, etc.): What user sees (HTML)
- **Controller** (`DeviceController.php`): Handles requests, coordinates Model and View

**Example:**
```
User clicks "Edit Device"
    ↓
Controller (DeviceController@edit) gets device from Model
    ↓
Controller passes device to View (device_edit.blade.php)
    ↓
View displays form with device data
```

---

### 2. Request Validation

**What it is:** Checking data before saving to database

**Why it's important:** Prevents invalid data, security issues, and errors

**How it works:**
```
User submits form
    ↓
StoreDeviceRequest validates data
    ↓
If valid: Controller method runs
If invalid: User sees errors, form not submitted
```

**Example:**
- User enters invalid MAC: `INVALID-MAC`
- `ValidMacAddress` rule rejects it
- User sees: "The mac_address must be a valid MAC address format"
- Device is NOT created

---

### 3. Authorization (Security)

**What it is:** Ensuring users can only access their own data

**Why it's important:** Prevents users from modifying other users' devices

**How it works:**
```
User tries to edit device
    ↓
DevicePolicy::update() checks ownership
    ↓
If user owns device: Allow (return true)
If user doesn't own: Deny (return false, 403 Forbidden)
```

**Example:**
- User 1 tries to edit User 2's device
- `DevicePolicy::update()` checks: `$device->user_id === $user->id`
- Returns `false` → Laravel throws 403 Forbidden
- User 1 cannot access the device

---

### 4. Service Layer

**What it is:** Classes that contain business logic (not HTTP handling)

**Why it exists:** Keeps controllers clean, makes code reusable and testable

**Example:**
```php
// Instead of putting this in Controller:
$mac = str_replace('-', ':', $mac);
$mac = strtoupper($mac);

// We put it in DeviceService:
$mac = $deviceService->normalizeMacAddress($mac);
```

**Benefits:**
- Reusable (can use in multiple places)
- Testable (can test service methods independently)
- Clean controllers (controllers focus on HTTP, not business logic)

---

### 5. MAC Address Normalization

**What it is:** Converting MAC addresses to standard format

**Why it's needed:** Users can enter MAC addresses in different formats

**Example:**
```
User enters: "aa-bb-cc-dd-ee-ff"
    ↓
DeviceService::normalizeMacAddress()
    ↓
Stored as: "AA:BB:CC:DD:EE:FF"
```

**Why important:**
- Network scripts expect colon format
- Prevents duplicates (same MAC in different formats)
- Makes matching easier

---

### 6. Status Management

**What it is:** Three device states that control internet access

**Status Types:**
- **Active:** Device can access internet (subject to time limits)
- **Blocked:** Device is blocked from internet (no access)
- **Whitelisted:** Device bypasses all restrictions (unlimited access)

**How it works:**
```
Device status changed to "blocked"
    ↓
DeviceController@updateStatus()
    ↓
NetworkService::blockDevice()
    ↓
Executes block_device.sh script
    ↓
Adds iptables rule
    ↓
Device cannot access internet
```

---

### 7. Role Management

**What it is:** Three device types with different access levels

**Role Types:**
- **Child:** Subject to time limits (default)
- **Guest:** Temporary access device
- **Parent:** Unrestricted access device

**How it's used:**
- Determines which devices are subject to time limits
- Parent devices bypass all restrictions
- Guest devices have temporary access

---

### 8. Time Allocation

**What it is:** How much internet time a device has

**Fields:**
- `remaining_time_minutes`: Current time left
- `total_time_allocated`: Total time ever allocated (for tracking)

**How it works:**
```
Device created with 30 minutes
    ↓
Device browses internet
    ↓
TrackActiveSessions job deducts time
    ↓
Time reaches 0
    ↓
CheckTimeExpiration job blocks device
    ↓
Device redirected to portal
    ↓
Child completes quiz/video
    ↓
Time granted (e.g., +15 minutes)
    ↓
Device unblocked, can browse again
```

---

## File Interconnections Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    USER (Browser)                           │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│                    ROUTES (web.php)                         │
│  Maps URLs to Controller methods                            │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ↓
┌─────────────────────────────────────────────────────────────┐
│              DEVICE CONTROLLER                               │
│  - Handles HTTP requests                                    │
│  - Coordinates between Views, Services, Models              │
└──────┬───────────────┬───────────────┬──────────────────────┘
       │               │               │
       ↓               ↓               ↓
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│   VIEWS      │ │   SERVICES   │ │    MODELS    │
│ (Blade)      │ │ (Business    │ │ (Database)   │
│              │ │  Logic)      │ │              │
│ - accounts   │ │ - Device     │ │ - Device     │
│ - create     │ │   Service    │ │              │
│ - edit       │ │              │ │              │
│ - stats      │ │              │ │              │
└──────────────┘ └──────────────┘ └──────────────┘
       │               │               │
       │               │               ↓
       │               │      ┌──────────────┐
       │               │      │   DATABASE   │
       │               │      │  (MariaDB)   │
       │               │      └──────────────┘
       │               │
       │               ↓
       │      ┌──────────────┐
       │      │  NETWORK     │
       │      │  SERVICE     │
       │      │ (Block/      │
       │      │  Unblock)    │
       │      └──────────────┘
       │
       ↓
┌──────────────┐
│  VALIDATION  │
│  & SECURITY  │
│              │
│ - Store      │
│   Device     │
│   Request    │
│ - Update     │
│   Device     │
│   Request    │
│ - ValidMac   │
│   Address    │
│ - Device     │
│   Policy     │
└──────────────┘
```

---

## Common Operations Explained

### Operation 1: Create Device

**Files Involved:**
1. `accounts.blade.php` - Shows "+ New" button
2. `device_create.blade.php` - Create form
3. `web.php` - Routes to `DeviceController@store`
4. `DeviceController.php` - Handles creation
5. `StoreDeviceRequest.php` - Validates data
6. `ValidMacAddress.php` - Validates MAC format
7. `DeviceService.php` - Normalizes MAC address
8. `Device.php` - Saves to database
9. `DevicePolicy.php` - Checks authorization
10. `NetworkService.php` - Applies network blocking if needed

**Step-by-Step:**
1. User clicks "+ New" → Route → `DeviceController@create()` → Shows form
2. User fills form → Submits → Route → `DeviceController@store()`
3. `StoreDeviceRequest` validates all fields
4. `DevicePolicy` checks if user can create devices
5. `DeviceService` normalizes MAC address
6. `Device` model saves to database
7. If status is 'blocked', `NetworkService` blocks device
8. Redirect to accounts page with success message

---

### Operation 2: Update Device Status

**Files Involved:**
1. `accounts.blade.php` - Shows status button
2. `web.php` - Route to `DeviceController@updateStatus`
3. `DeviceController.php` - Handles status update
4. `DevicePolicy.php` - Checks ownership
5. `Device.php` - Updates database
6. `NetworkService.php` - Applies network blocking/unblocking

**Step-by-Step:**
1. User clicks status button → Route → `DeviceController@updateStatus()`
2. `DevicePolicy` checks ownership
3. Controller validates status value
4. `Device` model updates status in database
5. `NetworkService` syncs network status (blocks/unblocks device)
6. Returns success response

---

### Operation 3: View Device Statistics

**Files Involved:**
1. `navigation.blade.php` - "Child Devices" link
2. `web.php` - Route to `DeviceController@index`
3. `DeviceController.php` - Calculates statistics
4. `Device.php` - Queries relationships (sessions, logs, attempts)
5. `child_devices.blade.php` - Displays statistics

**Step-by-Step:**
1. User clicks "Child Devices" → Route → `DeviceController@index()`
2. Controller gets user's devices
3. Controller selects device (first one or from query)
4. Controller calculates:
   - Time usage data (queries `device_sessions` table)
   - Quiz scores (queries `quiz_attempts` table)
   - Website history (queries `browsing_logs` table)
5. Controller passes data to view
6. View displays statistics with charts

---

## Testing Overview

### Why We Test

**Tests ensure:**
- Code works correctly
- Bugs are caught early
- Changes don't break existing features
- Code is reliable

### Test Files

1. **`DeviceManagementTest.php`** (Feature Tests)
   - Tests complete workflows (create device, update, delete)
   - Tests validation (invalid data rejected)
   - Tests authorization (users can only manage own devices)
   - 29 tests, all passing ✅

2. **`DeviceServiceTest.php`** (Unit Tests)
   - Tests service methods in isolation
   - Tests MAC address normalization
   - Tests MAC address validation
   - 16 tests, all passing ✅

### Test Results

- **Total Tests:** 45
- **Total Assertions:** 110
- **Success Rate:** 100% ✅
- **Compatible with:** SQLite (testing) and MariaDB (production)

---

## Summary

### What We Built

A complete **Device Management System** that allows parents to:
- ✅ Register devices (add child's phone, tablet, etc.)
- ✅ Control device access (block, allow, whitelist)
- ✅ Set time limits (how much internet time)
- ✅ Monitor activity (statistics, quiz scores, website history)
- ✅ Manage device roles (child/guest/parent)

### Key Files

**Frontend (6 files):**
- `accounts.blade.php` - Device list
- `device_create.blade.php` - Create form
- `device_edit.blade.php` - Edit form
- `child_devices.blade.php` - Statistics dashboard
- `device_blocklist.blade.php` - Blocked devices
- `device_whitelist.blade.php` - Whitelisted devices

**Backend (8 files):**
- `DeviceController.php` - Handles all requests
- `DeviceService.php` - Business logic
- `Device.php` - Database model
- `StoreDeviceRequest.php` - Create validation
- `UpdateDeviceRequest.php` - Update validation
- `ValidMacAddress.php` - MAC format validation
- `DevicePolicy.php` - Security/authorization
- `web.php` - Routes

**Database (3 files):**
- Migration: Add role column
- Migration: Add manual source
- Factory: Test data generation

**Tests (2 files):**
- `DeviceManagementTest.php` - Feature tests
- `DeviceServiceTest.php` - Unit tests

### How It All Works Together

1. **User interacts** with views (Blade files)
2. **Routes** map URLs to controller methods
3. **Controller** handles requests, validates, authorizes
4. **Service** contains business logic (MAC normalization, statistics)
5. **Model** interacts with database
6. **Policy** ensures security (users only manage own devices)
7. **Network Service** applies network-level blocking

### Security Features

- ✅ Authorization (users can only manage own devices)
- ✅ Validation (invalid data rejected)
- ✅ MAC address format validation
- ✅ Unique MAC address enforcement
- ✅ Input sanitization

### Testing

- ✅ 45 tests covering all functionality
- ✅ All tests passing
- ✅ Compatible with SQLite (testing) and MariaDB (production)

---

## Next Steps

After Device Management (TODO 18), the next TODO is:
- **TODO 19:** Website Management (BlockedWebsiteController, FlaggedWebsiteController)

This will build on Device Management to allow parents to block and flag specific websites for their child's devices.

---

## Questions?

If you have questions about any part of the Device Management system, refer to:
- Individual file comments (extensive documentation in each file)
- Test files (show how features are used)
- This documentation

**Remember:** The system follows Laravel conventions, so understanding Laravel basics (MVC, routing, validation) will help you understand this implementation better.

