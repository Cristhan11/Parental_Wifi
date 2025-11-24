# Child-Centric WiFi Captive Portal - Implementation Scope

## Project Description

**CHILD-CENTRIC WIFI MONITORING AND CONTROL SYSTEM WITH LEARNING ACCESS MANAGEMENT AND AUTOMATED REPORTING**

The system aids parents in monitoring and controlling their child's device on a Raspberry Pi WiFi access point. Laravel controls the Raspberry Pi using Linux shell scripts, acting as the web-based dashboard and automation manager.

### System Capabilities

1. Monitor visited websites, manually flag, and block selected websites of assigned child devices
2. Redirect the assigned child device to take a quiz or watch a selected educational video that should be passed or completed for continuation of the internet connection
3. Define the schedules and duration for internet use in the assigned child devices through the parent device
4. Notify in real-time to the parent's device if the usage time limit of the assigned child device has been reached, if the flag website was visited, an attempt is made to access blocked websites, or if new devices are connected to the system
5. Monitor the total time a child's device spends online
6. Generate daily, weekly, and monthly reports for a summary of internet usage, visited sites, access to the flagged websites, attempts to access blocked websites, and bandwidth used
7. Allow the parent device to configure access of connected devices, flag websites, block websites, add quiz and educational videos for the captive portal, and review reports through a web-based parental control dashboard
8. Manage connected devices for blocking and whitelisting
9. Provide basic security measures to prevent unauthorized access to the system, such as user authentication, firewall rules, MAC address whitelisting, session management, and regular log monitoring

## Technology Stack

- **Hardware**: Raspberry Pi 4B
- **OS**: Raspberry Pi OS Lite (64-bit)
- **Backend**: Laravel 12
- **Database**: MariaDB
- **Web Server**: Nginx/Apache + PHP-FPM
- **Captive Portal**: NoDogSplash
- **Frontend**: Blade Templates + Alpine.js
- **Real-time**: Laravel Broadcasting + WebSockets
- **Network Control**: Linux Shell Scripts (iptables/nftables)

## Architecture Overview

### System Setup

The Raspberry Pi 4B is connected through a LAN cable to WiFi and acts as the Access Point for the WiFi network. Laravel runs inside the Raspberry Pi itself (Nginx/Apache + PHP-FPM), which means the entire web system is on the same machine. Because everything is in the same machine, Laravel can directly execute Linux commands to control the system.

### Raspberry Pi 4B Roles

The Raspberry Pi 4B acts as:
- **The Access Point** - Provides WiFi connectivity to child devices
- **The Captive Portal** - Intercepts and redirects users to authentication/quiz pages
- **The Web Server** - Hosts the Laravel application (Nginx/Apache + PHP-FPM)
- **The Firewall/Router** - Controls network traffic using iptables/nftables
- **The Monitoring Device** - Tracks and logs all network activity

Since everything is in the same machine, Laravel can control it directly.

### Laravel's Role

**Laravel = the dashboard/UI**  
**Linux Shell Scripts = the "real power" controlling the Pi**

Laravel acts as the "manager" that sends instructions to the operating system. Laravel does NOT directly control the hardware. Instead, Laravel triggers system-level operations through:

- **Shell commands** - Direct Linux command execution
- **Python helper scripts** - Complex operations handled by Python
- **Bash scripts** - Network and system management scripts
- **Systemd service restarts** - Service management (NoDogSplash, network services)
- **IPTables/NFTables rules** - Firewall and routing rules

### How It Works

Laravel simply sends instructions such as:
- **"Block this MAC address"** → Executes iptables command via shell script
- **"Whitelist this device"** → Updates firewall rules via bash script
- **"Redirect the child to quiz/video"** → Configures NoDogSplash via service
- **"Schedule internet until 9PM"** → Sets up cron/systemd timer
- **"Record child's browsing logs"** → Parses network traffic logs

This architecture allows the web-based dashboard to have full control over the network, devices, and captive portal while maintaining security through proper script execution and validation.

## Captive Portal Flow (Core Logic)

### Primary Workflow

The captive portal is the **core focus** of this project. The logic works as follows:

1. **Initial Time Allocation**: Each child device is assigned a limited internet access time (e.g., 30 minutes, 1 hour, etc.)

2. **Time Tracking**: The system continuously monitors and tracks how much time each device has spent online

3. **Time Expiration**: When the allocated time expires:
   - The device is automatically blocked from internet access
   - The child is redirected to the captive portal (via NoDogSplash)
   - All HTTP requests are intercepted and redirected to the portal

4. **Portal Options**: The child sees two options to earn additional internet time:
   - **Take a Quiz**: Must pass the quiz to receive additional time
   - **Watch Educational Video**: Must complete watching the video to receive additional time

5. **Quiz Flow**:
   - Child selects "Take Quiz"
   - Quiz questions are displayed (parent-configured)
   - Child answers questions
   - System validates answers
   - **If passed**: Grant additional internet time → Redirect to success page → Unblock device
   - **If failed**: Show error → Allow retry or choose video option

6. **Video Flow** (Enhanced with Dictionary Word Validation):
   - Child selects "Watch Video"
   - Educational video is displayed (parent-configured)
   - **Video Player Controls**:
     - Fast forward button is **disabled**
     - Time skipping/seeking is **disabled**
     - Only play, pause, and volume controls are available
     - Video must be watched in chronological order
   - **Dictionary Word System**:
     - Random dictionary words are displayed at **random time intervals** during video playback
     - Words appear as overlays on the video (e.g., at 0:30, 2:15, 5:45, etc.)
     - Each word is displayed for a few seconds, then disappears
     - System tracks which words were shown and at what timestamps
     - **Educational purpose**: Children learn new vocabulary words while watching
   - **Word Collection**:
     - Child must remember/note down the words as they appear
     - Words are shown at unpredictable intervals to ensure active watching
   - **Video Completion & Validation**:
     - **When video reaches the end** (played to completion):
       - Video player shows a form asking for the dictionary words
       - Child must input all the words that were displayed during the video
       - System validates the input against the words that were actually shown
     - **If all words are correct**: Grant additional internet time → Redirect to success page → Unblock device
     - **If words are incorrect or missing**: 
       - Show error message with correct words
       - Child must **repeat watching the entire video** from the beginning
       - New random words will be displayed at different intervals
       - Process repeats until child correctly inputs all words
   - **If video not completed**: Device remains blocked

7. **Time Granting**: After successful quiz/video completion:
   - Additional time is added to device's time allocation
   - Device is unblocked via iptables/nftables
   - NoDogSplash allows device through
   - Child can continue browsing

### Key Components Required

- **Time Tracking System**: Monitor active internet sessions and calculate remaining time
- **Time Expiration Detection**: Background job that checks when time runs out
- **Portal Redirect Logic**: Automatically redirect expired devices to portal
- **Quiz Management**: Create, store, and validate quizzes
- **Video Management**: Store video URLs, track playback completion
- **Time Granting System**: Add time to device allocation after quiz/video completion
- **Device Blocking/Unblocking**: Network-level control when time expires/grants

## Database Schema (MariaDB)

### Core Tables

- **devices**: Store child device information (MAC address, name, assigned to parent user, status, **remaining_time_minutes**, **total_time_allocated**)
- **device_time_grants**: Track time grants given after quiz/video completion (device_id, minutes_granted, source: quiz/video, granted_at)
- **quizzes**: Store quiz questions and answers (title, description, questions JSON, passing_score, time_reward_minutes)
- **quiz_attempts**: Track child's quiz attempts (device_id, quiz_id, answers JSON, score, passed, completed_at)
- **videos**: Store educational videos (title, description, video_url, duration_seconds, time_reward_minutes, **dictionary_words_enabled**, **word_count**)
- **dictionary_words**: Store dictionary words pool (word, definition, difficulty_level) - educational word database
- **video_word_displays**: Track which words were shown during a video viewing session (video_completion_id, dictionary_word_id, displayed_at_timestamp, word_text)
- **video_completions**: Track video viewing completion (device_id, video_id, completed_at, watched_duration, **words_shown_count**, **words_entered**, **words_correct**, **passed_validation**, **attempt_number**)
- **blocked_websites**: Websites to block for specific devices
- **flagged_websites**: Websites to monitor/flag when visited
- **device_schedules**: Time-based internet access rules (day, start_time, end_time, duration_limit)
- **browsing_logs**: Track visited websites, timestamps, device association
- **access_attempts**: Log blocked website access attempts and flagged website visits
- **device_sessions**: Track active internet sessions with start/end times and duration

### Relationships

- User (parent) hasMany Devices
- Device hasMany BlockedWebsites, FlaggedWebsites, DeviceSchedules, BrowsingLogs, AccessAttempts
- Device hasMany DeviceTimeGrants, QuizAttempts, VideoCompletions
- Device belongsToMany Quizzes (through quiz assignments)
- Device belongsToMany Videos (through video assignments)
- Quiz hasMany QuizAttempts
- Video hasMany VideoCompletions, VideoWordDisplays
- VideoCompletion hasMany VideoWordDisplays
- DictionaryWord belongsToMany Videos (words can be reused across videos)
- VideoWordDisplay belongsTo VideoCompletion, DictionaryWord

## Implementation Components

### 1. Database Migrations
- Create migrations for all core tables with proper foreign keys
- Update users table to support parent role
- Add indexes for performance (MAC addresses, timestamps)

### 2. Models & Relationships
- **Device**: Child device model with relationships, time tracking methods (remaining_time, has_time_expired, grant_time)
- **DeviceTimeGrant**: Track time grants after quiz/video completion
- **Quiz**: Quiz management with questions, answers, passing score, time reward
- **QuizAttempt**: Track quiz attempts and results
- **Video**: Educational video management with URL, duration, time reward, dictionary words enabled flag, word count
- **DictionaryWord**: Educational word database (word, definition, difficulty level)
- **VideoWordDisplay**: Track which words were shown during video playback (timestamp, word shown)
- **VideoCompletion**: Track video viewing completion with word validation (words shown, words entered, validation status, attempt number)
- **BlockedWebsite**: Blocked site management
- **FlaggedWebsite**: Flagged site monitoring
- **DeviceSchedule**: Time-based access control
- **BrowsingLog**: Website visit tracking
- **AccessAttempt**: Security event logging
- **DeviceSession**: Active session tracking

### 3. Shell Script Service Layer
- **NetworkService**: Service class to execute shell commands safely
  - Block/unblock MAC addresses via iptables/nftables
  - Whitelist devices
  - Get connected device list
  - Monitor network traffic
- **NoDogSplashService**: Integration with NoDogSplash
  - Configure captive portal
  - Manage authentication tokens
  - Handle redirects
- **ScriptExecutor**: Secure wrapper for executing shell scripts with validation

### 4. Authentication & Authorization
- Extend Laravel Breeze/Jetstream or custom auth
- Parent login system
- Role-based access (parent vs admin)
- Session management

### 5. Device Management
- **DeviceController**: CRUD operations for child devices
- MAC address validation and management
- Device status (active/blocked/whitelisted)
- Real-time device connection detection
- Views: device list, add/edit device

### 6. Website Management
- **BlockedWebsiteController**: Manage blocked websites per device
- **FlaggedWebsiteController**: Manage flagged websites per device
- URL validation and normalization
- Bulk import/export
- Views: blocked sites list, flagged sites list

### 7. Scheduling System
- **DeviceScheduleController**: Create/edit schedules
- Time-based access rules (daily schedules)
- Duration limits per day
- Schedule enforcement logic
- Views: schedule management interface

### 8. Monitoring & Logging
- **BrowsingLogController**: View browsing history
- **AccessAttemptController**: View security events
- Background job to parse network logs (tcpdump, iptables logs)
- Real-time log ingestion
- Views: browsing logs, access attempts dashboard

### 9. Captive Portal Core System
- **Time Tracking Service**: Monitor and calculate remaining internet time per device
- **Time Expiration Detection**: Background job that checks when time runs out and triggers portal redirect
- **Portal Redirect Logic**: Automatically redirect expired devices to portal via NoDogSplash
- **Quiz Management System**:
  - **QuizController**: CRUD for quizzes (parent creates quizzes)
  - Quiz question/answer storage
  - Quiz validation and scoring
  - Time reward calculation
- **Video Management System**:
  - **VideoController**: CRUD for educational videos (parent adds videos, enables dictionary words, sets word count)
  - **DictionaryWordController**: Manage dictionary word database (parent can add/import educational words)
  - Video URL storage and playback tracking
  - **Video Player Controls**: Disable fast forward, disable seeking/time skipping (only play/pause/volume)
  - **Dictionary Word Display System**:
    - Random word selection from dictionary pool
    - Random timestamp generation for word display intervals
    - Word overlay display during video playback
    - Track which words were shown at which timestamps
  - **Video Completion & Word Validation**:
    - Video completion detection (track playback to end)
    - Word input form at video end
    - Word validation (compare entered words vs. displayed words)
    - Retry logic if validation fails (restart video with new random words)
  - Time reward calculation (only after successful word validation)
- **Time Granting Service**: Add time to device after successful quiz/video completion
- **Portal Flow Controller**: Handle portal landing, quiz selection, video selection, completion flows

### 10. NoDogSplash Integration
- Configuration file management for NoDogSplash
- Custom portal pages (quiz/video selection, quiz interface, video player)
- Automatic redirect when time expires (intercept all HTTP requests)
- Redirect handling after portal completion (allow device through)
- Integration with time tracking system

### 10. WebSocket Setup
- Install Laravel Echo Server or Pusher
- Configure broadcasting
- Real-time events:
  - Device connected/disconnected
  - Blocked website access attempt
  - Flagged website visited
  - Time limit reached
- Frontend: Alpine.js components for real-time updates

### 11. Dashboard UI
- Main dashboard with:
  - Active devices overview
  - Recent browsing activity
  - Security alerts
  - Schedule status
- Navigation structure
- Responsive design with Tailwind CSS
- Real-time notification panel

### 12. Background Jobs & Commands
- **ParseNetworkLogs**: Cron job to process network traffic logs
- **EnforceSchedules**: Check and enforce time-based rules
- **MonitorDeviceConnections**: Detect new/removed devices
- **CheckTimeLimits**: Monitor daily usage limits
- **CheckTimeExpiration**: **CRITICAL** - Continuously check if device time has expired, block device and trigger portal redirect
- **TrackActiveSessions**: Monitor active internet sessions and deduct time from device allocation

## Recommended Implementation Order

### Phase 1A: Foundation & Captive Portal Core (Start Here)

1. **Database Schema** - Create all migrations including:
   - Core tables (devices, sessions, etc.)
   - **Time tracking tables** (device_time_grants)
   - **Quiz tables** (quizzes, quiz_attempts)
   - **Video tables** (videos, video_completions)
   - Add time fields to devices table (remaining_time_minutes, total_time_allocated)

2. **Basic Authentication** - Parent login system (needed for both portal and admin)
   - 🧪 **TEST PHASE 1 & 2**: Test basic Laravel setup and database connectivity on Raspberry Pi

3. **Time Tracking System** - **CRITICAL FOUNDATION**
   - Device time allocation and tracking
   - Active session monitoring
   - Time expiration detection
   - Background job to check time expiration

4. **Captive Portal Core Logic** - **THE MAIN FOCUS**
   - Portal landing page (quiz vs video selection)
   - Time expiration detection and automatic redirect
   - Quiz system (display, answer validation, scoring)
   - Video system (playback, completion tracking)
   - Time granting after successful completion
   - Device unblocking after time grant
   - 🧪 **TEST PHASE 6**: Test full integration workflow on Raspberry Pi

5. **NoDogSplash Integration** - Connect portal to Laravel backend
   - Configuration setup
   - Automatic redirect when time expires (intercept HTTP requests)
   - Redirect handling after portal completion
   - Integration with time tracking system

6. **Quiz & Video Management (Parent Dashboard)**
   - Parent can create quizzes
   - Parent can add educational videos
   - Assign quizzes/videos to devices
   - Configure time rewards
   - 🧪 **TEST PHASE 3**: Test file system operations and video storage on Raspberry Pi (after video system)

**Why this order?** The captive portal with time expiration and quiz/video flow is the core of the project. Time tracking must be built first, then the portal logic, then parent management tools. This ensures the main workflow works end-to-end before adding other features.

## Raspberry Pi Testing Phases

To ensure compatibility and catch issues early, the following testing phases should be conducted on Raspberry Pi 4B with Raspberry Pi OS Lite (64-bit) at specific milestones during development.

### Test Phase 1: Basic Laravel Setup

**When to Test**: After Todo #3 (Authentication) is complete

**What to Test**:
- Laravel installation on Raspberry Pi OS Lite (64-bit)
- PHP version compatibility (PHP 8.2+)
- Web server setup (Nginx or Apache + PHP-FPM)
- Basic routing (homepage, login page)
- Environment configuration (.env file)
- Application key generation
- Basic Blade template rendering

**Why Important**: Ensures the foundation works before building features. Catches environment-specific issues early.

**Success Criteria**:
- Laravel application accessible via browser on Raspberry Pi
- Login page loads and displays correctly
- No PHP errors in logs
- Routes respond correctly

**Reference**: See `TESTING.md` for detailed checklist and procedures.

---

### Test Phase 2: Database and Models

**When to Test**: After Todo #3 (Authentication) is complete (can be combined with Phase 1)

**What to Test**:
- MariaDB connection and configuration
- Running all migrations successfully
- Model relationships (Device, User, Quiz, etc.)
- Basic CRUD operations (create device, create user)
- Database seeders (DictionaryWordSeeder)
- Query performance on Raspberry Pi

**Why Important**: Verifies data layer works correctly before building business logic. Ensures models and relationships function properly on Raspberry Pi.

**Success Criteria**:
- All migrations run without errors
- Models can create/read/update/delete records
- Relationships work correctly (e.g., `$device->user`, `$user->devices`)
- Seeders execute successfully
- Database queries complete in reasonable time (< 1 second for simple queries)

**Reference**: See `TESTING.md` for detailed checklist and procedures.

---

### Test Phase 3: File System and Storage

**When to Test**: After Todo #7 (Video System) is complete

**What to Test**:
- Storage directory permissions (`storage/app/videos/`)
- Video file upload functionality
- Video file reading/streaming
- File size limits (Raspberry Pi storage constraints)
- Symlink creation (`storage` → `public/storage`)
- Video playback in browser

**Why Important**: Video storage is critical for the captive portal. Raspberry Pi has specific storage constraints and permission requirements that must be verified.

**Success Criteria**:
- Video files can be uploaded successfully
- Videos can be streamed/played in browser
- Storage permissions are correct
- Symlinks work correctly
- File size limits are appropriate for Raspberry Pi storage

**Reference**: See `TESTING.md` for detailed checklist and procedures.

---

### Test Phase 4: Shell Script Execution

**When to Test**: After Todo #9 (Shell Scripts) is complete

**What to Test**:
- PHP `exec()` and `shell_exec()` permissions
- Bash script execution (`scripts/block_device.sh`)
- Command output parsing
- Error handling for failed commands
- Security (command injection prevention)
- iptables/nftables access (may need sudo)

**Why Important**: Network control depends on shell script execution. Raspberry Pi may have different permission requirements than development environment.

**Success Criteria**:
- Shell scripts execute successfully
- Command output is parsed correctly
- Errors are handled gracefully
- Security measures prevent command injection
- Network commands (iptables) work if permissions allow

**Reference**: See `TESTING.md` for detailed checklist and procedures.

---

### Test Phase 5: Background Jobs and Queues

**When to Test**: After Todo #12 (Background Jobs) is complete

**What to Test**:
- Queue system setup (database queue recommended for Pi)
- Background job execution (`CheckTimeExpiration`, `TrackActiveSessions`)
- Cron job scheduling
- Job failure handling
- Queue worker stability

**Why Important**: Time tracking requires reliable background processing. Raspberry Pi may have different performance characteristics that affect job processing.

**Success Criteria**:
- Queue system is configured correctly
- Background jobs execute successfully
- Cron jobs run on schedule
- Job failures are logged and handled
- Queue worker runs stably without crashes

**Reference**: See `TESTING.md` for detailed checklist and procedures.

---

### Test Phase 6: Full Integration Test

**When to Test**: After Todo #8 (Portal Core) is complete

**What to Test**:
- Complete workflow: time expiration → portal redirect → quiz/video → time grant
- NoDogSplash integration (if available)
- Real device connection (via WiFi)
- MAC address detection
- End-to-end time tracking

**Why Important**: Validates the complete core workflow before adding advanced features. Ensures all components work together on Raspberry Pi.

**Success Criteria**:
- Time expiration triggers portal redirect
- Quiz completion grants time correctly
- Video completion with word validation grants time correctly
- Device time tracking works accurately
- Portal flow completes successfully

**Reference**: See `TESTING.md` for detailed checklist and procedures.

---

### Phase 1B: Backend Services & Admin Dashboard

5. **Shell Scripts & Services** - Network control layer
   - 🧪 **TEST PHASE 4**: Test shell script execution on Raspberry Pi
6. **Device Management** - CRUD operations for devices
7. **Website Management** - Blocking and flagging
8. **Scheduling System** - Time-based access control
9. **Monitoring & Logging** - Browsing logs and access attempts
10. **Background Jobs** - Automated monitoring and enforcement
    - 🧪 **TEST PHASE 5**: Test background jobs and queue system on Raspberry Pi
11. **Admin Dashboard** - Parent control panel
12. **WebSockets** - Real-time notifications

**Why this order?** Once the portal is visually complete, focus on connecting it to real functionality. Admin dashboard comes last since parents use it after the system is operational.

## File Structure

```
app/
├── Models/
│   ├── Device.php
│   ├── DeviceTimeGrant.php
│   ├── Quiz.php
│   ├── QuizAttempt.php
│   ├── Video.php
│   ├── DictionaryWord.php
│   ├── VideoWordDisplay.php
│   ├── VideoCompletion.php
│   ├── BlockedWebsite.php
│   ├── FlaggedWebsite.php
│   ├── DeviceSchedule.php
│   ├── BrowsingLog.php
│   ├── AccessAttempt.php
│   └── DeviceSession.php
├── Services/
│   ├── NetworkService.php
│   ├── NoDogSplashService.php
│   └── ScriptExecutor.php
├── Http/Controllers/
│   ├── DeviceController.php
│   ├── QuizController.php
│   ├── VideoController.php
│   ├── DictionaryWordController.php
│   ├── PortalController.php
│   ├── BlockedWebsiteController.php
│   ├── FlaggedWebsiteController.php
│   ├── DeviceScheduleController.php
│   ├── BrowsingLogController.php
│   ├── AccessAttemptController.php
│   └── DashboardController.php
├── Services/
│   ├── TimeTrackingService.php
│   ├── TimeGrantingService.php
│   ├── VideoWordService.php (random word selection, timestamp generation, validation)
│   ├── NetworkService.php
│   ├── NoDogSplashService.php
│   └── ScriptExecutor.php
├── Jobs/
│   ├── ParseNetworkLogs.php
│   ├── EnforceSchedules.php
│   ├── MonitorDeviceConnections.php
│   ├── CheckTimeExpiration.php
│   └── TrackActiveSessions.php
├── Console/Commands/
│   ├── CheckTimeLimits.php
│   └── CheckTimeExpiration.php
└── Events/
    ├── DeviceConnected.php
    ├── BlockedWebsiteAccessed.php
    ├── FlaggedWebsiteVisited.php
    └── TimeLimitReached.php

database/migrations/
├── create_devices_table.php (with time tracking fields)
├── create_device_time_grants_table.php
├── create_quizzes_table.php
├── create_quiz_attempts_table.php
├── create_videos_table.php (with dictionary_words_enabled, word_count fields)
├── create_dictionary_words_table.php
├── create_video_word_displays_table.php
├── create_video_completions_table.php (with word validation fields)
├── create_blocked_websites_table.php
├── create_flagged_websites_table.php
├── create_device_schedules_table.php
├── create_browsing_logs_table.php
├── create_access_attempts_table.php
└── create_device_sessions_table.php

resources/views/
├── portal/
│   ├── landing.blade.php (quiz vs video selection)
│   ├── quiz.blade.php (quiz interface with questions)
│   ├── quiz-result.blade.php (pass/fail result)
│   ├── video.blade.php (video player with completion tracking)
│   └── success.blade.php (time granted, redirect to internet)
├── quizzes/
│   ├── index.blade.php (parent: list quizzes)
│   ├── create.blade.php (parent: create quiz)
│   └── edit.blade.php (parent: edit quiz)
├── videos/
│   ├── index.blade.php (parent: list videos)
│   ├── create.blade.php (parent: add video, enable dictionary words, set word count)
│   └── edit.blade.php (parent: edit video)
├── dictionary-words/
│   ├── index.blade.php (parent: list dictionary words)
│   ├── create.blade.php (parent: add dictionary word)
│   └── import.blade.php (parent: bulk import words)
├── dashboard/
│   └── index.blade.php
├── devices/
│   ├── index.blade.php
│   └── create.blade.php
├── blocked-websites/
│   └── index.blade.php
├── flagged-websites/
│   └── index.blade.php
├── schedules/
│   └── index.blade.php
└── logs/
    ├── browsing.blade.php
    └── attempts.blade.php

scripts/
├── block_device.sh
├── unblock_device.sh
├── whitelist_device.sh
├── get_connected_devices.sh
└── monitor_traffic.sh
```

## Key Implementation Details

### Shell Script Execution
- All shell commands executed via `ScriptExecutor` with:
  - Input validation
  - Output parsing
  - Error handling
  - Logging
  - Security checks (prevent command injection)

### NoDogSplash Configuration
- Manage `/etc/nodogsplash/nodogsplash.conf`
- Custom portal pages in `public/portal/`
- Integration with Laravel auth for portal access

### Real-time Updates
- Use Laravel Broadcasting with WebSockets
- Events broadcast when:
  - Device connects/disconnects
  - Blocked site accessed
  - Flagged site visited
  - Schedule changes
- Frontend listens via Laravel Echo

### Security Considerations
- Input sanitization for all user inputs
- MAC address validation
- URL validation for websites
- Secure shell script execution
- CSRF protection
- Rate limiting on API endpoints

## Phase 1 Deliverables

- Working authentication system
- **Time tracking system** (allocation, monitoring, expiration detection)
- **Captive portal core flow** (time expiration → redirect → quiz/video → time grant → unblock)
- **Quiz system** (parent creates quizzes, child takes quizzes, validation, time rewards)
- **Video system** (parent adds videos and dictionary words, child watches with disabled fast-forward/skip, dictionary words displayed at random intervals, word validation at end, retry on failure, time rewards)
- **Time granting system** (automatic time addition after quiz/video completion)
- Complete captive portal frontend (from Figma design)
- NoDogSplash integration with automatic redirects
- Device management (add, edit, block, whitelist, time allocation)
- Website blocking and flagging
- Basic scheduling (time windows)
- Real-time device monitoring
- Browsing log capture
- Admin dashboard with real-time updates

## Next Phase (Not in Scope)

- Advanced reporting (daily/weekly/monthly)
- Bandwidth monitoring
- Advanced security features
- Mobile app integration
- Multiple quiz attempts tracking/analytics
- Video analytics (watch time, engagement)

## Implementation Todos

1. **db-schema**: Create MariaDB migrations for all core tables including time tracking (devices with time fields, device_time_grants), quiz system (quizzes, quiz_attempts), video system (videos, video_completions), plus blocked_websites, flagged_websites, device_schedules, browsing_logs, access_attempts, device_sessions with proper relationships and indexes
2. **models**: Create Eloquent models with relationships: Device (with time tracking methods), DeviceTimeGrant, Quiz, QuizAttempt, Video, VideoCompletion, BlockedWebsite, FlaggedWebsite, DeviceSchedule, BrowsingLog, AccessAttempt, DeviceSession
3. **auth**: Set up Laravel authentication system for parent login with role management
4. **test-phase-1-2**: Test basic Laravel setup and database connectivity on Raspberry Pi OS Lite after authentication is complete. Verify Laravel installation, PHP compatibility, web server configuration, routing, environment setup, and model/database operations. See TESTING.md for detailed procedures.
5. **time-tracking**: Build TimeTrackingService to monitor device time, calculate remaining time, detect expiration, and track active sessions
6. **time-granting**: Build TimeGrantingService to add time to devices after quiz/video completion
7. **quiz-system**: Build QuizController for parent to create/edit quizzes, PortalController quiz flow for children (display, answer validation, scoring, time reward)
8. **video-system**: Build VideoController for parent to add/edit videos, PortalController video flow for children (playback, completion tracking, time reward)
9. **test-phase-3**: Test file system operations and video storage on Raspberry Pi after video system is built. Verify storage permissions, video upload/streaming, symlinks, and file size limits. See TESTING.md for detailed procedures.
10. **portal-core**: Build captive portal core flow: landing page (quiz vs video selection), time expiration detection, automatic redirect, completion handling, time granting, device unblocking
11. **test-phase-6**: Test full integration workflow (time expiration → portal → quiz/video → time grant) on Raspberry Pi after portal core is complete. Verify complete end-to-end flow, NoDogSplash integration, device connection, and time tracking accuracy. See TESTING.md for detailed procedures.
12. **shell-scripts**: Create shell scripts for network operations (block/unblock device, whitelist, get connected devices, monitor traffic) in scripts/ directory
13. **test-phase-4**: Test shell script execution and network control commands on Raspberry Pi after shell scripts are created. Verify PHP exec permissions, bash script execution, command output parsing, error handling, and security measures. See TESTING.md for detailed procedures.
14. **services**: Create service classes: TimeTrackingService, TimeGrantingService, NetworkService (iptables/nftables operations), NoDogSplashService (portal management), ScriptExecutor (secure shell execution)
15. **nodogsplash**: Integrate NoDogSplash: configuration management, automatic redirect when time expires (intercept HTTP requests), redirect handling after portal completion, integration with time tracking
16. **background-jobs**: Create background jobs: CheckTimeExpiration (CRITICAL - continuously check and redirect expired devices), TrackActiveSessions (monitor and deduct time), ParseNetworkLogs, EnforceSchedules, MonitorDeviceConnections
17. **test-phase-5**: Test background jobs and queue system on Raspberry Pi after background jobs are implemented. Verify queue configuration, job execution, cron scheduling, and worker stability. See TESTING.md for detailed procedures.
18. **device-management**: Build DeviceController with CRUD operations, MAC address validation, time allocation management, and views for device management
19. **website-management**: Build BlockedWebsiteController and FlaggedWebsiteController with URL validation and management views
20. **scheduling**: Build DeviceScheduleController for time-based access control with schedule enforcement logic
21. **monitoring**: Build BrowsingLogController and AccessAttemptController with log parsing and display views
22. **websockets**: Set up Laravel Broadcasting with WebSockets, create events (DeviceConnected, BlockedWebsiteAccessed, TimeExpired, TimeGranted, etc.), configure frontend with Laravel Echo
23. **dashboard**: Build admin dashboard UI with device overview, time status, recent activity, alerts, and real-time notification panel using Blade + Alpine.js

