# Chapter 2: Project Design

This chapter presents the design process for the Child-Centric WiFi Monitoring and Control System with Learning Access Management and Automated Reporting. It covers the alternative approaches we considered, explains why we chose our final design, and details both the hardware and software components that make the system work.

## 2.1 Discussion of Alternative Designs

Before settling on our final design, we explored several different approaches to building a parental control system. Each option had its own strengths and weaknesses, which we carefully evaluated based on our project requirements.

| Alternative | Description | Strengths | Limitations |
| --- | --- | --- | --- |
| **A. Router Firmware Customization** | Flash open-source firmware (e.g., OpenWRT) onto supported commercial routers, then install parental control packages. | Minimal additional hardware needed, native routing performance, broad community support. | High risk of bricking routers, limited compatibility with ISP-issued PLDT/Globe units, steep learning curve for parents, restricted UI customization. |
| **B. Cloud-Managed Parental Control Service** | Use third-party SaaS that tunnels traffic to the cloud for filtering and reporting. | Automatic updates, enterprise-grade analytics, no on-premise maintenance required. | Requires monthly fees, constant internet backhaul needed, uploads children's browsing data to external servers (privacy concern), limited offline functionality, harder to integrate quizzes/videos chosen by parents. |
| **C. DNS-Only Filtering Appliance** | Deploy a Raspberry Pi that only serves as DNS resolver plus blacklist/whitelist. | Simple installation, lightweight resource requirements, transparent to clients. | Cannot enforce time-based access or captive portal activities, no per-device analytics, easy for children to bypass via custom DNS settings, lacks positive reinforcement features like quizzes and videos. |

After building prototypes and talking with parents about what they actually needed, we decided to go with **Design 1**: an all-in-one Raspberry Pi access point that locally hosts the Laravel dashboard, enforces firewall rules, and powers the educational captive portal. This approach gives us complete control over the user experience, keeps all data private and local, works offline, and allows parents to customize the educational content (quizzes and videos) exactly how they want.

## 2.2 Design 1

### 2.2.1 Design Description

Our chosen design uses a Raspberry Pi 4B as the central hub for the household's "child WiFi" network. Here's how it works: The Pi connects to the existing home network through a LAN cable and acts as the access point for the child devices' WiFi network. It creates its own WiFi network (with a separate SSID) that child devices connect to, and runs our Laravel-based parental control system directly on the Pi itself.

When a child's allocated internet time runs out, the system automatically blocks their device from accessing the internet and redirects them to a captive portal. At this portal, the child can choose to either take a quiz or watch an educational video that the parent has set up. Once they successfully complete either activity, the system grants them additional internet time and unblocks their device.

Meanwhile, parents access a secure web dashboard where they can manage devices, block or flag websites, create quizzes and videos, set schedules, view logs, and check reports. The dashboard is accessible through the local network by default, and remote access can be configured through various methods such as VPN connections, cloud tunneling services, or port forwarding with appropriate security measures, enabling parents to monitor and manage their children's internet usage even when away from home. Everything runs locally on the Pi, so parents have full control and privacy.

### 2.2.2 Hardware Design

We selected specific hardware components based on what the system needs to do reliably:

- **Core Compute:** Raspberry Pi 4B with 4 GB RAM running Raspberry Pi OS Lite (64-bit). We chose this because it's affordable, has enough processing power to handle both the web server and network operations, and includes GPIO pins for potential future enhancements like status LEDs.

- **Networking:** The Pi uses a dual-mode configuration. It connects to the existing home network through a LAN cable for internet access, and its built-in 802.11ac WiFi is configured as an access point to create the child devices' network. We can optionally add a USB WiFi dongle to create a separate management network if needed.

- **Storage:** We're using a Kingston A400 2.5" SATA Internal SSD with 480GB capacity. The storage is partitioned to hold the operating system, Laravel application, MariaDB database, video files, and log files. We chose an SSD instead of a microSD card because it's much more reliable for continuous write operations (like logging and database transactions) and provides better performance for video streaming. This is especially important since the system needs to handle multiple video streams and database operations simultaneously.

- **Power and Cooling:** The Pi uses a 5V/3A USB-C power supply with inline surge protection. We added low-profile heat sinks to keep temperatures stable during continuous operation, since the Pi will be running firewall operations and video streaming 24/7.

- **Peripheral Support:** HDMI and USB ports remain available for local debugging and maintenance. The GPIO header is reserved for potential future features, like status LEDs that could show when devices are active or when alerts occur.

### 2.2.3 Schematic Design

The system architecture follows this structure:

1. **Network Topology:** The flow goes: ISP Modem/Router → LAN Cable → Raspberry Pi (acting as both Access Point and Web Server) → WiFi → Child Devices. This creates a clear separation where all child device traffic goes through the Pi first.

2. **Logical Segmentation:** The Pi manages two network zones—WAN (the uplink to the internet) and LAN (the child devices). We use NAT (Network Address Translation) plus firewall rules to isolate child traffic while still allowing the Pi itself to access the internet for updates and the parent dashboard.

3. **Data Flow:** When a child device connects, it gets a DHCP lease and is automatically registered in our `devices` database table. The Time Tracking Service continuously monitors active internet sessions and updates `device_sessions` records. When a device's `remaining_time_minutes` reaches zero, firewall rules automatically place that device's MAC address in a blocked chain and redirect all their traffic to the captive portal. After the child completes a quiz or video, background jobs call the Time Granting Service to update the time allocation and remove the block.

### 2.2.4 Illustrative Design

We designed the user interfaces to be intuitive for both parents and children:

- **Parent Dashboard Experience:** The dashboard uses mobile-responsive Blade templates with a clean, card-based layout. Each device shows its remaining time clearly, alert banners highlight important events (like blocked website attempts), and we use wizard-style forms to make creating quizzes and videos simple. Color coding helps parents quickly understand status: green for active devices, amber for warnings, and red for blocked devices.

- **Child Portal Experience:** The captive portal interface is intentionally simple and distraction-free. When children see it, they're greeted with friendly, motivational text and two large, clear options: "Take a Quiz" or "Watch a Video." During video playback, progress bars show completion status, and dictionary words appear at random intervals to keep children engaged. When they successfully complete an activity, celebratory animations provide positive reinforcement without feeling like a punishment.

- **Reporting Visualization:** Reports use charts and tables to show daily, weekly, and monthly usage patterns. Parents can see which websites were visited, how many times blocked sites were attempted, and what educational activities their children completed. This helps parents have productive conversations with their children about healthy internet habits.

### 2.2.5 Design Standards

To ensure the system works reliably and securely, we followed established industry standards:

- **Networking:** We comply with IEEE 802.11 (WiFi) and IEEE 802.3 (Ethernet) standards to guarantee compatibility with consumer devices and home routers.

- **Protocols:** The system uses standard network protocols: DHCP (RFC 2131) for automatic IP assignment, DNS (RFC 1034/1035) for domain resolution, and HTTP/HTTPS with TLS 1.3 (RFC 8446) for secure dashboard access.

- **Web/UI:** Frontend code follows W3C HTML5, CSS3, and ECMAScript standards for cross-browser compatibility. Backend code follows PSR-12 PHP coding standards, which Laravel also adheres to.

- **Security:** We implemented security measures based on OWASP Top Ten guidelines, including protection against injection attacks, CSRF protection, secure session management, and proper input validation. Passwords are hashed using bcrypt, which Laravel provides by default.

- **Quality:** We designed the system following ISO/IEC 25010 principles, focusing on usability (easy for parents to use), reliability (system works consistently), and maintainability (code is organized and documented).

### 2.2.6 Design Constraints

We had to work within several limitations that shaped our design decisions:

- **Hardware Limits:** The Raspberry Pi's CPU and memory limit how much we can do with packet inspection and video processing. Because of this, we focus on domain-level website blocking (blocking entire domains rather than inspecting packet contents) and require videos to be pre-encoded rather than transcoding them on the fly.

- **Network Dependencies:** The captive portal system relies on the home router (PLDT or Globe modems) allowing Ethernet connections and proper routing. Some households with locked-down modems might need help from their ISP to configure this properly.

- **Power/Environment:** The system needs stable power and adequate ventilation to run continuously. If the power goes out, devices will disconnect and need to reconnect when power returns. Critical services (Nginx, PHP-FPM, MariaDB, and network services) may need to be restarted manually or through automated recovery scripts.

- **User Skill Level:** We designed the dashboard to be simple enough for parents who aren't tech-savvy. We avoided technical jargon and kept advanced configuration options in admin-only areas.

- **Budget:** To keep costs low for families and stay within our research budget, we restricted ourselves to open-source tools and commonly available hardware. This means we can't use expensive commercial solutions, but it also makes the system more accessible.

## 2.3 Software Design

### 2.3.1 Design Description

The software side of the system is built on Laravel 12, which provides a solid MVC (Model-View-Controller) structure along with queued jobs and custom services. The entire Laravel application runs directly on the Raspberry Pi using either Nginx or Apache as the web server, with PHP-FPM handling PHP processing.

Controllers handle different aspects of the system: device management, quiz and video creation, reporting, and real-time notifications. Models represent the core data entities like `Device`, `DeviceSession`, `Quiz`, `Video`, `BrowsingLog`, `VideoWordDisplay`, `VideoCompletion`, and `DictionaryWord`. These models make it easy to work with database records and maintain relationships between different pieces of data.

For the user interface, we use Blade templates (Laravel's templating engine) to create responsive pages. We added Alpine.js for lightweight interactivity and Tailwind CSS for styling. This combination keeps the frontend fast and doesn't require heavy JavaScript frameworks.

Background workers (Laravel jobs) handle time-consuming tasks like tracking active sessions, sending alerts, generating reports, and parsing network logs. These run in the background so they don't slow down the web interface.

To control the network, Laravel acts as the central manager that sends instructions to the operating system through a secure, layered architecture. The system uses a three-tier approach: the `NetworkService` provides high-level network operations (blocking, unblocking, whitelisting devices, querying connected devices, and monitoring traffic), the `ScriptExecutor` service acts as a secure wrapper that validates, sanitizes, and executes shell scripts with proper security checks, and finally, Bash scripts in the `scripts/` directory execute iptables commands to modify firewall rules. Rather than directly controlling hardware, Laravel triggers system-level operations through these mechanisms: shell scripts for network control (block_device.sh, unblock_device.sh, whitelist_device.sh, get_connected_devices.sh, monitor_traffic.sh), Python helper scripts for complex operations, system service restarts for managing services like NoDogSplash and network services, and iptables/nftables rules for firewall and routing configuration. All script executions are carefully sanitized, validated against a whitelist, and logged for security auditing. The ScriptExecutor service ensures that only approved scripts can be executed, validates script paths to prevent directory traversal attacks, escapes all arguments to prevent command injection, and executes scripts with sudo privileges configured through the sudoers file.

For real-time updates, we use Laravel Broadcasting with WebSockets. This allows the parent dashboard to receive instant notifications when important events happen, like when a child's time expires, when they try to access a blocked website, or when a flagged website is visited.

### 2.3.2 Hardware Interaction

Even though this is primarily a software project, the code needs to interact with the Raspberry Pi's hardware and system services:

- **Network Control Architecture:** The network control system uses a three-tier architecture for secure and reliable device management. The `NetworkService` class provides high-level methods like `blockDevice()`, `unblockDevice()`, `whitelistDevice()`, `getConnectedDevices()`, `getTrafficStats()`, and `isDeviceBlocked()`. These methods handle device validation, database updates, and error logging. The `ScriptExecutor` service acts as a secure intermediary that validates script names against a whitelist, checks script paths to prevent directory traversal attacks, sanitizes all arguments using `escapeshellarg()`, and executes scripts with sudo privileges. The actual network control is performed by Bash scripts (`block_device.sh`, `unblock_device.sh`, `whitelist_device.sh`, `get_connected_devices.sh`, `monitor_traffic.sh`) that execute iptables commands to modify firewall rules on the INPUT and FORWARD chains based on MAC addresses. This layered approach ensures security, reliability, and maintainability.

- **Command Execution Layer:** We created service classes that safely execute shell commands and Python helper scripts to manage WiFi services, configure firewall rules, and control the captive portal. These services act as a secure layer between the web controllers and the actual system commands, preventing unauthorized access. The ScriptExecutor service implements multiple security measures: whitelist validation (only approved scripts can be executed), path validation (prevents directory traversal attacks), argument sanitization (prevents command injection), and comprehensive logging (all executions are logged for audit trails). Python scripts handle complex operations that are easier to implement in Python than in shell scripts.

- **Process Monitoring:** Laravel background jobs read system logs from hostapd and dnsmasq to figure out which devices are actively connected and using the internet. By correlating MAC addresses with active sessions, we can accurately track how much time each device has spent online and deduct it from their allocation.

- **Media Handling:** When parents upload educational videos, the system validates the files, stores them in `storage/app/videos`, and generates streaming-ready links. Laravel's filesystem features handle this efficiently, optimized for the Pi's storage capabilities.

### 2.3.3 Schematic Design

The software architecture is organized into seven main layers:

1. **Presentation Layer:** This is what users see and interact with. Blade views and UI components are located in `resources/views`, including the parent dashboard, captive portal pages, and report visualizations.

2. **Application Layer:** This contains the business logic. Controllers under `app/Http/Controllers` handle requests (like `DeviceController`, `QuizController`, `VideoController`, `PortalController`, `BlockedWebsiteController`, etc.). Middleware enforces role-based access (parents vs. admins), and service classes like `TimeTrackingService`, `TimeGrantingService`, `VideoWordService`, `NetworkService`, `NoDogSplashService`, and `ScriptExecutor` contain reusable logic.

3. **Data Layer:** Eloquent models (Laravel's ORM) interact with MariaDB database tables defined in `database/migrations`. Relationships are set up so that, for example, a `User` can have many `Device` records, a `Device` can have many `DeviceSession` records, a `Video` can have many `VideoCompletion` records, and each `VideoCompletion` can have many `VideoWordDisplay` records. These relationships maintain data integrity automatically.

4. **Automation Layer:** Laravel Queues (using the database driver, which works well on the Pi) schedule recurring background jobs. These include `TrackActiveSessions` (which monitors and deducts time), `CheckTimeExpiration` (which detects when time runs out and triggers the portal redirect), notification dispatch, report generation (daily, weekly, and monthly), network log parsing, and log cleanup.

5. **Network Control Layer:** This layer implements the network control system architecture with three components working together. The `NetworkService` provides high-level network operations (blocking, unblocking, whitelisting devices, querying connected devices, monitoring traffic) and handles database updates and error logging. The `ScriptExecutor` service acts as a secure wrapper that validates scripts against a whitelist, checks paths to prevent directory traversal, sanitizes arguments to prevent command injection, and executes scripts with sudo privileges configured through `/etc/sudoers.d/parental-wifi-scripts`. Shell scripts in the `scripts/` directory (`block_device.sh`, `unblock_device.sh`, `whitelist_device.sh`, `get_connected_devices.sh`, `monitor_traffic.sh`) execute iptables commands to modify firewall rules on the INPUT and FORWARD chains based on MAC addresses. The scripts normalize MAC addresses, validate input, and are idempotent (safe to run multiple times). This three-tier architecture ensures security, reliability, and proper separation of concerns.

6. **Captive Portal Layer:** NoDogSplash integration handles the automatic interception of HTTP requests when a device's time expires. It redirects all traffic to our custom portal pages where children choose between quiz and video options. After completion, it handles redirecting the device back to normal internet access.

7. **Real-time Communication Layer:** Laravel Broadcasting with WebSockets (using Laravel Echo Server or Pusher) enables instant event broadcasting. When events like device connections, blocked website attempts, flagged website visits, time limit reached, or time granted occur, the parent dashboard receives updates immediately without needing to refresh the page.

### 2.3.4 Illustrative Design

To better understand how everything works together, here are some detailed workflow examples:

- **Complete Device Registration to Time Grant Workflow:** When a parent registers a child device through `DeviceController`, it triggers a `DeviceConnected` event. The `TimeTrackingService` then initializes the device's baseline time allocation. A background job called `TrackActiveSessions` runs every minute to monitor which devices are actively using the internet and decrements their remaining time accordingly. Another background job, `CheckTimeExpiration`, continuously checks if any device's time has reached zero. When it detects an expired device, the `NetworkService::blockDevice()` method is called, which validates the device has a MAC address, calls `ScriptExecutor::execute('block_device.sh', [MAC])` to securely execute the blocking script, and updates the database status to 'blocked'. The `block_device.sh` script adds DROP rules to both the INPUT and FORWARD iptables chains based on the device's MAC address. NoDogSplash automatically intercepts all HTTP requests from that device and redirects them to the captive portal. The `PortalController` presents the quiz/video selection page. If the child chooses to take a quiz, the `QuizAttemptController` validates their answers, calculates the score, and if they pass, fires a `TimeGranted` event. The `TimeGrantingService` then updates the `DeviceTimeGrant` records in the database and calls `NetworkService::unblockDevice()`, which uses `ScriptExecutor` to execute `unblock_device.sh` to remove the iptables DROP rules from both INPUT and FORWARD chains. The device regains internet access, and a real-time notification is sent to the parent dashboard via WebSocket so they know their child completed an activity.

- **Video System with Dictionary Words Workflow:** A parent uploads an educational video through the `VideoController` and enables the dictionary words feature, setting how many words should appear. The `VideoWordService` randomly selects words from the dictionary pool. When a child selects "Watch Video" in the portal, the video player displays with fast-forward and seeking controls disabled (to ensure they actually watch). During playback, random dictionary words appear at unpredictable intervals as overlays. The system records which words were shown and at what timestamps in the `VideoWordDisplay` table. When the video reaches the end, a word validation form appears asking the child to input all the words they saw. The system compares their input against the recorded words. If all words are correct, time is granted and the device is unblocked. If any words are incorrect or missing, the video restarts from the beginning with a new set of random words, and the process repeats until the child gets it right.

- **Reporting System Workflow:** Background jobs continuously aggregate data from browsing logs, access attempts, and time usage records. Daily reports summarize internet usage, visited sites, flagged website access, blocked website attempts, and bandwidth consumption. Weekly and monthly reports provide trend analysis showing patterns over time. All reports are accessible through the parent dashboard with visualization charts and tables that make it easy to understand the data.

- **User Stories Visualization:** Sequence diagrams (not included in this document but available in design documentation) trace complete workflows like how a parent blocks a website or uploads a video, showing all the validation checkpoints, storage operations, and notification steps. These diagrams help verify that the system handles all edge cases correctly.

### 2.3.5 Design Standards

We followed coding and design standards to ensure the system is maintainable and reliable:

- **Coding:** Code follows PSR-12 style guidelines, uses Laravel's service container patterns for dependency injection, and applies SOLID principles to keep controllers focused and logic reusable across different parts of the system.

- **Database:** We follow Laravel migration conventions, enforce foreign key relationships to maintain data integrity, use timestamps for audit trails, and implement soft deletes where appropriate (so deleted records are marked as deleted but not actually removed, allowing recovery if needed).

- **Testing:** We wrote PHPUnit feature and unit tests to simulate user login, device management actions, and portal flows. These tests help ensure the system works correctly and align with ISO/IEC 25010 reliability targets.

- **Accessibility:** Frontend components follow WCAG 2.1 AA standards for color contrast and include descriptive labels. This ensures both parents and children can navigate the system comfortably, regardless of their abilities.

### 2.3.6 Design Constraints

Several constraints affected our software design decisions:

- **Processing Headroom:** Since the Raspberry Pi has limited CPU and memory, queue workers and cron tasks must be lightweight to avoid overwhelming the system. We batch intensive analytics during off-peak hours. Background jobs like `CheckTimeExpiration`, `TrackActiveSessions`, and `ParseNetworkLogs` run at optimized intervals to balance responsiveness with resource consumption.

- **Storage Footprint:** While the 480GB SSD provides plenty of space, we still need to manage video uploads carefully. We implemented retention policies to prevent storage from filling up. The SSD's superior wear-leveling and durability compared to microSD cards make it suitable for the continuous write operations from logs, video uploads, and database transactions.

- **Offline Operation:** The system must work even when the internet is down. All dependencies like fonts and JavaScript libraries are bundled locally. Only optional remote notifications require outbound connectivity. The captive portal, quiz system, and video playback all function entirely offline once content is uploaded.

- **Security Surface:** Executing shell commands from PHP introduces security risks, so we implemented multiple safeguards through the ScriptExecutor service. We use a strict whitelist of approved scripts (only `block_device.sh`, `unblock_device.sh`, `whitelist_device.sh`, `get_connected_devices.sh`, and `monitor_traffic.sh` can be executed). We configure sudoers entries in `/etc/sudoers.d/parental-wifi-scripts` to allow the `www-data` user to execute only these specific scripts with NOPASSWD, using full absolute paths. The ScriptExecutor service validates script paths to prevent directory traversal attacks (checks for `../` patterns), verifies scripts exist and are executable, resolves symlinks and verifies final paths, and sanitizes all arguments using `escapeshellarg()` to prevent command injection. All script executions are logged for audit trails. This multi-layered security approach ensures that even if the web application is compromised, attackers cannot execute arbitrary commands on the system.

- **Real-time Communication:** WebSocket connections require stable network conditions. We implemented a fallback to polling for unreliable connections. Laravel Broadcasting events are queued to prevent blocking during high-traffic periods, ensuring the system remains responsive even when many events occur simultaneously.

- **NoDogSplash Integration:** The captive portal depends on NoDogSplash being properly configured and running. Portal redirects require correct firewall rules and DNS configuration to intercept all HTTP requests effectively. If NoDogSplash isn't running or misconfigured, devices won't be redirected to the portal when their time expires.

---

Through this integrated design, the system balances parental oversight, child learning incentives, and responsible engineering practices. It remains practical for real home networks while leaving room for future enhancements like AI-assisted content recommendations or mobile companion apps.
