# Video Portal Access Guide - Quick Testing Reference

## Quick Access Instructions

### Step 1: Setup Test Data

```bash
# Seed test data (creates test user, device, and videos)
php artisan db:seed --class=VideoTestDataSeeder

# Verify setup
php artisan video:verify
```

### Step 2: Get Video ID

You can find video IDs in two ways:

**Option A: From the Parent Dashboard**
1. Login as parent: `parent@test.com` / `password`
2. Go to `/videos` 
3. Click on any video to see its ID in the URL (e.g., `/videos/1/edit`)

**Option B: Check Database**
```bash
php artisan tinker
```
Then run:
```php
\App\Models\Video::where('is_active', true)->get(['id', 'title', 'dictionary_words_enabled']);
```

### Step 3: Access the Portal

**Option A: Landing Page (Recommended)**
Use this URL to see all available activities:

```
http://127.0.0.1:8000/portal?mac=AA:BB:CC:DD:EE:FF
```

This shows:
- Device information (name, remaining time)
- All available quizzes
- All available videos
- Click on any activity to start it

**Option B: Direct Video Access**
Use this URL format to access a specific video directly:

```
http://127.0.0.1:8000/portal/video/{VIDEO_ID}?mac=AA:BB:CC:DD:EE:FF
```

**Example URLs** (after running seeder):
- Landing Page: `http://127.0.0.1:8000/portal?mac=AA:BB:CC:DD:EE:FF`
- Video ID 1 (with words): `http://127.0.0.1:8000/portal/video/1?mac=AA:BB:CC:DD:EE:FF`
- Video ID 2 (no words): `http://127.0.0.1:8000/portal/video/2?mac=AA:BB:CC:DD:EE:FF`

## Test Data Details

### Test Device
- **MAC Address:** `AA:BB:CC:DD:EE:FF`
- **Name:** Test Device
- **Status:** Active
- **Remaining Time:** 0 minutes (will trigger portal)

### Available Videos (Created by Seeder)

1. **Educational Video - With Dictionary Words**
   - Duration: 10 minutes (600 seconds)
   - Dictionary Words: Enabled (5 words)
   - Time Reward: 15 minutes
   - Status: Active

2. **Educational Video - No Dictionary Words**
   - Duration: 5 minutes (300 seconds)
   - Dictionary Words: Disabled
   - Time Reward: 10 minutes
   - Status: Active

3. **Inactive Video (Test)**
   - Status: Inactive (should NOT appear in portal)

4. **Short Video - Many Words**
   - Duration: 2 minutes (120 seconds)
   - Dictionary Words: Enabled (8 words - frequent display)
   - Time Reward: 20 minutes
   - Status: Active

## What You'll See

### Landing Page Features:
- ✅ Device name and remaining time display
- ✅ List of available quizzes (blue cards)
- ✅ List of available videos (green cards)
- ✅ Time reward shown for each activity
- ✅ Clickable links to start activities
- ✅ Friendly message if no activities available

### Video Interface Features:
- ✅ Yellow header with "VIDEO" label
- ✅ Video player with play/pause controls
- ✅ Volume control
- ✅ Seeking/fast-forward disabled (restricted controls)
- ✅ Dictionary word overlays (if enabled)
- ✅ Word submission form at video end
- ✅ Auto-redirect to landing page after completion

### Dictionary Word Display:
- Words appear at random timestamps during playback
- Each word displays for ~3 seconds
- Word and definition shown in yellow overlay
- Words fade in/out smoothly

## Testing Different Scenarios

### Test Landing Page:
1. Access landing page: `http://127.0.0.1:8000/portal?mac=AA:BB:CC:DD:EE:FF`
2. Verify device information is shown
3. Verify available quizzes and videos are listed
4. Click on a video to start it
5. After completing video, verify auto-redirect back to landing page

### Test Passing Word Validation:
1. Access landing page or direct video URL (with dictionary words enabled)
2. Watch video and note the words that appear
3. When video ends, enter all words (comma-separated)
4. Submit the form
5. You should see:
   - Success message
   - Word count (e.g., "5 / 5")
   - Time granted message
   - Countdown timer (3 seconds)
   - Auto-redirect to landing page after 3 seconds

### Test Failing Word Validation:
1. Access a video URL
2. Watch video and note the words
3. Enter incorrect or missing words
4. Submit the form
5. You should see:
   - Failure message
   - Word count (e.g., "3 / 5")
   - Correct words displayed (for learning)
   - No time granted
   - "Watch Video Again" button

### Test Video Without Dictionary Words:
1. Access Video ID 2 (no dictionary words)
2. Watch video to completion
3. No word submission form should appear
4. Time should be granted immediately (if configured)

### Test Retry Logic:
1. Fail word validation
2. Click "Watch Video Again"
3. Watch video again
4. New random words should appear at different timestamps

## Helper Commands

### Verify System Setup
```bash
php artisan video:verify
```

### Check Video Completions
```bash
# Check all completions for test device
php artisan video:check-completion --device=AA:BB:CC:DD:EE:FF

# Check specific completion
php artisan video:check-completion {completion_id}

# Check completions for specific video
php artisan video:check-completion --video={video_id}
```

## Troubleshooting

### Error: "Device not found"
- Make sure you're using the correct MAC address: `AA:BB:CC:DD:EE:FF`
- Check that the device exists: Run `VideoTestDataSeeder`

### Error: "You do not have access to this video"
- The device must be assigned to the video
- Run the seeder again: `php artisan db:seed --class=VideoTestDataSeeder`

### Error: "This video is not available"
- The video might be inactive
- Check `is_active` status in the database or parent dashboard

### Video Not Playing
- Make sure video file exists in `storage/app/videos/`
- Upload video file via parent dashboard
- Check file format (MP4, WebM, OGG)

### Words Not Appearing
- Verify dictionary words exist: `App\Models\DictionaryWord::count()`
- Check video has `dictionary_words_enabled = true`
- Check `word_count > 0`
- Enable JavaScript in browser

### Time Not Granted
- Check `passed_validation = true` in completion record
- Verify all words were entered correctly
- Check video has `time_reward_minutes > 0`

## Quick Test Script

To quickly test all videos, you can use this in your browser console or create bookmarks:

```javascript
// Test all active videos
const mac = 'AA:BB:CC:DD:EE:FF';
const videoIds = [1, 2, 4]; // Active video IDs from seeder
videoIds.forEach(id => {
    console.log(`Video ${id}: http://127.0.0.1:8000/portal/video/${id}?mac=${mac}`);
});
```

## Notes

- The portal is **public** (no authentication required)
- Access is controlled by MAC address and video assignment
- Session is used to track video completion
- Dictionary words are randomly selected each time
- Video must be watched to completion (seeking disabled)
- All words must be correct to pass validation (no partial credit)

## Complete Testing Guide

For comprehensive testing procedures, see **[VIDEO_SYSTEM_TESTING.md](VIDEO_SYSTEM_TESTING.md)**

