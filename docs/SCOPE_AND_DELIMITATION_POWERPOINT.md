# Scope and Delimitation: System Architecture Implementation
## PowerPoint Presentation Content

---

## Slide 1: Title Slide
**Scope and Delimitation**
*How System Components Achieve Project Objectives*

Child-Centric Wi-Fi Monitoring and Control System with Learning Access Management and Automated Reporting

---

## Slide 2: Network Topology
**Network Architecture: eth0 and wlan0 Communication with Component Integration**

### Network Topology Diagram

```
┌───────────┐
│ 🌐 Internet│
└───────────┘
      │
      │ (Internet Connection)
      |
┌─────────────────────────────────────────────────────────────┐
│              ISP Modem/Router                               │
│                  (Home Network)                             │
└─────────────────────────────────────────────────────────────┘
      │
      │ (LAN Cable - Ethernet)
      │ (Pi's Internet Access)
      |
┌─────────────────────────────────────────────────────────────┐
│                    Raspberry Pi 4B                          │
│                                                             │
│  - Access Point (SSID: Parental_WiFi)                       │
│  - Firewall (iptables/nftables)                             │
│  - Captive Portal (NoDogSplash)                             │
│  - Web Application (Laravel)                                │
└─────────────────────────────────────────────────────────────┘
      │
      │ (WiFi - 802.11ac/ax)
      │ (Child Device Network)
      |
┌─────────────────────────────────────────────────────────────┐
│                    Child Device                             │
│              (Smartphone/Tablet/Laptop)                     │
└─────────────────────────────────────────────────────────────┘

            Figure 2.4-2 Network Topology Diagram
```

### Network Address Translation (NAT) with MASQUERADE

**How NAT Works:**
1. **Child Device** (192.168.4.x) sends request to internet
2. **wlan0 Interface** receives packet from child device
3. **iptables MASQUERADE** translates source IP:
   - Changes source IP from `192.168.4.x` to Pi's `eth0` IP
   - Maintains connection tracking for return traffic
4. **eth0 Interface** forwards packet to ISP router
5. **Return Traffic** flows back through NAT translation
6. **wlan0 Interface** delivers packet to correct child device

**iptables NAT Configuration:**
```bash
# Enable IP forwarding
echo 1 > /proc/sys/net/ipv4/ip_forward

# MASQUERADE rule (dynamic NAT)
iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE

# Allow forwarding between interfaces
iptables -A FORWARD -i wlan0 -o eth0 -j ACCEPT
iptables -A FORWARD -i eth0 -o wlan0 -m state --state RELATED,ESTABLISHED -j ACCEPT
```

### Network Zones and Isolation

**Zone 1: WAN (eth0)**
- **IP Assignment**: DHCP from ISP router
- **Purpose**: Internet connectivity
- **Traffic**: Outbound to internet, return traffic from internet
- **Configuration**: Automatic via DHCP

**Zone 2: Child Network (wlan0)**
- **IP Assignment**: Static 192.168.4.1 (gateway)
- **Subnet**: 192.168.4.0/24
- **DHCP Service**: dnsmasq allocates 192.168.4.x to child devices
- **Purpose**: Isolated child device network
- **SSID**: Parental_WiFi

### Traffic Flow Control

**Outbound Traffic (Child → Internet):**
```
Child Device (192.168.4.2)
  → wlan0 (192.168.4.1) [WiFi AP]
  → iptables FORWARD chain [filtering/blocking]
  → NAT MASQUERADE [IP translation]
  → eth0 [Ethernet]
  → ISP Router
  → Internet
```

**Inbound Traffic (Internet → Child):**
```
Internet
  → ISP Router
  → eth0 [Ethernet]
  → NAT MASQUERADE [reverse translation]
  → iptables FORWARD chain [stateful filtering]
  → wlan0 (192.168.4.1) [WiFi AP]
  → Child Device (192.168.4.2)
```

### Key Network Services

**hostapd** - WiFi Access Point Manager
- Creates WiFi network (SSID: Parental_WiFi)
- Manages wlan0 interface in AP mode
- Handles device authentication

**dnsmasq** - DHCP & DNS Server
- **DHCP**: Assigns IP addresses to child devices (192.168.4.x)
- **DNS**: Handles DNS queries and domain blocking
- **Lease Management**: Tracks connected devices

**iptables** - Firewall & NAT
- **FORWARD Chain**: Controls traffic between wlan0 and eth0
- **INPUT Chain**: Controls access to Pi itself
- **NAT Table**: MASQUERADE rule for IP translation
- **MAC-based Rules**: Device-level blocking/unblocking

### Benefits of This Architecture

✅ **Network Isolation**: Child devices isolated from parent network
✅ **Centralized Control**: All child traffic flows through Pi
✅ **Monitoring Capability**: Complete visibility of child device traffic
✅ **Security**: MAC-based filtering and firewall rules
✅ **Scalability**: Supports multiple child devices simultaneously

---

## Slide 3: Scope Item 1 - Website Monitoring and Blocking
**1. Monitor visited websites, manually flag, and block selected websites**

### Blocking Types Supported:
- **URL-level**: Block specific URLs (e.g., `https://facebook.com/page`)
- **Domain-level**: Block entire domain + subdomains (e.g., `facebook.com` blocks `*.facebook.com`)
- **App-level**: Block app with all related domains (e.g., Facebook app blocks 30+ domains including `api.facebook.com`, `graph.facebook.com`, etc.)

### How Components Work Together:

```
┌─────────────────────────────────────────────────────────────┐
│ Child Device Accesses Website                               │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ dnsmasq (DNS Server)                                        │
│ - Intercepts DNS queries                                    │
│ - Checks domain blocklist per device                        │
│ - Returns 127.0.0.1 for blocked domains                     │
│ - Enforces URL/domain/app-level blocking via DNS            │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Laravel Application (ParseNetworkLogs Job)                  │
│ - Parses network logs for domain access                     │
│ - Records in MariaDB (browsing_logs table)                  │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ MariaDB Database                                            │
│ - Stores visited websites (browsing_logs)                   │
│ - Stores flagged websites (flagged_websites)                │
│ - Stores blocked websites (blocked_websites)                │
│   * block_type: url/domain/app                              │
│   * related_domains: JSON array for app-level blocking      │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Nginx + Laravel Dashboard                                   │
│ - Displays browsing history to parents                      │
│ - Interface to flag/block websites                          │
│ - Supports URL/domain/app-level blocking selection          │
└─────────────────────────────────────────────────────────────┘
```

**Key Components:**
- **dnsmasq**: DNS-based blocking via domain blocklists per device (enforces URL/domain/app-level blocking)
- **DomainBlockingService**: Detects related domains for app-level blocking (30+ domains for apps like Facebook)
- **MariaDB**: Stores browsing_logs, flagged_websites, blocked_websites (with block_type and related_domains)
- **Laravel Background Jobs**: ParseNetworkLogs monitors and records access
- **Nginx + Laravel**: Parent dashboard for management with blocking type selection

---

## Slide 4: Scope Item 2 - Portal Redirection for Learning
**2. Redirect child devices to quiz or educational video when time expires**

### How Components Work Together:

```
┌─────────────────────────────────────────────────────────────┐
│ Background Job: CheckTimeExpiration (Runs every 2 min)     │
│ - Detects device with time = 0                             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ NetworkService + iptables                                   │
│ - Adds DROP rules to FORWARD chain                         │
│ - Blocks device MAC address from internet access           │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ NoDogSplashService                                          │
│ - Executes redirect_device_portal.sh                       │
│ - Uses ndsctl deauth <token>                               │
│ - Puts device in Preauthenticated state                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ NoDogSplash (Captive Portal)                                │
│ - Intercepts HTTP requests                                  │
│ - Redirects to splash.html?tok=TOKEN                       │
│ - Splash page redirects to portal with token               │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Nginx + Laravel Portal Interface                            │
│ - Portal page displays quiz/video options                  │
│ - Child selects quiz or video                               │
│ - System validates completion                               │
└──────────────────────┬──────────────────────────────────────┘
```

**Key Components:**
- **iptables**: Physical blocking at network level
- **NoDogSplash**: HTTP interception and redirect to portal
- **Laravel Portal Controller**: Serves quiz/video interface
- **Nginx**: Hosts portal pages and handles redirects

---

## Slide 5: Scope Item 3 - Schedule and Duration Management
**3. Define schedules and duration for internet use**

### How Components Work Together:

```
┌─────────────────────────────────────────────────────────────┐
│ Nginx + Laravel Dashboard                                   │
│ - Parent creates schedules via web interface                │
│ - Sets time allocations for devices                         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Laravel Controllers (DeviceScheduleController)              │
│ - Validates schedule input                                  │
│ - Processes time allocation updates                         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ MariaDB Database                                            │
│ - device_schedules table (time windows)                     │
│ - devices table (time_allocation)                           │
│ - device_time_grants table (granted time)                   │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Background Jobs: EnforceSchedules (Every 1 min)             │
│ - Checks current time against schedules                     │
│ - Blocks/unblocks devices based on schedule                 │
│ - Uses NetworkService + iptables for enforcement            │
└─────────────────────────────────────────────────────────────┘
```

**Key Components:**
- **MariaDB**: Stores device_schedules, devices, device_time_grants
- **Laravel Background Jobs**: EnforceSchedules enforces time windows
- **iptables**: Physical enforcement of schedule restrictions
- **Nginx + Laravel Dashboard**: Parent interface for schedule configuration

---

## Slide 6: Scope Item 5 - Time Tracking
**5. Monitor total time child's device spends online**

### How Components Work Together:

```
┌─────────────────────────────────────────────────────────────┐
│ Background Job: MonitorDeviceConnections (Every 2 min)      │
│ - Queries ARP table (get_connected_devices.sh)             │
│ - Identifies currently connected devices                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Background Job: TrackActiveSessions (Every 5 min)           │
│ - Reads device_sessions from MariaDB                        │
│ - Correlates MAC addresses with active sessions             │
│ - Calculates time spent online                              │
│ - Deducts time from device allocation                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ MariaDB Database                                            │
│ - device_sessions table (active session records)            │
│ - devices table (remaining_time tracking)                   │
│ - device_time_grants table (time grant history)             │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Nginx + Laravel Dashboard                                   │
│ - Displays real-time time usage                             │
│ - Shows remaining time for each device                      │
│ - Displays time usage history                               │
└─────────────────────────────────────────────────────────────┘
```

**Key Components:**
- **iptables monitor_traffic.sh**: Queries traffic statistics
- **Laravel Background Jobs**: TrackActiveSessions calculates time usage
- **MariaDB**: Stores device_sessions and time tracking data
- **Nginx + Laravel Dashboard**: Displays time information to parents

---

## Slide 7: Scope Item 7 - Parent Dashboard
**7. Web-based dashboard for configuration and management**

### How Components Work Together:

```
┌─────────────────────────────────────────────────────────────┐
│ Parent Device (Browser)                                     │
│ - Accesses dashboard via http://192.168.4.1                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Nginx (Web Server)                                          │
│ - Routes requests to PHP-FPM                                │
│ - Serves static assets (CSS, JS, images)                    │
│ - Handles SSL/HTTPS for secure access                       │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ PHP-FPM + Laravel Application                               │
│ - Controllers handle requests:                              │
│   * DeviceController (device management)                    │
│   * DeviceScheduleController (schedule management)           │
│   * PortalController (captive portal interface)             │
│   * QuizController (quiz management)                        │
│   * VideoController (video management)                      │
│   * BlockedWebsiteController (blocking management)          │
│   * FlaggedWebsiteController (flagging management)          │
│   * BrowsingLogController (browsing history)                │
│   * AccessAttemptController (security events)               │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ MariaDB Database                                            │
│ - Stores all configuration data                             │
│ - Retrieves device, quiz, video, report data                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Laravel Blade Templates + Alpine.js                         │
│ - Renders dashboard interface                               │
│ - Interactive UI for configuration                          │
│ - Real-time updates via WebSockets                          │
└─────────────────────────────────────────────────────────────┘
```

**Key Components:**
- **Nginx**: Web server hosting the application
- **Laravel Application**: Business logic and routing
- **MariaDB**: Data persistence
- **Blade + Alpine.js**: User interface rendering

---

## Slide 8: Scope Item 8 - Device Management
**8. Manage connected devices for blocking and whitelisting**

### How Components Work Together:

```
┌─────────────────────────────────────────────────────────────┐
│ Nginx + Laravel Dashboard                                   │
│ - Parent views connected devices                            │
│ - Selects device to block/whitelist                         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ NetworkService (Laravel)                                    │
│ - getConnectedDevices() → get_connected_devices.sh          │
│ - blockDevice() → block_device.sh                           │
│ - unblockDevice() → unblock_device.sh                       │
│ - whitelistDevice() → whitelist_device.sh                   │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ ScriptExecutor (Security Layer)                             │
│ - Validates script whitelist                                │
│ - Sanitizes arguments                                       │
│ - Executes scripts with sudo                                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Bash Scripts                                                │
│ - get_connected_devices.sh (queries ARP table)              │
│ - block_device.sh (iptables DROP rules)                     │
│ - whitelist_device.sh (iptables ACCEPT rules at pos 1)      │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ iptables (Linux Firewall)                                   │
│ - INPUT chain (blocks access to Pi)                         │
│ - FORWARD chain (blocks internet access)                    │
│ - MAC-based rules for device identification                 │
└─────────────────────────────────────────────────────────────┘
```

**Key Components:**
- **iptables**: Physical network-level blocking via MAC address rules
- **ScriptExecutor**: Secure execution of network control scripts
- **NetworkService**: High-level interface for device management
- **Laravel Dashboard**: Parent interface for device operations

---

## Slide 9: Scope Item 9 - Security Measures
**9. Basic security measures: authentication, firewall, MAC whitelisting, session management**

### How Components Work Together:

```
┌─────────────────────────────────────────────────────────────┐
│ Nginx                                                       │
│ - HTTPS/TLS encryption (RFC 8446)                          │
│ - SSL certificate validation                                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Laravel Security Middleware                                 │
│ - Authentication (bcrypt password hashing)                  │
│ - CSRF protection (tokens on forms)                         │
│ - Session management (secure session storage)               │
│ - Role-based access control (Parent/Admin)                  │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ iptables (Firewall Rules)                                   │
│ - Blocks unauthorized access attempts                       │
│ - MAC-based filtering                                       │
│ - Whitelist rules at position 1 (bypass blocking)          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ ScriptExecutor (Command Security)                           │
│ - Script whitelist validation                               │
│ - Path validation (prevents directory traversal)            │
│ - Argument sanitization (prevents command injection)        │
│ - Audit logging of all executions                           │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ MariaDB                                                     │
│ - Stores user credentials (hashed)                          │
│ - Session storage                                           │
│ - Audit logs                                                │
└─────────────────────────────────────────────────────────────┘
```

**Key Components:**
- **Nginx**: HTTPS encryption and secure transport
- **Laravel**: Authentication, CSRF protection, session management
- **iptables**: Network-level firewall and MAC-based filtering
- **ScriptExecutor**: Secure command execution with validation
- **MariaDB**: Secure credential storage and audit logging

---

## Slide 10: Delimitation - System Constraints
**Design Constraints and How Components Address Them**

### 1. **HTTPS and Encryption Limitation**
- **Constraint**: Cannot inspect encrypted traffic
- **Component Impact**: 
  - **dnsmasq**: Blocks at DNS level (domain names), not content inspection
  - **NoDogSplash**: Only intercepts HTTP (not HTTPS) requests
  - **Delimitation**: DNS-based blocking (URL/domain/app-level) works, but no deep packet inspection or content-level filtering

### 2. **Limited Processing Capacity**
- **Constraint**: Raspberry Pi has limited CPU/RAM
- **Component Impact**:
  - **Laravel Background Jobs**: Optimized to run at intervals (1-10 min)
  - **iptables**: Lightweight firewall rules (no deep inspection)
  - **dnsmasq**: Efficient DNS blocking (no content analysis)
  - **Delimitation**: No computationally intensive analytics

### 3. **Network Infrastructure Dependencies**
- **Constraint**: Depends on PLDT router compatibility
- **Component Impact**:
  - **hostapd**: WiFi AP functionality requires compatible router
  - **dnsmasq**: DHCP service requires proper network configuration
  - **Delimitation**: May require router configuration assistance

### 4. **Data Privacy Constraints**
- **Constraint**: Collects only necessary data
- **Component Impact**:
  - **MariaDB**: Stores only domains, timestamps, MAC addresses
  - **Laravel ParseNetworkLogs**: Logs domain-level access only
  - **Delimitation**: No packet payload inspection, no content logging

### 5. **Free and Open-Source Tools Only**
- **Constraint**: No commercial licensing
- **Component Impact**:
  - All components are open-source:
    - **Laravel** (MIT), **MariaDB** (GPL), **Nginx** (BSD)
    - **NoDogSplash** (GPL), **iptables** (GPL), **dnsmasq** (GPL)
  - **Delimitation**: No enterprise-grade features from commercial solutions

---

## Slide 11: Component Roles Summary
**How Each Component Achieves Scope**

### **Laravel**
- **Primary Role**: Business Logic & Coordination
- **Scope Items Supported**: All items
- **Description**: Central orchestrator and MVC framework that coordinates all system components. Handles routing, controllers, services, and business logic for all scope items.

### **MariaDB**
- **Primary Role**: Data Storage
- **Scope Items Supported**: All items
- **Description**: Relational database that provides data persistence for devices, schedules, browsing logs, quizzes, videos, time tracking, and all system configuration data.

### **Nginx**
- **Primary Role**: Web Server
- **Scope Items Supported**: Item 7 (Dashboard), Item 2 (Portal hosting)
- **Description**: Web server that hosts the Laravel application, serves the parent dashboard, hosts captive portal pages, and provides HTTPS/TLS encryption for secure access.

### **PHP-FPM**
- **Primary Role**: PHP Processor
- **Scope Items Supported**: All items
- **Description**: FastCGI Process Manager that processes all Laravel application requests, executing PHP code and handling dynamic content generation.

### **iptables**
- **Primary Role**: Network-Level Blocking & NAT
- **Scope Items Supported**: Item 1 (Blocking), Item 8 (Device management), Item 9 (Security)
- **Description**: Linux firewall that provides network-level blocking via MAC address rules, controls traffic between wlan0 and eth0 interfaces, and implements NAT MASQUERADE for IP translation.

### **NAT (MASQUERADE)**
- **Primary Role**: Network Address Translation
- **Scope Items Supported**: All items
- **Description**: Enables communication between wlan0 (child network) and eth0 (WAN) by translating source IP addresses. Allows multiple child devices to share the Pi's internet connection while maintaining network isolation.

### **dnsmasq**
- **Primary Role**: DNS Blocking & DHCP
- **Scope Items Supported**: Item 1 (URL/domain/app-level blocking), Item 8 (Device identification)
- **Description**: Provides DHCP service to assign IP addresses to child devices (192.168.4.x) and DNS service for domain-level blocking. Tracks connected devices via DHCP lease management.

### **hostapd**
- **Primary Role**: WiFi Access Point Manager
- **Scope Items Supported**: All items
- **Description**: Creates and manages the WiFi access point (SSID: Parental_WiFi), configures the wlan0 interface in AP mode, and handles device authentication and connection management.

### **NoDogSplash**
- **Primary Role**: Captive Portal & HTTP Intercept
- **Scope Items Supported**: Item 2 (Quiz/Video redirect)
- **Description**: Captive portal solution that intercepts HTTP requests from child devices and redirects them to the quiz/video portal when time expires. Manages device authentication states.

### **DomainBlockingService**
- **Primary Role**: App-level Blocking Logic
- **Scope Items Supported**: Item 1 (Related domain detection for apps)
- **Description**: Laravel service that detects related domains for app-level blocking (e.g., Facebook app blocks 30+ related domains), manages DNS configuration via dnsmasq, and handles domain blocklist updates.

### **ScriptExecutor**
- **Primary Role**: Secure Script Execution
- **Scope Items Supported**: All items
- **Description**: Security layer that validates script whitelists, sanitizes arguments, and securely executes shell scripts with sudo privileges. Prevents command injection and provides audit logging.

### **Cron/Laravel Scheduler**
- **Primary Role**: Job Scheduling
- **Scope Items Supported**: Item 3 (Schedule enforcement), Item 5 (Time tracking)
- **Description**: Cron job runs Laravel's scheduler every minute to execute scheduled background jobs. Enables automated monitoring, schedule enforcement, and time tracking without manual intervention.

### **Background Jobs**
- **Primary Role**: Automated Monitoring
- **Scope Items Supported**: Item 3 (EnforceSchedules), Item 5 (TrackActiveSessions), Item 1 (ParseNetworkLogs), Item 8 (MonitorDeviceConnections), Item 2 (CheckTimeExpiration)
- **Description**: Five automated background jobs that run continuously: CheckTimeExpiration (every 2 min), TrackActiveSessions (every 5 min), MonitorDeviceConnections (every 2 min), EnforceSchedules (every 1 min), and ParseNetworkLogs (every 10 min).

### **Systemd**
- **Primary Role**: Service Management
- **Scope Items Supported**: All items
- **Description**: Linux service manager that manages the queue worker service, NoDogSplash service, network services, and provides auto-restart capabilities for reliable 24/7 operation.

### **Queue Worker**
- **Primary Role**: Background Job Execution
- **Scope Items Supported**: Item 3, Item 5
- **Description**: Executes scheduled background jobs from the database queue. Runs as a systemd service (parental-wifi-queue.service) and processes jobs for schedule enforcement and time tracking.





## Slide 12: Questions & Discussion
**Thank You**

Questions?

For detailed documentation:
- Network Control System Architecture
- NoDogSplash Setup and Integration
- Background Jobs Overview
- Device Management Implementation

