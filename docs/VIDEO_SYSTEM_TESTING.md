# Video System Testing Guide (Todo #8)

## Quick Start

### Step 1: Setup Test Data

```bash
# Seed test data (creates test user, device, and videos)
php artisan db:seed --class=VideoTestDataSeeder

# Verify setup
php artisan video:verify
```

### Step 2: Upload Test Video Files

**Option A: Via Parent Dashboard**
1. Login: `parent@test.com` / `password`
2. Go to `/videos`
3. Edit each test video and upload actual video files

**Option B: Manual File Placement**
1. Place test video files in `storage/app/videos/`
2. Name them: `test_video_1.mp4`, `test_video_2.mp4`, etc.
3. Ensure files are MP4, WebM, or OGG format

### Step 3: Access Portal

Use these URLs (replace `{video_id}` with actual video ID):

```
http://127.0.0.1:8000/portal/video/{video_id}?mac=AA:BB:CC:DD:EE:FF
```

Get video IDs:
```bash
php artisan tinker
>>> App\Models\Video::where('is_active', true)->get(['id', 'title']);
```

## Test Checklist

### ✅ Test 1: Parent Dashboard - Video List

**URL**: `http://127.0.0.1:8000/videos`

**Steps**:
1. Login as `parent@test.com` / `password`
2. Navigate to `/videos`
3. Verify page loads
4. Check videos are listed (or empty state shown)

**Expected**: Yellow header "VIDEOS", "+ New" button, video table or empty state

---

### ✅ Test 2: Parent Dashboard - Create Video

**URL**: `http://127.0.0.1:8000/videos/create`

**Steps**:
1. Click "+ New"
2. Fill form:
   - Title: "My Test Video"
   - Upload video file (MP4/WebM/OGG, < 512MB)
   - Duration: 600 seconds
   - Time Reward: 15 minutes
   - Enable Dictionary Words: ✓
   - Word Count: 5
   - Assign to device: ✓
   - Active: ✓
3. Click "Create Video"

**Expected**: Success message, redirect to index, video appears in list

**Validation Tests**:
- Submit without title → Error
- Upload file > 512MB → Error
- Upload non-video file → Error
- Word count without enabling words → Error

---

### ✅ Test 3: Parent Dashboard - Edit Video

**URL**: `http://127.0.0.1:8000/videos/{video_id}/edit`

**Steps**:
1. Click "Edit" on any video
2. Change title, description, word count
3. Optionally upload new video file
4. Click "Update Video"

**Expected**: Changes saved, video updated

---

### ✅ Test 4: Parent Dashboard - Delete Video

**Steps**:
1. Click "Delete" on a video
2. Confirm deletion

**Expected**:
- If video has completions → Error, not deleted
- If no completions → Video deleted, file removed

---

### ✅ Test 5: Device Assignment

**Steps**:
1. Edit a video
2. Select/deselect devices
3. Save
4. Test access in portal

**Expected**: Only selected devices can access video

---

### ✅ Test 6: Portal - Landing Page

**URL**: `http://127.0.0.1:8000/portal?mac=AA:BB:CC:DD:EE:FF`

**Steps**:
1. Access landing page URL
2. Verify page loads
3. Check device information is displayed
4. Verify available quizzes are listed
5. Verify available videos are listed
6. Click on a video to start it

**Expected**: 
- Landing page displays with device info
- Quizzes shown in blue cards with time rewards
- Videos shown in green cards with time rewards
- Clicking video navigates to video player

**Error Cases**:
- Wrong MAC → "Device not found" error message
- No activities assigned → "No quizzes or videos available" message

---

### ✅ Test 7: Portal - Access Video

**URL**: `http://127.0.0.1:8000/portal/video/{video_id}?mac=AA:BB:CC:DD:EE:FF`

**Steps**:
1. Get video ID from dashboard or landing page
2. Access portal URL (directly or from landing page)
3. Verify video player loads

**Expected**: Video player displays, title shown, play button visible

**Error Cases**:
- Wrong MAC → "Device not found"
- Video not assigned → "You do not have access"
- Video inactive → "This video is not available"

---

### ✅ Test 8: Portal - Video Playback Controls

**Steps**:
1. Start video playback
2. Test:
   - Play/Pause ✓
   - Volume control ✓
   - Try to seek/fast-forward ✗ (should be disabled)

**Expected**: Play/pause/volume work, seeking disabled

---

### ✅ Test 9: Portal - Dictionary Word Display

**Prerequisites**: Video with dictionary words enabled

**Steps**:
1. Start video playback
2. Watch for word overlays
3. Note words and timestamps

**Expected**: Words appear at random timestamps, display for ~3 seconds, fade in/out

**Verification**:
```bash
php artisan video:check-completion --device=AA:BB:CC:DD:EE:FF
```

---

### ✅ Test 10: Portal - Video Completion

**Steps**:
1. Play video to end
2. Observe behavior

**Expected**: Word submission form appears, scrolls into view

---

### ✅ Test 11: Portal - Word Submission (Correct)

**Steps**:
1. Complete video
2. Enter all words that appeared (comma-separated)
3. Submit

**Expected**: 
- Validation passes
- Success page shown
- Time granted
- Countdown timer (3 seconds)
- Auto-redirect to landing page after 3 seconds

**Verification**:
```bash
php artisan tinker
>>> $device = App\Models\Device::where('mac_address', 'AA:BB:CC:DD:EE:FF')->first();
>>> echo "Remaining time: {$device->remaining_time_minutes} minutes\n";
>>> $completion = App\Models\VideoCompletion::latest()->first();
>>> echo "Passed: " . ($completion->passed_validation ? 'Yes' : 'No') . "\n";
```

---

### ✅ Test 12: Portal - Word Submission (Incorrect)

**Steps**:
1. Complete video
2. Enter wrong/missing words
3. Submit

**Expected**: 
- Validation fails
- Failure page shown
- Correct words displayed (for learning)
- No time granted
- "Watch Video Again" button

---

### ✅ Test 13: Portal - Retry Logic

**Steps**:
1. Fail validation (Test 11)
2. Click "Watch Video Again"
3. Watch video again
4. Observe new words

**Expected**: New attempt, new random words, new timestamps, previous attempt preserved

**Verification**:
```bash
php artisan video:check-completion --device=AA:BB:CC:DD:EE:FF
```

---

### ✅ Test 14: Time Granting (Success)

**Steps**:
1. Complete video with correct words
2. Verify time granted

**Expected**: Device time increases, DeviceTimeGrant record created

**Verification**:
```bash
php artisan tinker
>>> $device = App\Models\Device::where('mac_address', 'AA:BB:CC:DD:EE:FF')->first();
>>> $grant = $device->timeGrants()->latest()->first();
>>> echo "Source: {$grant->source}\n";
>>> echo "Minutes: {$grant->minutes_granted}\n";
```

---

### ✅ Test 15: Time Granting (Failure)

**Steps**:
1. Complete video with incorrect words
2. Verify time NOT granted

**Expected**: Device time unchanged, no DeviceTimeGrant record

---

### ✅ Test 16: Video Without Dictionary Words

**Steps**:
1. Create video with words disabled
2. Access in portal
3. Watch to completion

**Expected**: No word overlays, no submission form, time granted immediately (if configured)

---

### ✅ Test 17: Video File Storage

**Steps**:
1. Upload video
2. Check storage

**Expected**: File in `storage/app/videos/`, accessible via URL

**Verification**:
```bash
php artisan tinker
>>> $video = App\Models\Video::latest()->first();
>>> echo "Path: {$video->video_path}\n";
>>> echo "URL: {$video->getVideoUrl()}\n";
>>> echo "Exists: " . (file_exists($video->getFullPath()) ? 'Yes' : 'No') . "\n";
```

---

### ✅ Test 18: Multiple Devices, Same Video

**Steps**:
1. Assign video to multiple devices
2. Access from different devices
3. Complete from each

**Expected**: Separate completions, separate word displays, independent time grants

---

### ✅ Test 19: Portal - Auto-Redirect After Completion

**Steps**:
1. Complete video with correct words
2. Wait on result page
3. Observe countdown timer
4. Wait for auto-redirect

**Expected**: 
- Countdown shows 3, 2, 1
- Automatically redirects to landing page after 3 seconds
- Landing page shows updated remaining time

---

## Helper Commands

### Verify System Setup
```bash
php artisan video:verify
```

### Check Video Completions
```bash
# Check specific completion
php artisan video:check-completion {completion_id}

# Check all completions for device
php artisan video:check-completion --device=AA:BB:CC:DD:EE:FF

# Check all completions for video
php artisan video:check-completion --video={video_id}
```

## Troubleshooting

### Video File Not Uploading
- Check `storage/app/videos/` exists and is writable
- Check PHP upload limits: `php -i | grep upload_max_filesize`
- Check file size < 512MB

### Words Not Appearing
- Verify dictionary words exist: `App\Models\DictionaryWord::count()`
- Check video has `dictionary_words_enabled = true`
- Check `word_count > 0`
- Enable JavaScript in browser

### Time Not Granted
- Check `passed_validation = true` in completion
- Verify `words_correct = words_shown_count`
- Check video has `time_reward_minutes > 0`
- Check logs: `storage/logs/laravel.log`

### Video Not Playing
- Check file format (MP4, WebM, OGG)
- Verify file exists in storage
- Check browser codec support
- Verify `getVideoUrl()` returns correct URL

## Test Data

**Test User**: `parent@test.com` / `password`

**Test Device**: MAC `AA:BB:CC:DD:EE:FF`

**Test Videos** (created by seeder):
1. Educational Video - With Dictionary Words (5 words, 10 min)
2. Educational Video - No Dictionary Words (5 min)
3. Inactive Video (should not appear)
4. Short Video - Many Words (8 words, 2 min)

## Next Steps

After testing:
1. Document any issues
2. Test on Raspberry Pi (if applicable)
3. Test with various video file sizes
4. Test concurrent video sessions
5. Performance test with large files

