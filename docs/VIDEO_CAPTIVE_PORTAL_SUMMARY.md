# Video Captive Portal System - Summary

## Quick Overview

The Video System allows parents to upload educational videos that children can watch to earn additional internet time. Children must watch the entire video and remember dictionary words that appear during playback to receive their time reward.

---

## How It Works (Simple Explanation)

### For Parents:

1. **Upload Video** → Parent uploads an educational video file (MP4, WebM, or OGG)
2. **Configure Settings** → Set duration, time reward, enable dictionary words, assign to devices
3. **Video is Ready** → Video appears in child portal for assigned devices

### For Children:

1. **Access Portal** → Child connects to WiFi and accesses captive portal landing page
2. **View Available Activities** → See all quizzes and videos they can complete
3. **Select Video** → Choose from available videos assigned to their device
4. **Watch Video** → Video plays with dictionary words appearing at random times
5. **Enter Words** → At video end, child enters the words they saw
6. **Get Time** → If all words correct, child receives time reward
7. **Return to Portal** → Auto-redirects to landing page to continue browsing

---

## System Components

### 1. Parent Dashboard (`/videos`)

**Purpose:** Parents manage educational videos

**Features:**
- Create new videos with file upload
- Edit video settings (title, description, word count, etc.)
- Assign videos to specific devices
- View completion statistics
- Delete videos (with force delete option for videos with completions)

**Key Files:**
- `app/Http/Controllers/VideoController.php` - Handles all parent operations
- `resources/views/videos/index.blade.php` - Video list view
- `resources/views/videos/create.blade.php` - Video creation form
- `resources/views/videos/edit.blade.php` - Video editing form

### 2. Child Portal Landing Page (`/portal`)

**Purpose:** Main entry point showing all available activities

**Features:**
- Device information (name, remaining time)
- List of available quizzes with time rewards
- List of available videos with time rewards
- Clickable links to start activities
- Error handling for invalid devices

**Key Files:**
- `app/Http/Controllers/PortalController.php` - `landing()` method
- `resources/views/portal/landing.blade.php` - Landing page view

### 3. Child Portal Video Viewing (`/portal/video/{id}`)

**Purpose:** Children watch videos and earn time

**Features:**
- Video player with playback controls
- Dictionary word overlays during playback
- Auto-pause when words appear
- Word submission form at video end
- Result page showing pass/fail status
- Auto-redirect to landing page after success

**Key Files:**
- `app/Http/Controllers/PortalController.php` - Handles video viewing
- `resources/views/portal/video.blade.php` - Video player interface
- `resources/views/portal/video-result.blade.php` - Result page

### 3. Dictionary Word System

**Purpose:** Ensures children actively watch and learn

**How it works:**
1. Random words selected from dictionary pool
2. Random timestamps generated throughout video
3. Words appear as overlays during playback
4. Video pauses when word appears (8 seconds)
5. Child must remember and enter all words at end
6. Validation: All words must be correct (case-insensitive)

**Key Files:**
- `app/Services/VideoWordService.php` - Word selection and validation logic

### 4. Time Granting System

**Purpose:** Rewards children for completing videos

**How it works:**
- When child successfully completes video (all words correct)
- TimeGrantingService grants time reward
- Time added to device's `remaining_time_minutes`
- Grant recorded in `device_time_grants` table

---

## Data Flow

### Video Creation Flow

```
Parent Dashboard
    ↓
Upload Video File
    ↓
File Saved to storage/app/public/videos/
    ↓
Video Record Created in Database
    ↓
Assigned to Devices
    ↓
Video Available in Portal
```

### Video Viewing Flow

```
Child Portal Landing Page
    ↓
Select Video from List
    ↓
Video Completion Record Created
    ↓
Random Words Selected & Timestamps Generated
    ↓
Video Plays with Word Overlays
    ↓
Child Enters Words at End
    ↓
Words Validated
    ↓
Time Granted (if passed)
    ↓
Result Page Shown
    ↓
Auto-Redirect to Landing Page (after 3 seconds)
```

---

## Database Tables

### `videos`
Stores video metadata and settings
- Video file path, duration, word settings, time reward

### `video_completions`
Tracks when children complete videos
- Device, video, attempt number, validation results

### `video_word_displays`
Tracks which words were shown during viewing
- Links to completion, word ID, timestamp, word text

### `device_video`
Many-to-many relationship (devices ↔ videos)
- Which devices can watch which videos

---

## Key Features

### 1. Automatic Duration Detection
- When video file is uploaded, duration is automatically detected
- No manual entry required
- Ensures accuracy

### 2. Random Word Selection
- Different words each time child watches
- Prevents memorization
- Encourages active learning

### 3. Distributed Word Display
- Words appear throughout video (not all at start/end)
- Ensures child watches entire video
- Better learning experience

### 4. Auto-Pause on Word Display
- Video pauses when word appears
- Child has 8 seconds to read word and definition
- Video resumes automatically

### 5. Seeking Prevention
- Timeline controls hidden
- Fast-forward/seek disabled
- Ensures child watches entire video

### 6. Retry Logic
- If child fails validation, can retry
- New attempt = new random words
- Previous attempts preserved for history

### 7. Force Delete Option
- Videos with completions can be force deleted
- Removes video, completions, and file
- Useful for cleanup

### 8. Storage Management
- Automatic file cleanup on deletion
- Commands for orphaned file cleanup
- Commands for old data cleanup

---

## Security Features

### Parent Dashboard
- Authentication required (login)
- Parents can only manage their own videos
- File upload validation (type, size)

### Child Portal
- Device-based access (MAC address)
- Video must be active and assigned to device
- Session-based completion tracking

---

## File Storage

### Location
- `storage/app/public/videos/` - Video files stored here
- Accessible via `/storage/videos/filename.mp4` URL

### Why `public` disk?
- Files accessible via web URL
- Can be streamed in browser
- Symlink connects `public/storage` → `storage/app/public`

### File Management
- Automatic deletion when video deleted
- Cleanup commands for orphaned files
- Size limits (512MB max per file)

---

## Validation Logic

### Word Validation
- **Case-insensitive:** "Adventure" = "adventure" = "ADVENTURE"
- **Trims whitespace:** " adventure " = "adventure"
- **All words required:** Child must get ALL words correct (no partial credit)

### Why strict validation?
- Ensures active watching
- Maintains educational value
- Prevents guessing

---

## Time Granting

### When Time is Granted
- Child successfully completes video
- All dictionary words entered correctly
- `passed_validation = true`

### Time Amount
- Set by parent in video settings
- Example: 15 minutes for 10-minute video
- Added to device's remaining time

### Time Grant Record
- Stored in `device_time_grants` table
- Links to video completion
- Tracks when and why time was granted

---

## Retry System

### How Retries Work
1. Child fails word validation
2. Result page shows "Watch Video Again" button
3. New attempt created (attempt_number increments)
4. New random words selected
5. New random timestamps generated
6. Previous attempt preserved in database

### Why New Words?
- Prevents memorization
- Ensures child actually watches again
- Maintains educational value

---

## Common Use Cases

### Use Case 1: Simple Video (No Words)
1. Parent creates video, disables dictionary words
2. Child watches video to completion
3. Time granted immediately (no word validation)

### Use Case 2: Educational Video (With Words)
1. Parent creates video, enables dictionary words (5 words)
2. Child watches video
3. 5 random words appear during playback
4. Child enters words at end
5. If all correct → time granted
6. If incorrect → can retry with new words

### Use Case 3: Video Cleanup
1. Parent wants to remove old video
2. Video has completions (viewing history)
3. Parent uses force delete
4. Video, completions, and file deleted
5. Storage space freed

---

## Technical Details

### Video Formats Supported
- MP4 (recommended - best browser support)
- WebM
- OGG

### File Size Limit
- Maximum: 512MB per video
- Reason: Raspberry Pi storage consideration

### Duration Detection
- Uses HTML5 video element metadata
- JavaScript reads `video.duration`
- Auto-fills duration field

### Word Display Timing
- Words distributed throughout video
- Random timestamps within intervals
- Ensures even distribution

### Video Playback
- HTML5 `<video>` element
- Native browser controls (modified)
- Seeking/fast-forward disabled
- Auto-pause on word display

---

## Maintenance Commands

### Cleanup Orphaned Files
```bash
php artisan video:cleanup-orphaned
php artisan video:cleanup-orphaned --delete
```
Finds and optionally deletes video files without database records.

### Cleanup Old Data
```bash
php artisan video:cleanup-old --days=90
php artisan video:cleanup-old --days=90 --delete-videos
```
Deletes old completions and optionally inactive videos.

### Cleanup Test Data
```bash
php artisan video:cleanup-test
```
Removes all test videos, completions, and files.

---

## Summary

The Video System is a complete solution for educational video management in a parental WiFi control system. It includes:

✅ **Parent Dashboard** - Upload and manage videos  
✅ **Child Portal** - Watch videos with word overlays  
✅ **Word Validation** - Ensures active watching  
✅ **Time Granting** - Rewards successful completion  
✅ **File Management** - Automatic cleanup and storage management  
✅ **Security** - Proper access control and validation  
✅ **Retry Logic** - Allows children to retry with new words  

The system is designed to be educational, secure, and efficient, with proper storage management for Raspberry Pi deployment.

