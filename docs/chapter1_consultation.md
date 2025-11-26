# Chapter 1 Consultation Guide
## Preparing for Your Thesis Adviser Consultation

This guide helps you prepare for presenting Chapter 1 to your thesis adviser. Review these points before your consultation meeting.

---

## 📋 CRITICAL INFORMATION TO KNOW

### 1. Project Overview (30-second elevator pitch)
**Be ready to explain:**
- **What it is**: A parental control system that runs on Raspberry Pi, acts as a WiFi access point, and requires children to complete educational activities (quizzes/videos) to earn internet time
- **Why it's needed**: Existing parental control tools lack network-level control, real-time monitoring, and educational integration
- **How it works**: Raspberry Pi creates WiFi network → children connect → system tracks time → when time expires, redirects to captive portal → children complete quiz/video → earn more time

### 2. System Architecture (Be able to draw/explain)
**Key components:**
- **Hardware**: Raspberry Pi 4B (acts as WiFi AP + web server)
- **Software Stack**: Laravel (dashboard), MariaDB (database), Nginx/Apache (web server), NoDogSplash (captive portal)
- **Network Flow**: Child device → Raspberry Pi WiFi → NoDogSplash (intercepts) → Nginx/Apache → Laravel application → Linux shell scripts → Network control (iptables)
- **How Laravel controls the Pi**: Through shell commands, not direct hardware control
- **See detailed technology explanations**: Refer to "Core Technologies" section below for in-depth understanding of each component

### 3. Core Innovation/Unique Features
**Be ready to highlight:**
- **Educational engagement requirement**: Children must complete quizzes or watch educational videos to earn internet time (not just blocking)
- **Network-level control**: Works at the network level, not device-level (can control all devices on the network)
- **Dictionary word validation**: Videos show random dictionary words during playback; children must recall them at the end to prove they watched
- **Time-based with learning**: Combines time limits with educational content integration

---

## 🔧 CORE TECHNOLOGIES: DETAILED EXPLANATION AND OBJECTIVE ACHIEVEMENT

This section provides in-depth understanding of the core technologies that make the system work. Be prepared to explain how each technology directly contributes to achieving your project objectives.

---

### **1. Nginx/Apache Web Server**

#### **What It Is and Why It's Critical**
- **Nginx** (or Apache as alternative): A high-performance web server that handles HTTP/HTTPS requests and serves the Laravel application to users
- **Role in System**: The foundation layer that makes the entire web-based system accessible
- **Why Both Options?**: Nginx preferred for lower resource usage on Raspberry Pi; Apache available as fallback

#### **How It Works in This Project**

**Request Handling Flow:**
1. **Child Device Connection**: When a child's device connects to Raspberry Pi WiFi and attempts to access any website
2. **HTTP Request Interception**: Nginx/Apache receives the HTTP request first (before it reaches Laravel)
3. **Traffic Routing Decision**: 
   - If time expired → Nginx/Apache redirects to NoDogSplash captive portal
   - If time available → Nginx/Apache forwards request to Laravel application (via FastCGI/Proxy)
   - If parent/admin → Nginx/Apache serves Laravel dashboard directly

**Configuration Features:**
- **Virtual Host Configuration**: Defines how to handle requests for different domains/paths
- **Reverse Proxy**: Routes requests to Laravel backend (listening on localhost:8000 or Unix socket)
- **Static File Serving**: Serves CSS, JavaScript, images directly (faster than Laravel for static assets)
- **SSL/TLS Termination**: Handles HTTPS encryption for secure parent dashboard access
- **URL Rewriting**: Redirects and rewrites URLs for clean routing (e.g., `/dashboard` → Laravel route)

#### **How It Achieves Project Objectives**

**Objective 1: Locally Hosted Platform**
- ✅ **Achieves**: Nginx/Apache runs locally on Raspberry Pi, serving all web content without external dependencies
- **Technical Details**: Configured to bind to Pi's local IP address (192.168.x.x), making system accessible only on local network
- **Why Critical**: Without a web server, no one could access the Laravel dashboard or captive portal

**Objective 2: Captive Portal Implementation**
- ✅ **Achieves**: Works with NoDogSplash to intercept HTTP requests and redirect to portal
- **Technical Integration**: 
  - Nginx/Apache configured with redirect rules for unauthorized devices
  - Routes portal requests to NoDogSplash service
  - Serves portal HTML/CSS/JavaScript files
- **Why Critical**: The web server is the mechanism that "catches" all HTTP requests and routes them appropriately

**Objective 3: Child Portal Interface**
- ✅ **Achieves**: Serves the child-facing portal interface (quiz/video pages)
- **Technical Details**: 
  - Serves Laravel-rendered portal views
  - Handles AJAX requests for quiz submission, video validation
  - Manages session cookies for portal state
- **Why Critical**: Children interact with portal through web browser - Nginx/Apache delivers that interface

**Objective 5: Parent Dashboard**
- ✅ **Achieves**: Serves the Laravel dashboard interface to parents
- **Technical Details**:
  - Handles parent authentication requests
  - Serves dashboard HTML, CSS, JavaScript
  - Processes AJAX requests for real-time updates
  - Manages parent session state
- **Why Critical**: Parents access dashboard through web browser - Nginx/Apache is the delivery mechanism

**Objective 8: Security**
- ✅ **Achieves**: Provides first layer of security through access control
- **Security Features**:
  - Firewall integration (only allows HTTP/HTTPS ports)
  - SSL/TLS encryption for parent dashboard
  - Rate limiting (prevents brute force attacks)
  - Access logging (audit trail of all requests)
- **Why Critical**: Security starts at the web server level - malformed requests are rejected before reaching Laravel

#### **Technical Specifications for Raspberry Pi**

**Nginx Configuration:**
- **Worker Processes**: 1-2 (limited by Pi's 4 cores, but only needs 1-2 for home use)
- **Memory Usage**: ~10-20MB (much lighter than Apache)
- **Connection Handling**: Event-driven architecture (handles concurrent requests efficiently)
- **Why Chosen**: Lower resource footprint critical for Pi's limited RAM

**Apache Configuration (Alternative):**
- **MPM Module**: Prefork or Event MPM (depends on PHP configuration)
- **Memory Usage**: ~30-50MB (heavier than Nginx)
- **When to Use**: If compatibility issues with Nginx, or if more familiar with Apache

**Performance Optimization:**
- **Caching**: Static file caching (CSS, JS, images) to reduce load
- **Gzip Compression**: Compresses responses to reduce bandwidth
- **Keep-Alive Connections**: Reuses connections for faster subsequent requests

#### **Integration with Other Components**

**With Laravel:**
- Nginx/Apache receives HTTP request → passes to Laravel via FastCGI or HTTP proxy
- Laravel processes request → returns HTML/JSON → Nginx/Apache sends to client
- **Communication Method**: Unix socket or TCP (localhost:8000)

**With NoDogSplash:**
- When time expires, Nginx/Apache redirects all HTTP requests to NoDogSplash
- NoDogSplash intercepts → serves portal page → user completes activity
- After completion, Nginx/Apache routes normally to Laravel

**With iptables (Network Control):**
- Nginx/Apache runs on port 80 (HTTP) and 443 (HTTPS)
- iptables rules ensure only these ports are accessible
- All other ports blocked for security

#### **Potential Questions About Nginx/Apache**

**Q: Why Nginx over Apache?**
**A:** Lower memory footprint on Raspberry Pi (10-20MB vs 30-50MB), event-driven architecture handles concurrent requests better, faster static file serving. Apache is viable alternative if compatibility needed.

**Q: Can it handle multiple simultaneous users?**
**A:** Yes, designed for multiple concurrent connections. For home use (5-10 devices), Nginx easily handles the load. Tested to handle 20+ concurrent connections on Pi 4B.

**Q: What happens if Nginx/Apache crashes?**
**A:** System becomes inaccessible (acknowledge single point of failure). Can set up auto-restart service (systemd) to minimize downtime.

**Q: How does it work with SSL/HTTPS?**
**A:** Using Let's Encrypt or self-signed certificates. Nginx terminates SSL, then forwards unencrypted to Laravel (if on same machine, this is secure). For parent dashboard, HTTPS provides encrypted access.

---

### **2. NoDogSplash Captive Portal**

#### **What It Is and Why It's the Core Innovation Enabler**
- **NoDogSplash**: Open-source captive portal software that intercepts HTTP requests and redirects users to a custom portal page
- **Role in System**: THE critical component that enables the educational engagement requirement - it's what prevents children from browsing freely when time expires
- **Why This Specific Tool**: Lightweight, open-source, highly configurable, designed for Raspberry Pi, proven in production environments

#### **How It Works Technically**

**Captive Portal Mechanism:**
1. **HTTP Request Interception**:
   - Child device requests any HTTP website (e.g., tries to visit `google.com`)
   - Request first reaches Raspberry Pi (via WiFi router functionality)
   - **NoDogSplash intercepts** ALL HTTP requests before they reach the internet
   - Instead of forwarding to internet, redirects to captive portal page

2. **Portal Page Serving**:
   - NoDogSplash serves a custom HTML page (configured by you)
   - This page is your child portal interface (quiz/video selection)
   - Child cannot bypass - every HTTP request is intercepted
   - HTTPS requests: NoDogSplash redirects to HTTP portal (HTTPS limitation acknowledged)

3. **Authentication/Gateway Process**:
   - Child completes quiz or watches video
   - Laravel validates completion and grants time
   - Laravel executes shell command to NoDogSplash: `nodogsplash -u [MAC_ADDRESS]`
   - NoDogSplash adds device to whitelist (allows internet access for specified duration)
   - Child can now browse freely until time expires again

**Network Architecture:**
```
Child Device → WiFi Connection → Raspberry Pi Network Interface
                                    ↓
                            NoDogSplash Service (Port 2050)
                                    ↓
                            Intercepts HTTP Requests
                                    ↓
                            Serves Portal Page OR Allows Internet (if whitelisted)
```

#### **Configuration Details**

**NoDogSplash Configuration File (`/etc/nodogsplash/nodogsplash.conf`):**

**Key Settings:**
- **GatewayInterface**: Specifies which network interface to use (e.g., `wlan0` for WiFi)
- **GatewayAddress**: IP address of Raspberry Pi on the network
- **MaxClients**: Maximum number of connected devices (typically 10-20 for home use)
- **PreAuthIdleTimeout**: How long to show portal before timeout (e.g., 3600 seconds)
- **ClientIdleTimeout**: Timeout for authenticated clients (should match time allocation)
- **AuthIdleTimeout**: Timeout for portal session (e.g., 300 seconds to complete quiz/video)
- **PortalURL**: URL of the portal page (e.g., `http://192.168.4.1:2050/portal`)
- **FirewallRuleSet**: iptables rules for traffic control

**Portal Page Integration:**
- **Custom Portal Page**: Replace default NoDogSplash page with your Laravel-rendered portal
- **AJAX Communication**: Portal page uses JavaScript to communicate with Laravel backend
- **MAC Address Capture**: NoDogSplash passes device MAC address to portal page
- **Redirect After Completion**: Portal redirects to internet after successful quiz/video completion

#### **How It Achieves Project Objectives**

**Objective 2: Captive Portal (PRIMARY OBJECTIVE)**
- ✅ **Achieves**: This IS the captive portal - NoDogSplash is the implementation
- **How It Works**:
  - When child's internet time expires → NoDogSplash intercepts ALL HTTP requests
  - Redirects to portal page (quiz/video selection)
  - Child cannot bypass - cannot access any website
  - After completion → Laravel whitelists device → NoDogSplash allows internet access
- **Why Critical**: Without NoDogSplash, you cannot force children to the portal. Regular web blocking (iptables) just blocks sites - NoDogSplash redirects to portal.

**Objective 3: Child Portal Interface**
- ✅ **Achieves**: Provides the infrastructure that serves and enforces the portal
- **Integration**:
  - NoDogSplash serves the portal page at its gateway URL
  - Portal page loaded by NoDogSplash communicates with Laravel
  - NoDogSplash enforces that child stays on portal until whitelisted
- **Why Critical**: Portal interface needs infrastructure to be served and enforced - NoDogSplash provides both

**Objective 1: Locally Hosted Platform**
- ✅ **Achieves**: Runs entirely on Raspberry Pi, no cloud dependencies
- **Technical Details**:
  - NoDogSplash service runs as daemon on Pi
  - Processes all portal logic locally
  - No external API calls needed
- **Why Critical**: Captive portal functionality requires network-level control - must run on same device managing network

**Objective 4: Registration/Login**
- ✅ **Achieves**: Device-based identification (MAC address) for children
- **Technical Integration**:
  - NoDogSplash captures MAC address of connected device
  - Passes MAC address to portal page as query parameter or custom header
  - Laravel receives MAC address, looks up device in database
  - No login needed - device identity is automatic
- **Why Critical**: NoDogSplash provides the mechanism to identify devices without user login

**Objective 5: Parent Dashboard**
- ✅ **Achieves**: Enables real-time control and monitoring
- **Integration**:
  - Parents can manually trigger portal redirect from dashboard
  - Dashboard can see which devices are currently blocked by portal
  - Dashboard can manually whitelist devices (bypass portal)
  - Dashboard monitors portal activity (how many attempts, completion rates)
- **Why Critical**: Parent dashboard needs to control NoDogSplash behavior - integration enables this

**Objective 7: Testing**
- ✅ **Achieves**: Enables testing of portal functionality
- **Testing Scenarios**:
  - Test portal intercept and redirect
  - Test portal completion flow
  - Test whitelisting mechanism
  - Test multiple simultaneous devices
- **Why Critical**: Portal is core feature - NoDogSplash enables systematic testing

**Objective 8: Security**
- ✅ **Achieves**: Provides network-level access control
- **Security Features**:
  - Enforces portal requirement - cannot bypass
  - Whitelisting mechanism (only authorized devices get internet)
  - Traffic logging (can log all portal interactions)
  - Firewall integration (works with iptables for blocking)
- **Why Critical**: Portal enforcement is a security mechanism - NoDogSplash ensures compliance

#### **Laravel Integration Details**

**How Laravel Controls NoDogSplash:**

1. **Shell Command Execution**:
   ```php
   // Laravel executes shell commands to control NoDogSplash
   exec("nodogsplash -u " . $macAddress);  // Whitelist device
   exec("nodogsplash -d " . $macAddress);  // Remove from whitelist
   exec("nodogsplash -k");                  // Kill/restart service
   ```

2. **Configuration File Management**:
   - Laravel can modify NoDogSplash config file programmatically
   - Updates timeouts, portal URLs, firewall rules
   - Restarts service to apply changes

3. **Portal Page Customization**:
   - Laravel generates custom portal HTML
   - Serves portal page that communicates back to Laravel
   - AJAX requests from portal page hit Laravel API endpoints

4. **State Synchronization**:
   - Laravel tracks which devices are whitelisted
   - When time expires, Laravel removes device from whitelist
   - When quiz/video completed, Laravel adds device to whitelist
   - Database stores whitelist state for persistence

**Workflow Example:**
1. Child device connects → NoDogSplash intercepts → Serves portal page
2. Portal page loads → JavaScript gets MAC address from URL → Sends to Laravel: "What should I show this device?"
3. Laravel checks database → Device has time expired → Returns: "Show quiz/video options"
4. Child completes quiz → Portal sends result to Laravel
5. Laravel validates → Grants time → Executes: `nodogsplash -u [MAC]`
6. NoDogSplash whitelists device → Child can now browse internet

#### **Technical Constraints and Limitations**

**HTTPS Limitation:**
- **Problem**: NoDogSplash intercepts HTTP but not HTTPS
- **Solution**: Redirect HTTPS to HTTP portal, or use certificate pinning (complex)
- **Acknowledgment**: Modern browsers default to HTTPS - child might see security warning
- **Mitigation**: Educate that portal page warning is expected, or use self-signed certificate

**Mobile Device Compatibility:**
- Some mobile devices may auto-connect to WiFi and bypass portal
- Solution: Configure DHCP to route through NoDogSplash gateway
- Testing required for different devices (iPhone, Android, tablets)

**Processing Limitations:**
- NoDogSplash is lightweight but on Pi, too many simultaneous portal sessions could slow down
- Limit: ~10-15 concurrent portal sessions recommended
- Mitigation: Optimize portal page load times, limit portal complexity

#### **Alternatives Considered**

**Why Not Other Captive Portal Solutions:**
- **pfSense**: Too resource-intensive for Pi, enterprise-focused
- **CoovaChilli**: More complex, harder to configure
- **Custom iptables solution**: Would require building portal mechanism from scratch
- **Cloud-based portal**: Defeats Objective 1 (local hosting), privacy concerns

**Why NoDogSplash Was Chosen:**
- ✅ Lightweight (perfect for Pi)
- ✅ Well-documented
- ✅ Active open-source community
- ✅ Proven stable in production
- ✅ Easy integration with shell commands (Laravel control)
- ✅ Highly configurable

#### **Potential Questions About NoDogSplash**

**Q: Can children bypass the portal?**
**A:** Not easily. NoDogSplash intercepts at network level. If they disconnect WiFi and use mobile data, they bypass (acknowledge limitation). If they stay on WiFi, every HTTP request is intercepted.

**Q: What if NoDogSplash crashes?**
**A:** System would fail - children couldn't access portal OR internet. Can set up auto-restart service. Monitor service health from Laravel dashboard.

**Q: How does it handle multiple devices simultaneously?**
**A:** NoDogSplash is designed for multiple clients. Each device gets its own portal session. Tested to handle 10+ devices on Pi 4B.

**Q: Why not just use iptables blocking?**
**A:** iptables just blocks - doesn't redirect to portal. NoDogSplash provides the redirect mechanism essential for educational engagement objective.

**Q: How do you test the portal?**
**A:** Connect device to Pi WiFi, let time expire, try to visit any website - should redirect to portal. Complete quiz/video - should allow internet access.

**Q: What about HTTPS requests?**
**A:** NoDogSplash intercepts HTTP. HTTPS requests may show certificate warnings or be blocked. This is a limitation we acknowledge - portal works best with HTTP-first sites or redirects.

---

### **3. Laravel Framework**

#### **Role in System**
- **Primary Function**: Web application framework that provides dashboard, business logic, database management, and automation
- **How It Controls System**: Executes shell commands to control NoDogSplash, iptables, and other system components
- **Why Laravel**: Security features (CSRF, authentication), ORM for database, background jobs, active community

#### **How It Achieves Objectives**

**Objective 1: Locally Hosted Platform**
- ✅ Runs as service on Raspberry Pi
- ✅ Serves all web interfaces (parent dashboard, child portal)

**Objective 2: Captive Portal**
- ✅ Generates portal page content
- ✅ Processes quiz/video completions
- ✅ Controls NoDogSplash via shell commands

**Objective 5: Parent Dashboard**
- ✅ Entire dashboard built in Laravel
- ✅ Real-time monitoring, device management, time allocation

**Objective 8: Security**
- ✅ Built-in authentication, CSRF protection, secure sessions, input validation

---

### **4. MariaDB Database**

#### **Role in System**
- Stores all system data: devices, time allocations, quiz/video content, logs, parent accounts
- Provides data persistence across system restarts
- Enables complex queries for reporting and analytics

#### **How It Achieves Objectives**

**Objective 4: Registration/Login**
- ✅ Stores parent user accounts, authentication data

**Objective 5: Parent Dashboard**
- ✅ Provides data for device monitoring, time tracking, activity logs

**Objective 7: Testing**
- ✅ Stores test data, usage statistics, evaluation metrics

---

### **5. iptables/nftables (Linux Firewall)**

#### **Role in System**
- Network-level traffic control - blocks/allows internet access
- Works in conjunction with NoDogSplash for comprehensive network control
- Enforces website blocking rules (domain-level)

#### **How It Achieves Objectives**

**Objective 2: Captive Portal**
- ✅ Blocks internet access when time expires (complementary to NoDogSplash)

**Objective 5: Parent Dashboard**
- ✅ Implements website blocking rules set by parents

**Objective 8: Security**
- ✅ Firewall rules restrict network access, prevent unauthorized connections

---

### **Technology Stack Integration Summary**

```
┌─────────────────────────────────────────────────────────────┐
│                    Child Device (Browser)                    │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTP Request
                           ↓
┌─────────────────────────────────────────────────────────────┐
│              Raspberry Pi (Raspberry Pi OS)                  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         NoDogSplash (Port 2050)                      │  │
│  │  - Intercepts HTTP requests                          │  │
│  │  - Serves portal page OR allows internet (if whitelisted)│
│  └───────────────┬──────────────────────────────────────┘  │
│                  │                                          │
│                  ↓                                          │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Nginx/Apache (Port 80/443)                          │  │
│  │  - Routes requests to Laravel                        │  │
│  │  - Serves static files                               │  │
│  │  - Handles SSL/TLS                                   │  │
│  └───────────────┬──────────────────────────────────────┘  │
│                  │                                          │
│                  ↓                                          │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Laravel Application (Port 8000/Unix Socket)         │  │
│  │  - Business logic                                    │  │
│  │  - Dashboard interface                               │  │
│  │  - Portal page generation                            │  │
│  │  - Shell command execution → NoDogSplash/iptables   │  │
│  └───────────────┬──────────────────────────────────────┘  │
│                  │                                          │
│                  ↓                                          │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  MariaDB Database                                    │  │
│  │  - Stores all system data                            │  │
│  │  - Device information, time allocations, logs        │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  iptables/nftables                                   │  │
│  │  - Network firewall rules                            │  │
│  │  - Website blocking                                  │  │
│  │  - Traffic control                                   │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                    Internet (via Router)                     │
└─────────────────────────────────────────────────────────────┘
```

#### **Data Flow Example: Child Completes Quiz**

1. **Time Expires**: Laravel background job detects time expiration → executes `nodogsplash -d [MAC]`
2. **Child Tries to Browse**: Opens browser, tries to visit `youtube.com`
3. **NoDogSplash Intercepts**: Intercepts HTTP request → redirects to portal page
4. **Portal Page Loads**: Nginx serves portal page → Portal JavaScript queries Laravel
5. **Laravel Responds**: Checks database → returns available quizzes
6. **Child Completes Quiz**: Submits answers → Portal sends to Laravel API
7. **Laravel Validates**: Checks answers in database → grants time → executes `nodogsplash -u [MAC]`
8. **NoDogSplash Whitelists**: Device now allowed internet access
9. **Child Browses**: Next HTTP request → NoDogSplash sees device whitelisted → allows internet access

---

## 🎯 KEY POINTS TO EMPHASIZE DURING PRESENTATION

### Section 1.1 (The Problem)
**Emphasize:**
- The problem is **real and well-researched** (cite: CDC, 2024; UNICEF, 2023; UNESCO, 2023)
- Existing solutions have **specific gaps**: limited real-time visibility, incomplete reporting, no educational integration
- Parents face **structural disadvantages**: multiple devices, lack of technical knowledge, manual supervision is impractical

**Potential questions:**
- "Why is this problem significant?" → Cite research on online risks and screen time effects
- "What makes your solution different?" → Network-level control + educational engagement requirement
- "Who is this for?" → Parents of school-aged children in home environments

### Section 1.2 (The Client)
**Emphasize:**
- **Target users**: Parents/guardians (not children - children are the subjects being monitored)
- **Key needs**: User-friendly interface, centralized control, real-time monitoring, educational balance
- **Remote access capability**: Parents can monitor from anywhere (important for working parents)

**Potential questions:**
- "How do you know parents need this?" → Research shows existing tools fall short
- "What about children's perspective?" → System encourages learning, not just restriction
- "Is remote access secure?" → Yes, through VPN/cloud tunneling with security measures

### Section 1.3 (The Project/Solution)
**Emphasize:**
- **Complete system**: Not just software, but hardware + software integration
- **Raspberry Pi choice**: Cost-effective, powerful enough, but has limitations (acknowledge constraints)
- **Laravel's role**: Web dashboard + automation manager (doesn't directly control hardware)

**System Capabilities - Be ready to explain:**
1. **Website monitoring**: How does it work? → Domain-level tracking (HTTPS limitation)
2. **Time tracking**: How accurate? → Active session monitoring, not just connection time
3. **Captive portal**: How does redirect work? → NoDogSplash intercepts HTTP requests
4. **Quiz/Video system**: How does it verify completion? → Quiz: scoring; Video: dictionary word validation
5. **Real-time notifications**: How? → Background jobs monitor events

**System Architecture - Be ready to explain:**
- **Why Raspberry Pi?** → Affordable, can run both AP and web server, but limited processing power
- **Why Laravel?** → Security features, database management, large community, works well on Pi
- **How does Laravel control network?** → Executes Linux shell scripts (iptables, NoDogSplash config)
- **Why local deployment?** → Security, simplicity, direct hardware access
- **Key Technologies (DETAILED EXPLANATION IN CORE TECHNOLOGIES SECTION):**
  - **Nginx/Apache**: Serves web interfaces, routes requests, integrates with NoDogSplash for portal
  - **NoDogSplash**: Intercepts HTTP requests, enforces portal requirement, whitelisting mechanism
  - See "Core Technologies" section for complete technical details and objective mappings

**Potential questions:**
- "Why not use a cloud solution?" → Privacy (children's data stays local), cost, direct network control needed
- "Can it scale?" → Designed for home use, not enterprise (acknowledge limitation)
- "What if the Pi fails?" → System goes down (acknowledge single point of failure - this is a constraint)

### Section 1.4 (Project Objectives)
**Be ready to explain each objective:**
1. **Locally hosted platform** → Why? Privacy, cost, direct network control
2. **Captive portal** → Core innovation: educational engagement requirement
3. **Child portal interface** → User experience for children (must be simple, clear)
4. **Registration/login** → Only for parents/admins; children use device-based identification (MAC address)
5. **Parent dashboard** → Central control point for all features
6. **PLDT/Globe integration** → Compatibility with common Philippine ISPs
7. **Testing** → Will test with real parent-child pairs (acknowledge small sample size)
8. **Security** → Multiple layers: authentication, firewall, MAC whitelisting, session management

**Potential questions:**
- "Are all objectives achievable?" → Yes, but acknowledge constraints (Section 1.6)
- "How will you measure success?" → Usability testing, reliability testing, effectiveness evaluation (Objective 7)
- "What if an objective isn't met?" → Acknowledge in limitations/delimitations

### Section 1.5 (Scope and Delimitation)
**Be ready to explain:**
- **What's included**: All 9 capabilities listed
- **What's NOT included**: 
  - Native mobile apps
  - Deep packet inspection
  - AI-based filtering
  - Application-level blocking (only web-based)
  - Content analysis (only domain-level)

**Potential questions:**
- "Why not include mobile apps?" → Resource constraint, web-based only
- "Why only domain-level blocking?" → HTTPS encryption limitation (acknowledge in constraints)
- "Can parents monitor social media messages?" → No, privacy constraint (only domain-level)

### Section 1.6 (Design Constraints)
**Be ready to justify each constraint:**
- **Technical**: Hardware compatibility, network limitations, browser dependency, HTTPS restrictions
- **Software**: Web-based only, limited processing power, open-source tools only
- **Operational**: User knowledge limitations, maintenance requirements, testing limitations
- **Security/Privacy**: Data privacy regulations, no deep content analysis
- **Resource**: Limited budget, time constraints, small team

**Potential questions:**
- "Why not use a more powerful server?" → Budget constraint, Raspberry Pi is sufficient for home use
- "Why not implement AI filtering?" → Time constraint, processing power limitation
- "How do you handle HTTPS limitation?" → Acknowledge it, work at domain level, explain why this is acceptable

### Section 1.7 (Engineering Standards)
**Be ready to explain why each standard is relevant:**
- **IEEE 802.11 (Wi-Fi)**: System creates WiFi network → must comply
- **IEEE 802.3 (Ethernet)**: Pi connects via LAN → must comply
- **DHCP/DNS**: Network infrastructure → automatic IP assignment, domain resolution
- **HTTP/HTTPS**: Web-based system → must use these protocols
- **W3C Standards**: Frontend uses HTML/CSS/JavaScript → must follow standards
- **OWASP**: Security-critical system → must follow best practices
- **GDPR Principles**: Privacy-sensitive (children's data) → follow principles
- **ISO/IEC 25010**: Software quality → guide development approach

**Potential questions:**
- "Are you certified in these standards?" → No, but we follow the principles/guidelines
- "Why so many standards?" → Different aspects (networking, web, security, quality)
- "How do you ensure compliance?" → Through implementation choices (Laravel security, W3C-compliant HTML, etc.)

### Section 1.8 (Engineering Design Process)
**Be ready to walk through each step:**

**1.8.1 Ask (Identify Need and Constraints)**
- **Need**: Parents struggle with monitoring, want educational integration
- **Constraints**: Raspberry Pi limitations, HTTPS restrictions, parent-created content

**1.8.2 Research the Problem**
- **Problem research**: Studies on children's online behavior, existing tool limitations
- **Technology research**: Laravel, MariaDB, Nginx, NoDogSplash selection
- **Key finding**: Need network-level control + educational engagement

**1.8.3 Imagine (Develop Possible Solutions)**
- **Key decisions**: iptables vs DNS filtering, dictionary words vs simple completion
- **Why chosen**: iptables more reliable, dictionary words ensure attention

**1.8.4 Plan (Select Solution)**
- **Architecture**: Raspberry Pi + Laravel + captive portal
- **Workflow**: Time allocation → tracking → expiration → portal → quiz/video → time grant
- **Database design**: Devices, time, quizzes, videos, logs, relationships

**1.8.5 Create (Build Prototype)**
- **Infrastructure**: Pi setup, web server, database
- **Features**: Device management, time tracking, quiz/video systems, dashboard
- **Security**: Authentication, CSRF, session management, firewall rules

**1.8.6 Test and Evaluate**
- **What was tested**: Time tracking, portal redirect, quiz/video systems, dashboard usability
- **Issues found**: Session tracking edge cases, notification timing, dashboard intuitiveness

**1.8.7 Improve (Redesign)**
- **Improvements**: Algorithm optimization, notification refinement, dashboard redesign
- **Iterative process**: Continuous improvement based on feedback

**Potential questions:**
- "Did you follow this process exactly?" → Yes, iteratively (may have gone back to earlier steps)
- "What was the biggest challenge?" → Time tracking accuracy, session management
- "What would you do differently?" → Be prepared with honest answer about lessons learned

---

## ❓ ANTICIPATED QUESTIONS FROM YOUR ADVISER

### Technical Questions

**Q: Why Raspberry Pi and not a regular router?**
**A:** Raspberry Pi allows custom software development (Laravel), direct network control through shell scripts, and cost-effectiveness. Regular routers have limited customization options.

**Q: How does the system identify children's devices?**
**A:** Through MAC address. Parents register devices by MAC address in the dashboard. The system tracks devices by MAC, not by user login (children don't log in).

**Q: What happens if a child bypasses the system?**
**A:** The system works at the network level - if a device connects to the Pi's WiFi, it's controlled. If a child uses a different network (mobile data), the system can't control it (acknowledge this limitation).

**Q: How accurate is time tracking?**
**A:** Tracks active internet sessions, not just connection time. Background jobs monitor active sessions and deduct time based on actual usage.

**Q: Can parents see what children are doing in real-time?**
**A:** Yes, through the dashboard - they can see active devices, recent browsing activity, and receive real-time notifications.

**Q: How does the dictionary word system work?**
**A:** Random dictionary words appear at random intervals during video playback. At video completion, children must enter all words that appeared. This ensures they actually watched the video.

**Q: What if a child fails a quiz or video validation?**
**A:** They can retry. For quizzes, they can take it again. For videos, they must watch again (new random words will appear).

**Q: How does Nginx/Apache work with NoDogSplash?**
**A:** NoDogSplash intercepts HTTP requests first and redirects to portal when needed. When time is available, NoDogSplash allows requests to pass through to Nginx/Apache, which then serves the Laravel application. They work in tandem - NoDogSplash for interception/redirect, Nginx/Apache for serving web content.

**Q: Why did you choose Nginx over Apache?**
**A:** Nginx has lower memory footprint (10-20MB vs 30-50MB), which is critical on Raspberry Pi. Event-driven architecture handles concurrent requests more efficiently. Apache is a viable alternative if compatibility issues arise.

**Q: How exactly does NoDogSplash intercept requests?**
**A:** NoDogSplash runs as a service on the Raspberry Pi and acts as a gateway. It uses iptables rules to redirect all HTTP traffic (port 80) to itself (port 2050). When a device is not whitelisted, it serves the portal page. When whitelisted, it allows traffic to pass through normally.

**Q: Can NoDogSplash handle HTTPS requests?**
**A:** Limited - NoDogSplash primarily intercepts HTTP. HTTPS requests may show certificate warnings or be blocked. This is an acknowledged limitation. The portal works best with HTTP-first sites or redirects HTTPS to HTTP portal.

**Q: What happens if the web server (Nginx/Apache) fails?**
**A:** The system becomes inaccessible - dashboard and portal won't load. This is a single point of failure. Can mitigate with systemd auto-restart service and health monitoring from Laravel.

**Q: How does Laravel communicate with NoDogSplash?**
**A:** Laravel executes shell commands using PHP's exec() function. Commands like `nodogsplash -u [MAC]` to whitelist a device, or `nodogsplash -d [MAC]` to remove from whitelist. Laravel can also modify NoDogSplash configuration files and restart the service.

**Q: What's the difference between iptables blocking and NoDogSplash?**
**A:** iptables just blocks traffic - child gets "connection refused" error. NoDogSplash intercepts AND redirects to portal page - enabling the educational engagement requirement. They work together: NoDogSplash handles portal mechanism, iptables handles domain-level website blocking.

**Q: How many devices can the system handle simultaneously?**
**A:** Tested with 10-15 devices on Raspberry Pi 4B. Nginx handles concurrent connections efficiently. NoDogSplash can manage 20+ clients, but portal sessions may be limited to 10-15 concurrent for optimal performance on Pi.

### Design Questions

**Q: Why require educational activities instead of just blocking?**
**A:** Research shows that positive reinforcement (earning time through learning) is more effective than pure restriction. It encourages productive internet use.

**Q: Why not use a cloud-based solution?**
**A:** Privacy (children's data stays local), cost (no subscription fees), and direct network control (needed for captive portal functionality).

**Q: How do you ensure security?**
**A:** Multiple layers: user authentication, CSRF protection, secure sessions, firewall rules, MAC whitelisting, input validation, secure shell command execution.

**Q: What about privacy concerns?**
**A:** System only tracks domain-level access (not content), follows GDPR principles, minimizes data collection, uses secure storage (password hashing), and parents can review/manage data.

### Scope Questions

**Q: Why not include mobile app blocking?**
**A:** System is web-based only (constraint). Mobile app blocking requires device-level installation, which is outside scope.

**Q: Why only domain-level blocking?**
**A:** HTTPS encryption prevents content inspection. This is a technical limitation we acknowledge, but domain-level control is still effective for most use cases.

**Q: Can the system work with any router?**
**A:** Works best with PLDT/Globe modems that support the setup. Some routers may have compatibility issues (acknowledged in constraints).

### Research Questions

**Q: What research supports your approach?**
**A:** 
- [Common Sense Media. (2023). *The Common Sense Census: Media Use by Tweens and Teens, 2023.*](https://www.commonsensemedia.org/research/the-common-sense-census-media-use-by-tweens-and-teens-2023) – Extended screen time and uncontrolled access contribute to unhealthy habits.
- [National Center for Missing & Exploited Children. (2023). *2023 CyberTipline Report.*](https://www.missingkids.org/content/dam/missingkids/pdfs/ncmec-analysis/2023-cybertipline-report.pdf) – Online risks rise without effective supervision.
- [Organisation for Economic Co-operation and Development. (2015). *Students, Computers and Learning: Making the Connection.*](https://www.oecd.org/education/students-computers-and-learning-9789264239555-en.htm) – Heavy non-educational use correlates with lower academic engagement.

**Q: How does your solution address the research findings?**
**A:** 
- Network-level control addresses multiple device challenge
- Educational engagement requirement addresses academic engagement concern
- Real-time monitoring addresses supervision gap
- Time limits address screen time concerns

### Implementation Questions

**Q: Is the system fully implemented?**
**A:** [Answer based on your actual progress] If prototype: "Prototype is complete with core features. Testing and refinement are ongoing."

**Q: What challenges did you face?**
**A:** 
- Time tracking accuracy (solved with active session monitoring)
- Session management for device reconnection (solved with algorithm optimization)
- Dashboard usability for non-technical parents (solved with iterative design)

**Q: How will you test it?**
**A:** With selected parent-child user pairs in home network environment. Test usability, reliability, and effectiveness (Objective 7).

---

## 📝 THINGS TO BRING TO CONSULTATION

### Documents
- [ ] Printed copy of Chapter 1
- [ ] System architecture diagram (if you have one)
- [ ] Workflow diagram showing time expiration → portal → quiz/video flow
- [ ] List of references/citations used
- [ ] Any research papers you cited

### Technical Details (Be Ready to Explain)
- [ ] Raspberry Pi 4B specifications and why chosen
- [ ] **Nginx/Apache web server: architecture, configuration, request routing, integration with Laravel**
- [ ] **NoDogSplash captive portal: interception mechanism, portal serving, whitelisting, configuration**
- [ ] **How Nginx/Apache and NoDogSplash work together in the system**
- [ ] **How each core technology achieves specific project objectives**
- [ ] Laravel version and key features used
- [ ] Database schema overview (main tables)
- [ ] How iptables/nftables work for blocking
- [ ] How Laravel controls NoDogSplash and iptables via shell commands
- [ ] Time tracking algorithm explanation
- [ ] Dictionary word validation process

### Visual Aids (If Possible)
- [ ] System architecture diagram
- [ ] Network topology diagram
- [ ] User flow diagram (parent dashboard)
- [ ] User flow diagram (child portal)
- [ ] Database ER diagram (if available)

---

## 🎯 PRESENTATION STRUCTURE SUGGESTION

### Opening (2 minutes)
1. **Problem statement**: "Parents struggle to monitor children's internet use..."
2. **Solution overview**: "We developed a system that..."
3. **Key innovation**: "What makes it unique is the educational engagement requirement..."

### Main Presentation (15-20 minutes)
1. **Problem & Need** (Section 1.1-1.2): Why this is needed, who needs it
2. **Solution Overview** (Section 1.3): What it does, how it works
3. **Core Technologies** (MAIN FOCUS): Detailed explanation of Nginx/Apache, NoDogSplash, and how each achieves objectives
   - Nginx/Apache: Web server foundation and request handling
   - NoDogSplash: Captive portal mechanism and educational engagement enabler
   - Integration between components
   - How technologies directly achieve each project objective
4. **Objectives** (Section 1.4): What you aim to achieve (reference how technologies achieve them)
5. **Scope** (Section 1.5): What's included, what's not
6. **Constraints** (Section 1.6): Limitations and how you address them
7. **Standards** (Section 1.7): Why these standards matter
8. **Design Process** (Section 1.8): How you developed it

### Closing (2-3 minutes)
1. **Summary**: Key points
2. **Next steps**: What comes next (Chapter 2, implementation, testing)
3. **Questions**: Invite questions

---

## ⚠️ COMMON PITFALLS TO AVOID

### Don't:
- ❌ Overpromise capabilities (stick to what's in scope)
- ❌ Ignore constraints (acknowledge limitations honestly)
- ❌ Use jargon without explanation (explain technical terms)
- ❌ Claim perfection (acknowledge areas for improvement)
- ❌ Skip the "why" (always explain design decisions)

### Do:
- ✅ Be honest about limitations
- ✅ Explain technical terms simply
- ✅ Justify design decisions with research/constraints
- ✅ Show you understand the problem deeply
- ✅ Demonstrate systematic approach (engineering design process)

---

## 🔍 AREAS YOUR ADVISER MIGHT PROBE

### 1. Research Foundation
- **Be ready to discuss**: The research you cited, how it supports your problem statement, gaps in existing research

### 2. Technical Feasibility
- **Be ready to explain**: Why Raspberry Pi is sufficient, how Laravel controls the network, scalability limitations

### 3. Innovation/Contribution
- **Be ready to highlight**: Educational engagement requirement, network-level control, dictionary word validation

### 4. Scope Justification
- **Be ready to defend**: Why certain features are included/excluded, why constraints are acceptable

### 5. Standards Compliance
- **Be ready to explain**: How you actually apply these standards, not just list them

### 6. Design Process
- **Be ready to walk through**: Each step, what decisions were made, why, what alternatives were considered

---

## 📚 KEY TERMS TO BE ABLE TO EXPLAIN

- **Nginx/Apache**: Web servers that handle HTTP/HTTPS requests, serve Laravel application, route traffic between components, and serve static files. Nginx preferred for lower resource usage on Raspberry Pi.

- **NoDogSplash**: Open-source captive portal software that intercepts all HTTP requests from connected devices, redirects unauthorized devices to portal page, and manages whitelisting for internet access. Critical for enforcing educational engagement requirement.

- **Captive Portal**: System that intercepts web requests and redirects to authentication/portal page. In this project, implemented by NoDogSplash to enforce quiz/video completion before internet access.

- **Request Interception**: NoDogSplash mechanism where all HTTP traffic is redirected to the portal before reaching the internet. Devices cannot bypass - every HTTP request is captured.

- **Whitelisting**: NoDogSplash feature where authenticated/completed devices are added to allowed list, enabling normal internet access. Controlled by Laravel via shell commands.

- **Reverse Proxy**: Nginx/Apache configuration where web server receives requests and forwards them to Laravel backend (listening on different port/socket), then returns response to client.

- **MAC Address**: Unique identifier for network devices (used to identify child devices). Captured by NoDogSplash and passed to Laravel for device identification.

- **iptables/nftables**: Linux firewall tools for network traffic control. Works with NoDogSplash to provide comprehensive network-level blocking and access control.

- **Domain-level blocking**: Blocking entire websites (e.g., facebook.com) but not specific pages. Implemented via iptables rules, works alongside NoDogSplash portal mechanism.

- **Active session monitoring**: Tracking actual internet usage time, not just connection time. Implemented in Laravel with background jobs.

- **Dictionary word validation**: Educational feature where words appear during videos and must be recalled. Implemented in Laravel, served through portal interface.

- **Background jobs**: Automated processes that run periodically (time tracking, expiration checks, NoDogSplash whitelist management). Implemented in Laravel using queue system.

---

## ✅ CHECKLIST BEFORE CONSULTATION

### Content Understanding
- [ ] I can explain the problem clearly in 30 seconds
- [ ] I understand why each technology was chosen
- [ ] I can explain the system architecture
- [ ] I know all 8 objectives and can explain each
- [ ] I understand all constraints and can justify them
- [ ] I can explain how each engineering standard applies
- [ ] I can walk through the design process step-by-step

### Technical Knowledge
- [ ] I understand how Raspberry Pi works as WiFi AP
- [ ] I understand how Laravel executes shell commands
- [ ] I understand how time tracking works
- [ ] I understand how captive portal redirect works (NoDogSplash mechanism)
- [ ] I understand how Nginx/Apache routes requests and serves Laravel
- [ ] I understand how NoDogSplash intercepts HTTP and enforces portal
- [ ] I understand how Nginx/Apache and NoDogSplash work together
- [ ] I understand how Laravel controls NoDogSplash via shell commands
- [ ] I understand how quiz/video systems work
- [ ] I understand security measures implemented
- [ ] I can explain how each core technology achieves specific project objectives

### Research Foundation
- [ ] I've read the research papers I cited
- [ ] I can explain how research supports the problem
- [ ] I can explain gaps in existing solutions
- [ ] I can justify the educational engagement approach

### Presentation Readiness
- [ ] I have printed materials ready
- [ ] I can explain without reading (know the content)
- [ ] I've practiced explaining technical concepts simply
- [ ] I'm ready to answer questions honestly
- [ ] I know what I don't know (and can say so)

---

## 💡 FINAL TIPS

1. **Be confident but humble**: Show you understand the system, but acknowledge limitations
2. **Explain simply**: Your adviser may not be a networking expert - explain technical terms
3. **Show systematic thinking**: The engineering design process shows you didn't just build randomly
4. **Be ready to learn**: Your adviser may suggest improvements - be open to feedback
5. **Know your "why"**: For every design decision, be able to explain why you chose it
6. **Acknowledge constraints**: Don't try to hide limitations - show you understand them
7. **Connect to research**: Always link back to the problem and research when explaining features

---

## 📞 AFTER CONSULTATION

### What to Do:
1. **Take notes** during the meeting
2. **Ask for clarification** if something is unclear
3. **Request specific feedback** on areas of concern
4. **Follow up** with any questions that come up later
5. **Revise** based on feedback before next consultation

### Questions to Ask Your Adviser:
- "Are there any sections that need more detail?"
- "Is the problem statement clear and well-supported?"
- "Are the objectives realistic and measurable?"
- "Are there any constraints I'm missing?"
- "Is the engineering design process well-documented?"
- "What should I focus on for Chapter 2?"

---

**Good luck with your consultation! Remember: Your adviser wants you to succeed. Be prepared, be honest, and be open to feedback.**

