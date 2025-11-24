# Thesis Defense Document
## Child-Centric WiFi Monitoring and Control System with Learning Access Management and Automated Reporting

## Table of Contents

1. [Introduction](#1-introduction)
   - [Project Title and Description](#11-project-title-and-description)
   - [Problem Statement](#12-problem-statement)
   - [Objectives](#13-objectives)
   - [Scope and Limitations](#14-scope-and-limitations)
   - [Technology Stack Overview](#15-technology-stack-overview)
2. [Methodology / System Architecture](#2-methodology--system-architecture)
   - [System Architecture Overview](#21-system-architecture-overview)
   - [Technology Stack Details](#22-technology-stack-details)
   - [Database Design](#23-database-design)
   - [MVC Architecture](#24-mvc-architecture-laravel)
   - [Service-Oriented Architecture](#25-service-oriented-architecture)
   - [Raspberry Pi Integration Architecture](#26-raspberry-pi-integration-architecture)
3. [Summary of Implementation](#3-summary-of-implementation)
   - [Overview of Completed Work](#31-overview-of-completed-work)
   - [Todo #1: Database Schema](#32-todo-1-database-schema-implementation)
   - [Todo #2: Eloquent Models](#33-todo-2-eloquent-models-implementation)
   - [Todo #3: Authentication System](#34-todo-3-authentication-system)
   - [Todo #4: Raspberry Pi Setup](#35-todo-4-raspberry-pi-setup--testing)
   - [Todo #5: Time Tracking Service](#36-todo-5-time-tracking-service-implementation)
4. [Notes for Defense](#notes-for-defense)
5. [References](#references)

---

## 1. Introduction

### 1.1 Project Title and Description

**Project Title:** Child-Centric WiFi Monitoring and Control System with Learning Access Management and Automated Reporting

**Description:**
This system enables parents to monitor and control their child's internet access through a Raspberry Pi WiFi access point. The system uses Laravel as a web-based dashboard and automation manager, controlling the Raspberry Pi through Linux shell scripts. The core functionality includes time-based internet access control, where children must complete educational quizzes or watch educational videos to earn additional internet time.

### 1.2 Problem Statement

Parents face challenges in:
- Monitoring and controlling children's internet usage
- Ensuring children access appropriate content
- Balancing internet access with educational activities
- Managing time limits effectively
- Tracking browsing history and access attempts

Traditional parental control solutions are often expensive, complex, or lack the educational component that encourages learning while managing internet access.

### 1.3 Objectives

1. **Monitor and Control Internet Access**: Track visited websites, block inappropriate content, and flag suspicious websites
2. **Time-Based Access Management**: Implement a captive portal system that requires educational activities (quizzes/videos) to earn internet time
3. **Schedule Management**: Allow parents to define schedules and duration limits for internet use
4. **Real-Time Notifications**: Notify parents when time limits are reached, flagged websites are visited, or new devices connect
5. **Usage Monitoring**: Track total online time, browsing history, and bandwidth usage
6. **Reporting**: Generate daily, weekly, and monthly reports on internet usage
7. **Security**: Implement authentication, firewall rules, MAC address whitelisting, and session management

### 1.4 Scope and Limitations

**Scope:**
- Monitor visited websites, manually flag, and block selected websites per child device
- Redirect assigned child devices to quizzes or curated educational videos that must be passed/completed before internet resumes
- Define schedules and duration limits for internet use from the parent dashboard
- Notify parents in real time when limits are reached, flagged sites are opened, blocked sites are attempted, or new devices join
- Track total online time for each child device
- Generate daily/weekly/monthly usage reports covering visits, flagged attempts, blocked attempts, and bandwidth usage
- Provide a web dashboard where parents configure device access, manage flagged/blocked lists, upload quizzes/videos, and review reports
- Manage device approvals, blocks, and whitelists
- Enforce baseline security through authentication, firewall rules, MAC address whitelisting, session management, and log monitoring

**Recommended Delimitations (project-wide, post-completion):**
- Focus is limited to the home network managed by the Raspberry Pi; devices outside that WiFi scope are not governed.
- Only domain-level visibility is possible for HTTPS traffic, so granular URL-path inspection is out of scope.
- Educational content quality and appropriateness rely on parent-provided materials; automatic content vetting is excluded.
- Reporting covers usage, visits, and violations, but advanced analytics (AI insights, behavioral prediction) are not targeted.
- Notifications are confined to the parent dashboard and optional email/SMS; mobile push apps and voice assistants are future enhancements.
- Security measures include auth, firewall, MAC filtering, and monitoring, yet enterprise-grade IDS/IPS or VPN tunneling are beyond scope.
- System assumes cooperative use of quizzes/videos; detecting spoofed completions or device tampering requires additional hardware not included.

**Limitations:**
- Requires Raspberry Pi 4B hardware
- Local network deployment (not cloud-based)
- HTTPS full URLs are encrypted (only domains visible)
- Dependent on NoDogSplash for captive portal functionality
- Network monitoring requires all traffic to pass through Raspberry Pi

### 1.5 Technology Stack Overview

- **Hardware**: Raspberry Pi 4B
- **Operating System**: Raspberry Pi OS Lite (64-bit)
- **Backend Framework**: Laravel 12
- **Database**: MariaDB
- **Web Server**: Nginx + PHP 8.4-FPM
- **Captive Portal**: NoDogSplash
- **Frontend**: Blade Templates + Alpine.js + Tailwind CSS v4
- **Real-time Communication**: Laravel Broadcasting + WebSockets (planned)
- **Network Control**: Linux Shell Scripts (iptables/nftables)

---

## 2. Methodology / System Architecture

### 2.1 System Architecture Overview

The system follows a **three-tier architecture**:

```
┌─────────────────────────────────────────────────────────┐
│                    Presentation Layer                    │
│  (Blade Templates, Alpine.js, Tailwind CSS)             │
│  - Parent Dashboard                                      │
│  - Captive Portal (Quiz/Video Interface)                 │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                    Application Layer                     │
│  (Laravel Framework)                                     │
│  - Controllers (HTTP Request Handling)                  │
│  - Services (Business Logic)                            │
│  - Models (Data Access)                                 │
│  - Middleware (Authentication, Authorization)           │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                    Infrastructure Layer                  │
│  (Raspberry Pi + Linux)                                  │
│  - Nginx (Web Server)                                    │
│  - PHP-FPM (PHP Processor)                              │
│  - MariaDB (Database)                                    │
│  - Shell Scripts (Network Control)                      │
│  - NoDogSplash (Captive Portal)                         │
└─────────────────────────────────────────────────────────┘
```

### 2.2 Technology Stack Details

#### 2.2.1 Backend Framework: Laravel 12

**Why Laravel:**
- MVC architecture for organized code structure
- Eloquent ORM for database interactions
- Built-in authentication system
- Service container for dependency injection
- Queue system for background jobs
- Blade templating engine

**Key Laravel Features Used:**
- **Migrations**: Database schema version control
- **Models**: Eloquent ORM for data access
- **Controllers**: HTTP request handling
- **Services**: Business logic separation
- **Middleware**: Request filtering (authentication, authorization)
- **Jobs**: Background task processing
- **Routes**: URL routing and request mapping

#### 2.2.2 Database: MariaDB

**Why MariaDB:**
- MySQL-compatible (familiar syntax)
- High performance for read/write operations
- ACID compliance for data integrity
- Supports complex relationships and transactions
- Well-suited for Raspberry Pi hardware

**Database Design Principles:**
- Normalized schema (3NF) to reduce redundancy
- Foreign key constraints for referential integrity
- Indexes on frequently queried columns
- Timestamps for audit trails
- Soft deletes where appropriate

#### 2.2.3 Web Server: Nginx + PHP-FPM

**Why Nginx:**
- High performance and low memory usage
- Efficient handling of concurrent connections
- Reverse proxy capabilities
- Static file serving optimization

**Why PHP-FPM:**
- Process manager for PHP execution
- Handles multiple PHP requests efficiently
- Socket-based communication with Nginx
- Resource management and process pooling

**Request Flow:**
```
Browser Request → Nginx (Port 80) → PHP-FPM Socket → Laravel → Response
```

### 2.3 Database Design

#### 2.3.1 Core Tables

**Users Table:**
- Stores parent/admin accounts
- Fields: id, name, email, password, role (parent/admin)
- Role-based access control foundation

**Devices Table:**
- Stores child device information
- Key fields: mac_address (unique), status (active/blocked/whitelisted), remaining_time_minutes, total_time_allocated
- Foreign key to users (parent ownership)

**Device Sessions Table:**
- Tracks active internet sessions
- Key fields: started_at, ended_at, duration_seconds
- Used for time deduction calculations

**Device Time Grants Table:**
- Audit trail of time grants
- Fields: minutes_granted, source (quiz/video), source_id
- Tracks when and why time was granted

**Quizzes & Quiz Attempts Tables:**
- Quiz questions and child attempts
- Tracks pass/fail status and scores

**Videos & Video Completions Tables:**
- Educational videos and completion tracking
- Dictionary word validation system

**Browsing Logs & Access Attempts Tables:**
- Website visit history
- Blocked/flagged website access attempts

#### 2.3.2 Key Relationships

- **User → Devices**: One-to-Many (one parent has many devices)
- **Device → Sessions**: One-to-Many (one device has many sessions)
- **Device → Time Grants**: One-to-Many (one device receives many grants)
- **Device → Quizzes**: Many-to-Many (devices can have multiple quizzes assigned)
- **Device → Videos**: Many-to-Many (devices can have multiple videos assigned)

### 2.4 MVC Architecture (Laravel)

**Model-View-Controller Pattern:**

```
┌──────────┐      ┌──────────────┐      ┌─────────┐
│   View   │ ←──→ │  Controller  │ ←──→ │  Model  │
│ (Blade)  │      │ (HTTP Logic) │      │ (Data)  │
└──────────┘      └──────────────┘      └─────────┘
```

**Models (app/Models/):**
- Represent database tables
- Handle data access and relationships
- Contain business logic methods (e.g., `deductTime()`, `grantTime()`)

**Controllers (app/Http/Controllers/):**
- Handle HTTP requests
- Validate input
- Call services for business logic
- Return responses (views, JSON, redirects)

**Views (resources/views/):**
- Blade templates for HTML rendering
- Display data from controllers
- User interface components

### 2.5 Service-Oriented Architecture

**Services (app/Services/):**
- Contain complex business logic
- Reusable across controllers, jobs, and commands
- Independent of HTTP layer
- Single Responsibility Principle

**Key Services:**
- **TimeTrackingService**: Core time tracking logic
- **TimeGrantingService**: Time grant management (planned)
- **NetworkService**: Network control operations (planned)
- **NoDogSplashService**: Captive portal management (planned)

**Service Benefits:**
- Separation of concerns
- Testability
- Reusability
- Maintainability

### 2.6 Raspberry Pi Integration Architecture

**Raspberry Pi 4B Roles:**
1. **Access Point**: Provides WiFi connectivity
2. **Web Server**: Hosts Laravel application (Nginx + PHP-FPM)
3. **Database Server**: MariaDB for data storage
4. **Firewall/Router**: Controls network traffic (iptables/nftables)
5. **Captive Portal**: Intercepts and redirects (NoDogSplash)
6. **Monitoring Device**: Tracks network activity

**Laravel → Linux Integration:**
- Laravel executes shell commands via `exec()` or `shell_exec()`
- Bash scripts handle network operations
- Systemd services manage background processes
- iptables/nftables control firewall rules

**Example Flow:**
```
Laravel Controller → Service → Shell Script → iptables → Network Control
```

---

## 3. Summary of Implementation

### 3.1 Overview of Completed Work

**Completed TODOs:**
1. ✅ **Todo #1**: Database Schema Implementation (20 migrations)
2. ✅ **Todo #2**: Eloquent Models Implementation (14 models)
3. ✅ **Todo #3**: Authentication System (Laravel Breeze + Role-based access)
4. ✅ **Todo #4**: Raspberry Pi Setup & Testing (Production deployment)
5. ✅ **Todo #5**: Time Tracking Service (Core business logic)

### 3.2 Todo #1: Database Schema Implementation

**What Was Implemented:**
- 20 database migration files creating all core tables
- Proper foreign key relationships
- Indexes for performance optimization
- Timestamps for audit trails

**Key Tables Created:**

1. **devices** - Core device management
   - `mac_address` (unique identifier)
   - `status` (active/blocked/whitelisted)
   - `remaining_time_minutes` (time tracking)
   - `total_time_allocated` (total time ever granted)

2. **device_sessions** - Active session tracking
   - `started_at`, `ended_at` (session duration)
   - `duration_seconds` (calculated duration)
   - Used by TimeTrackingService for time deduction

3. **device_time_grants** - Time grant audit trail
   - `minutes_granted` (amount of time)
   - `source` (quiz/video)
   - `source_id` (reference to quiz_attempt or video_completion)

4. **quizzes & quiz_attempts** - Quiz system
   - Questions, answers, scoring
   - Attempt tracking with pass/fail status

5. **videos & video_completions** - Video system
   - Video URLs and metadata
   - Completion tracking with word validation

6. **browsing_logs & access_attempts** - Monitoring
   - Website visit history
   - Blocked/flagged site access attempts

**Core Functions/Technologies:**
- **Laravel Migrations**: Schema version control
- **Foreign Keys**: Referential integrity
- **Indexes**: Query performance optimization
- **ENUM Types**: Status management (active/blocked/whitelisted)
- **Timestamps**: Automatic created_at/updated_at tracking

**Design Decisions:**
- Normalized schema to prevent data redundancy
- Composite indexes for common query patterns
- Cascade deletes for data consistency
- Nullable fields where appropriate (ip_address, ended_at)

### 3.3 Todo #2: Eloquent Models Implementation

**What Was Implemented:**
- 14 Eloquent model classes
- Relationship definitions (hasMany, belongsTo, belongsToMany)
- Business logic methods
- Data casting and validation

**Key Models:**

1. **Device Model** - Core device functionality
   - **Relationships**: user, sessions, timeGrants, quizAttempts, videoCompletions
   - **Key Methods**:
     - `activeSession()` - Get current active session
     - `hasRemainingTime()` - Check if time available
     - `hasTimeExpired()` - Check if time ran out
     - `grantTime($minutes, $source)` - Add time to device
     - `deductTime($minutes)` - Remove time from device
     - `isWhitelisted()`, `isBlocked()`, `isActive()` - Status checks

2. **DeviceSession Model** - Session management
   - **Key Methods**:
     - `isActive()` - Check if session is ongoing
     - `calculateDuration()` - Calculate session duration
     - `getDurationMinutes()` - Get duration in minutes

3. **DeviceTimeGrant Model** - Time grant tracking
   - Links to device and source (quiz/video)

4. **User Model** - Authentication and authorization
   - **Key Methods**:
     - `isParent()` - Check if user is parent
     - `isAdmin()` - Check if user is admin
   - **Relationships**: devices, quizzes, videos

**Core Functions/Technologies:**
- **Eloquent ORM**: Object-relational mapping
- **Relationships**: hasMany, belongsTo, belongsToMany
- **Accessors/Mutators**: Data transformation
- **Casting**: Automatic type conversion (datetime, boolean)
- **Mass Assignment Protection**: fillable/guarded arrays

**Design Decisions:**
- Business logic in models (e.g., `deductTime()`)
- Relationship methods for code readability
- Helper methods for common checks (e.g., `isWhitelisted()`)
- Type casting for data consistency

### 3.4 Todo #3: Authentication System

**What Was Implemented:**
- Laravel Breeze package installation
- Authentication controllers and views
- Role-based access control (parent/admin)
- Middleware for route protection
- Tailwind CSS v4 configuration

**Key Components:**

1. **Laravel Breeze**
   - Pre-built authentication scaffolding
   - Login, registration, password reset
   - Email verification support
   - Blade template views

2. **Role-Based Access Control**
   - `role` field in users table (parent/admin)
   - `isParent()` and `isAdmin()` methods in User model
   - Middleware: `EnsureUserIsParent`, `EnsureUserIsAdmin`

3. **Middleware Implementation**
   - **EnsureUserIsParent**: Restricts routes to parent users
   - **EnsureUserIsAdmin**: Restricts routes to admin users
   - Checks authentication status
   - Returns 403 Forbidden for unauthorized access

**Core Functions/Technologies:**
- **Laravel Breeze**: Authentication scaffolding
- **Middleware**: Request filtering
- **Session Management**: Laravel's built-in session handling
- **Password Hashing**: bcrypt algorithm
- **CSRF Protection**: Token-based request validation
- **Route Protection**: Middleware groups

**Authentication Flow:**
```
User Login → AuthenticatedSessionController → Auth Facade → Session Created → Redirect to Dashboard
```

**Design Decisions:**
- Used Laravel Breeze for rapid development
- Role-based access for future admin features
- Middleware for clean route protection
- Tailwind CSS for modern UI

### 3.5 Todo #4: Raspberry Pi Setup & Testing

**What Was Implemented:**
- Complete production environment setup on Raspberry Pi 4B
- All required software installation and configuration
- Network configuration for multi-device access
- Testing and verification procedures

**Hardware Setup:**
- Raspberry Pi 4B
- Raspberry Pi OS Lite (64-bit)
- SSH access enabled
- Network connectivity

**Software Stack Installed:**

1. **Nginx** - Web server
   - Listens on port 80
   - Serves static files
   - Forwards PHP requests to PHP-FPM

2. **PHP 8.4-FPM** - PHP processor
   - FastCGI Process Manager
   - Socket communication with Nginx
   - Required extensions: mysql, mbstring, xml, curl, zip, gd

3. **MariaDB** - Database server
   - MySQL-compatible
   - Socket authentication for root
   - Dedicated user for Laravel application

4. **Composer** - PHP dependency manager
   - Installed globally
   - Manages Laravel packages

5. **Node.js & npm** - JavaScript runtime
   - Required for frontend asset building (Vite)
   - Builds Tailwind CSS and JavaScript

6. **Git** - Version control
   - SSH key authentication for repository cloning

**Configuration Details:**

1. **Nginx Configuration**
   - Server block for Laravel application
   - Root directory: `/var/www/parental_wifi/public`
   - PHP-FPM socket: `unix:/var/run/php/php8.4-fpm.sock`
   - URL rewriting for Laravel routes

2. **PHP-FPM Configuration**
   - Pool configuration for process management
   - Socket file location
   - User/group: www-data

3. **MariaDB Configuration**
   - Database: `parental_wifi`
   - User: `parental_wifi_user`
   - Password authentication
   - Privileges granted

4. **File Permissions**
   - Owner: `www-data` (web server user)
   - Storage and cache directories: 775 permissions
   - Application files: 755 permissions

**Network Access:**
- Accessible from any device on local network
- IP address: `192.168.1.173` (example)
- HTTP only (no HTTPS in initial setup)
- Local network only (not internet-accessible)

**Core Functions/Technologies:**
- **systemctl**: Service management (start, stop, enable, status)
- **chown/chmod**: File permission management
- **sudo**: Privilege escalation for system operations
- **SSH**: Remote access and file transfer
- **Git**: Repository cloning and version control

**Testing Results:**
- ✅ Nginx serving Laravel application
- ✅ PHP-FPM processing PHP requests
- ✅ MariaDB connection successful
- ✅ Application accessible from other devices
- ✅ Login functionality working
- ✅ Database migrations successful
- ✅ Frontend assets building correctly

### 3.6 Todo #5: Time Tracking Service Implementation

**What Was Implemented:**
- Complete TimeTrackingService class
- 12 core methods for time tracking
- Automatic session management
- Device disconnection handling (Option 1)
- Comprehensive logging

**Service Architecture:**
- Located in `app/Services/TimeTrackingService.php`
- Business logic layer (separate from controllers)
- Reusable across controllers, jobs, and commands
- No HTTP dependencies

**Core Methods:**

1. **calculateRemainingTime(Device $device): int**
   - Calculates accurate remaining time
   - Formula: `remaining_time_minutes - active_session_duration`
   - Returns 999999 for whitelisted devices (unlimited)
   - Accounts for time not yet deducted by background job

2. **hasTimeExpired(Device $device): bool**
   - Checks if device time has run out
   - Uses `calculateRemainingTime()` for accuracy
   - Returns false for whitelisted devices
   - Used by background jobs to find expired devices

3. **getExpiredDevices(): Collection**
   - Finds all devices with expired time
   - Filters active devices only
   - Excludes whitelisted devices
   - Returns collection for background job processing

4. **startSession(Device $device): ?DeviceSession**
   - Creates new session when device starts browsing
   - Checks device approval (active/whitelisted)
   - Logs unauthorized attempts (security)
   - Prevents duplicate sessions

5. **endSession(DeviceSession $session): void**
   - Ends active session
   - Calculates session duration
   - Deducts time from device (if not whitelisted)
   - Updates last_seen_at timestamp

6. **trackActiveSessions(): void**
   - Main method called by background job
   - Processes all active sessions
   - Deducts time periodically (every 1-5 minutes)
   - Skips whitelisted devices
   - Updates last_seen_at for active devices

7. **handleDeviceDisconnection(string $macAddress): bool** (Option 1)
   - Automatically ends session when device disconnects
   - Prevents time waste during standby/disconnection
   - Clears device IP address
   - Logs disconnection events

8. **endSessionsForDisconnectedDevices(): int**
   - Safety mechanism for missed disconnections
   - Finds sessions for devices without IP addresses
   - Automatically ends "zombie" sessions
   - Called periodically by background job

**Core Functions/Technologies:**
- **Service Pattern**: Business logic separation
- **Eloquent ORM**: Database queries
- **Carbon**: Date/time manipulation
- **Logging**: Laravel Log facade for debugging
- **Collections**: Laravel collection methods (filter, map)

**Business Logic:**
- **Time Calculation**: Accurate remaining time considering active sessions
- **Periodic Deduction**: Background job deducts time every 1-5 minutes
- **Whitelist Handling**: Whitelisted devices skip all time tracking
- **Session Management**: One active session per device
- **Security**: Unauthorized session attempts are logged

**Design Decisions:**
- **Service Layer**: Separates business logic from HTTP layer
- **Accurate Calculation**: Accounts for time not yet deducted
- **Periodic Deduction**: More efficient than real-time
- **Option 1 Implementation**: Automatic disconnection handling
- **Comprehensive Logging**: All operations logged for debugging

**Integration Points:**
- Called by background jobs (TrackActiveSessions, CheckTimeExpiration)
- Called by controllers (DeviceController, PortalController)
- Uses Device and DeviceSession models
- Will integrate with TimeGrantingService (future)

---

## Notes for Defense

### Key Points to Remember

1. **Architecture**: Three-tier architecture (Presentation, Application, Infrastructure)
2. **Database**: 20 tables with proper relationships and indexes
3. **Models**: 14 Eloquent models with business logic methods
4. **Authentication**: Laravel Breeze with role-based access control
5. **Raspberry Pi**: Complete production environment setup
6. **Time Tracking**: Core service with 12 methods for time management
7. **Service Pattern**: Business logic separated from controllers
8. **Security**: Middleware, logging, unauthorized attempt detection

### Potential Defense Questions

**Q: Why Laravel?**
A: MVC architecture, Eloquent ORM, built-in authentication, service container, queue system, active community support.

**Q: Why separate Services from Controllers?**
A: Separation of concerns, reusability (controllers, jobs, commands), testability, maintainability, follows Single Responsibility Principle.

**Q: How does time tracking work?**
A: Devices have `remaining_time_minutes`. Sessions track browsing time. Background job deducts time periodically. Formula: `remaining_time - active_session_duration` for accuracy.

**Q: Why periodic deduction instead of real-time?**
A: More efficient, prevents database overload, still accurate through calculation formula, acceptable delay (1-5 minutes).

**Q: How does device disconnection work?**
A: Option 1 implementation - when device disconnects, `handleDeviceDisconnection()` is called, session ends automatically, time stops deducting, prevents time waste.

**Q: Why Raspberry Pi?**
A: Low cost, sufficient processing power, GPIO capabilities, Linux-based (full control), acts as access point, web server, and firewall in one device.

**Q: How does the database design ensure data integrity?**
A: Foreign key constraints, cascade deletes, indexes for performance, normalized schema, timestamps for audit trails.

---

**Document Status**: This document covers completed TODOs (1-5). It will be updated as more features are implemented.

**Last Updated**: Based on implementation through Todo #5 (Time Tracking Service with Option 1: Device Disconnection Handling)

---

## References

1. Raspberry Pi Foundation. *Raspberry Pi OS Documentation*. Retrieved from https://www.raspberrypi.com/documentation/
2. Laravel LLC. *Laravel 12 Documentation*. Retrieved from https://laravel.com/docs
3. NGINX, Inc. *NGINX Admin Guide*. Retrieved from https://nginx.org/en/docs/
4. MariaDB Foundation. *MariaDB Knowledge Base*. Retrieved from https://mariadb.com/kb/en/documentation/
5. PHP Group. *PHP Manual (Version 8.4)*. Retrieved from https://www.php.net/manual/en/
6. Node.js Foundation. *Node.js Documentation*. Retrieved from https://nodejs.org/en/docs
7. Tailwind Labs. *Tailwind CSS v4 Documentation*. Retrieved from https://tailwindcss.com/docs
8. NoDogSplash Community. *NoDogSplash Documentation*. Retrieved from https://github.com/nodogsplash/nodogsplash
9. Debian Project. *Debian GNU/Linux Administrator’s Handbook*. Retrieved from https://www.debian.org/doc/

