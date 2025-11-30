# Chapter 2: Required Diagrams and Visual Aids

### Required Diagrams:

#### 2.2 Design 1:
- [ ] Diagram 2.2.1: Hardware Components Block Diagram (Section 2.2.2 Hardware Design)
- [ ] Diagram 2.2.2: Network Topology Diagram (Section 2.2.3 Schematic Design)
- [ ] Diagram 2.2.3: System Architecture Diagram (Section 2.2.3 Schematic Design)
- [ ] Diagram 2.2.4: Data Flow Diagram (Section 2.2.3 Schematic Design)
- [ ] Diagram 2.2.5: Parent Dashboard UI Mockup (Section 2.2.4 Illustrative Design)
- [ ] Diagram 2.2.6: Child Portal UI Mockup (Section 2.2.4 Illustrative Design)
- [ ] Diagram 2.2.7: Reporting Dashboard Visualization (Section 2.2.4 Illustrative Design)

#### 2.3 Software Design:
- [ ] Diagram 2.3.1: Software Component Overview (Section 2.3.1 Design Description)
- [ ] Diagram 2.3.2: Hardware Interaction Architecture (Section 2.3.2 Hardware Interaction)
- [ ] Diagram 2.3.3: Software Architecture Layers (Section 2.3.3 Schematic Design)
- [ ] Diagram 2.3.3a: Network Control System Architecture (Section 2.3.3 Schematic Design - Detailed Network Control)
- [ ] Diagram 2.3.4: Device Registration to Time Grant Sequence (Section 2.3.4 Illustrative Design)
- [ ] Diagram 2.3.5: Video System with Dictionary Words Workflow (Section 2.3.4 Illustrative Design)
- [ ] Diagram 2.3.6: Quiz System Workflow (Section 2.3.4 Illustrative Design)
- [ ] Diagram 2.3.7: Database Entity Relationship Diagram (Section 2.3.3 Schematic Design)

This document lists all the diagrams, schematics, and visual aids needed for Chapter 2: Project Design. These diagrams will enhance the thesis presentation and help readers better understand the system architecture and design.


## Important Network Architecture Note

**CRITICAL:** The network topology must clearly show (as per scope.md):
- **Raspberry Pi 4B is connected through a LAN cable to the ISP Router** - This is the Pi's WAN connection for internet access (as per scope.md: "connected through a LAN cable to WiFi" - meaning connected to router via LAN cable)
- **Child devices connect ONLY via WiFi to the Raspberry Pi's Access Point** - They cannot directly access the ISP router
- **All child device traffic must pass through the Raspberry Pi** - The Pi acts as a gateway/router for child devices
- **The Pi's Ethernet connection is NOT accessible to child devices** - It's only for the Pi itself to access the internet
- **Laravel runs inside the Raspberry Pi itself** - The entire web system (Nginx/Apache + PHP-FPM) is on the same machine, allowing Laravel to directly execute Linux commands to control the system (as per scope.md)

This architecture ensures that all child device traffic is controlled and monitored by the system, matching the specifications in scope.md.

---

## 2.2 Design 1 (Integrated Raspberry Pi Access Point Design)

### 2.2.1 Design Description

*Note: This section contains descriptive text only. No diagrams are required as the design description is explained in prose in Chapter 2.2.1.*

---

### 2.2.2 Hardware Design

### Diagram 2.2.1: Hardware Components Block Diagram
**Type:** Block Diagram  
**Section:** 2.2.2 Hardware Design  
**Purpose:** Show physical hardware components and their connections  
**Content:**
```
                    [ISP Modem/Router]
                           |
                           | (LAN Cable - Ethernet)
                           | (WAN Connection)
                           |
                    [Raspberry Pi 4B]
                      /    |    |    \
                     /     |    |     \
              [SSD] [WiFi AP] [Ethernet] [Power]
               |        |         |         |
               |        |         |         |
          [Storage] [WiFi]   [Internet]  [5V/3A]
               |        |      Access    Supply
               |        |         |
               |        |         |
               |    [Child Devices] (WiFi Only)
               |    (Smartphones, Tablets, Laptops)
```

**Details to Include:**
- Raspberry Pi 4B as central component
- Kingston A400 SSD connection (internal storage)
- Ethernet port connection to ISP router (WAN - for Pi's internet access only)
- WiFi Access Point broadcasting (LAN - for child devices to connect)
- Power supply connection (5V/3A USB-C)
- **IMPORTANT:** Child devices connect ONLY via WiFi to the Pi's Access Point
- **IMPORTANT:** Ethernet connection is ONLY for Pi-to-Router communication
- Optional USB WiFi dongle for management network
- GPIO header for future use

**Suggested Tool:** Draw.io, Fritzing, or hardware diagram software

---

### 2.2.3 Schematic Design

### Diagram 2.2.2: Network Topology Diagram
**Type:** Network Diagram  
**Section:** 2.2.3 Schematic Design  
**Purpose:** Show network architecture and data flow  
**Content:**
```
                    Internet
                       |
                       |
            ┌──────────────────────┐
            │  ISP Modem/Router    │
            │  (Home Network)      │
            └──────────┬───────────┘
                       |
                       | (LAN Cable - Ethernet)
                       | WAN Connection
                       | (Pi's Internet Access)
                       |
        ┌──────────────▼──────────────┐
        │     Raspberry Pi 4B          │
        │  ┌────────────────────────┐  │
        │  │  Ethernet Port        │  │
        │  │  (Connected to Router) │  │
        │  └────────────────────────┘  │
        │  ┌────────────────────────┐  │
        │  │  WiFi Access Point     │  │
        │  │  SSID: Parental_WiFi  │  │
        │  │  Interface: wlan0     │  │
        │  │  IP: 192.168.4.1/24   │  │
        │  │  (802.11ac)           │  │
        │  └────────────────────────┘  │
        │  ┌────────────────────────┐  │
        │  │  Web Server            │  │
        │  │  (Nginx + PHP-FPM)     │  │
        │  └────────────────────────┘  │
        │  ┌────────────────────────┐  │
        │  │  Firewall (iptables)   │  │
        │  └────────────────────────┘  │
        └──────────────┬───────────────┘
                       |
                       | (WiFi Connection ONLY)
                       | LAN Network
                       | (Child Devices Network)
                       |
        ┌──────────────┼──────────────┐
        |              |              |
   ┌────▼───┐    ┌────▼───┐    ┌────▼───┐
   │ Child  │    │ Child  │    │ Child  │
   │ Device │    │ Device │    │ Device │
   │   1    │    │   2    │    │   3    │
   │(Phone) │    │(Tablet)│    │(Laptop)│
   └────────┘    └────────┘    └────────┘
   
   Note: Child devices CANNOT directly access
   ISP Router - they must connect via WiFi to Pi
```

**Details to Include:**
- Internet connection path through ISP router
- WAN zone: ISP Router → Raspberry Pi (via Ethernet/LAN cable)
- LAN zone: Raspberry Pi → Child Devices (via WiFi ONLY)
- Raspberry Pi internal components (Ethernet Port, WiFi AP, Web Server, Firewall)
- **CRITICAL:** Child devices connect ONLY via WiFi to the Pi's Access Point
- **CRITICAL:** Child devices cannot directly access the ISP router's network
- Data flow direction arrows showing all traffic goes through Pi
- Network segmentation clearly marked (WAN vs LAN)
- Note explaining that child devices must connect through Pi

**Suggested Tool:** Draw.io, Cisco Packet Tracer, or network diagram software

---

### Diagram 2.2.3: System Architecture Diagram
**Type:** System Architecture Diagram  
**Section:** 2.2.3 Schematic Design  
**Purpose:** Show logical system components and their relationships  
**Content:**
```
┌─────────────────────────────────────────────────────────┐
│              Child Devices (WiFi Connection ONLY)      │
│         (Smartphones, Tablets, Laptops)                 │
│         Connect via WiFi to Pi's Access Point           │
└────────────────────┬────────────────────────────────────┘
                     │
                     │ WiFi (802.11ac)
                     │ HTTP/HTTPS Requests
                     │ (All traffic routed through Pi)
                     │
┌────────────────────▼────────────────────────────────────┐
│              Raspberry Pi 4B System                      │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │  WiFi Access Point (hostapd)                     │   │
│  │  - Receives WiFi connections from child devices  │   │
│  │  - SSID: Parental_WiFi                           │   │
│  │  - Interface: wlan0                              │   │
│  │  - IP: 192.168.4.1/24                            │   │
│  │  - DHCP Range: 192.168.4.2 to 192.168.4.51       │   │
│  └──────────────────────────────────────────────────┘   │
│                          │                               │
│  ┌───────────────────────▼──────────────────────────┐   │
│  │         NoDogSplash (Captive Portal)             │   │
│  │  - Intercepts HTTP requests                       │   │
│  │  - Redirects expired devices                     │   │
│  └──────────────────────────────────────────────────┘   │
│                          │                               │
│  ┌───────────────────────▼──────────────────────────┐   │
│  │         Laravel Application                       │   │
│  │  ┌──────────────┐  ┌──────────────┐             │   │
│  │  │ Controllers  │  │   Services   │             │   │
│  │  └──────────────┘  └──────────────┘             │   │
│  │  ┌──────────────┐  ┌──────────────┐             │   │
│  │  │   Models     │  │ Background   │             │   │
│  │  │              │  │    Jobs      │             │   │
│  │  └──────────────┘  └──────────────┘             │   │
│  └──────────────────────────────────────────────────┘   │
│                          │                               │
│  ┌───────────────────────▼──────────────────────────┐   │
│  │         MariaDB Database                           │   │
│  │  - Devices, Sessions, Quizzes, Videos, Logs       │   │
│  └──────────────────────────────────────────────────┘   │
│                          │                               │
│  ┌───────────────────────▼──────────────────────────┐   │
│  │         System Services                           │   │
│  │  - iptables (Firewall)                            │   │
│  │  - hostapd (WiFi AP)                              │   │
│  │  - dnsmasq (DHCP/DNS)                             │   │
│  └──────────────────────────────────────────────────┘   │
│                          │                               │
│  ┌───────────────────────▼──────────────────────────┐   │
│  │  Ethernet Port                                    │   │
│  │  - Connected to ISP Router via LAN Cable          │   │
│  │  - Pi's internet access (WAN)                     │   │
│  └──────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────┘
                     │
                     │ Ethernet (LAN Cable)
                     │ Internet Access
                     │
┌────────────────────▼────────────────────────────────────┐
│              ISP Modem/Router                             │
│              (Home Network)                              │
└────────────────────┬────────────────────────────────────┘
                     │
                     │
┌────────────────────▼────────────────────────────────────┐
│                    Internet                              │
└──────────────────────────────────────────────────────────┘

Note: Child devices connect ONLY via WiFi to Pi.
      Pi connects to router via Ethernet for internet access.
```

**Details to Include:**
- All major system components matching scope.md:
  - WiFi Access Point (hostapd) - Provides WiFi connectivity to child devices
  - Captive Portal (NoDogSplash) - Intercepts and redirects users
  - Web Server (Nginx/Apache + PHP-FPM) - Hosts Laravel application
  - Firewall/Router (iptables/nftables) - Controls network traffic
  - Monitoring Device - Tracks and logs all network activity
- WiFi Access Point as entry point for child devices
- Ethernet connection shown as Pi's internet gateway (not for child devices)
- Data flow showing child devices → WiFi → Pi → Ethernet → Router → Internet
- Service layers (NoDogSplash, Laravel, Database, System Services)
- Clear separation of software layers
- **CRITICAL:** Show that child devices cannot bypass the Pi
- Background jobs for monitoring (TrackActiveSessions, CheckTimeExpiration, ParseNetworkLogs, etc.)

**Suggested Tool:** Draw.io, Lucidchart, or architecture diagram tools

---

### Diagram 2.2.4: Data Flow Diagram
**Type:** Data Flow Diagram (DFD)  
**Section:** 2.2.3 Schematic Design  
**Purpose:** Show how data flows through the system  
**Content:**
```
[Child Device Connects via WiFi]
         │
         │ (WiFi Connection to Pi's AP)
         │ MAC Address detected
         ▼
[DHCP Service (dnsmasq)] ──→ Assigns IP Address
         │                      (Pi's LAN network)
         │ Device Info
         ▼
[Device Registration] ──→ [devices table]
         │
         │ Time Allocation
         ▼
[Time Tracking Service] ──→ [device_sessions table]
         │
         │ Monitors Active Sessions
         │ (Tracks WiFi connection activity)
         ▼
[Background Job: TrackActiveSessions]
         │
         │ Deducts Time
         ▼
[Check Time Remaining]
         │
    ┌────┴────┐
    │         │
Time > 0   Time = 0
    │         │
    │         ▼
    │    [Block Device via iptables]
    │         │
    │         ▼
    │    [NoDogSplash Redirect]
    │         │ (Intercepts WiFi traffic)
    │         ▼
    │    [Captive Portal]
    │         │ (Served via WiFi)
    │    ┌────┴────┐
    │    │         │
    │  Quiz      Video
    │    │         │
    │    ▼         ▼
    │ [Take Quiz] [Watch Video]
    │    │         │
    │    ▼         │ (Dictionary Words)
    │ [Submit]     │
    │    │         ▼
    │    │    [Submit Words]
    │    │         │
    │    ▼         ▼
    │ [Validate] [Validate]
    │    │         │
    │    │    ┌────┴────┐
    │    │    │         │
    │    │ Pass      Fail
    │    │    │         │
    │    │    │         ▼
    │    │    │    [Show Error]
    │    │    │         │
    │    │    │         └───→ [ReWatch Video] (Retry)
    │    │    │                    
    │    │    │                    
    │    │    │                        
    │    │    └─────
    │    │         │
    │    │         │ (Video Pass)
    │    │         │
    │    │         └──────────────────────┐
    │    │                                │
    │    ┌─────────┐                      │
    │    │         │                      │
    │ Pass      Fail                      │
    │    │         │                      │
    │    │         ▼                      │
    │    │    [Show Error]                │
    │    │         │                      │
    │    │         └───→[ReTake Quiz]     |
    │    │                 (Retry)        │
    │    │                                │
    │    │                                │
    │    │                                │
    │    └─────                          │
    │         │                          │
    │         │ (Quiz Pass)              │
    │         │                          │
    │         │                          │
    │         │ (Both paths converge)    │
    │         │                          │
    │         └──────────────────────────┘
    │              │
    │              ▼
    │    [Time Granted]
    │         │
    └─────────┘
         │
         ▼
[Unblock Device] ──→ [Update Database]
         │
         ▼
[Device Regains Internet Access]
         │
         │ (WiFi → Pi → Ethernet → Router → Internet)
         ▼
[Child Device Can Browse Internet]
```

**Details to Include:**
- Complete workflow from device connection to time grant
- Database interactions
- Decision points (time expired, quiz/video choice)
- Background job processes
- Service interactions
- **Note:** This diagram focuses on the captive portal core flow. Additional system capabilities (website blocking/flagging, scheduling, reporting, real-time notifications) are shown in other diagrams or handled by background jobs.

**Suggested Tool:** Draw.io, Lucidchart, or DFD tools

---

## 2.2.4 Illustrative Design

**Note on Illustrative Design Sections:** Chapter 2 contains two Illustrative Design sections with different purposes:
- **Section 2.2.4 (Hardware Design Illustrative Design)**: Focuses on user interface design and visual presentation - how the system looks and feels to users.
- **Section 2.3.4 (Software Design Illustrative Design)**: Focuses on workflow examples and process flows - how the system operates step-by-step.

The Illustrative Design section (2.2.4) demonstrates the user interface design and visual presentation of the system as described in Chapter 2.2.4. It shows how the system appears to users (both parents and children) and how information is presented through visualizations. This section includes:

- **Parent Dashboard Experience**: Shows the mobile-responsive dashboard with device cards, alert banners, and color-coded status indicators (green for active, amber for warnings, red for blocked devices).
- **Child Portal Experience**: Demonstrates the simple, distraction-free captive portal interface with quiz/video selection options and celebratory animations upon completion.
- **Reporting Visualization**: Illustrates how reports use charts and tables to display usage patterns, visited sites, and educational activity completion.

### Diagram 2.2.5: Parent Dashboard UI Mockup
**Type:** UI Mockup/Screenshot  
**Section:** 2.2.4 Illustrative Design  
**Purpose:** Show parent dashboard interface design as described in Chapter 2.2.4  
**Content:**
- Screenshot or mockup of the actual parent dashboard
- Show device cards with remaining time
- Alert banners
- Navigation menu
- Color coding (green/amber/red)

**Details to Include:**
- Main dashboard view
- Device management interface
- Quiz/video creation forms
- Report visualization

**Suggested Tool:** Screenshot from actual system, or Figma/Adobe XD for mockups

---

### Diagram 2.2.6: Child Portal UI Mockup
**Type:** UI Mockup/Screenshot  
**Section:** 2.2.4 Illustrative Design  
**Purpose:** Show captive portal interface design as described in Chapter 2.2.4  
**Content:**
- Screenshot or mockup of the captive portal landing page
- Quiz vs Video selection interface
- Video player with dictionary words overlay
- Word validation form
- Success/completion screens

**Details to Include:**
- Portal landing page with quiz/video options
- Video player interface showing disabled controls
- Dictionary word display during video
- Completion and validation screens

**Suggested Tool:** Screenshot from actual system, or Figma/Adobe XD for mockups

---

### Diagram 2.2.7: Reporting Dashboard Visualization
**Type:** Chart/Graph Examples  
**Section:** 2.2.4 Illustrative Design  
**Purpose:** Show report visualization examples as described in Chapter 2.2.4 and scope.md  
**Content:**
- Daily usage chart (bar/line chart)
- Weekly usage trends
- Monthly usage summary
- Blocked website attempts pie chart
- Flagged website visits chart
- Time usage breakdown
- Educational activity completion rates (quiz/video completion)
- **Note:** According to scope.md, reports include: internet usage summary, visited sites, access to flagged websites, attempts to access blocked websites, and bandwidth used (if available)

**Details to Include:**
- Sample charts and graphs showing daily, weekly, and monthly reports
- Table examples with browsing logs and access attempts
- Data visualization styles
- Reports generated from browsing_logs, access_attempts, device_sessions, and quiz_attempts/video_completions tables

**Suggested Tool:** Screenshot from actual system, or Excel/Google Sheets for sample charts

---

### 2.2.5 Design Standards

*Note: This section contains descriptive text about standards followed (IEEE, RFC, W3C, OWASP, ISO/IEC). No diagrams are required as standards are explained in prose in Chapter 2.2.5.*

---

### 2.2.6 Design Constraints

*Note: This section contains descriptive text about hardware limits, network dependencies, power/environment constraints, user skill level, and budget constraints. No diagrams are required as constraints are explained in prose in Chapter 2.2.6.*

---

## 2.3 Software Design

### 2.3.1 Design Description

### Diagram 2.3.1: Software Component Overview
**Type:** Component Diagram  
**Section:** 2.3.1 Design Description  
**Purpose:** Show the main software components and their relationships  
**Content:**
```
┌─────────────────────────────────────────────────────────┐
│              Raspberry Pi 4B System                    │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Web Server Layer                                  │  │
│  │  ┌──────────────┐  ┌──────────────┐              │  │
│  │  │   Nginx/     │  │   PHP-FPM    │              │  │
│  │  │   Apache     │  │   (PHP 8.x)  │              │  │
│  │  └──────────────┘  └──────────────┘              │  │
│  └──────────────────────────────────────────────────┘  │
│                          │                              │
│  ┌───────────────────────▼──────────────────────────┐  │
│  │  Laravel 12 Application                            │  │
│  │  ┌──────────────┐  ┌──────────────┐              │  │
│  │  │ Controllers  │  │   Services   │              │  │
│  │  │   Models     │  │ Background   │              │  │
│  │  │   Views      │  │    Jobs      │              │  │
│  │  │ (Blade +     │  │   Queues     │              │  │
│  │  │  Alpine.js)  │  │              │              │  │
│  │  └──────────────┘  └──────────────┘              │  │
│  └──────────────────────────────────────────────────┘  │
│                          │                              │
│  ┌───────────────────────▼──────────────────────────┐  │
│  │  Database Layer                                    │  │
│  │  ┌──────────────┐                                │  │
│  │  │   MariaDB    │                                │  │
│  │  │  (Database)  │                                │  │
│  │  └──────────────┘                                │  │
│  └──────────────────────────────────────────────────┘  │
│                          │                              │
│  ┌───────────────────────▼──────────────────────────┐  │
│  │  System Integration Layer                          │  │
│  │  ┌──────────────┐  ┌──────────────┐              │  │
│  │  │ Shell Scripts│  │ Python       │              │  │
│  │  │ (iptables,   │  │ Helper       │              │  │
│  │  │  hostapd,    │  │ Scripts      │              │  │
│  │  │  dnsmasq)    │  │              │              │  │
│  │  └──────────────┘  └──────────────┘              │  │
│  └──────────────────────────────────────────────────┘  │
│                          │                              │
│  ┌───────────────────────▼──────────────────────────┐  │
│  │  Real-time Communication                          │  │
│  │  ┌──────────────┐                                │  │
│  │  │ Laravel      │                                │  │
│  │  │ Broadcasting │                                │  │
│  │  │ + WebSockets │                                │  │
│  │  └──────────────┘                                │  │
│  └──────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
```

**Details to Include:**
- Web server (Nginx/Apache) and PHP-FPM (as per scope.md technology stack)
- Laravel 12 application components (MVC structure)
- MariaDB database
- System integration (shell scripts, Python helper scripts, Bash scripts, systemd service restarts, iptables/nftables rules - all as per scope.md)
- Real-time communication (Laravel Broadcasting + WebSockets)
- Frontend: Blade Templates + Alpine.js (as per scope.md)
- Captive Portal: NoDogSplash (as per scope.md)
- Data flow between components

**Suggested Tool:** Draw.io, Lucidchart, or component diagram tools

---

### 2.3.2 Hardware Interaction

### Diagram 2.3.2: Hardware Interaction Architecture
**Type:** Interaction Diagram  
**Section:** 2.3.2 Hardware Interaction  
**Purpose:** Show how Laravel interacts with Raspberry Pi hardware and system services, including the network control system architecture  
**Content:**
```
┌─────────────────────────────────────────────────────────┐
│              Laravel Application                        │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Controllers                                      │  │
│  │  (DeviceController, CheckTimeExpiration Job,      │  │
│  │   TimeGrantingService, PortalController)          │  │
│  └───────────────────────┬──────────────────────────┘  │
│                          │                               │
│  ┌───────────────────────▼──────────────────────────┐  │
│  │  Network Control Service Layer                      │  │
│  │  ┌──────────────────────────────────────────────┐  │  │
│  │  │  NetworkService (High-Level Interface)        │  │  │
│  │  │  - blockDevice()                             │  │  │
│  │  │  - unblockDevice()                           │  │  │
│  │  │  - whitelistDevice()                         │  │  │
│  │  │  - getConnectedDevices()                     │  │  │
│  │  │  - getTrafficStats()                         │  │  │
│  │  │  - isDeviceBlocked()                         │  │  │
│  │  └──────────────────┬───────────────────────────┘  │  │
│  │                      │                               │  │
│  │  ┌───────────────────▼───────────────────────────┐  │  │
│  │  │  ScriptExecutor (Secure Wrapper)               │  │  │
│  │  │  - Whitelist validation                        │  │  │
│  │  │  - Path validation (prevents ../ attacks)     │  │  │
│  │  │  - Argument sanitization (escapeshellarg)       │  │  │
│  │  │  - Sudo execution with logging                 │  │  │
│  │  └──────────────────┬───────────────────────────┘  │  │
│  └──────────────────────┼───────────────────────────┘  │
│                          │                               │
│  ┌───────────────────────▼──────────────────────────┐  │
│  │  Other Service Layer                               │  │
│  │  ┌──────────────┐  ┌──────────────┐              │  │
│  │  │ NoDogSplash │  │ Process      │  │ Media     │  │
│  │  │ Service     │  │ Monitoring   │  │ Handling │  │
│  │  └──────────────┘  └──────────────┘  └──────────┘  │  │
│  └───────────────────────┬──────────────────────────┘  │
│                          │                               │
│        ┌─────────────────┼─────────────────┐          │
│        │                 │                 │          │
│  ┌─────▼─────┐  ┌───────▼──────┐  ┌───────▼──────┐  │
│  │ Shell     │  │ Process      │  │ Media       │  │
│  │ Scripts   │  │ Monitoring   │  │ Handling    │  │
│  │ Execution  │  │ Layer        │  │ Layer        │  │
│  │ Layer      │  │              │  │              │  │
│  │            │  │              │  │              │  │
│  │ ┌────────┐ │  │              │  │              │  │
│  │ │block_  │ │  │              │  │              │  │
│  │ │device. │ │  │              │  │              │  │
│  │ │sh      │ │  │              │  │              │  │
│  │ ├────────┤ │  │              │  │              │  │
│  │ │unblock_│ │  │              │  │              │  │
│  │ │device. │ │  │              │  │              │  │
│  │ │sh      │ │  │              │  │              │  │
│  │ ├────────┤ │  │              │  │              │  │
│  │ │whitelist│ │  │              │  │              │  │
│  │ │_device.│ │  │              │  │              │  │
│  │ │sh      │ │  │              │  │              │  │
│  │ ├────────┤ │  │              │  │              │  │
│  │ │get_    │ │  │              │  │              │  │
│  │ │connected│ │  │              │  │              │  │
│  │ │_devices│ │  │              │  │              │  │
│  │ │.sh     │ │  │              │  │              │  │
│  │ ├────────┤ │  │              │  │              │  │
│  │ │monitor_│ │  │              │  │              │  │
│  │ │traffic.│ │  │              │  │              │  │
│  │ │sh      │ │  │              │  │              │  │
│  │ └────────┘ │  │              │  │              │  │
│  └─────┬─────┘  └───────┬──────┘  └───────┬──────┘  │
│        │                 │                 │          │
│        │                 │                 │          │
│  ┌─────▼─────────────────▼─────────────────▼──────┐  │
│  │  System Services & Hardware                     │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐     │  │
│  │  │ iptables │  │ System   │  │ File     │     │  │
│  │  │ (INPUT   │  │ Logs     │  │ System   │     │  │
│  │  │  FORWARD │  │ (hostapd │  │ (SSD     │     │  │
│  │  │  chains)│  │ dnsmasq) │  │ Storage) │     │  │
│  │  │          │  │          │  │          │     │  │
│  │  │ MAC-based│  │          │  │          │     │  │
│  │  │ DROP/    │  │          │  │          │     │  │
│  │  │ ACCEPT  │  │          │  │          │     │  │
│  │  │ rules   │  │          │  │          │     │  │
│  │  └──────────┘  └──────────┘  └──────────┘     │  │
│  │  ┌──────────┐  ┌──────────┐                    │  │
│  │  │ Python    │  │ Network  │                    │  │
│  │  │ Scripts   │  │ Services │                    │  │
│  │  │ (Complex  │  │ (hostapd │                    │  │
│  │  │ Operations)│ │ dnsmasq) │                    │  │
│  │  └──────────┘  └──────────┘                    │  │
│  └──────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
```

**Details to Include:**
- Network Control Architecture showing three-tier structure:
  - NetworkService (high-level interface with methods: blockDevice, unblockDevice, whitelistDevice, getConnectedDevices, getTrafficStats, isDeviceBlocked)
  - ScriptExecutor (secure wrapper with whitelist validation, path validation, argument sanitization, sudo execution)
  - Shell scripts (block_device.sh, unblock_device.sh, whitelist_device.sh, get_connected_devices.sh, monitor_traffic.sh)
- Three interaction layers: Network Control (via ScriptExecutor), Process Monitoring, Media Handling
- Service classes that act as security layer (ScriptExecutor, NetworkService, NoDogSplashService)
- System services and hardware components matching scope.md and NETWORK_CONTROL_SYSTEM_ARCHITECTURE.md:
  - iptables INPUT and FORWARD chains with MAC-based DROP/ACCEPT rules
  - Shell scripts for network control (executed via ScriptExecutor)
  - Python helper scripts (complex operations)
  - System logs (hostapd, dnsmasq) for process monitoring
  - Systemd service restarts (NoDogSplash, network services)
- Data flow from Laravel Controllers → NetworkService → ScriptExecutor → Shell Scripts → iptables
- **Note:** Laravel does NOT directly control hardware - it triggers system-level operations through these mechanisms (as per scope.md)
- **Security:** Show ScriptExecutor's security features (whitelist, path validation, argument sanitization)

**Suggested Tool:** Draw.io, Lucidchart, or interaction diagram tools

---

### 2.3.3 Schematic Design

**Note:** Schematic Design section contains two diagrams:
- **Diagram 2.3.3**: Shows the overall seven-layer software architecture
- **Diagram 2.3.7**: Shows the detailed database structure (which is part of Layer 3: Data Layer)

Both diagrams are needed to fully illustrate the system's schematic design.

### Diagram 2.3.3: Software Architecture Layers
**Type:** Layered Architecture Diagram  
**Section:** 2.3.3 Schematic Design  
**Purpose:** Show the seven-layer software architecture  
**Content:**
```
┌─────────────────────────────────────────────┐
│  Layer 7: Real-time Communication Layer     │
│  (Laravel Broadcasting + WebSockets)         │
└─────────────────────────────────────────────┘
┌─────────────────────────────────────────────┐
│  Layer 6: Captive Portal Layer              │
│  (NoDogSplash Integration)                  │
└─────────────────────────────────────────────┘
┌─────────────────────────────────────────────┐
│  Layer 5: Network Control Layer              │
│  ┌─────────────────────────────────────────┐ │
│  │  NetworkService (High-Level Interface)  │ │
│  │  - blockDevice(), unblockDevice()       │ │
│  │  - whitelistDevice(), getConnectedDevices()│ │
│  │  - getTrafficStats(), isDeviceBlocked() │ │
│  └──────────────────┬──────────────────────┘ │
│                     │                        │
│  ┌──────────────────▼──────────────────────┐ │
│  │  ScriptExecutor (Secure Wrapper)         │ │
│  │  - Whitelist validation                  │ │
│  │  - Path validation                       │ │
│  │  - Argument sanitization                 │ │
│  │  - Sudo execution                        │ │
│  └──────────────────┬──────────────────────┘ │
│                     │                        │
│  ┌──────────────────▼──────────────────────┐ │
│  │  Shell Scripts (scripts/ directory)      │ │
│  │  - block_device.sh                       │ │
│  │  - unblock_device.sh                     │ │
│  │  - whitelist_device.sh                   │ │
│  │  - get_connected_devices.sh              │ │
│  │  - monitor_traffic.sh                    │ │
│  └──────────────────┬──────────────────────┘ │
│                     │                        │
│  ┌──────────────────▼──────────────────────┐ │
│  │  iptables (INPUT & FORWARD chains)       │ │
│  │  - MAC-based DROP/ACCEPT rules          │ │
│  └──────────────────────────────────────────┘ │
└─────────────────────────────────────────────┘
┌─────────────────────────────────────────────┐
│  Layer 4: Automation Layer                   │
│  (Laravel Queues + Background Jobs)          │
└─────────────────────────────────────────────┘
┌─────────────────────────────────────────────┐
│  Layer 3: Data Layer                         │
│  (Eloquent Models + MariaDB)                 │
└─────────────────────────────────────────────┘
┌─────────────────────────────────────────────┐
│  Layer 2: Application Layer                  │
│  (Controllers + Services + Middleware)       │
└─────────────────────────────────────────────┘
┌─────────────────────────────────────────────┐
│  Layer 1: Presentation Layer                 │
│  (Blade Views + UI Components)               │
└─────────────────────────────────────────────┘
```

**Details to Include:**
- All seven layers clearly labeled
- Layer 5 (Network Control Layer) expanded to show the three-tier architecture:
  - NetworkService (high-level interface)
  - ScriptExecutor (secure wrapper)
  - Shell Scripts (actual execution)
  - iptables (system-level firewall)
- Direction of communication (requests flow down, responses flow up)
- Key technologies in each layer
- Security features shown in ScriptExecutor layer

**Suggested Tool:** Draw.io, PowerPoint, or architecture diagram tools

---

### Diagram 2.3.3a: Network Control System Architecture
**Type:** Detailed Architecture Diagram  
**Section:** 2.3.3 Schematic Design (Network Control Layer Detail)  
**Purpose:** Show the complete network control system architecture with all components and data flow, based on NETWORK_CONTROL_SYSTEM_ARCHITECTURE.md  
**Content:**
```
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Application                      │
│  (CheckTimeExpiration Job, TimeGrantingService, etc.)      │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Calls methods
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                   NetworkService (PHP)                       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  High-Level Network Operations                       │   │
│  │  - blockDevice(Device $device): bool                │   │
│  │  - unblockDevice(Device $device): bool              │   │
│  │  - whitelistDevice(Device $device): bool             │   │
│  │  - getConnectedDevices(): array                      │   │
│  │  - getTrafficStats(?string $macAddress): array      │   │
│  │  - isDeviceBlocked(Device $device): bool            │   │
│  │                                                       │   │
│  │  Responsibilities:                                    │   │
│  │  - Validates device has MAC address                  │   │
│  │  - Updates database status                           │   │
│  │  - Logs operations                                   │   │
│  │  - Handles errors gracefully                         │   │
│  └──────────────────────┬───────────────────────────────┘   │
└─────────────────────────┼───────────────────────────────────┘
                          │
                          │ Uses ScriptExecutor
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                ScriptExecutor Service (PHP)                  │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Secure Script Execution Wrapper                      │   │
│  │                                                       │   │
│  │  Security Features:                                   │   │
│  │  - Whitelist validation (only approved scripts)      │   │
│  │  - Path validation (prevents ../ attacks)            │   │
│  │  - Argument sanitization (escapeshellarg)             │   │
│  │  - Sudo execution (via /etc/sudoers.d config)          │   │
│  │  - Comprehensive logging (all executions logged)     │   │
│  │                                                       │   │
│  │  Allowed Scripts:                                     │   │
│  │  - block_device.sh                                    │   │
│  │  - unblock_device.sh                                  │   │
│  │  - whitelist_device.sh                                │   │
│  │  - get_connected_devices.sh                           │   │
│  │  - monitor_traffic.sh                                 │   │
│  └──────────────────────┬───────────────────────────────┘   │
└─────────────────────────┼───────────────────────────────────┘
                          │
                          │ Executes with sudo
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    Shell Scripts (Bash)                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  block_device.sh                                      │   │
│  │  - Validates MAC address format                     │   │
│  │  - Normalizes MAC address (uppercase, colons)        │   │
│  │  - Adds DROP rules to INPUT chain                    │   │
│  │  - Adds DROP rules to FORWARD chain                  │   │
│  │  - Idempotent (safe to run multiple times)           │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  unblock_device.sh                                    │   │
│  │  - Validates and normalizes MAC address              │   │
│  │  - Removes DROP rules from INPUT chain               │   │
│  │  - Removes DROP rules from FORWARD chain             │   │
│  │  - Idempotent                                        │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  whitelist_device.sh                                  │   │
│  │  - Removes any existing DROP rules                   │   │
│  │  - Adds ACCEPT rule at position 1 in INPUT chain      │   │
│  │  - Adds ACCEPT rule at position 1 in FORWARD chain   │   │
│  │  - Position 1 ensures bypass of all DROP rules       │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  get_connected_devices.sh                             │   │
│  │  - Queries ARP table (ip neigh show dev wlan0)       │   │
│  │  - Extracts IP and MAC addresses                     │   │
│  │  - Performs reverse DNS lookup for hostnames         │   │
│  │  - Outputs JSON array                                │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  monitor_traffic.sh                                   │   │
│  │  - Queries iptables statistics (iptables -L -v -n -x)│   │
│  │  - Correlates traffic with MAC addresses             │   │
│  │  - Outputs JSON with bytes_sent/bytes_received       │   │
│  └──────────────────────┬───────────────────────────────┘   │
└─────────────────────────┼───────────────────────────────────┘
                          │
                          │ Modifies/Queries
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    iptables (Linux Firewall)                 │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  INPUT Chain                                            │   │
│  │  - Handles traffic coming TO the Raspberry Pi          │   │
│  │  - DROP rules block device from accessing Pi services │   │
│  │  - ACCEPT rules (position 1) bypass all restrictions  │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  FORWARD Chain                                         │   │
│  │  - Handles traffic being FORWARDED through Pi        │   │
│  │  - DROP rules block device from accessing internet   │   │
│  │  - ACCEPT rules (position 1) bypass all restrictions  │   │
│  ├──────────────────────────────────────────────────────┤   │
│  │  Rule Format:                                          │   │
│  │  iptables -A FORWARD -i wlan0 -m mac                  │   │
│  │           --mac-source AA:BB:CC:DD:EE:FF -j DROP      │   │
│  │                                                       │   │
│  │  iptables -I FORWARD 1 -i wlan0 -m mac                │   │
│  │           --mac-source AA:BB:CC:DD:EE:FF -j ACCEPT   │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘

Data Flow Example (Blocking a Device):
1. CheckTimeExpiration Job detects time = 0
2. Calls NetworkService::blockDevice($device)
3. NetworkService validates MAC address exists
4. NetworkService calls ScriptExecutor::execute('block_device.sh', [MAC])
5. ScriptExecutor validates script is whitelisted
6. ScriptExecutor validates script path (prevents ../ attacks)
7. ScriptExecutor sanitizes MAC address argument
8. ScriptExecutor executes: sudo /path/to/block_device.sh 'AA:BB:CC:DD:EE:FF'
9. block_device.sh validates MAC format
10. block_device.sh normalizes MAC address
11. block_device.sh adds iptables DROP rules to INPUT and FORWARD chains
12. ScriptExecutor captures output and return code
13. ScriptExecutor logs execution
14. NetworkService updates database status to 'blocked'
15. NetworkService logs operation
16. Device is now blocked at network level
```

**Details to Include:**
- Complete three-tier architecture: NetworkService → ScriptExecutor → Shell Scripts → iptables
- NetworkService methods and responsibilities clearly shown
- ScriptExecutor security features (whitelist, path validation, argument sanitization, logging)
- All five shell scripts with their specific functions
- iptables INPUT and FORWARD chains with rule examples
- MAC address-based rules (DROP for blocking, ACCEPT for whitelisting)
- Rule priority explanation (position 1 = highest priority)
- Complete data flow example showing step-by-step blocking process
- Security measures at each layer
- Idempotent operations (scripts safe to run multiple times)
- JSON output format for query scripts (get_connected_devices.sh, monitor_traffic.sh)

**Suggested Tool:** Draw.io, Lucidchart, or architecture diagram tools

**Reference:** This diagram is based on the detailed architecture documented in `docs/NETWORK_CONTROL_SYSTEM_ARCHITECTURE.md`

---

### Diagram 2.3.7: Database Entity Relationship Diagram (ERD)
**Type:** Entity Relationship Diagram  
**Section:** 2.3.3 Schematic Design  
**Purpose:** Show complete database structure and relationships as described in Chapter 2  
**Content:**
```
┌─────────────────────────────────────────────────────────────────┐
│                    CORE USER & DEVICE ENTITIES                  │
└─────────────────────────────────────────────────────────────────┘

                    [User]
                    (Parent)
                       │
                       │ 1:N
                       │
                       ▼
                   [Device]
        (MAC address, name, status,
         remaining_time_minutes,
         total_time_allocated)
                       │
        ┌───────────────┼───────────────┬───────────────┐
        │               │               │               │
        │ 1:N           │ 1:N           │ 1:N           │ 1:N
        │               │               │               │
        ▼               ▼               ▼               ▼
[DeviceSession]  [BlockedWebsite] [FlaggedWebsite] [DeviceSchedule]
(start_time,     (url, domain)     (url, domain)    (day, start_time,
 end_time,                                              end_time,
 duration)                                               duration_limit)
        │
        │
        │ 1:N
        │
        ▼
[DeviceTimeGrant]
(device_id, minutes_granted,
 source: quiz/video, granted_at)
        │
        │
        │ 1:N
        │
        ▼
[BrowsingLog]
(device_id, url,
 visited_at, timestamp)
        │
        │
        │ 1:N
        │
        ▼
[AccessAttempt]
(device_id, url,
 attempt_type: blocked/flagged,
 attempted_at)

┌─────────────────────────────────────────────────────────────────┐
│                    QUIZ SYSTEM ENTITIES                         │
└─────────────────────────────────────────────────────────────────┘

                   [Device]
                       │
                       │ N:M (Many Devices ↔ Many Quizzes)
                       │     (via pivot table)
                       │
                       │
        ┌──────────────┴──────────────┐
        │                             │
        │                             │
        ▼                             ▼
    [Quiz]                      [QuizAttempt]
(title, description,        (device_id, quiz_id,
 questions JSON,            answers JSON,
 passing_score,             score, passed,
 time_reward_minutes)       completed_at)
        │                             │
        │ 1:N                         │
        │ (One Quiz has Many          │
        │  QuizAttempts)              │
        │                             │
        └──────────────┬──────────────┘
                       │
                       │ (QuizAttempt links Device to Quiz)

┌─────────────────────────────────────────────────────────────────┐
│                    VIDEO SYSTEM ENTITIES                         │
└─────────────────────────────────────────────────────────────────┘

                   [Device]
                       │
                       │ N:M (Many Devices ↔ Many Videos)
                       │     (via pivot table for assignments)
                       │
                       ▼
                   [Video]
        (title, description,
         video_url,
         duration_seconds,
         time_reward_minutes,
         dictionary_words_enabled,
         word_count)
                       │
                       │ 1:N (One Video can have Many Completions)
                       │
                       ▼
            [VideoCompletion]
        (device_id, video_id,
         completed_at,
         watched_duration,
         words_shown_count,
         words_entered,
         words_correct,
         passed_validation,
         attempt_number)
                       │
                       │ 1:N (One Completion records Many Word Displays)
                       │
                       ▼
            [VideoWordDisplay]
        (video_completion_id,
         dictionary_word_id,
         displayed_at_timestamp,
         word_text)
                       │
                       │ N:1 (Many Displays reference One Dictionary Word)
                       │
                       ▼
            [DictionaryWord]
        (word, definition,
         difficulty_level)

Note: Video ↔ DictionaryWord relationship is N:M
      (Many Videos can use Many Words, words can be reused)
      (Relationship established through VideoWordDisplay table)
```

**Simplified Relationship Summary:**

**Core Relationships:**
- User → Device (1:N): One parent user manages many child devices
- Device → DeviceSession, BlockedWebsite, FlaggedWebsite, DeviceSchedule, DeviceTimeGrant, BrowsingLog, AccessAttempt (1:N each)

**Quiz System:**
- Device ↔ Quiz (N:M): Many devices can be assigned many quizzes (via pivot table)
- Quiz → QuizAttempt (1:N): One quiz can have many attempts
- Device → QuizAttempt (1:N): One device can make many quiz attempts
- QuizAttempt links Device to Quiz

**Video System:**
- Device ↔ Video (N:M): Many devices can be assigned many videos (via pivot table)
- Video → VideoCompletion (1:N): One video can have many completions
- Device → VideoCompletion (1:N): One device can complete many videos
- VideoCompletion → VideoWordDisplay (1:N): One completion records many word displays
- VideoWordDisplay → DictionaryWord (N:1): Many displays reference one dictionary word
- Video ↔ DictionaryWord (N:M): Videos can use many words, words can be used in many videos

**Relationship Notation Explanation:**

In Entity Relationship Diagrams (ERDs), relationship notation describes how entities (database tables) relate to each other:

- **1:N (One-to-Many)**: One record in the first table can be associated with many records in the second table, but each record in the second table belongs to only one record in the first table.
  - **Example**: One User (parent) can have many Devices, but each Device belongs to only one User.
  - **Example**: One Device can have many DeviceSessions, but each DeviceSession belongs to only one Device.

- **N:1 (Many-to-One)**: Many records in the first table can be associated with one record in the second table. This is the inverse of 1:N.
  - **Example**: Many VideoWordDisplays can reference one DictionaryWord, but each VideoWordDisplay belongs to one DictionaryWord.

- **N:M (Many-to-Many)**: Many records in the first table can be associated with many records in the second table, and vice versa. This typically requires a junction/pivot table.
  - **Example**: Many Devices can be assigned to many Quizzes, and many Quizzes can be assigned to many Devices. This allows parents to assign different quizzes to different devices.

**Details to Include:**
- All core entities (tables) from scope.md database schema:
  - **User** (parent user)
  - **Device** (with remaining_time_minutes, total_time_allocated as per scope.md)
  - **DeviceSession** (track active internet sessions)
  - **DeviceTimeGrant** (track time grants after quiz/video completion)
  - **BlockedWebsite** (websites to block for specific devices)
  - **FlaggedWebsite** (websites to monitor/flag when visited)
  - **DeviceSchedule** (time-based internet access rules)
  - **Quiz** (with title, description, questions JSON, passing_score, time_reward_minutes)
  - **QuizAttempt** (with device_id, quiz_id, answers JSON, score, passed, completed_at)
  - **Video** (with dictionary_words_enabled, word_count as per scope.md)
  - **VideoCompletion** (with words_shown_count, words_entered, words_correct, passed_validation, attempt_number as per scope.md)
  - **VideoWordDisplay** (track which words shown during video viewing)
  - **DictionaryWord** (educational word database)
  - **BrowsingLog** (track visited websites, timestamps, device association)
  - **AccessAttempt** (log blocked website access attempts and flagged website visits)
- All relationship types (1:N, N:1, N:M) clearly labeled matching scope.md relationships
- Key attributes for each entity shown in parentheses (matching scope.md field descriptions)
- Foreign key relationships indicated by the connection lines
- Relationship cardinality explained in the notation section

**Suggested Tool:** Draw.io, MySQL Workbench, or ERD tools

---

### 2.3.4 Illustrative Design

The Illustrative Design section provides detailed workflow examples that demonstrate how the system operates in practice. These diagrams illustrate the step-by-step processes described in Chapter 2.3.4, showing how different components interact to accomplish system goals. This section includes:

- **Complete Device Registration to Time Grant Workflow**: Shows the full process from when a parent registers a device through the system granting time after quiz/video completion.
- **Video System with Dictionary Words Workflow**: Demonstrates how the video system works with dictionary word validation.
- **Quiz System Workflow**: Shows how the quiz system validates answers and grants time.

### Diagram 2.3.4: Device Registration to Time Grant Sequence
**Type:** Simplified Sequence Diagram  
**Section:** 2.3.4 Illustrative Design  
**Purpose:** Show complete workflow from device registration to time grant  
**Content:**
```
┌─────────────────────────────────────────────────────────────────┐
│ STEP 1: Device Registration & Time Initialization              │
└─────────────────────────────────────────────────────────────────┘
    Parent          DeviceController      TimeTrackingService
      │                    │                      │
      │──Register Device──>│                      │
      │                    │                      │
      │                    │──Initialize Time────>│
      │                    │                      │
      │                    │<──Time Allocated─────│
      │                    │                      │
      │<──Device Registered│                      │

┌─────────────────────────────────────────────────────────────────┐
│ STEP 2: Background Monitoring (Runs Continuously)               │
└─────────────────────────────────────────────────────────────────┘
    Background Jobs         TimeTrackingService
          │                         │
          │──Monitor Sessions──────>│
          │   (Every minute)        │
          │                         │
          │<──Active Devices─────────│
          │                         │
          │──Deduct Time───────────>│
          │                         │
          │<──Time Updated──────────│

┌─────────────────────────────────────────────────────────────────┐
│ STEP 3: Time Expiration Detection                                │
└─────────────────────────────────────────────────────────────────┘
    Background Jobs    TimeTrackingService    NetworkService    ScriptExecutor    iptables
          │                    │                    │                  │              │
          │──Check Expiration─>│                    │                  │              │
          │                    │                    │                  │              │
          │<──Time = 0─────────│                    │                  │              │
          │                    │                    │                  │              │
          │──Block Device──────┼──>blockDevice()───>│                  │              │
          │                    │                    │                  │              │
          │                    │                    │──execute()──────>│              │
          │                    │                    │  ('block_device.│              │
          │                    │                    │   sh', [MAC])    │              │
          │                    │                    │                  │              │
          │                    │                    │                  │──sudo exec──>│
          │                    │                    │                  │  (adds DROP  │
          │                    │                    │                  │   rules to    │
          │                    │                    │                  │   INPUT &     │
          │                    │                    │                  │   FORWARD)    │
          │                    │                    │                  │              │
          │                    │                    │<──Success───────│<──Rules Added│
          │                    │                    │                  │              │
          │                    │<──Device Blocked───│                  │              │

┌─────────────────────────────────────────────────────────────────┐
│ STEP 4: Captive Portal Redirect                                 │
└─────────────────────────────────────────────────────────────────┘
    NoDogSplash          PortalController
          │                     │
          │──Intercept HTTP────>│
          │                     │
          │<──Redirect Request──│
          │                     │
          │──Show Portal────────>│
          │                     │
          │<──Portal Displayed───│

┌─────────────────────────────────────────────────────────────────┐
│ STEP 5: Quiz Selection & Validation                             │
└─────────────────────────────────────────────────────────────────┘
    Child          PortalController      QuizController
      │                    │                    │
      │──Select Quiz──────>│                    │
      │                    │                    │
      │                    │──Load Quiz────────>│
      │                    │                    │
      │                    │<──Quiz Questions───│
      │                    │                    │
      │<──Display Quiz──────│                    │
      │                    │                    │
      │──Submit Answers───>│                    │
      │                    │                    │
      │                    │──Validate Answers─>│
      │                    │                    │
      │                    │<──Validation Result│
      │                    │                    │
      │<──Quiz Result───────│                    │

┌─────────────────────────────────────────────────────────────────┐
│ STEP 6: Time Granting & Device Unblocking                       │
└─────────────────────────────────────────────────────────────────┘
    QuizController  TimeGrantingService  NetworkService  ScriptExecutor  iptables  WebSocket
          │                  │                  │              │            │          │
          │──Grant Time──────>│                  │              │            │          │
          │                  │                  │              │            │          │
          │                  │──unblockDevice()─>│              │            │          │
          │                  │                  │              │            │          │
          │                  │                  │──execute()───>│            │          │
          │                  │                  │  ('unblock_  │            │          │
          │                  │                  │   device.sh',│            │          │
          │                  │                  │   [MAC])     │            │          │
          │                  │                  │              │            │          │
          │                  │                  │              │──sudo exec>│          │
          │                  │                  │              │  (removes  │          │
          │                  │                  │              │   DROP     │          │
          │                  │                  │              │   rules)   │          │
          │                  │                  │              │            │          │
          │                  │                  │<──Success─────│<──Rules    │          │
          │                  │                  │              │   Removed  │          │
          │                  │                  │              │            │          │
          │                  │<──Device Unblocked│              │            │          │
          │                  │                  │              │            │          │
          │                  │──Notify Parent───┼──────────────┼───────────>│
          │                  │                  │              │            │          │
          │<──Time Granted───│                  │              │            │          │

┌─────────────────────────────────────────────────────────────────┐
│ STEP 7: Device Regains Internet Access                          │
└─────────────────────────────────────────────────────────────────┘
    Child Device          Firewall          Internet
          │                    │                │
          │──Request Access───>│                │
          │                    │                │
          │                    │──Allow Traffic─>│
          │                    │                │
          │<──Internet Access───│                │
```

**Instruction Text:**
1. **Device Registration**: Parent registers child device through dashboard. System initializes time allocation (as per scope.md captive portal flow).
2. **Background Monitoring**: Background jobs (TrackActiveSessions) continuously monitor active sessions and deduct time every minute (as per scope.md).
3. **Time Expiration**: When time reaches zero, background job (CheckTimeExpiration) triggers firewall to block device and redirects to portal (as per scope.md).
4. **Captive Portal**: NoDogSplash intercepts HTTP requests and redirects to captive portal (as per scope.md).
5. **Quiz Activity**: Child selects quiz, answers questions, and system validates responses (as per scope.md quiz flow).
6. **Time Granting**: Upon successful validation, TimeGrantingService grants time, unblocks device via iptables/nftables, and notifies parent via WebSocket (as per scope.md).
7. **Internet Access Restored**: Device can now access internet through the Pi's WiFi network.

**Details to Include:**
- Simplified step-by-step flow (7 main steps)
- Clear component interactions in each step
- Instruction text explaining what happens at each stage
- Logical grouping of related operations
- Easy to follow top-to-bottom sequence

**Suggested Tool:** Draw.io, PlantUML, or sequence diagram tools

---

### Diagram 2.3.5: Video System with Dictionary Words Workflow
**Type:** Flowchart  
**Section:** 2.3.4 Illustrative Design  
**Purpose:** Show video system with dictionary word validation workflow as described in Chapter 2  
**Content:**
```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: Parent Configuration (Parent Dashboard)            │
└─────────────────────────────────────────────────────────────┘
    Parent          VideoController      VideoWordService
      │                    │                      │
      │──Upload Video─────>│                      │
      │                    │                      │
      │                    │──Enable Dictionary──>│
      │                    │   Words Feature      │
      │                    │                      │
      │                    │──Set Word Count─────>│
      │                    │   (How many words)   │
      │                    │                      │
      │                    │──Select Random Words>│
      │                    │   (From dictionary   │
      │                    │    pool)            │
      │                    │                      │
      │<──Video Saved──────│                      │

┌─────────────────────────────────────────────────────────────┐
│ STEP 2: Child Selects Video (Captive Portal)                │
└─────────────────────────────────────────────────────────────┘
    Child           PortalController      VideoController
      │                    │                      │
      │──Select "Watch───>│                      │
      │   Video"           │                      │
      │                    │                      │
      │                    │──Load Video─────────>│
      │                    │                      │
      │<──Video Player─────│                      │
      │   Displayed        │                      │

┌─────────────────────────────────────────────────────────────┐
│ STEP 3: Video Playback with Dictionary Words                │
└─────────────────────────────────────────────────────────────┘
    Video Player    VideoWordService      VideoWordDisplay Table
          │                 │                      │
          │──Video Plays───>│                      │
          │                 │                      │
          │ (Fast-forward   │                      │
          │  disabled)      │                      │
          │ (Seeking        │                      │
          │  disabled)      │                      │
          │                 │                      │
          │                 │──Display Word───────>│
          │                 │   (Random interval) │
          │                 │   (e.g., 0:30, 2:15) │
          │                 │                      │
          │<──Word Overlay──│                      │
          │   (Appears for  │                      │
          │    few seconds) │                      │
          │                 │                      │
          │──Record Word───>│                      │
          │   + Timestamp   │                      │
          │                 │                      │
          │                 │──Save to DB─────────>│
          │                 │                      │
          │ (Video continues│                      │
          │  playing)       │                      │
          │                 │                      │
          │ (More words     │                      │
          │  appear at      │                      │
          │  random times)  │                      │

┌─────────────────────────────────────────────────────────────┐
│ STEP 4: Video Completion & Word Validation                  │
└─────────────────────────────────────────────────────────────┘
    Video Player    VideoCompletion      VideoWordService
          │                 │                      │
          │──Video Reaches─>│                      │
          │   End           │                      │
          │                 │                      │
          │<──Show Word─────│                      │
          │   Validation    │                      │
          │   Form          │                      │
          │                 │                      │
          │──Child Inputs──>│                      │
          │   Words         │                      │
          │                 │                      │
          │                 │──Validate Words─────>│
          │                 │   (Compare entered  │
          │                 │    vs. displayed)   │
          │                 │                      │
          │                 │<──Validation Result─│
          │                 │                      │
          │    ┌────────────┴──────────┐          │
          │    │                       │          │
          │ All Correct        Incorrect/Missing  │
          │    │                       │          │
          │    │                       ▼          │
          │    │                  [Show Error]    │
          │    │                  (Correct words) │
          │    │                       │          │
          │    │                       │          │
          │    │                  [Restart Video] │
          │    │                  (From beginning)│
          │    │                       │          │
          │    │                       │          │
          │    │                  [New Random Words]│
          │    │                       │          │
          │    │                       └──────┐   │
          │    │                              │   │
          │    └──────────────────────────────┘   │
          │                 │                      │
          │                 │                      │
          │                 │ (Loop until correct) │

┌─────────────────────────────────────────────────────────────┐
│ STEP 5: Time Granting & Device Unblocking                   │
└─────────────────────────────────────────────────────────────┘
    VideoCompletion  TimeGrantingService  Firewall    WebSocket
          │                 │                  │          │
          │──All Words─────>│                  │          │
          │   Correct       │                  │          │
          │                 │                  │          │
          │                 │──Grant Time──────>│          │
          │                 │   (Update DB)     │          │
          │                 │                  │          │
          │                 │──Unblock Device──>│          │
          │                 │   (iptables)      │          │
          │                 │                  │          │
          │                 │──Notify Parent───┼─────────>│
          │                 │                  │          │
          │<──Time Granted──│                  │          │
          │   Success Page  │                  │          │
          │                 │                  │          │
          │──Device Regains─┼──────────────────>│          │
          │   Internet      │                  │          │
```

**Instruction Text:**
1. **Parent Configuration**: Parent uploads video through VideoController, enables dictionary words feature, sets word count (as per scope.md video management system). VideoWordService randomly selects words from dictionary pool.
2. **Child Video Selection**: Child selects "Watch Video" in captive portal (as per scope.md video flow). PortalController loads video and displays video player.
3. **Video Playback**: Video plays with fast-forward and seeking disabled (only play, pause, volume controls available as per scope.md). Random dictionary words appear at random time intervals as overlays (e.g., at 0:30, 2:15, 5:45 as per scope.md). System records each word and timestamp in VideoWordDisplay table.
4. **Word Validation**: When video reaches end (played to completion as per scope.md), validation form appears. Child inputs words they saw. System compares input against recorded words.
5. **Retry Logic**: If words are incorrect or missing, show error message with correct words, video restarts from beginning with new random words (as per scope.md). Process repeats until all words are correct.
6. **Time Granting**: Upon successful validation, TimeGrantingService grants time, device is unblocked via iptables/nftables, NoDogSplash allows device through, and parent is notified via WebSocket (as per scope.md).

**Details to Include:**
- Complete workflow from parent upload to device unblocking
- VideoWordService for random word selection and timestamp generation
- VideoWordDisplay table for tracking displayed words
- VideoCompletion table for tracking completion with word validation fields
- Disabled video controls (fast-forward, seeking)
- Random word display at unpredictable intervals
- Word validation and retry mechanism
- Integration with TimeGrantingService, Firewall, and WebSocket

**Suggested Tool:** Draw.io, Lucidchart, or flowchart tools

---

### Diagram 2.3.6: Quiz System Workflow
**Type:** Flowchart  
**Section:** 2.3.4 Illustrative Design  
**Purpose:** Show quiz system workflow from selection to time grant as described in Chapter 2  
**Content:**
```
┌─────────────────────────────────────────────────────────────┐
│ STEP 1: Child Selects Quiz (Captive Portal)                 │
└─────────────────────────────────────────────────────────────┘
    Child           PortalController      QuizController
      │                    │                      │
      │──Select "Take─────>│                      │
      │   Quiz"            │                      │
      │                    │                      │
      │                    │──Load Quiz───────────>│
      │                    │   (Parent-configured)│
      │                    │   (questions JSON,   │
      │                    │    passing_score,     │
      │                    │    time_reward_minutes)│
      │                    │                      │
      │<──Display Quiz─────│                      │
      │   Questions        │                      │

┌─────────────────────────────────────────────────────────────┐
│ STEP 2: Quiz Answer Submission & Validation                  │
└─────────────────────────────────────────────────────────────┘
    Child           QuizAttemptController  Quiz Table
      │                    │                      │
      │──Submit Answers───>│                      │
      │                    │                      │
      │                    │──Validate Answers───>│
      │                    │   (Compare against  │
      │                    │    correct answers) │
      │                    │                      │
      │                    │──Calculate Score───>│
      │                    │   (Based on correct │
      │                    │    answers)         │
      │                    │                      │
      │                    │──Check Passing──────>│
      │                    │   Score             │
      │                    │                      │
      │                    │<──Validation Result─│
      │                    │                      │
      │    ┌───────────────┴──────────┐         │
      │    │                           │         │
      │ Pass (Score >=              Fail (Score <│
      │  passing_score)              passing_score)│
      │    │                           │         │
      │    │                           ▼         │
      │    │                      [Show Error]   │
      │    │                      (Display score │
      │    │                       and feedback) │
      │    │                           │         │
      │    │                           │         │
      │    │                      [Allow Retry] │
      │    │                      (Retry quiz or │
      │    │                       choose video) │
      │    │                           │         │
      │    │                           └───────┐ │
      │    │                                   │ │
      │    │                                   │ │
      │    │                      ┌────────────┘ │
      │    │                      │              │
      │    │                      ▼              │
      │    │              [Back to Quiz/Video    │
      │    │               Selection]            │
      │    │                                     │
      │    └─────────────                        │
      │                 │                        │
      │                 │                        │

┌─────────────────────────────────────────────────────────────┐
│ STEP 3: Time Granting & Device Unblocking                   │
└─────────────────────────────────────────────────────────────┘
    QuizAttempt     TimeGrantingService  DeviceTimeGrant  Firewall
      │                 │                    │              │
      │──Fire TimeGrant─>│                    │              │
      │   Event          │                    │              │
      │                 │                    │              │
      │                 │──Create Record─────>│              │
      │                 │   (device_id,       │              │
      │                 │    minutes_granted, │              │
      │                 │    source: 'quiz', │              │
      │                 │    granted_at)      │              │
      │                 │                    │              │
      │                 │──Update Device────>│              │
      │                 │   (remaining_time_ │              │
      │                 │    minutes)        │              │
      │                 │                    │              │
      │                 │──Execute Script───>│              │
      │                 │   (unblock_device.│              │
      │                 │    sh via          │              │
      │                 │    ScriptExecutor) │              │
      │                 │                    │              │
      │                 │                    │──Remove Block>│
      │                 │                    │   (iptables) │
      │                 │                    │              │
      │                 │──Notify Parent────┼───────────────>│
      │                 │   (WebSocket)     │              │
      │                 │                    │              │
      │<──Success Page───│                    │              │
      │   (Time granted) │                    │              │
      │                 │                    │              │
      │──Device Regains─┼────────────────────┼──────────────>│
      │   Internet      │                    │              │
```

**Instruction Text:**
1. **Quiz Selection**: Child selects "Take Quiz" in captive portal (as per scope.md quiz flow). PortalController loads parent-configured quiz from database.
2. **Answer Validation**: Child submits answers. QuizAttemptController validates answers against correct answers, calculates score, and checks against passing score (as per scope.md).
3. **Pass/Fail Handling**: If score meets passing score, proceed to time granting. If failed, show error message and allow retry or choose video option (as per scope.md).
4. **Time Granting**: TimeGrantingService creates DeviceTimeGrant record, updates Device.remaining_time_minutes, and executes unblock_device.sh script via ScriptExecutor (as per scope.md).
5. **Device Unblocking**: Shell script removes iptables/nftables block. NoDogSplash allows device through (as per scope.md).
6. **Real-time Notification**: WebSocket broadcasts TimeGranted event to parent dashboard for real-time notification (as per scope.md WebSocket setup).
7. **Success**: Device regains internet access, child can continue browsing.

**Details to Include:**
- Complete workflow from quiz selection to internet access restoration
- QuizAttemptController validation and scoring logic
- Database operations (QuizAttempt creation, DeviceTimeGrant creation, Device time update)
- Shell script execution for firewall unblocking via ScriptExecutor
- NoDogSplash integration for device unblocking
- Real-time parent notification via WebSocket (Laravel Broadcasting)
- Retry mechanism allowing child to retry quiz or choose video option

**Suggested Tool:** Draw.io, Lucidchart, or flowchart tools

---

### 2.3.5 Design Standards

*Note: This section contains descriptive text about coding standards (PSR-12), database conventions, testing (PHPUnit), and accessibility (WCAG 2.1 AA). No diagrams are required as standards are explained in prose in Chapter 2.3.5.*

---

### 2.3.6 Design Constraints

*Note: This section contains descriptive text about processing headroom, storage footprint, offline operation, security surface, real-time communication, and NoDogSplash integration constraints. No diagrams are required as constraints are explained in prose in Chapter 2.3.6.*

---

## Summary Checklist


### Recommended Tools:
- **Draw.io (diagrams.net)** - Free, web-based, good for all diagram types
- **Lucidchart** - Professional, subscription-based
- **PowerPoint** - Simple diagrams, widely available
- **Figma/Adobe XD** - For UI mockups
- **PlantUML** - For sequence diagrams (text-based)
- **MySQL Workbench** - For ERD diagrams

### Notes:
- All diagrams should be numbered (Figure 2.1, Figure 2.2, etc.)
- Each diagram needs a caption explaining what it shows
- Diagrams should be referenced in the text (e.g., "as shown in Figure 2.2.2")
- High resolution (300 DPI minimum) for printing
- Consistent styling and color scheme throughout

### Priority Levels:
- **High Priority:** Diagrams 2.2.2, 2.2.3, 2.3.1, 2.3.2, 2.3.3, 2.3.3a, 2.3.7 (Core architecture and system design)
- **Medium Priority:** Diagrams 2.2.1, 2.2.4, 2.3.4, 2.3.5, 2.3.6 (Detailed workflows and processes)
- **Low Priority:** Diagrams 2.2.5, 2.2.6, 2.2.7 (UI mockups - can use screenshots from actual system)

**Note:** Diagram 2.3.3a (Network Control System Architecture) is a new high-priority diagram that provides detailed insight into the network control system, complementing the overview shown in Diagram 2.3.3. This diagram is essential for understanding how device blocking/unblocking works at the system level.

---

## How to Reference Diagrams in Chapter 2

When writing Chapter 2, reference diagrams like this:

**Example:**
"The network topology follows the structure shown in Figure 2.2.2, where the Raspberry Pi acts as an intermediary between the home router and child devices."

**Format:**
- First mention: "Figure 2.2.2" or "as shown in Figure 2.2.2"
- Subsequent mentions: "Figure 2.2.2" or "the diagram"
- Caption format: "Figure 2.2.2: Network Topology Diagram showing the connection between ISP router, Raspberry Pi, and child devices."

