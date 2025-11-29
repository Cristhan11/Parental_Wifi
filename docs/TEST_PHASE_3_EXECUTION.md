# Test Phase 3 Execution Guide

This document provides a step-by-step guide for executing Test Phase 3 (TODO #9) to verify file system operations and video storage on Raspberry Pi.

## Overview

Test Phase 3 verifies critical file system operations required for video storage on Raspberry Pi:
- Storage directory permissions
- Symlink functionality
- Video file upload/streaming
- File size limits
- Web server file serving
- Network access via Raspberry Pi WiFi
- Storage constraints

## Prerequisites

Before starting Test Phase 3, ensure:
- ✅ Video system is built and functional (TODO #8 complete)
- ✅ Raspberry Pi is set up with Laravel application (see `VIDEO_SYSTEM_TESTING.md` Steps 1-10)
- ✅ You have SSH access to Raspberry Pi
- ✅ At least one test video file (20MB+) available for upload

## Quick Start

### Option 1: Automated Verification (Recommended)

Run the automated verification script:

```bash
# Make script executable
chmod +x scripts/test-phase3.sh

# Run automated tests
./scripts/test-phase3.sh
```

Or use the Laravel artisan command:

```bash
# Run Laravel verification
php artisan test:phase3

# For detailed output
php artisan test:phase3 --verbose
```

### Option 2: Manual Testing

Follow the detailed procedures in `VIDEO_SYSTEM_TESTING.md` starting from line 451.

## Test Execution Steps

### Step 1: Pre-Testing Checklist

Verify all prerequisites are met:

```bash
# Check Laravel is accessible
curl http://[RASPBERRY_PI_IP]/

# Check storage directory exists
ls -la storage/app/public/videos/

# Check symlink exists
ls -la public/storage

# Check available storage space (need at least 100MB)
df -h

# Check web server is running
sudo systemctl status nginx
# OR
sudo systemctl status apache2

# Check PHP upload limits (should be >= 512M)
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

**Expected Results:**
- ✅ Laravel application accessible
- ✅ Storage directory exists and is writable
- ✅ Symlink exists and points to correct location
- ✅ At least 100MB free storage space
- ✅ Web server is running
- ✅ PHP upload limits >= 512M

### Step 2: Run Automated Tests

Execute the automated verification:

```bash
# Run bash script
./scripts/test-phase3.sh

# Run Laravel command
php artisan test:phase3 --verbose
```

**Expected Results:**
- ✅ All 6 automated tests pass
- ✅ No errors or warnings

### Step 3: Manual Tests

Perform the 7 critical manual tests from `VIDEO_SYSTEM_TESTING.md`:

#### Test 1: Storage Directory Permissions
```bash
ls -la storage/app/public/videos/
touch storage/app/public/videos/test.txt
rm storage/app/public/videos/test.txt
```

#### Test 2: Symlink Functionality
```bash
ls -la public/storage
# Should show: public/storage -> ../storage/app/public
```

#### Test 3: Video File Upload
1. Login to Parent Dashboard: `http://[RASPBERRY_PI_IP]/videos`
2. Create new video or edit existing
3. Upload test video file (at least 20MB)
4. Verify file appears: `ls -la storage/app/public/videos/`

#### Test 4: Video File Serving
1. Access video directly: `http://[RASPBERRY_PI_IP]/storage/videos/[filename].mp4`
2. Check browser network tab for:
   - HTTP 200 OK
   - MIME type: `video/mp4`
   - Video streams correctly

#### Test 5: Video Playback via Portal
1. Connect device to Raspberry Pi WiFi
2. Access portal: `http://[RASPBERRY_PI_IP]/portal?mac=AA:BB:CC:DD:EE:FF`
3. Select and play video
4. Verify playback is smooth and features work

#### Test 6: Storage Space Management
```bash
# Before upload
df -h
du -sh storage/app/public/videos/

# After upload
df -h
du -sh storage/app/public/videos/

# After deletion
df -h
du -sh storage/app/public/videos/
```

#### Test 7: File Size Limit Enforcement
```bash
# Check PHP config
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Check validation rule exists
grep "max:512000" app/Http/Requests/StoreVideoRequest.php
```

### Step 4: Document Results

Record test results:

- [ ] Test 1: Storage Directory Permissions - PASSED/FAILED
- [ ] Test 2: Symlink Functionality - PASSED/FAILED
- [ ] Test 3: Video File Upload - PASSED/FAILED
- [ ] Test 4: Video File Serving - PASSED/FAILED
- [ ] Test 5: Video Playback via Portal - PASSED/FAILED
- [ ] Test 6: Storage Space Management - PASSED/FAILED
- [ ] Test 7: File Size Limit Enforcement - PASSED/FAILED

Note any failures and apply fixes using the troubleshooting guide.

## Troubleshooting

### Permission Errors

```bash
# Fix storage permissions
chmod -R 775 storage/app/public/videos/
chown -R www-data:www-data storage/app/public/videos/
```

### Symlink Issues

```bash
# Remove old symlink/directory
rm -rf public/storage

# Recreate symlink
php artisan storage:link
```

### PHP Upload Limits Too Small

```bash
# Edit php.ini
sudo nano /etc/php/8.2/fpm/php.ini

# Update these values:
upload_max_filesize = 512M
post_max_size = 512M

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Web Server Not Running

```bash
# Start Nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# OR start Apache
sudo systemctl start apache2
sudo systemctl enable apache2
```

### Storage Full

```bash
# Check disk usage
df -h

# Clean up old videos
php artisan video:cleanup-orphaned --delete

# Delete test videos
php artisan video:cleanup-test
```

## Success Criteria

All tests pass when:
- ✅ All 6 automated verification tests pass
- ✅ All 7 manual tests pass
- ✅ Video uploads work correctly
- ✅ Videos are accessible via URL
- ✅ Video playback works from WiFi-connected device
- ✅ Storage space is properly managed
- ✅ File size limits are enforced

## Completion

Once all tests pass:
1. Document results in test log
2. Mark TODO #9 as complete in project tracking
3. Proceed to next phase (TODO #10: Portal Core)

## References

- **Detailed Test Procedures**: `docs/VIDEO_SYSTEM_TESTING.md`
- **Project Scope**: `docs/scope.md` (line 705, Test Phase 3 section)
- **Troubleshooting**: `docs/VIDEO_SYSTEM_TESTING.md` (lines 706-716)

## Quick Test Summary (5 minutes)

For a quick validation:
1. Upload one test video (at least 20MB)
2. Verify file exists: `ls storage/app/public/videos/`
3. Access portal from WiFi device
4. Play video - verify it loads and plays
5. Complete video - verify word submission works

If all 5 steps work, the system is functional on Raspberry Pi.

