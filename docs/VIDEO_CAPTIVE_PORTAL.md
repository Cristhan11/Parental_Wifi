# Video Captive Portal System - Detailed Implementation Guide

## Overview

This document provides a comprehensive, beginner-friendly explanation of the Video System implementation (Todo #8). It covers how educational videos work in the parental WiFi control system, from video upload to child viewing and time granting.

## Table of Contents

1. [System Architecture](#system-architecture)
2. [Parent Dashboard - Video Management](#parent-dashboard---video-management)
3. [Child Portal - Landing Page](#child-portal---landing-page)
4. [Child Portal - Video Viewing](#child-portal---video-viewing)
5. [Dictionary Word System](#dictionary-word-system)
6. [Time Granting System](#time-granting-system)
7. [File Storage and Management](#file-storage-and-management)
8. [Database Structure](#database-structure)
9. [Security and Access Control](#security-and-access-control)

---

## System Architecture

### High-Level Flow

```
Parent Dashboard                    Child Portal
     │                                    │
     ├─ Upload Video                     │
     ├─ Configure Settings                │
     ├─ Assign to Devices                │
     │                                    │
     │                                    ├─ Access Landing Page
     │                                    ├─ View Available Activities
     │                                    ├─ Select Video
     │                                    ├─ Watch Video
     │                                    ├─ See Dictionary Words
     │                                    ├─ Enter Words
     │                                    ├─ Get Time Reward
     │                                    └─ Return to Landing Page
```

### Key Components

1. **VideoController** - Handles parent dashboard operations (CRUD)
2. **PortalController** - Handles child-facing portal (landing page, video viewing, word submission)
3. **VideoWordService** - Manages dictionary word selection and validation
4. **Video Model** - Database representation of videos
5. **VideoCompletion Model** - Tracks child viewing sessions
6. **Blade Views** - User interface templates (landing page, video player, result page)

---

## Parent Dashboard - Video Management

### 1. Video List View (`/videos`)

**File:** `resources/views/videos/index.blade.php`

**What it does:**
- Displays all videos created by the logged-in parent
- Shows video details: title, duration, word settings, time reward
- Shows completion count (how many times video was watched)
- Provides action buttons: Edit, Delete

**How it works:**
1. Parent logs in and navigates to `/videos`
2. `VideoController@index` fetches videos from database
3. Only videos created by this parent are shown (filtered by `user_id`)
4. Videos are displayed in a table with all relevant information
5. Parent can click "Edit" or "Delete" to manage videos

**Key Code Logic:**
```php
// In VideoController@index
$videos = Video::where('user_id', Auth::id())
    ->withCount('completions')  // Efficiently count completions
    ->latest()                  // Newest first
    ->get();
```

**Why `withCount('completions')`?**
- Instead of loading all completion records (which could be thousands), Laravel counts them in the database query
- Much faster and uses less memory
- Result is available as `$video->completions_count`

---

### 2. Create Video Form (`/videos/create`)

**File:** `resources/views/videos/create.blade.php`

**What it does:**
- Provides form for parents to upload and configure videos
- Video file upload is the FIRST field (important for duration detection)
- Automatically detects video duration when file is selected
- Shows remaining fields only after duration is detected

**Form Fields:**
1. **Video File** (Required) - First field, triggers duration detection
2. **Title** (Required) - Video name
3. **Description** (Optional) - Additional information
4. **Duration** (Auto-filled) - Detected from video file
5. **Time Reward** (Required) - Minutes granted upon completion
6. **Dictionary Words** (Checkbox) - Enable/disable word validation
7. **Word Count** (Conditional) - Number of words to display
8. **Device Assignment** (Optional) - Which devices can watch
9. **Active Status** (Checkbox) - Whether video appears in portal

**Duration Detection Logic:**
```javascript
// When video file is selected
function handleVideoFileSelect(event) {
    const file = event.target.files[0];
    
    // Create temporary video element
    const video = document.createElement('video');
    video.preload = 'metadata';  // Only load metadata, not full video
    
    // Create object URL for the file
    const url = URL.createObjectURL(file);
    video.src = url;
    
    // When metadata loads, get duration
    video.addEventListener('loadedmetadata', function() {
        const durationSeconds = Math.round(video.duration);
        
        // Fill in duration field
        document.getElementById('duration_seconds').value = durationSeconds;
        
        // Show rest of form fields
        showFormSections();
    });
}
```

**Why detect duration automatically?**
- Prevents manual entry errors
- Ensures accuracy (duration from video file is always correct)
- Better user experience (one less thing to fill in)

**Validation:**
- Handled by `StoreVideoRequest` class
- Validates file type (MP4, WebM, OGG)
- Validates file size (max 512MB)
- Validates all required fields
- Shows friendly error messages if validation fails

---

### 3. Store Video (`VideoController@store`)

**File:** `app/Http/Controllers/VideoController.php`

**What it does:**
1. Validates form data (via `StoreVideoRequest`)
2. Uploads video file to `storage/app/public/videos/`
3. Creates video record in database
4. Assigns video to selected devices
5. Redirects to video list with success message

**Step-by-Step Process:**

```php
public function store(StoreVideoRequest $request): RedirectResponse
{
    // Step 1: Get validated form data
    $validated = $request->validated();
    
    // Step 2: Upload video file
    // ->store('videos', 'public') saves to storage/app/public/videos/
    // Returns relative path: "videos/unique_filename.mp4"
    $videoPath = $request->file('video_file')->store('videos', 'public');
    
    // Step 3: Create video record in database
    $video = Video::create([
        'user_id' => Auth::id(),              // Link to parent
        'title' => $validated['title'],
        'video_path' => $videoPath,           // File location
        'duration_seconds' => $validated['duration_seconds'],
        'dictionary_words_enabled' => $dictionaryWordsEnabled,
        'word_count' => $wordCount,
        'time_reward_minutes' => $validated['time_reward_minutes'],
        'is_active' => $isActive,
    ]);
    
    // Step 4: Assign to devices (if any selected)
    if (isset($validated['devices'])) {
        $video->devices()->sync($validated['devices']);
    }
    
    // Step 5: Redirect with success message
    return redirect()->route('videos.index')
        ->with('success', 'Video created successfully!');
}
```

**Why `store('videos', 'public')`?**
- `'videos'` = subdirectory name
- `'public'` = storage disk (saves to `storage/app/public/videos/`)
- Files in `public` disk are accessible via `/storage/` URL
- This allows videos to be streamed in the browser

**File Naming:**
- Laravel automatically generates unique filenames
- Format: `{random_string}.{extension}`
- Example: `XGrNVAHkv91m04a16UPZXL81wfR5AQ6hyfLrccsU.mp4`
- Prevents filename conflicts and overwrites

---

### 4. Edit Video (`/videos/{id}/edit`)

**File:** `resources/views/videos/edit.blade.php`

**What it does:**
- Allows parents to update video settings
- Video file upload is optional (can keep existing file)
- If new file uploaded, automatically detects duration
- All fields are pre-filled with existing values

**Key Differences from Create:**
- Video file is optional (not required)
- Duration field shows existing value
- If new file uploaded, duration is updated automatically
- All form sections visible by default (since video already exists)

**Update Process:**
```php
public function update(UpdateVideoRequest $request, Video $video): RedirectResponse
{
    // Security check: Ensure parent owns this video
    if ($video->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.');
    }
    
    // If new video file uploaded
    if ($request->hasFile('video_file')) {
        // Delete old file
        Storage::disk('public')->delete($video->video_path);
        
        // Upload new file
        $videoPath = $request->file('video_file')->store('videos', 'public');
        $validated['video_path'] = $videoPath;
    }
    
    // Update video record
    $video->update($validated);
    
    // Update device assignments
    if (isset($validated['devices'])) {
        $video->devices()->sync($validated['devices']);
    }
}
```

**Why delete old file?**
- Prevents storage from filling up with unused files
- Important for Raspberry Pi with limited storage
- Old file is deleted before new one is saved (prevents data loss if upload fails)

---

### 5. Delete Video (`VideoController@destroy`)

**What it does:**
- Deletes video record from database
- Deletes video file from storage
- Removes device assignments (cascade delete)
- Prevents deletion if video has completions (unless force delete)

**Deletion Logic:**
```php
public function destroy(Video $video): RedirectResponse
{
    // Security check
    if ($video->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.');
    }
    
    // Check for completions
    $completionCount = $video->completions()->count();
    
    if ($completionCount > 0) {
        // Check if force delete requested
        $forceDelete = $request->input('force', false);
        
        if (!$forceDelete) {
            return redirect()->route('videos.index')
                ->with('error', 'Cannot delete video with completions. Use force delete.');
        }
    }
    
    // Delete video file
    if (Storage::disk('public')->exists($video->video_path)) {
        Storage::disk('public')->delete($video->video_path);
    }
    
    // Delete video record (completions cascade delete automatically)
    $video->delete();
}
```

**Why prevent deletion with completions?**
- Preserves viewing history for parents
- Maintains data integrity
- Parents can deactivate instead (set `is_active = false`)

**Force Delete:**
- Available for when video is no longer needed
- Removes video AND all completion history
- Useful for cleaning up old/unused videos

---

## Child Portal - Landing Page

### 1. Portal Landing Page (`/portal?mac=AA:BB:CC:DD:EE:FF`)

**File:** `app/Http/Controllers/PortalController.php` - `landing()` method  
**View:** `resources/views/portal/landing.blade.php`

**What it does:**
- Main entry point for children accessing the portal
- Identifies device by MAC address
- Displays device information (name, remaining time)
- Shows all available quizzes assigned to the device
- Shows all available videos assigned to the device
- Provides clickable links to start activities

**How it works:**
1. Child accesses portal URL with MAC address: `/portal?mac=AA:BB:CC:DD:EE:FF`
2. System looks up device by MAC address
3. Fetches all active quizzes assigned to this device
4. Fetches all active videos assigned to this device
5. Displays landing page with all available activities

**Step-by-Step Process:**

```php
public function landing(Request $request): View
{
    // Step 1: Get device from MAC address
    $device = $this->getDevice($request);
    
    // Step 2: If device not found, show error
    if (!$device) {
        return view('portal.landing', [
            'device' => null,
            'error' => 'Device not found. Please connect to the network.',
        ]);
    }
    
    // Step 3: Get available quizzes (active and assigned)
    $quizzes = $device->quizzes()
        ->where('is_active', true)
        ->get();
    
    // Step 4: Get available videos (active and assigned)
    $videos = $device->videos()
        ->where('is_active', true)
        ->get();
    
    // Step 5: Display landing page
    return view('portal.landing', [
        'device' => $device,
        'quizzes' => $quizzes,
        'videos' => $videos,
    ]);
}
```

**Landing Page Features:**

#### A. Device Information Section
- Shows device name
- Displays remaining internet time (formatted: "1 hour 30 minutes" or "45 minutes")
- Yellow background to match portal theme

#### B. Available Quizzes Section
- Lists all active quizzes assigned to device
- Each quiz card shows:
  - Quiz title and description
  - Time reward (minutes child will earn)
  - Clickable link to start quiz
- Blue theme (#3B82F6) to distinguish from videos

#### C. Available Videos Section
- Lists all active videos assigned to device
- Each video card shows:
  - Video title and description
  - Video duration (formatted as MM:SS)
  - Word count (if dictionary words enabled)
  - Time reward (minutes child will earn)
  - Clickable link to start video
- Green theme (#10B981) to distinguish from quizzes

#### D. No Activities Message
- Shown when device has no quizzes or videos assigned
- Friendly message: "No quizzes or videos available at this time"
- Suggests contacting parent

**Why a landing page?**
- Provides central hub for all activities
- Shows child what's available to earn time
- Displays remaining time so child knows their status
- Better user experience than direct video/quiz URLs
- Allows parents to manage what children see

**Access Control:**
- Only shows activities assigned to the specific device
- Only shows active activities (inactive ones are hidden)
- Device must exist in database (MAC address validation)

**Navigation Flow:**
```
Portal Landing Page
    ↓
Child clicks on Quiz/Video
    ↓
Activity starts (quiz or video player)
    ↓
Child completes activity
    ↓
Result page shown
    ↓
Auto-redirect back to Landing Page (after 3 seconds)
```

---

## Child Portal - Video Viewing

### 1. Video Access (`/portal/video/{id}?mac=AA:BB:CC:DD:EE:FF`)

**File:** `app/Http/Controllers/PortalController.php` - `showVideo()` method

**What it does:**
1. Identifies child's device by MAC address
2. Validates video is active and assigned to device
3. Creates video completion record (tracks viewing session)
4. Selects random dictionary words (if enabled)
5. Generates random timestamps for word display
6. Displays video player interface

**Step-by-Step Process:**

```php
public function showVideo(Request $request, Video $video): View|RedirectResponse
{
    // Step 1: Get device from MAC address
    $device = $this->getDevice($request);
    
    // Step 2: Validate device exists
    if (!$device) {
        return redirect()->route('portal.landing')
            ->with('error', 'Device not found.');
    }
    
    // Step 3: Validate video is active
    if (!$video->is_active) {
        return redirect()->route('portal.landing')
            ->with('error', 'This video is not available.');
    }
    
    // Step 4: Validate device has access
    if (!$device->videos->contains($video)) {
        return redirect()->route('portal.landing')
            ->with('error', 'You do not have access to this video.');
    }
    
    // Step 5: Create completion record
    $completion = VideoCompletion::create([
        'device_id' => $device->id,
        'video_id' => $video->id,
        'attempt_number' => $attemptNumber,  // 1, 2, 3, etc.
        // ... other fields initialized
    ]);
    
    // Step 6: Handle dictionary words (if enabled)
    if ($video->dictionary_words_enabled) {
        // Select random words
        $words = $this->videoWordService->selectRandomWords($video->word_count);
        
        // Generate random timestamps
        $timestamps = $this->videoWordService->generateRandomTimestamps(
            $video->duration_seconds,
            $video->word_count
        );
        
        // Store word displays in database
        foreach ($words as $index => $word) {
            VideoWordDisplay::create([
                'video_completion_id' => $completion->id,
                'dictionary_word_id' => $word->id,
                'displayed_at_timestamp' => $timestamps[$index],
                'word_text' => $word->word,
            ]);
        }
    }
    
    // Step 7: Display video player
    return view('portal.video', [
        'video' => $video,
        'device' => $device,
        'wordsData' => $wordsData,  // For JavaScript
    ]);
}
```

**Why create completion record before video starts?**
- Tracks the viewing session from the beginning
- Stores attempt number (for retry logic)
- Links word displays to this specific viewing session
- Allows tracking even if child doesn't finish video

**Retry Logic:**
- If child fails word validation, they can retry
- Each retry creates a new completion record
- Attempt number increments: 1, 2, 3, etc.
- New random words are selected each time
- Prevents memorization of words from previous attempts

---

### 2. Video Player Interface

**File:** `resources/views/portal/video.blade.php`

**What it does:**
- Displays HTML5 video player
- Shows dictionary words as overlays during playback
- Pauses video when words appear
- Prevents seeking/fast-forward
- Shows word submission form when video ends

**Key Features:**

#### A. Video Player Element

```html
<video 
    id="videoPlayer" 
    controls 
    preload="metadata"
    playsinline
    ontimeupdate="handleTimeUpdate()"
    onended="handleVideoEnded()"
    onerror="handleVideoError(event)">
    <source src="{{ $video->getVideoUrl() }}" type="video/mp4">
</video>
```

**Attributes Explained:**
- `controls` - Shows play/pause/volume controls
- `preload="metadata"` - Loads video info (duration) but not full video
- `playsinline` - Allows inline playback on mobile devices
- `ontimeupdate` - Fires continuously as video plays (for word display)
- `onended` - Fires when video reaches the end
- `onerror` - Fires if video fails to load

#### B. Word Display System

**How it works:**
1. JavaScript receives word data from server (word, definition, timestamp)
2. `handleTimeUpdate()` runs every ~250ms as video plays
3. Checks if current video time matches any word timestamp
4. If match found, displays word overlay
5. Video pauses automatically
6. Word stays visible for 8 seconds
7. Video resumes automatically

**JavaScript Logic:**
```javascript
// Words data from server
const wordsData = [
    { word: "adventure", definition: "An exciting experience", timestamp: 45 },
    { word: "curious", definition: "Eager to learn", timestamp: 180 },
    // ... more words
];

// Track which words have been shown
const shownWords = [];

// Called continuously as video plays
function handleTimeUpdate() {
    const currentTime = Math.floor(videoPlayer.currentTime);
    
    // Check each word
    wordsData.forEach((wordData, index) => {
        const wordTimestamp = wordData.timestamp;
        
        // Show word if time matches (within 1 second tolerance)
        if (currentTime >= wordTimestamp && 
            currentTime < wordTimestamp + 1 && 
            !shownWords.includes(index)) {
            
            showWord(wordData);  // Display word overlay
            shownWords.push(index);  // Mark as shown
        }
    });
}

// Display word overlay
function showWord(wordData) {
    // Pause video so child can read
    if (!videoPlayer.paused) {
        videoPlayer.pause();
    }
    
    // Create overlay element
    const overlay = document.createElement('div');
    overlay.className = 'word-overlay';
    overlay.innerHTML = `
        <div>${wordData.word}</div>
        <div>${wordData.definition}</div>
    `;
    
    // Add to page
    wordOverlayContainer.appendChild(overlay);
    
    // Remove after 8 seconds and resume video
    setTimeout(() => {
        overlay.remove();
        videoPlayer.play();
    }, 8000);
}
```

**Why pause video when word appears?**
- Ensures child has time to read the word
- Prevents missing words if they appear too quickly
- Better learning experience (child can focus on word)

**Why 8 seconds?**
- Gives child enough time to read word and definition
- Not too long (would be boring)
- Not too short (child might miss it)

#### C. Seeking Prevention

**Why prevent seeking?**
- Ensures child watches entire video
- Prevents skipping to the end
- Maintains educational value

**How it's prevented:**
1. CSS hides timeline/seekbar controls
2. JavaScript event listeners block seek attempts
3. If user tries to seek, video resets to last valid position

**CSS:**
```css
/* Hide timeline controls */
video::-webkit-media-controls-timeline {
    display: none !important;
}

/* Hide time displays */
video::-webkit-media-controls-current-time-display {
    display: none !important;
}
```

**JavaScript (commented out for now, but available):**
```javascript
// Prevent seeking by resetting to last valid position
videoPlayer.addEventListener('seeked', function(e) {
    const currentTime = videoPlayer.currentTime;
    const lastValidTime = videoPlayer.lastValidTime || 0;
    
    // If seeking forward more than 1 second, reset
    if (currentTime > lastValidTime + 1) {
        videoPlayer.currentTime = lastValidTime;
    }
});
```

---

### 3. Word Submission (`/portal/video/submit`)

**File:** `app/Http/Controllers/PortalController.php` - `submitVideoWords()` method

**What it does:**
1. Gets video completion from session
2. Retrieves child's entered words from form
3. Gets words that were actually displayed
4. Validates entered words against displayed words
5. Updates completion record with results
6. Grants time if validation passed
7. Redirects to result page

**Process:**
```php
public function submitVideoWords(Request $request): RedirectResponse
{
    // Step 1: Get device and completion
    $device = $this->getDevice($request);
    $completionId = session('video_completion_id');
    $completion = VideoCompletion::findOrFail($completionId);
    $video = $completion->video;
    
    // Step 2: Handle word validation (if enabled)
    if ($video->dictionary_words_enabled) {
        // Get entered words from form (comma-separated)
        $enteredWords = array_filter(
            array_map('trim', explode(',', $request->input('words', '')))
        );
        
        // Get displayed words from database
        $displayedWords = $completion->getWordsShown();
        
        // Validate words
        $validationResult = $this->videoWordService->validateWords(
            $displayedWords,
            $enteredWords
        );
        
        // Update completion record
        $completion->update([
            'completed_at' => now(),
            'words_entered' => json_encode($enteredWords),
            'words_correct' => $validationResult['words_correct'],
            'passed_validation' => $validationResult['passed_validation'],
        ]);
    } else {
        // No words required - just mark as passed
        $completion->update([
            'completed_at' => now(),
            'passed_validation' => true,
        ]);
    }
    
    // Step 3: Grant time if validation passed
    if ($completion->passed_validation) {
        $this->timeGrantingService->grantTimeFromVideoCompletion($device, $completion);
    }
    
    // Step 4: Redirect to result page
    return redirect()->route('portal.video.result', $completion);
}
```

**Word Validation Logic:**
- Case-insensitive: "Adventure" = "adventure" = "ADVENTURE"
- Trims whitespace: " adventure " = "adventure"
- Compares arrays to find matches
- Child must get ALL words correct (no partial credit)

**Why no partial credit?**
- Encourages active watching
- Ensures child remembers all words
- Maintains educational value

---

### 4. Video Result Page (`/portal/video/result/{completion}`)

**File:** `resources/views/portal/video-result.blade.php`

**What it does:**
- Shows pass/fail status
- Displays words that were shown vs words child entered
- Shows time granted (if passed)
- Provides "Watch Again" button (if failed)
- Auto-redirects to landing page after success

**Result Display:**
- **If Passed:**
  - Green success message
  - Shows time granted (e.g., "You earned 15 minutes!")
  - Shows all words were correct
  - Countdown timer (3 seconds)
  - Auto-redirects to landing page after 3 seconds

- **If Failed:**
  - Red failure message
  - Shows how many words were correct (e.g., "3 out of 5")
  - Shows correct words (for learning)
  - Shows words child entered (for comparison)
  - "Watch Video Again" button (creates new attempt with new words)
  - "Go Back" button (returns to landing page)

**Auto-Redirect Logic:**
```javascript
// Countdown from 3 seconds, then redirect to landing page
let countdown = 3;
const countdownInterval = setInterval(() => {
    countdown--;
    if (countdown > 0) {
        countdownElement.textContent = countdown;
    } else {
        clearInterval(countdownInterval);
        // Redirect to landing page
        window.location.href = '{{ route("portal.landing", ["mac" => $device->mac_address]) }}';
    }
}, 1000);
```

**Why auto-redirect?**
- Allows child to see success message
- Automatically returns to landing page to continue browsing
- Better user experience (no manual navigation needed)

---

## Dictionary Word System

### VideoWordService

**File:** `app/Services/VideoWordService.php`

**Purpose:**
Centralized service for all dictionary word operations. Separates business logic from controllers, making code more organized and testable.

### 1. Random Word Selection

**Method:** `selectRandomWords(int $wordCount): Collection`

**What it does:**
- Randomly selects words from dictionary word pool
- Returns collection of DictionaryWord models

**How it works:**
```php
public function selectRandomWords(int $wordCount): Collection
{
    // Get all dictionary words, randomize order, take N words
    return DictionaryWord::inRandomOrder()
        ->take($wordCount)
        ->get();
}
```

**Why random?**
- Different words each time child watches
- Prevents memorization
- Encourages active learning

**Example:**
- Video has `word_count = 5`
- Method selects 5 random words from dictionary
- Next time child watches, different 5 words are selected

---

### 2. Random Timestamp Generation

**Method:** `generateRandomTimestamps(int $durationSeconds, int $wordCount): array`

**What it does:**
- Generates random timestamps when words should appear
- Distributes words throughout video duration
- Returns array of timestamps in seconds

**How it works:**
```php
public function generateRandomTimestamps(int $durationSeconds, int $wordCount): array
{
    // Calculate interval per word
    $interval = $durationSeconds / $wordCount;
    
    $timestamps = [];
    for ($i = 0; $i < $wordCount; $i++) {
        // Calculate interval for this word
        $intervalStart = $i * $interval;
        $intervalEnd = ($i + 1) * $interval;
        
        // Random timestamp within interval
        $timestamp = rand($intervalStart, $intervalEnd);
        $timestamps[] = $timestamp;
    }
    
    // Sort in chronological order
    sort($timestamps);
    return $timestamps;
}
```

**Example:**
- Video duration: 600 seconds (10 minutes)
- Word count: 5 words
- Interval: 600 / 5 = 120 seconds per interval
- Word 1: Random between 0-120 seconds (e.g., 45 seconds)
- Word 2: Random between 120-240 seconds (e.g., 180 seconds)
- Word 3: Random between 240-360 seconds (e.g., 290 seconds)
- Word 4: Random between 360-480 seconds (e.g., 420 seconds)
- Word 5: Random between 480-600 seconds (e.g., 550 seconds)

**Why distributed intervals?**
- Prevents all words at start or end
- Ensures child watches entire video
- Better learning experience

---

### 3. Word Validation

**Method:** `validateWords(array $wordsShown, array $wordsEntered): array`

**What it does:**
- Compares entered words with displayed words
- Case-insensitive and trims whitespace
- Returns validation result with counts

**How it works:**
```php
public function validateWords(array $wordsShown, array $wordsEntered): array
{
    // Normalize both arrays (lowercase, trim)
    $normalizedShown = array_map(function ($word) {
        return strtolower(trim($word));
    }, $wordsShown);
    
    $normalizedEntered = array_map(function ($word) {
        return strtolower(trim($word));
    }, $wordsEntered);
    
    // Count matches
    $wordsCorrect = count(array_intersect($normalizedShown, $normalizedEntered));
    
    // Child passes only if ALL words correct
    $passedValidation = ($wordsCorrect === count($normalizedShown)) && 
                        (count($normalizedShown) > 0);
    
    return [
        'words_shown_count' => count($normalizedShown),
        'words_entered_count' => count($normalizedEntered),
        'words_correct' => $wordsCorrect,
        'passed_validation' => $passedValidation,
    ];
}
```

**Example:**
- Words shown: ["adventure", "curious", "discover"]
- Words entered: ["adventure", "curious", "discover"]
- Result: 3 correct, passed = true

- Words shown: ["adventure", "curious", "discover"]
- Words entered: ["adventure", "curious"]
- Result: 2 correct, passed = false (missing "discover")

**Normalization:**
- "Adventure" = "adventure" = "ADVENTURE" (case-insensitive)
- " adventure " = "adventure" (trims whitespace)
- Makes validation forgiving of minor input differences

---

## Time Granting System

### Integration with TimeGrantingService

**What it does:**
- When child successfully completes video, grants time reward
- Time is added to device's `remaining_time_minutes`
- Time grant is recorded in `device_time_grants` table

**Process:**
```php
// In PortalController@submitVideoWords
if ($completion->passed_validation) {
    $this->timeGrantingService->grantTimeFromVideoCompletion($device, $completion);
}
```

**Time Grant Details:**
- Amount: `$video->time_reward_minutes` (set by parent)
- Example: 15 minutes for completing a 10-minute video
- Added to device's remaining time
- Recorded for tracking/analytics

**Why grant time?**
- Incentivizes children to watch educational videos
- Rewards active learning
- Encourages engagement with educational content

---

## File Storage and Management

### Storage Location

**Path:** `storage/app/public/videos/`

**Why `public` disk?**
- Files in `public` disk are accessible via web URL
- Symlink connects `public/storage` → `storage/app/public`
- Videos can be streamed in browser via `/storage/videos/filename.mp4`

### File Upload Process

1. **Parent uploads file** via dashboard form
2. **Laravel validates** file (type, size)
3. **File is stored** using `store('videos', 'public')`
4. **Unique filename generated** automatically
5. **Path saved** in database: `videos/filename.mp4`
6. **File accessible** via URL: `/storage/videos/filename.mp4`

### File Deletion

**When video is deleted:**
1. Check if file exists in storage
2. Delete file using `Storage::disk('public')->delete()`
3. Fallback to direct `unlink()` if Storage fails
4. Log success/failure for debugging

**Why delete files?**
- Prevents storage from filling up
- Important for Raspberry Pi with limited storage
- Keeps system clean

### Cleanup Commands

**Orphaned Files:**
```bash
php artisan video:cleanup-orphaned
```
- Finds video files without database records
- Shows file sizes and total space used
- Can delete with `--delete` flag

**Old Videos:**
```bash
php artisan video:cleanup-old --days=90
```
- Deletes completions older than X days
- Optionally deletes inactive videos with no recent completions
- Useful for long-term storage management

---

## Database Structure

### Videos Table

**Purpose:** Stores video metadata and settings

**Key Fields:**
- `id` - Unique identifier
- `user_id` - Parent who created video
- `title` - Video name
- `video_path` - File location (e.g., "videos/filename.mp4")
- `duration_seconds` - Video length
- `dictionary_words_enabled` - Whether words are used
- `word_count` - Number of words to display
- `time_reward_minutes` - Minutes granted upon completion
- `is_active` - Whether video appears in portal

### Video Completions Table

**Purpose:** Tracks when children complete videos

**Key Fields:**
- `id` - Unique identifier
- `device_id` - Device that watched video
- `video_id` - Video that was watched
- `attempt_number` - Retry number (1, 2, 3, etc.)
- `completed_at` - When video was finished
- `words_entered` - Words child entered (JSON)
- `words_correct` - Number of correct words
- `passed_validation` - Whether child passed

**Relationships:**
- `belongsTo Video` - One completion belongs to one video
- `belongsTo Device` - One completion belongs to one device
- `hasMany VideoWordDisplay` - One completion has many word displays

### Video Word Displays Table

**Purpose:** Tracks which words were displayed during viewing

**Key Fields:**
- `id` - Unique identifier
- `video_completion_id` - Links to viewing session
- `dictionary_word_id` - Which word was shown
- `displayed_at_timestamp` - When word appeared (in seconds)
- `word_text` - Word text (preserved in case word is deleted)

**Why store word text?**
- Preserves word even if dictionary word is deleted later
- Allows validation even if word no longer exists
- Maintains historical accuracy

### Device Video Pivot Table

**Purpose:** Many-to-many relationship (devices ↔ videos)

**Key Fields:**
- `device_id` - Device that can watch video
- `video_id` - Video that device can watch
- `created_at` - When assignment was made

**Why pivot table?**
- One device can watch many videos
- One video can be assigned to many devices
- Flexible assignment system

---

## Security and Access Control

### Parent Dashboard Security

**Authentication:**
- All routes protected by `auth` middleware
- Only logged-in parents can access

**Authorization:**
- Parents can only manage their own videos
- Checked via `user_id` comparison
- Prevents unauthorized access/modification

**File Upload Security:**
- Validates file type (MP4, WebM, OGG only)
- Validates file size (max 512MB)
- Prevents malicious file uploads

### Child Portal Security

**Device-Based Access:**
- No login required (captive portal)
- Access controlled by MAC address
- Device must exist in database

**Video Access Control:**
- Video must be active (`is_active = true`)
- Device must be assigned to video
- Prevents unauthorized video access

**Session Management:**
- Completion ID stored in session
- Prevents tampering with completion records
- Session cleared after submission

---

## Common Issues and Solutions

### Issue: Video doesn't play

**Possible Causes:**
1. File not in correct location (`storage/app/public/videos/`)
2. Storage symlink not created (`php artisan storage:link`)
3. File permissions incorrect
4. Video codec not supported by browser

**Solutions:**
- Check file exists: `ls storage/app/public/videos/`
- Create symlink: `php artisan storage:link`
- Check file permissions
- Try different browser or re-encode video

### Issue: Words don't appear

**Possible Causes:**
1. Dictionary words not enabled
2. No words in dictionary database
3. JavaScript errors
4. Timestamps incorrect

**Solutions:**
- Check `dictionary_words_enabled = true`
- Check `word_count > 0`
- Verify dictionary words exist: `DictionaryWord::count()`
- Check browser console for errors

### Issue: Time not granted

**Possible Causes:**
1. Word validation failed
2. `passed_validation = false`
3. TimeGrantingService error

**Solutions:**
- Check completion record: `passed_validation` field
- Verify all words were entered correctly
- Check logs for TimeGrantingService errors

---

## Testing the System

### Test Checklist

1. **Parent Dashboard:**
   - [ ] Create video with file upload
   - [ ] Verify duration auto-detected
   - [ ] Edit video settings
   - [ ] Assign video to device
   - [ ] Delete video (with and without completions)

2. **Child Portal:**
   - [ ] Access video via portal URL
   - [ ] Video plays correctly
   - [ ] Words appear at correct timestamps
   - [ ] Video pauses when words appear
   - [ ] Word submission form appears at end
   - [ ] Word validation works (correct/incorrect)
   - [ ] Time is granted after successful validation

3. **Edge Cases:**
   - [ ] Video without dictionary words
   - [ ] Retry after failed validation
   - [ ] Inactive video (should not appear)
   - [ ] Unassigned video (should not be accessible)

---

## Summary

The Video System allows parents to upload educational videos that children can watch to earn internet time. The system includes:

- **Parent Dashboard:** Upload, configure, and manage videos
- **Child Portal:** Watch videos with dictionary word overlays
- **Word Validation:** Ensures active watching and learning
- **Time Granting:** Rewards successful completion
- **File Management:** Automatic cleanup and storage management

The system is designed to be educational, secure, and efficient, with proper access control and storage management for Raspberry Pi deployment.

