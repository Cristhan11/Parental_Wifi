# Website Management Implementation (TODO19)

**Date:** December 8, 2025  
**Status:** ✅ Complete  
**Feature:** URL-level, Domain-level, and App-level Website Blocking with DNS Enforcement

---

## Overview

This document explains the implementation of website blocking functionality (TODO19), including URL-level, domain-level, and app-level blocking with DNS enforcement via `dnsmasq`. The system allows parents to block websites for child devices, with support for blocking entire apps (like Facebook) by blocking all related domains.

### Key Concepts

- **URL-level blocking**: Block specific URLs (e.g., `https://facebook.com/page`)
- **Domain-level blocking**: Block entire domain + subdomains (e.g., `facebook.com` blocks `*.facebook.com`)
- **App-level blocking**: Block app with all related domains (e.g., Facebook app blocks `facebook.com`, `api.facebook.com`, `graph.facebook.com`, etc. - 30+ domains)
- **DNS enforcement**: Uses `dnsmasq` to redirect blocked domains to `127.0.0.1` (localhost), preventing access

---

## Implementation Steps

### Step 1: Database Migration - Add Blocking Fields

**File:** `database/migrations/2024_01_15_120010_create_blocked_websites_table.php`

**What Changed:**
- Added `block_type` enum field: `'url'`, `'domain'`, or `'app'`
- Added `block_subdomains` boolean field: Whether to block subdomains (e.g., `*.facebook.com`)
- Added `related_domains` JSON field: Array of related domains for app-level blocking

**Why:**
- Enables different blocking strategies (URL vs Domain vs App)
- Stores related domains for app-level blocking (e.g., all Facebook API domains)
- Supports wildcard subdomain blocking

**Database Schema:**
```php
$table->enum('block_type', ['url', 'domain', 'app'])->default('url');
$table->boolean('block_subdomains')->default(false);
$table->json('related_domains')->nullable();
```

---

### Step 2: Model - BlockedWebsite with Helper Methods

**File:** `app/Models/BlockedWebsite.php`

**Key Methods:**
- `isDomainBlock()`: Returns true if `block_type === 'domain'`
- `isAppBlock()`: Returns true if `block_type === 'app'`
- `getDomainsToBlock()`: Returns array of all domains to block (main domain + related domains)
- `shouldBlockSubdomains()`: Returns true if subdomains should be blocked

**Interconnection:**
- **Used by:** `BlockedWebsiteController`, `DomainBlockingService`
- **Relationships:** `belongsTo(Device::class)` - Each blocked website belongs to one device

**Example Usage:**
```php
$blocked = BlockedWebsite::find(1);
$domains = $blocked->getDomainsToBlock(); 
// Returns: ['facebook.com', 'api.facebook.com', 'graph.facebook.com', ...]
```

---

### Step 3: Service Layer - DomainBlockingService

**File:** `app/Services/DomainBlockingService.php`

**Key Responsibilities:**
1. **Detect Related Domains**: Auto-suggests related domains when blocking apps
2. **Block/Unblock Domains**: Manages DNS blocking via shell scripts
3. **Update dnsmasq Config**: Regenerates dnsmasq configuration files

**Key Methods:**
- `detectRelatedDomains($domain, $appName)`: Returns array of related domains for an app
- `blockDomainForDevice($blockedWebsite, $device)`: Blocks domain(s) for a device
- `unblockDomainForDevice($blockedWebsite, $device)`: Unblocks domain(s) for a device
- `updateDnsmasqBlocklist($device)`: Regenerates dnsmasq config file from database

**App Domain Mappings:**
- Predefined array `$appDomainMappings` contains known apps and their related domains
- Example: `'facebook.com' => ['api.facebook.com', 'graph.facebook.com', ...]` (30+ domains)
- Used to auto-suggest domains when parent blocks an app

**Interconnection:**
- **Uses:** `ScriptExecutor` for secure shell script execution
- **Called by:** `BlockedWebsiteController` when creating/updating/deleting blocked websites
- **Works with:** Shell scripts (`block_domain.sh`, `update_dnsmasq_blocklist.sh`)

**Data Flow:**
```
BlockedWebsiteController 
  → DomainBlockingService 
    → ScriptExecutor 
      → Shell Scripts 
        → dnsmasq Config Files
```

---

### Step 4: Shell Scripts - DNS Enforcement

**Files:**
- `scripts/block_domain.sh`: Adds domain to dnsmasq config (deprecated - now handled by `update_dnsmasq_blocklist.sh`)
- `scripts/unblock_domain.sh`: Removes domain from dnsmasq config (deprecated - now handled by `update_dnsmasq_blocklist.sh`)
- `scripts/update_dnsmasq_blocklist.sh`: **Main script** - Regenerates complete dnsmasq config from database

**How `update_dnsmasq_blocklist.sh` Works:**
1. Receives device MAC address as argument
2. Reads domains from stdin in format: `DOMAIN:BLOCK_SUBDOMAINS` (one per line)
3. Generates dnsmasq config file: `/etc/dnsmasq.d/blocked-domains-{MAC}.conf`
4. Reloads dnsmasq service to apply changes

**Config File Format:**
```bash
# Blocked domains for device E6:6A:8F:19:BE:B1
address=/facebook.com/127.0.0.1          # Main domain only
address=/.youtube.com/127.0.0.1           # All subdomains (leading dot)
address=/api.facebook.com/127.0.0.1      # Related domain
```

**Interconnection:**
- **Called by:** `DomainBlockingService` via `ScriptExecutor`
- **Receives input:** Via stdin from `DomainBlockingService::updateDnsmasqBlocklist()`
- **Output:** dnsmasq config file in `/etc/dnsmasq.d/`
- **Effect:** dnsmasq redirects blocked domains to `127.0.0.1`

---

### Step 5: Controller - BlockedWebsiteController

**File:** `app/Http/Controllers/BlockedWebsiteController.php`

**Key Methods:**
- `index()`: List all blocked websites (filterable by device, block_type)
- `create()`: Show form to create new blocked website
- `store()`: Create new blocked website and apply DNS blocking
- `edit()`: Show form to edit existing blocked website
- `update()`: Update blocked website and regenerate DNS config
- `destroy()`: Delete blocked website and regenerate DNS config
- `suggestRelatedDomains()`: AJAX endpoint to suggest related domains for apps

**Interconnection:**
- **Uses:** `DomainBlockingService` for DNS blocking operations
- **Uses:** `BlockedWebsitePolicy` for authorization
- **Uses:** `StoreBlockedWebsiteRequest` / `UpdateBlockedWebsiteRequest` for validation
- **Renders:** Views in `resources/views/blocked-websites/`

**Data Flow (Create Blocked Website):**
```
User submits form
  → BlockedWebsiteController::store()
    → Validates request (StoreBlockedWebsiteRequest)
    → Creates BlockedWebsite record in database
    → Calls DomainBlockingService::blockDomainForDevice()
      → Calls DomainBlockingService::updateDnsmasqBlocklist()
        → Calls ScriptExecutor::execute('update_dnsmasq_blocklist.sh')
          → Shell script generates dnsmasq config
            → dnsmasq reloads and applies blocking
```

---

### Step 6: Views - User Interface

**Files:**
- `resources/views/blocked-websites/index.blade.php`: List all blocked websites
- `resources/views/blocked-websites/create.blade.php`: Form to create blocked website
- `resources/views/blocked-websites/edit.blade.php`: Form to edit blocked website

**Key Features:**
- **Block Type Selection**: Radio buttons for URL/Domain/App
- **Related Domains Suggestion**: AJAX call to `suggestRelatedDomains()` when "App" is selected
- **Subdomain Blocking**: Checkbox to enable subdomain blocking
- **Block Type Indicators**: Visual badges showing block type (URL/Domain/App)

**Interconnection:**
- **Rendered by:** `BlockedWebsiteController`
- **AJAX calls:** `BlockedWebsiteController::suggestRelatedDomains()` for related domains
- **Form submits to:** `BlockedWebsiteController::store()` or `update()`

---

### Step 7: ScriptExecutor - Secure Script Execution

**File:** `app/Services/ScriptExecutor.php`

**Key Features:**
- Whitelists allowed scripts (security)
- Validates script paths
- Sanitizes arguments (prevents command injection)
- Supports stdin input (for `update_dnsmasq_blocklist.sh`)
- Handles errors and logging

**Interconnection:**
- **Used by:** `DomainBlockingService` to execute shell scripts
- **Executes:** All scripts in `scripts/` directory
- **Security:** Prevents unauthorized script execution

---

## File Interconnection Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interface (Views)                    │
│  resources/views/blocked-websites/*.blade.php               │
└───────────────────────┬─────────────────────────────────────┘
                        │ HTTP Request
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              BlockedWebsiteController                       │
│  - index(), create(), store(), edit(), update(), destroy()  │
└───────────────┬───────────────────────┬─────────────────────┘
                │                       │
                │ Uses                 │ Uses
                ▼                       ▼
┌──────────────────────────┐  ┌──────────────────────────┐
│  DomainBlockingService    │  │  BlockedWebsitePolicy     │
│  - detectRelatedDomains() │  │  - Authorization checks  │
│  - blockDomainForDevice() │  └──────────────────────────┘
│  - updateDnsmasqBlocklist│
└───────────────┬──────────┘
                │ Uses
                ▼
┌──────────────────────────┐
│  ScriptExecutor          │
│  - execute()            │
│  - Validates & executes  │
└───────────────┬──────────┘
                │ Executes
                ▼
┌──────────────────────────┐
│  Shell Scripts           │
│  - update_dnsmasq_       │
│    blocklist.sh          │
└───────────────┬──────────┘
                │ Generates
                ▼
┌──────────────────────────┐
│  dnsmasq Config Files    │
│  /etc/dnsmasq.d/         │
│  blocked-domains-*.conf  │
└───────────────┬──────────┘
                │ Applied by
                ▼
┌──────────────────────────┐
│  dnsmasq Service         │
│  - Redirects blocked    │
│    domains to 127.0.0.1  │
└──────────────────────────┘
```

---

## Data Flow: Creating a Blocked Website

### 1. User Action
- Parent navigates to `/blocked-websites/create`
- Selects device, enters domain (e.g., `facebook.com`)
- Selects block type: **App**
- Checks "Block Subdomains"
- Clicks "Block Website"

### 2. Controller Processing
```php
BlockedWebsiteController::store()
  ├─ Validates request (StoreBlockedWebsiteRequest)
  ├─ Detects related domains (DomainBlockingService::detectRelatedDomains())
  │   └─ Returns: ['api.facebook.com', 'graph.facebook.com', ...] (30+ domains)
  ├─ Creates BlockedWebsite record in database
  │   └─ Stores: domain, block_type='app', related_domains=[...], block_subdomains=true
  └─ Calls DomainBlockingService::blockDomainForDevice()
```

### 3. Service Layer Processing
```php
DomainBlockingService::blockDomainForDevice()
  └─ Calls DomainBlockingService::updateDnsmasqBlocklist()
      ├─ Retrieves all BlockedWebsite records for device
      ├─ Formats domains for script: "facebook.com:1\napi.facebook.com:0\n..."
      └─ Calls ScriptExecutor::execute('update_dnsmasq_blocklist.sh', [MAC], $stdin)
```

### 4. Script Execution
```bash
ScriptExecutor::execute()
  ├─ Validates script path
  ├─ Sanitizes arguments (MAC address)
  ├─ Executes: ./scripts/update_dnsmasq_blocklist.sh E6:6A:8F:19:BE:B1
  └─ Pipes stdin: "facebook.com:1\napi.facebook.com:0\n..."
```

### 5. Shell Script Processing
```bash
update_dnsmasq_blocklist.sh
  ├─ Reads stdin (domains)
  ├─ Generates config file: /etc/dnsmasq.d/blocked-domains-E6:6A:8F:19:BE:B1.conf
  │   └─ Contains: address=/.facebook.com/127.0.0.1 (subdomains)
  │                 address=/api.facebook.com/127.0.0.1
  │                 ... (30+ domains)
  └─ Reloads dnsmasq: sudo systemctl restart dnsmasq
```

### 6. DNS Enforcement
- dnsmasq reads config file
- When device queries `facebook.com` → Returns `127.0.0.1` (blocked)
- When device queries `api.facebook.com` → Returns `127.0.0.1` (blocked)
- Device cannot connect (127.0.0.1 is not the real server)
- **Result:** Facebook app is effectively blocked

---

## Key Design Decisions

### 1. Why Regenerate Config File Instead of Incremental Updates?

**Decision:** Use `update_dnsmasq_blocklist.sh` to regenerate entire config from database

**Reason:**
- Ensures config file always matches database state
- Prevents inconsistencies (e.g., config file has domains not in database)
- Simpler logic (one script handles all cases)
- Atomic operation (regenerate entire file, then reload)

**Alternative (Rejected):**
- Incremental updates (`block_domain.sh` adds one domain, `unblock_domain.sh` removes one)
- Problem: Risk of config file and database getting out of sync

### 2. Why Pass Domains via stdin Instead of Command Arguments?

**Decision:** Pass domains via stdin to `update_dnsmasq_blocklist.sh`

**Reason:**
- Can handle many domains (30+ for apps) without command-line length limits
- More secure (no shell escaping issues)
- Cleaner interface (script reads from stdin)

**Implementation:**
```php
// DomainBlockingService formats domains
$domainsInput = "facebook.com:1\napi.facebook.com:0\n...";

// ScriptExecutor pipes stdin
ScriptExecutor::execute('update_dnsmasq_blocklist.sh', [$mac], $domainsInput);
```

### 3. Why Predefined App Domain Mappings?

**Decision:** Store app domain mappings in `DomainBlockingService::$appDomainMappings`

**Reason:**
- Parents don't know all domains an app uses (e.g., Facebook uses 30+ domains)
- Auto-suggestion improves user experience
- Can be expanded as new apps are identified

**Alternative (Rejected):**
- Dynamic domain discovery (DNS queries, network scanning)
- Problem: Unreliable, slow, may miss domains

### 4. Why Use dnsmasq Instead of iptables?

**Decision:** Use DNS blocking (dnsmasq) for domain/app blocking

**Reason:**
- Works for both web browsers AND mobile apps (apps also use DNS)
- Simpler configuration (one DNS redirect vs. many iptables rules)
- Per-device blocking via separate config files

**iptables Alternative:**
- Would require blocking by IP address (domains change IPs)
- More complex (need to track IP changes)
- Less effective for mobile apps

---

## Testing & Verification

### Manual Testing
See `docs/DOMAIN_BLOCKING_TESTING_GUIDE.md` for detailed testing procedures.

**Key Tests:**
1. ✅ Basic domain blocking (single domain)
2. ✅ Subdomain blocking (wildcard pattern)
3. ✅ App-level blocking (30+ domains for Facebook)
4. ✅ Unblocking (removes from config, domain accessible again)

### Verification Commands
```bash
# Check config file exists
ls -la /etc/dnsmasq.d/blocked-domains-*.conf

# View config file content
sudo cat /etc/dnsmasq.d/blocked-domains-E6:6A:8F:19:BE:B1.conf

# Test DNS resolution (should return 127.0.0.1 if blocked)
dig @127.0.0.1 facebook.com +short

# Check dnsmasq logs
sudo journalctl -u dnsmasq -f | grep facebook
```

---

## Summary

The website management system (TODO19) provides comprehensive blocking capabilities:

1. **Database Layer**: Stores blocking configuration (block_type, related_domains, block_subdomains)
2. **Model Layer**: `BlockedWebsite` model with helper methods (`getDomainsToBlock()`, etc.)
3. **Service Layer**: `DomainBlockingService` handles domain detection and DNS blocking
4. **Controller Layer**: `BlockedWebsiteController` provides CRUD operations and UI
5. **Script Layer**: Shell scripts generate dnsmasq config files
6. **DNS Layer**: dnsmasq enforces blocking by redirecting domains to 127.0.0.1

**Result:** Parents can effectively block websites and apps for child devices, with support for URL-level, domain-level, and app-level blocking via DNS enforcement.

---

## Related Documentation

- `docs/DOMAIN_BLOCKING_TESTING_GUIDE.md` - Manual testing procedures
- `docs/DEVICE_MANAGEMENT_IMPLEMENTATION.md` - Device management system
- `docs/scope.md` - Overall project scope and architecture
