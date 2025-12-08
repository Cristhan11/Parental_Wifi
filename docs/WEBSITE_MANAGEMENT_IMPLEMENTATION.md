# Website Management Implementation Documentation

## Overview

This document explains the complete implementation of the Website Management system (TODO #19) for the Parental WiFi Control System. This system allows parents to block and flag websites for their children's devices, with support for URL-level, domain-level, and app-level blocking using DNS enforcement.

## Table of Contents

1. [Why Domain/App-Level Blocking?](#why-domainapp-level-blocking)
2. [System Architecture](#system-architecture)
3. [File Structure and Interconnections](#file-structure-and-interconnections)
4. [Database Schema](#database-schema)
5. [Service Layer](#service-layer)
6. [Controllers and Routes](#controllers-and-routes)
7. [Views and User Interface](#views-and-user-interface)
8. [Security and Authorization](#security-and-authorization)
9. [DNS Blocking Mechanism](#dns-blocking-mechanism)
10. [Data Flow Examples](#data-flow-examples)

---

## Why Domain/App-Level Blocking?

### The Problem

Mobile apps (like Facebook, Instagram, TikTok) don't just use one domain. They make API calls to multiple domains:

- **Facebook app** uses: `facebook.com`, `api.facebook.com`, `graph.facebook.com`, `m.facebook.com`, `connect.facebook.com`, `static.xx.fbcdn.net`
- **Instagram app** uses: `instagram.com`, `api.instagram.com`, `i.instagram.com`, `graph.instagram.com`
- **TikTok app** uses: `tiktok.com`, `api.tiktok.com`, `m.tiktok.com`, `log.tiktok.com`

If you only block `facebook.com`, the Facebook app will still work because it uses `api.facebook.com` and other domains for API calls.

### The Solution

**Domain-level blocking** uses DNS (dnsmasq) to redirect ALL requests to blocked domains to `127.0.0.1` (localhost). This works for both:
- **Web browsers** (when child visits facebook.com in browser)
- **Mobile apps** (when child uses Facebook app - all API calls are blocked)

**App-level blocking** automatically detects and blocks all related domains for an app, so parents don't have to manually find and add all domains.

### How DNS Blocking Works

1. Child device tries to access `facebook.com`
2. Device asks dnsmasq (DNS server on Raspberry Pi): "What's the IP for facebook.com?"
3. dnsmasq checks if domain is in blocklist
4. If blocked, dnsmasq returns `127.0.0.1` instead of real IP
5. Device can't connect because `127.0.0.1` is not the real server
6. This blocks both web browsers AND mobile apps

---

## System Architecture

### Three Levels of Blocking

1. **URL-Level Blocking**: Block specific URLs (e.g., `https://facebook.com/page`)
   - Use case: Block a specific page but allow the rest of the site
   - Implementation: Stores URL in database, extracts domain for DNS blocking

2. **Domain-Level Blocking**: Block entire domain + optionally subdomains (e.g., `facebook.com` blocks `*.facebook.com`)
   - Use case: Block entire website (all pages)
   - Implementation: Stores domain, blocks via DNS, optionally blocks subdomains

3. **App-Level Blocking**: Block app with all related domains (e.g., Facebook app blocks `facebook.com`, `api.facebook.com`, etc.)
   - Use case: Block mobile apps completely
   - Implementation: Stores main domain + related domains array, blocks all via DNS

### Component Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interface Layer                     │
│  (Blade Views: index, create, edit)                         │
│  - Blocked Websites UI (with blocking type selection)      │
│  - Flagged Websites UI (simpler, no blocking)             │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│                    Controller Layer                         │
│  - BlockedWebsiteController (CRUD + DNS enforcement)       │
│  - FlaggedWebsiteController (CRUD only)                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│                    Service Layer                             │
│  - DomainBlockingService (DNS blocking logic)               │
│  - ScriptExecutor (secure script execution)                │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│                    Shell Scripts Layer                      │
│  - block_domain.sh (add domain to dnsmasq)                 │
│  - unblock_domain.sh (remove domain from dnsmasq)          │
│  - update_dnsmasq_blocklist.sh (regenerate config)         │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│                    System Layer                              │
│  - dnsmasq (DNS server on Raspberry Pi)                    │
│  - /etc/dnsmasq.d/blocked-domains-{MAC}.conf (config files)│
└─────────────────────────────────────────────────────────────┘
```

---

## File Structure and Interconnections

### Database Layer

#### Migration: `database/migrations/2025_12_08_000000_add_domain_blocking_fields_to_blocked_websites_table.php`

**What**: Adds three new fields to `blocked_websites` table:
- `block_type`: Enum ('url', 'domain', 'app') - Type of blocking
- `block_subdomains`: Boolean - Whether to block subdomains
- `related_domains`: JSON - Array of related domains for app-level blocking

**Why**: 
- Existing schema only supported URL-level blocking
- Need to distinguish between URL, domain, and app blocking
- Need to store related domains for app-level blocking
- Need to control subdomain blocking

**Interconnections**:
- Used by: `BlockedWebsite` model
- Updates: `blocked_websites` table
- Affects: All queries on `blocked_websites` table

#### Model: `app/Models/BlockedWebsite.php`

**What**: Eloquent model for blocked websites with helper methods

**Key Methods**:
- `isDomainBlock()`: Check if this is domain-level blocking
- `isAppBlock()`: Check if this is app-level blocking
- `getDomainsToBlock()`: Return array of all domains to block (main + related)
- `shouldBlockSubdomains()`: Check if subdomains should be blocked

**Why These Methods**:
- `getDomainsToBlock()`: Used by `DomainBlockingService` to generate dnsmasq config
- `shouldBlockSubdomains()`: Used to determine if wildcard DNS rules needed
- Type checking methods: Used in views to display appropriate UI

**Interconnections**:
- Used by: `BlockedWebsiteController`, `DomainBlockingService`
- Uses: `Device` model (belongsTo relationship)
- Provides: Helper methods for domain blocking logic

### Service Layer

#### Service: `app/Services/DomainBlockingService.php`

**What**: Central service for domain/app blocking logic and DNS enforcement

**Key Methods**:

1. **`detectRelatedDomains(string $domain, ?string $appName): array`**
   - **What**: Detects related domains for apps (e.g., Facebook → all Facebook API domains)
   - **Why**: Apps use multiple domains that parents may not know about. System automatically suggests all related domains.
   - **How**: Looks up domain in predefined `$appDomainMappings` array
   - **Used by**: `BlockedWebsiteController::store()`, `BlockedWebsiteController::suggestRelatedDomains()`

2. **`blockDomainForDevice(BlockedWebsite $blockedWebsite, Device $device): bool`**
   - **What**: Blocks domain(s) for a device via DNS
   - **Why**: Need to enforce blocking at network level (DNS) not just database
   - **How**: Gets all domains to block, calls `block_domain.sh` for each, updates dnsmasq config
   - **Used by**: `BlockedWebsiteController::store()`

3. **`unblockDomainForDevice(BlockedWebsite $blockedWebsite, Device $device): bool`**
   - **What**: Unblocks domain(s) for a device
   - **Why**: When parent removes blocked website, need to remove DNS blocking
   - **How**: Calls `unblock_domain.sh` for each domain, updates dnsmasq config
   - **Used by**: `BlockedWebsiteController::destroy()`

4. **`updateDnsmasqBlocklist(Device $device): bool`**
   - **What**: Regenerates complete dnsmasq blocklist for device from database
   - **Why**: Ensures dnsmasq config matches database state (handles bulk updates, deletions)
   - **How**: Calls `update_dnsmasq_blocklist.sh` script
   - **Used by**: `blockDomainForDevice()`, `unblockDomainForDevice()`, `BlockedWebsiteController::update()`

5. **`getBlockedDomainsForDevice(Device $device): array`**
   - **What**: Returns array of all domains blocked for device
   - **Why**: Used by `update_dnsmasq_blocklist.sh` to generate config
   - **How**: Queries database, collects all domains (main + related)
   - **Used by**: `update_dnsmasq_blocklist.sh` (via Laravel command or JSON file)

6. **`normalizeDomain(string $url): string`**
   - **What**: Extracts clean domain from URL
   - **Why**: URLs come in many formats, need consistent domain format for DNS blocking
   - **How**: Removes protocol, path, www prefix, converts to lowercase
   - **Used by**: `BlockedWebsiteController::store()`, `FlaggedWebsiteController::store()`

**Predefined App Mappings**:
- Stores common apps and their related domains in `$appDomainMappings` array
- Example: `'facebook.com' => ['api.facebook.com', 'graph.facebook.com', ...]`
- Allows system to auto-suggest related domains when blocking apps

**Interconnections**:
- Uses: `ScriptExecutor` (injected via constructor)
- Uses: `BlockedWebsite` model (for helper methods)
- Uses: `Device` model (for device information)
- Used by: `BlockedWebsiteController`, `FlaggedWebsiteController`

### Shell Scripts Layer

#### Script: `scripts/block_domain.sh`

**What**: Adds domain to dnsmasq blocklist for specific device

**Usage**: `./block_domain.sh <DOMAIN> <MAC_ADDRESS> [BLOCK_SUBDOMAINS]`

**What It Does**:
1. Validates domain and MAC address formats
2. Normalizes MAC address to standard format
3. Adds domain to `/etc/dnsmasq.d/blocked-domains-{MAC}.conf`
4. Handles subdomain blocking (wildcard patterns)
5. Restarts dnsmasq service

**Why Per-Device Config Files**:
- Each device can have different blocked domains
- Config file format: `blocked-domains-{MAC_ADDRESS}.conf`
- Allows granular control per device

**dnsmasq Config Format**:
```
address=/facebook.com/127.0.0.1          # Block main domain
address=/.facebook.com/127.0.0.1        # Block domain + all subdomains (note leading dot)
```

**Interconnections**:
- Called by: `DomainBlockingService::blockDomainForDevice()`
- Uses: `ScriptExecutor` for secure execution
- Modifies: `/etc/dnsmasq.d/blocked-domains-{MAC}.conf`
- Restarts: `dnsmasq` systemd service

#### Script: `scripts/unblock_domain.sh`

**What**: Removes domain from dnsmasq blocklist for specific device

**Usage**: `./unblock_domain.sh <DOMAIN> <MAC_ADDRESS>`

**What It Does**:
1. Validates inputs
2. Removes domain from per-device config file
3. Removes config file if empty
4. Restarts dnsmasq service

**Interconnections**:
- Called by: `DomainBlockingService::unblockDomainForDevice()`
- Uses: `ScriptExecutor` for secure execution
- Modifies: `/etc/dnsmasq.d/blocked-domains-{MAC}.conf`

#### Script: `scripts/update_dnsmasq_blocklist.sh`

**What**: Regenerates complete dnsmasq blocklist for device from database

**Usage**: `./update_dnsmasq_blocklist.sh <MAC_ADDRESS>`

**Input Format** (from stdin):
```
facebook.com:1
api.facebook.com:0
instagram.com:1
```
Format: `DOMAIN:BLOCK_SUBDOMAINS` (1 = block subdomains, 0 = don't block)

**What It Does**:
1. Reads domains from stdin (one per line)
2. Generates complete config file with all domains
3. Handles subdomain blocking (wildcard patterns)
4. Restarts dnsmasq service

**Why This Script**:
- Database is source of truth for blocked domains
- Ensures dnsmasq config matches database state
- Handles bulk updates, deletions, complex scenarios
- Called after creating/updating/deleting blocked websites

**Interconnections**:
- Called by: `DomainBlockingService::updateDnsmasqBlocklist()`
- Input: Domains from database (via Laravel command or JSON file)
- Output: Complete dnsmasq config file

### Controller Layer

#### Controller: `app/Http/Controllers/BlockedWebsiteController.php`

**What**: Full CRUD controller for blocked websites with DNS enforcement

**Key Methods**:

1. **`index(Request $request): View`**
   - **What**: Lists all blocked websites for user's devices
   - **Why**: Parents need to see what websites are blocked
   - **Features**: Filterable by device, block_type, searchable
   - **Returns**: View with paginated blocked websites

2. **`create(): View`**
   - **What**: Shows form for creating new blocked website
   - **Why**: Parents need UI to block websites
   - **Features**: Blocking type selection, related domains UI
   - **Returns**: Create form view

3. **`store(StoreBlockedWebsiteRequest $request): RedirectResponse`**
   - **What**: Creates new blocked website and enforces DNS blocking
   - **Why**: Need to save to database AND enforce at network level
   - **Process**:
     1. Validates request (form request)
     2. Extracts domain from URL if needed
     3. Detects related domains if app-level blocking
     4. Creates `BlockedWebsite` record
     5. Calls `DomainBlockingService::blockDomainForDevice()` to enforce DNS blocking
   - **Returns**: Redirect to index with success message

4. **`edit(BlockedWebsite $blockedWebsite): View`**
   - **What**: Shows form for editing existing blocked website
   - **Why**: Parents need to update blocked websites
   - **Returns**: Edit form view (pre-filled with existing data)

5. **`update(UpdateBlockedWebsiteRequest $request, BlockedWebsite $blockedWebsite): RedirectResponse`**
   - **What**: Updates blocked website and refreshes DNS blocking if domain changed
   - **Why**: If domain changes, need to update DNS blocking
   - **Process**:
     1. Validates request
     2. Updates `BlockedWebsite` record
     3. If domain changed, calls `DomainBlockingService::updateDnsmasqBlocklist()`
   - **Returns**: Redirect to index with success message

6. **`destroy(BlockedWebsite $blockedWebsite): RedirectResponse`**
   - **What**: Deletes blocked website and removes DNS blocking
   - **Why**: Need to remove from database AND remove DNS blocking
   - **Process**:
     1. Checks authorization (policy)
     2. Calls `DomainBlockingService::unblockDomainForDevice()` to remove DNS blocking
     3. Deletes `BlockedWebsite` record
   - **Returns**: Redirect to index with success message

7. **`suggestRelatedDomains(Request $request): JsonResponse`** (AJAX endpoint)
   - **What**: Returns JSON array of related domains for app
   - **Why**: Frontend needs to show suggested domains when blocking apps
   - **Process**: Calls `DomainBlockingService::detectRelatedDomains()`
   - **Returns**: JSON with `domains` array
   - **Used by**: Frontend Alpine.js code in create/edit forms

8. **`bulkImport(Request $request): RedirectResponse`**
   - **What**: Imports multiple blocked websites from CSV/JSON file
   - **Why**: Parents may want to bulk import blocked websites
   - **Status**: TODO - Implementation placeholder

9. **`bulkExport(Request $request)`**
   - **What**: Exports blocked websites to CSV/JSON
   - **Why**: Parents may want to export/backup blocked websites
   - **Returns**: CSV/JSON download

**Interconnections**:
- Uses: `DomainBlockingService` (injected via constructor)
- Uses: `StoreBlockedWebsiteRequest`, `UpdateBlockedWebsiteRequest` (form requests)
- Uses: `BlockedWebsitePolicy` (authorization)
- Uses: `BlockedWebsite` model (database operations)
- Uses: `Device` model (device information)

#### Controller: `app/Http/Controllers/FlaggedWebsiteController.php`

**What**: CRUD controller for flagged websites (simpler than blocked - no DNS blocking)

**Key Methods**:
- `index()`: List flagged websites
- `create()`: Show create form
- `store()`: Create flagged website (extracts domain from URL)
- `edit()`: Show edit form
- `update()`: Update flagged website
- `destroy()`: Delete flagged website

**Why Simpler**:
- Flagged websites are monitored (not blocked)
- No DNS blocking needed
- No related domains needed
- Just URL validation and domain extraction

**Interconnections**:
- Uses: `DomainBlockingService` (only for `normalizeDomain()` method)
- Uses: `StoreFlaggedWebsiteRequest`, `UpdateFlaggedWebsiteRequest` (form requests)
- Uses: `FlaggedWebsitePolicy` (authorization)
- Uses: `FlaggedWebsite` model (database operations)

### Form Requests Layer

#### Form Request: `app/Http/Requests/StoreBlockedWebsiteRequest.php`

**What**: Validation rules for creating blocked websites

**Key Rules**:
- `device_id`: Required, must exist, user must own device
- `url`: Required if `block_type = 'url'`, valid URL format
- `domain`: Required if `block_type = 'domain'` or `'app'`, valid domain format
- `block_type`: Required, must be one of: 'url', 'domain', 'app'
- `block_subdomains`: Optional boolean
- `related_domains`: Optional array, each element valid domain format (if `block_type = 'app'`)
- `reason`: Optional, string, max 500 characters

**Why These Rules**:
- Conditional validation: URL required for URL blocking, domain required for domain/app blocking
- Domain format validation: Prevents invalid domains from being stored
- Device ownership check: Prevents blocking websites for other parents' devices

**Interconnections**:
- Used by: `BlockedWebsiteController::store()`
- Validates: Data before it reaches controller

#### Form Request: `app/Http/Requests/UpdateBlockedWebsiteRequest.php`

**What**: Validation rules for updating blocked websites (same as Store, but for updates)

**Interconnections**:
- Used by: `BlockedWebsiteController::update()`

#### Form Requests: `StoreFlaggedWebsiteRequest.php`, `UpdateFlaggedWebsiteRequest.php`

**What**: Validation rules for flagged websites (simpler - just device_id, url, reason)

**Interconnections**:
- Used by: `FlaggedWebsiteController::store()`, `FlaggedWebsiteController::update()`

### Policy Layer

#### Policy: `app/Policies/BlockedWebsitePolicy.php`

**What**: Authorization policy ensuring users can only manage blocked websites for their own devices

**Key Methods**:
- `viewAny()`: Can view blocked websites list (all authenticated users)
- `view()`: Can view specific blocked website (if owns device)
- `create()`: Can create blocked websites (all authenticated users - device ownership checked in form request)
- `update()`: Can update blocked website (if owns device)
- `delete()`: Can delete blocked website (if owns device)

**Authorization Check**: `$blockedWebsite->device->user_id === $user->id`

**Why This Policy**:
- Security: Prevents parents from blocking/unblocking websites for other parents' devices
- Data privacy: Ensures parents only see their own devices' blocked websites

**Interconnections**:
- Used by: `BlockedWebsiteController` (via `$this->authorize()`)
- Checks: Device ownership via `BlockedWebsite->device->user_id`

#### Policy: `app/Policies/FlaggedWebsitePolicy.php`

**What**: Authorization policy for flagged websites (same structure as BlockedWebsitePolicy)

**Interconnections**:
- Used by: `FlaggedWebsiteController` (via `$this->authorize()`)

### View Layer

#### Views: `resources/views/blocked-websites/`

**Files**:
- `index.blade.php`: List view with filters and table
- `create.blade.php`: Create form with blocking type selection and Alpine.js
- `edit.blade.php`: Edit form (similar to create, pre-filled)

**Key Features**:
- **Blocking Type Selection**: Radio buttons for URL/Domain/App (Alpine.js shows/hides fields)
- **Related Domains UI**: Alpine.js displays suggested domains, allows adding/removing
- **AJAX Domain Suggestion**: Calls `suggestRelatedDomains()` endpoint when domain/app name entered
- **Filtering**: Filter by device, block_type, search
- **Block Type Badges**: Visual indicators (URL=blue, Domain=green, App=purple)

**Alpine.js Integration**:
- `x-data="blockedWebsiteForm()"`: Manages form state
- `x-show`: Shows/hides fields based on blocking type
- `x-on:blur="suggestDomains()"`: Triggers domain suggestion when domain/app name entered
- `x-for`: Loops through related domains for display

**Interconnections**:
- Uses: `BlockedWebsiteController` methods (via routes)
- Displays: Data from `BlockedWebsite` model
- Calls: AJAX endpoint `suggestRelatedDomains()` for domain suggestions

#### Views: `resources/views/flagged-websites/`

**Files**:
- `index.blade.php`: List view with filters
- `create.blade.php`: Create form (simpler - just device, URL, reason)
- `edit.blade.php`: Edit form

**Key Features**:
- Simpler UI (no blocking type selection, no related domains)
- Info banner explaining that flagged sites are monitored but not blocked
- Filtering by device and search

**Interconnections**:
- Uses: `FlaggedWebsiteController` methods (via routes)
- Displays: Data from `FlaggedWebsite` model

### Routes Layer

#### Routes: `routes/web.php`

**Blocked Websites Routes**:
```php
Route::prefix('blocked-websites')->name('blocked-websites.')->group(function () {
    Route::get('/', [BlockedWebsiteController::class, 'index']);
    Route::get('/create', [BlockedWebsiteController::class, 'create']);
    Route::post('/', [BlockedWebsiteController::class, 'store']);
    Route::get('/{blockedWebsite}/edit', [BlockedWebsiteController::class, 'edit']);
    Route::put('/{blockedWebsite}', [BlockedWebsiteController::class, 'update']);
    Route::delete('/{blockedWebsite}', [BlockedWebsiteController::class, 'destroy']);
    Route::post('/suggest-domains', [BlockedWebsiteController::class, 'suggestRelatedDomains']);
    Route::post('/bulk-import', [BlockedWebsiteController::class, 'bulkImport']);
    Route::get('/export', [BlockedWebsiteController::class, 'bulkExport']);
});
```

**Flagged Websites Routes**:
```php
Route::prefix('flagged-websites')->name('flagged-websites.')->group(function () {
    Route::get('/', [FlaggedWebsiteController::class, 'index']);
    Route::get('/create', [FlaggedWebsiteController::class, 'create']);
    Route::post('/', [FlaggedWebsiteController::class, 'store']);
    Route::get('/{flaggedWebsite}/edit', [FlaggedWebsiteController::class, 'edit']);
    Route::put('/{flaggedWebsite}', [FlaggedWebsiteController::class, 'update']);
    Route::delete('/{flaggedWebsite}', [FlaggedWebsiteController::class, 'destroy']);
});
```

**Route Model Binding**:
- `{blockedWebsite}` and `{flaggedWebsite}` parameters automatically resolve to model instances
- Laravel automatically calls policies for authorization

**Interconnections**:
- Maps: URLs to controller methods
- Uses: Route model binding for automatic model resolution
- Protected by: `auth` middleware (requires login)

### ScriptExecutor Integration

#### Update: `app/Services/ScriptExecutor.php`

**What**: Added new shell scripts to whitelist

**Scripts Added**:
- `block_domain.sh`
- `unblock_domain.sh`
- `update_dnsmasq_blocklist.sh`

**Why Whitelist**:
- Security: Only pre-approved scripts can be executed
- Prevents: Command injection attacks
- Validates: Script paths and arguments

**Interconnections**:
- Used by: `DomainBlockingService` (for executing shell scripts)
- Validates: Script names before execution

---

## Database Schema

### Table: `blocked_websites`

**Existing Fields**:
- `id`: Primary key
- `device_id`: Foreign key to `devices` table
- `url`: Full URL (for URL-level blocking)
- `domain`: Domain extracted from URL
- `reason`: Optional reason for blocking
- `created_at`, `updated_at`: Timestamps

**New Fields** (added by migration):
- `block_type`: Enum('url', 'domain', 'app') - Type of blocking
- `block_subdomains`: Boolean - Whether to block subdomains
- `related_domains`: JSON - Array of related domains for app-level blocking

**Indexes**:
- `device_id`: For fast filtering by device
- `domain`: For fast filtering by domain
- `block_type`: For fast filtering by block type
- Unique constraint: `['device_id', 'domain', 'block_type']` - Same domain can be blocked as URL and domain separately

**Relationships**:
- `belongsTo Device`: Each blocked website belongs to one device
- Device `hasMany BlockedWebsites`: Each device can have many blocked websites

### Table: `flagged_websites`

**Fields**:
- `id`: Primary key
- `device_id`: Foreign key to `devices` table
- `url`: Full URL
- `domain`: Domain extracted from URL (auto-extracted)
- `reason`: Optional reason for flagging
- `created_at`, `updated_at`: Timestamps

**No Changes**: Flagged websites table unchanged (no domain/app blocking needed)

---

## DNS Blocking Mechanism

### How It Works

1. **Parent blocks website** via UI
2. **Laravel creates** `BlockedWebsite` record in database
3. **DomainBlockingService** calls `block_domain.sh` script
4. **Script adds** domain to `/etc/dnsmasq.d/blocked-domains-{MAC}.conf`
5. **Script restarts** dnsmasq service
6. **dnsmasq intercepts** DNS queries from devices
7. **When device asks** "What's the IP for facebook.com?"
8. **dnsmasq checks** if domain is in blocklist
9. **If blocked**, returns `127.0.0.1` instead of real IP
10. **Device can't connect** because `127.0.0.1` is not the real server

### dnsmasq Config File Format

**Location**: `/etc/dnsmasq.d/blocked-domains-{MAC_ADDRESS}.conf`

**Format**:
```
# Blocked domains for device AA:BB:CC:DD:EE:FF
# Generated by update_dnsmasq_blocklist.sh
address=/facebook.com/127.0.0.1          # Block main domain only
address=/.facebook.com/127.0.0.1        # Block domain + all subdomains (note leading dot)
address=/api.facebook.com/127.0.0.1     # Block specific subdomain
```

**Per-Device Config Files**:
- Each device has its own config file (identified by MAC address)
- Allows different blocked domains for different devices
- Config files are auto-generated from database

### Subdomain Blocking

**Wildcard Pattern**: `address=/.domain.com/127.0.0.1`
- The leading dot (`.`) means "domain and all subdomains"
- Blocks: `domain.com`, `www.domain.com`, `api.domain.com`, `*.domain.com`

**Main Domain Only**: `address=/domain.com/127.0.0.1`
- No leading dot means "only main domain"
- Blocks: `domain.com` only
- Allows: `www.domain.com`, `api.domain.com`, etc.

---

## Data Flow Examples

### Example 1: Parent Blocks Facebook App

**Step 1: Parent fills form**
- Selects device: "John's iPhone"
- Selects blocking type: "App"
- Enters domain: "facebook.com"
- Enters app name: "Facebook"
- Clicks "Block Website"

**Step 2: Form submission**
- `BlockedWebsiteController::store()` receives request
- `StoreBlockedWebsiteRequest` validates data
- Controller calls `DomainBlockingService::detectRelatedDomains('facebook.com', 'Facebook')`
- Service returns: `['api.facebook.com', 'graph.facebook.com', 'm.facebook.com', ...]`
- Controller creates `BlockedWebsite` record with:
  - `block_type = 'app'`
  - `domain = 'facebook.com'`
  - `related_domains = ['api.facebook.com', 'graph.facebook.com', ...]`

**Step 3: DNS enforcement**
- Controller calls `DomainBlockingService::blockDomainForDevice($blockedWebsite, $device)`
- Service gets all domains: `['facebook.com', 'api.facebook.com', 'graph.facebook.com', ...]`
- For each domain, calls `block_domain.sh` script
- Scripts add domains to `/etc/dnsmasq.d/blocked-domains-{MAC}.conf`
- dnsmasq service restarted

**Step 4: Result**
- Facebook app blocked (all API calls fail)
- Web browser access to facebook.com blocked
- All related domains blocked

### Example 2: Parent Flags Instagram (Monitoring Only)

**Step 1: Parent fills form**
- Selects device: "Sarah's iPad"
- Enters URL: "https://instagram.com"
- Enters reason: "Monitor social media usage"
- Clicks "Flag Website"

**Step 2: Form submission**
- `FlaggedWebsiteController::store()` receives request
- `StoreFlaggedWebsiteRequest` validates data
- Controller calls `DomainBlockingService::normalizeDomain('https://instagram.com')`
- Returns: `'instagram.com'`
- Controller creates `FlaggedWebsite` record

**Step 3: Result**
- Instagram NOT blocked (still accessible)
- When child visits Instagram, it's logged in `BrowsingLog` table
- Parent notified (via WebSocket or email)
- No DNS blocking (flagged sites are allowed)

### Example 3: Parent Updates Blocked Website

**Step 1: Parent edits blocked website**
- Changes domain from "facebook.com" to "instagram.com"
- Changes block_type from "app" to "domain"
- Clicks "Update"

**Step 2: Form submission**
- `BlockedWebsiteController::update()` receives request
- Controller detects domain changed
- Updates `BlockedWebsite` record
- Calls `DomainBlockingService::updateDnsmasqBlocklist($device)`
- Service calls `update_dnsmasq_blocklist.sh` script
- Script regenerates complete config file from database
- dnsmasq service restarted

**Step 3: Result**
- Old domain (facebook.com) unblocked
- New domain (instagram.com) blocked
- dnsmasq config matches database state

---

## Security Considerations

### Input Validation

1. **Form Requests**: All user input validated before reaching controller
   - URL format validation
   - Domain format validation (regex)
   - Device ownership validation

2. **ScriptExecutor**: Shell script execution secured
   - Whitelist of allowed scripts
   - Path validation (prevents directory traversal)
   - Argument sanitization

3. **Authorization**: Policies ensure users can only manage their own devices
   - `BlockedWebsitePolicy`: Checks device ownership
   - `FlaggedWebsitePolicy`: Checks device ownership

### DNS Config Security

1. **File Permissions**: Config files readable by dnsmasq only
2. **Path Validation**: Scripts validate paths before writing
3. **Input Sanitization**: Domain names validated before adding to config

### Database Security

1. **SQL Injection**: Prevented by Eloquent ORM (parameterized queries)
2. **XSS**: Prevented by Blade template escaping
3. **CSRF**: Protected by Laravel CSRF tokens

---

## Testing Considerations

### Unit Tests Needed

1. **DomainBlockingService Tests**:
   - Test `detectRelatedDomains()` with various domains
   - Test `normalizeDomain()` with various URL formats
   - Test `getDomainsToBlock()` with different block types

2. **Model Tests**:
   - Test `BlockedWebsite` helper methods
   - Test `getDomainsToBlock()` for URL/domain/app blocks
   - Test `shouldBlockSubdomains()` logic

3. **Form Request Tests**:
   - Test validation rules
   - Test conditional validation (URL required for URL blocking, etc.)
   - Test device ownership validation

### Integration Tests Needed

1. **Controller Tests**:
   - Test CRUD operations
   - Test authorization (can't access other parents' blocked websites)
   - Test DNS blocking integration

2. **Shell Script Tests**:
   - Test script execution with valid inputs
   - Test script validation (invalid domain, invalid MAC address)
   - Test dnsmasq config generation

### Manual Testing on Raspberry Pi

1. **DNS Blocking Verification**:
   - Block a domain
   - Verify dnsmasq config file created correctly
   - Test from device: `nslookup blocked-domain.com` should return 127.0.0.1
   - Test app blocking: Verify app can't connect

2. **Subdomain Blocking Verification**:
   - Block domain with subdomains enabled
   - Verify wildcard pattern in config file
   - Test subdomain access (should be blocked)

3. **App Blocking Verification**:
   - Block Facebook app
   - Verify all related domains in config file
   - Test Facebook app (should be completely blocked)

---

## Future Enhancements

### Potential Improvements

1. **Bulk Import Implementation**:
   - Complete `bulkImport()` method in `BlockedWebsiteController`
   - Support CSV and JSON formats
   - Validate and import multiple blocked websites

2. **Advanced App Detection**:
   - Enhance `detectRelatedDomains()` with more apps
   - Allow parents to add custom app mappings
   - Use API to detect related domains dynamically

3. **Scheduled Blocking**:
   - Block websites only during certain times
   - Integrate with `DeviceSchedule` system

4. **Reporting**:
   - Show statistics: How many times blocked website accessed
   - Show flagged website visit history
   - Generate reports for parents

5. **Whitelist Override**:
   - Allow specific URLs to bypass domain blocking
   - Example: Block facebook.com but allow facebook.com/educational-page

---

## Summary

The Website Management system provides comprehensive website blocking and monitoring capabilities:

- **Three levels of blocking**: URL, Domain, and App-level
- **DNS enforcement**: Blocks at network level (works for browsers and apps)
- **Related domain detection**: Automatically suggests all domains for apps
- **Per-device blocking**: Each device can have different blocked websites
- **Security**: Authorization policies, input validation, secure script execution
- **User-friendly UI**: Alpine.js for dynamic features, Tailwind CSS for styling

All components are interconnected and work together to provide a complete website management solution for parents.

