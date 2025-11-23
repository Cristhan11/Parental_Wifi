# Raspberry Pi Testing Guide

## Introduction

This document provides comprehensive testing procedures for the Child-Centric WiFi Captive Portal system on Raspberry Pi 4B with Raspberry Pi OS Lite (64-bit). Testing is conducted at specific milestones during development to catch compatibility issues early and ensure the system works correctly on the target hardware.

## Testing Strategy

Testing is divided into 6 phases, each triggered by the completion of specific development todos:

1. **Test Phase 1 & 2**: Basic Laravel Setup & Database (after Todo #3 - Authentication)
2. **Test Phase 3**: File System & Storage (after Todo #7 - Video System)
3. **Test Phase 4**: Shell Script Execution (after Todo #9 - Shell Scripts)
4. **Test Phase 5**: Background Jobs (after Todo #12 - Background Jobs)
5. **Test Phase 6**: Full Integration (after Todo #8 - Portal Core)

## Prerequisites for Raspberry Pi Testing

Before starting any testing phase, ensure the following is set up on your Raspberry Pi 4B:

### Hardware Requirements
- Raspberry Pi 4B (4GB RAM minimum, 8GB recommended)
- MicroSD card (32GB minimum, Class 10 or better)
- Power supply (5V 3A USB-C)
- Ethernet cable (for initial setup)
- WiFi adapter (if not using built-in WiFi)

### Software Requirements
- Raspberry Pi OS Lite (64-bit) installed and updated
- SSH access enabled
- Root or sudo access

### Development Environment Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd
sudo apt install -y mariadb-server mariadb-client
sudo apt install -y nginx
sudo apt install -y git composer

# Install Node.js and npm (for asset compilation)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Project Setup
```bash
# Clone or transfer project to Raspberry Pi
cd /var/www
sudo git clone <your-repo-url> parental_wifi
cd parental_wifi

# Set permissions
sudo chown -R www-data:www-data /var/www/parental_wifi
sudo chmod -R 755 /var/www/parental_wifi

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

---

## Test Phase 1 & 2: Basic Laravel Setup & Database

**Trigger**: After Todo #3 (Authentication) is complete

### Test Phase 1: Basic Laravel Setup Checklist

#### 1.1 Environment Configuration
- [ ] `.env` file exists and is properly configured
- [ ] `APP_KEY` is generated (`php artisan key:generate`)
- [ ] `APP_ENV` is set to `production` or `local` as appropriate
- [ ] `APP_DEBUG` is set to `false` for production
- [ ] `APP_URL` is set correctly (e.g., `http://raspberry-pi-ip` or domain)

**Test Procedure**:
```bash
cd /var/www/parental_wifi
php artisan key:generate
php artisan config:cache
php artisan route:cache
```

**Expected Result**: No errors, configuration cached successfully.

#### 1.2 PHP Version and Extensions
- [ ] PHP version is 8.2 or higher
- [ ] Required PHP extensions are installed:
  - [ ] php8.2-fpm
  - [ ] php8.2-mysql
  - [ ] php8.2-mbstring
  - [ ] php8.2-xml
  - [ ] php8.2-curl
  - [ ] php8.2-zip
  - [ ] php8.2-gd

**Test Procedure**:
```bash
php -v  # Should show PHP 8.2.x or higher
php -m  # List all installed modules
```

**Expected Result**: PHP 8.2+ installed, all required extensions listed.

#### 1.3 Web Server Configuration
- [ ] Nginx or Apache is installed and running
- [ ] PHP-FPM is running
- [ ] Web server configuration points to Laravel public directory
- [ ] Web server can access Laravel files

**Test Procedure**:
```bash
# Check Nginx status
sudo systemctl status nginx

# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Test Nginx configuration
sudo nginx -t
```

**Expected Result**: Both services running, configuration test passes.

#### 1.4 Basic Routing
- [ ] Homepage route is accessible
- [ ] Login page route is accessible
- [ ] Routes respond correctly (not 404 or 500 errors)

**Test Procedure**:
```bash
# Test from command line
curl http://localhost/
curl http://localhost/login

# Or access from browser
# http://raspberry-pi-ip/
# http://raspberry-pi-ip/login
```

**Expected Result**: Pages load without errors, login page displays correctly.

#### 1.5 Blade Template Rendering
- [ ] Blade templates compile without errors
- [ ] Views render correctly
- [ ] No template syntax errors

**Test Procedure**:
```bash
php artisan view:clear
php artisan view:cache
```

**Expected Result**: Views cached successfully, no errors.

### Test Phase 2: Database and Models Checklist

#### 2.1 MariaDB Connection
- [ ] MariaDB is installed and running
- [ ] Database connection works from Laravel
- [ ] `.env` database credentials are correct

**Test Procedure**:
```bash
# Check MariaDB status
sudo systemctl status mariadb

# Test connection from Laravel
php artisan tinker
>>> DB::connection()->getPdo();
```

**Expected Result**: MariaDB running, connection successful, PDO object returned.

#### 2.2 Database Creation
- [ ] Database exists (create if needed)
- [ ] Database user has proper permissions

**Test Procedure**:
```bash
# Create database (if needed)
sudo mysql -u root -p
CREATE DATABASE parental_wifi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'parental_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON parental_wifi.* TO 'parental_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Expected Result**: Database created, user has permissions.

#### 2.3 Migrations
- [ ] All migrations run successfully
- [ ] No migration errors
- [ ] All tables are created

**Test Procedure**:
```bash
php artisan migrate
php artisan migrate:status
```

**Expected Result**: All migrations completed, status shows all migrations ran.

#### 2.4 Model Operations
- [ ] Models can create records
- [ ] Models can read records
- [ ] Models can update records
- [ ] Models can delete records
- [ ] Relationships work correctly

**Test Procedure**:
```bash
php artisan tinker

# Test User model
>>> $user = App\Models\User::create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password'), 'role' => 'parent']);
>>> $user->id;  // Should return user ID
>>> $user->isParent();  // Should return true

# Test Device model
>>> $device = $user->devices()->create(['name' => 'Test Device', 'mac_address' => 'AA:BB:CC:DD:EE:FF', 'status' => 'active', 'remaining_time_minutes' => 30]);
>>> $device->user->name;  // Should return 'Test User'
>>> $device->hasRemainingTime();  // Should return true

# Test relationships
>>> $user->devices()->count();  // Should return 1
>>> $device->user->email;  // Should return 'test@example.com'
```

**Expected Result**: All CRUD operations work, relationships function correctly.

#### 2.5 Database Seeders
- [ ] DictionaryWordSeeder runs successfully
- [ ] Seeded data is in database

**Test Procedure**:
```bash
php artisan db:seed --class=DictionaryWordSeeder
php artisan tinker
>>> App\Models\DictionaryWord::count();  // Should return number of seeded words
```

**Expected Result**: Seeder runs without errors, words are in database.

#### 2.6 Query Performance
- [ ] Simple queries complete quickly (< 1 second)
- [ ] No slow query warnings

**Test Procedure**:
```bash
php artisan tinker
>>> $start = microtime(true);
>>> App\Models\Device::with('user')->get();
>>> $end = microtime(true);
>>> echo ($end - $start) . " seconds";
```

**Expected Result**: Queries complete in < 1 second.

### Troubleshooting Test Phase 1 & 2

**Issue**: PHP version too old
- **Solution**: Install PHP 8.2: `sudo apt install php8.2`

**Issue**: Missing PHP extensions
- **Solution**: Install missing extensions: `sudo apt install php8.2-<extension-name>`

**Issue**: Database connection failed
- **Solution**: Check `.env` credentials, verify MariaDB is running, check user permissions

**Issue**: Permission denied errors
- **Solution**: Fix ownership: `sudo chown -R www-data:www-data /var/www/parental_wifi`

**Issue**: Routes return 404
- **Solution**: Check web server configuration, verify document root points to `public/` directory

---

## Test Phase 3: File System and Storage

**Trigger**: After Todo #7 (Video System) is complete

### Checklist

#### 3.1 Storage Directory Setup
- [ ] `storage/app/videos/` directory exists
- [ ] Directory has correct permissions (755 or 775)
- [ ] Web server user (www-data) can write to directory

**Test Procedure**:
```bash
# Create directory if needed
mkdir -p storage/app/videos
chmod 775 storage/app/videos
chown www-data:www-data storage/app/videos

# Test write permission
sudo -u www-data touch storage/app/videos/test.txt
sudo -u www-data rm storage/app/videos/test.txt
```

**Expected Result**: Directory exists, permissions correct, write test succeeds.

#### 3.2 Symlink Creation
- [ ] `public/storage` symlink exists
- [ ] Symlink points to `storage/app/public`
- [ ] Symlink is accessible via web

**Test Procedure**:
```bash
php artisan storage:link
ls -la public/storage  # Should show symlink
```

**Expected Result**: Symlink created, accessible.

#### 3.3 Video File Upload
- [ ] Video files can be uploaded via form
- [ ] Files are saved to `storage/app/videos/`
- [ ] File names are handled correctly
- [ ] File size validation works

**Test Procedure**:
```bash
# Test via tinker or create a test upload
php artisan tinker
>>> $file = Illuminate\Http\UploadedFile::fake()->create('test_video.mp4', 1024);
>>> $path = $file->store('videos');
>>> Storage::exists($path);  // Should return true
```

**Expected Result**: Files upload successfully, stored in correct location.

#### 3.4 Video File Reading
- [ ] Video files can be read from storage
- [ ] File paths are correct
- [ ] Files are accessible

**Test Procedure**:
```bash
php artisan tinker
>>> Storage::exists('videos/test_video.mp4');  // Should return true
>>> Storage::size('videos/test_video.mp4');  // Should return file size
```

**Expected Result**: Files can be read, paths are correct.

#### 3.5 Video Streaming
- [ ] Videos can be streamed via HTTP
- [ ] Video URLs are accessible
- [ ] Browser can play videos

**Test Procedure**:
```bash
# Create a test video record
php artisan tinker
>>> $video = App\Models\Video::create(['user_id' => 1, 'title' => 'Test Video', 'video_path' => 'videos/test_video.mp4', 'duration_seconds' => 60, 'time_reward_minutes' => 15]);
>>> $video->getVideoUrl();  // Should return accessible URL

# Test in browser
# Access: http://raspberry-pi-ip/storage/videos/test_video.mp4
```

**Expected Result**: Video URLs accessible, videos play in browser.

#### 3.6 File Size Limits
- [ ] Large files (> 100MB) are handled appropriately
- [ ] Storage space is sufficient
- [ ] File size limits are enforced

**Test Procedure**:
```bash
# Check available storage
df -h

# Test file size limit in PHP
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

**Expected Result**: Sufficient storage available, limits are appropriate.

### Troubleshooting Test Phase 3

**Issue**: Permission denied when uploading
- **Solution**: Fix directory permissions: `sudo chown -R www-data:www-data storage/`

**Issue**: Symlink not working
- **Solution**: Remove and recreate: `rm public/storage && php artisan storage:link`

**Issue**: Videos don't play in browser
- **Solution**: Check MIME types in web server config, verify file paths

**Issue**: Storage full
- **Solution**: Clean up old files, expand storage, or implement file cleanup

---

## Test Phase 4: Shell Script Execution

**Trigger**: After Todo #9 (Shell Scripts) is complete

### Checklist

#### 4.1 PHP Execution Functions
- [ ] `exec()` function is enabled
- [ ] `shell_exec()` function is enabled
- [ ] `system()` function is enabled (if used)
- [ ] Functions are not disabled in `php.ini`

**Test Procedure**:
```bash
php -i | grep -E "disable_functions|exec|shell_exec"
```

**Expected Result**: Functions are not in `disable_functions` list.

#### 4.2 Script File Permissions
- [ ] Script files in `scripts/` directory are executable
- [ ] Scripts have correct permissions (755)
- [ ] Scripts can be executed by PHP/web server user

**Test Procedure**:
```bash
chmod +x scripts/*.sh
ls -la scripts/
```

**Expected Result**: All scripts are executable.

#### 4.3 Basic Script Execution
- [ ] Scripts can be executed from PHP
- [ ] Command output is captured correctly
- [ ] Exit codes are handled

**Test Procedure**:
```bash
php artisan tinker
>>> exec('ls -la scripts/', $output, $return);
>>> print_r($output);
>>> echo $return;  // Should be 0
```

**Expected Result**: Scripts execute, output captured, exit code 0.

#### 4.4 Network Commands (if applicable)
- [ ] iptables commands work (may require sudo)
- [ ] Network interface commands work
- [ ] MAC address detection works

**Test Procedure**:
```bash
# Test iptables (may need sudo)
sudo iptables -L

# Test network commands
ip addr show
```

**Expected Result**: Network commands execute (may require proper permissions).

#### 4.5 Error Handling
- [ ] Failed commands are handled gracefully
- [ ] Error messages are logged
- [ ] System doesn't crash on command failure

**Test Procedure**:
```bash
php artisan tinker
>>> exec('invalid_command_xyz', $output, $return);
>>> echo $return;  // Should be non-zero
```

**Expected Result**: Errors handled, return code indicates failure.

#### 4.6 Security
- [ ] Command injection is prevented
- [ ] User input is sanitized
- [ ] Only allowed commands are executed

**Test Procedure**:
```bash
# Test with malicious input (should be sanitized)
php artisan tinker
>>> $input = "test; rm -rf /";  // Should be sanitized
>>> // Test that sanitization works
```

**Expected Result**: Malicious input is sanitized, dangerous commands are prevented.

### Troubleshooting Test Phase 4

**Issue**: `exec()` function disabled
- **Solution**: Edit `php.ini`, remove `exec` from `disable_functions`, restart PHP-FPM

**Issue**: Permission denied executing scripts
- **Solution**: Fix script permissions: `chmod +x scripts/*.sh`

**Issue**: iptables requires sudo
- **Solution**: Configure sudoers to allow specific commands without password, or run web server with appropriate permissions

**Issue**: Scripts not found
- **Solution**: Use absolute paths in PHP, or ensure scripts are in PATH

---

## Test Phase 5: Background Jobs and Queues

**Trigger**: After Todo #12 (Background Jobs) is complete

### Checklist

#### 5.1 Queue Configuration
- [ ] Queue driver is set (database recommended for Pi)
- [ ] Queue tables exist (`jobs`, `failed_jobs`)
- [ ] Queue configuration is correct

**Test Procedure**:
```bash
# Check .env
grep QUEUE_CONNECTION .env  # Should be 'database'

# Check queue tables
php artisan queue:table
php artisan migrate
```

**Expected Result**: Queue configured, tables exist.

#### 5.2 Queue Worker
- [ ] Queue worker can be started
- [ ] Worker processes jobs
- [ ] Worker runs stably

**Test Procedure**:
```bash
# Start queue worker
php artisan queue:work --tries=3

# In another terminal, dispatch a test job
php artisan tinker
>>> dispatch(new App\Jobs\CheckTimeExpiration());
```

**Expected Result**: Worker starts, processes jobs, runs without crashes.

#### 5.3 Job Execution
- [ ] Jobs are dispatched successfully
- [ ] Jobs execute correctly
- [ ] Job results are as expected

**Test Procedure**:
```bash
php artisan tinker
>>> App\Jobs\CheckTimeExpiration::dispatch();
>>> // Check jobs table
>>> DB::table('jobs')->count();  // Should show job
```

**Expected Result**: Jobs dispatched, appear in queue, execute successfully.

#### 5.4 Cron Scheduling
- [ ] Cron jobs are configured
- [ ] Scheduled tasks run on time
- [ ] Cron logs show execution

**Test Procedure**:
```bash
# Add to crontab
crontab -e
# Add: * * * * * cd /var/www/parental_wifi && php artisan schedule:run >> /dev/null 2>&1

# Check cron logs
grep CRON /var/log/syslog
```

**Expected Result**: Cron configured, tasks run, logs show execution.

#### 5.5 Job Failure Handling
- [ ] Failed jobs are logged
- [ ] Failed jobs table is updated
- [ ] Retry logic works

**Test Procedure**:
```bash
# Check failed_jobs table
php artisan tinker
>>> DB::table('failed_jobs')->count();
```

**Expected Result**: Failed jobs are logged, retries work.

#### 5.6 Worker Stability
- [ ] Worker runs for extended periods without crashing
- [ ] Memory usage is reasonable
- [ ] No memory leaks

**Test Procedure**:
```bash
# Monitor worker
php artisan queue:work --verbose &
# Let it run for 30+ minutes, check memory usage
ps aux | grep "queue:work"
```

**Expected Result**: Worker stable, memory usage reasonable.

### Troubleshooting Test Phase 5

**Issue**: Queue worker not processing jobs
- **Solution**: Check queue connection, verify worker is running, check job table

**Issue**: Jobs failing immediately
- **Solution**: Check job code for errors, verify dependencies, check logs

**Issue**: Cron not running
- **Solution**: Verify crontab entry, check cron service status, check permissions

**Issue**: Worker crashes
- **Solution**: Check memory limits, review job code for errors, check PHP error logs

---

## Test Phase 6: Full Integration Test

**Trigger**: After Todo #8 (Portal Core) is complete

### Checklist

#### 6.1 Time Expiration Detection
- [ ] System detects when device time expires
- [ ] Expired devices are identified correctly
- [ ] Expiration triggers portal redirect

**Test Procedure**:
```bash
php artisan tinker
>>> $device = App\Models\Device::find(1);
>>> $device->remaining_time_minutes = 0;
>>> $device->save();
>>> $device->hasTimeExpired();  // Should return true
```

**Expected Result**: Expiration detected, redirect triggered.

#### 6.2 Portal Redirect
- [ ] Expired devices are redirected to portal
- [ ] Portal landing page loads
- [ ] Quiz/video options are displayed

**Test Procedure**:
```bash
# Access portal from expired device
# http://raspberry-pi-ip/portal/landing?mac=AA:BB:CC:DD:EE:FF
```

**Expected Result**: Portal loads, options displayed.

#### 6.3 Quiz Flow
- [ ] Quiz can be selected
- [ ] Questions are displayed
- [ ] Answers can be submitted
- [ ] Scoring works correctly
- [ ] Passing grants time

**Test Procedure**:
```bash
# Complete quiz flow via browser
# 1. Select quiz
# 2. Answer questions
# 3. Submit
# 4. Verify time granted
php artisan tinker
>>> $device = App\Models\Device::find(1);
>>> $device->remaining_time_minutes;  // Should be > 0 after passing
```

**Expected Result**: Quiz flow completes, time granted on pass.

#### 6.4 Video Flow
- [ ] Video can be selected
- [ ] Video plays correctly
- [ ] Dictionary words appear at intervals
- [ ] Words can be entered at end
- [ ] Validation works
- [ ] Passing grants time

**Test Procedure**:
```bash
# Complete video flow via browser
# 1. Select video
# 2. Watch video (words appear)
# 3. Enter words at end
# 4. Verify time granted
php artisan tinker
>>> $completion = App\Models\VideoCompletion::latest()->first();
>>> $completion->passed_validation;  // Should be true if words correct
>>> $completion->device->remaining_time_minutes;  // Should be > 0
```

**Expected Result**: Video flow completes, time granted on validation pass.

#### 6.5 Time Granting
- [ ] Time is added to device correctly
- [ ] Time grant is logged
- [ ] Device is unblocked

**Test Procedure**:
```bash
php artisan tinker
>>> $device = App\Models\Device::find(1);
>>> $before = $device->remaining_time_minutes;
>>> $device->grantTime(15, 'quiz', 1);
>>> $after = $device->remaining_time_minutes;
>>> echo $after - $before;  // Should be 15
>>> $device->timeGrants()->count();  // Should be > 0
```

**Expected Result**: Time granted, logged, device unblocked.

#### 6.6 End-to-End Workflow
- [ ] Complete flow works: expiration → portal → quiz/video → grant
- [ ] No errors in workflow
- [ ] All components work together

**Test Procedure**:
```bash
# Manual end-to-end test:
# 1. Set device time to 0
# 2. Try to access internet (should redirect to portal)
# 3. Complete quiz or video
# 4. Verify time granted
# 5. Verify device can access internet again
```

**Expected Result**: Complete workflow functions correctly.

### Troubleshooting Test Phase 6

**Issue**: Portal not redirecting
- **Solution**: Check NoDogSplash configuration, verify redirect logic, check routes

**Issue**: Quiz/video not granting time
- **Solution**: Check time granting service, verify completion validation, check logs

**Issue**: Device not unblocked after time grant
- **Solution**: Check network service, verify iptables rules, check device status

---

## General Troubleshooting

### Common Issues

#### Performance Issues
- **Symptom**: Slow page loads, timeouts
- **Solutions**:
  - Enable OPcache: `sudo apt install php8.2-opcache`
  - Use database queue instead of Redis
  - Optimize database queries
  - Enable Laravel caching: `php artisan config:cache`

#### Permission Issues
- **Symptom**: Permission denied errors
- **Solutions**:
  - Fix ownership: `sudo chown -R www-data:www-data /var/www/parental_wifi`
  - Fix permissions: `sudo chmod -R 755 /var/www/parental_wifi`
  - Check storage permissions: `sudo chmod -R 775 storage/`

#### Database Connection Issues
- **Symptom**: Database connection failed
- **Solutions**:
  - Verify MariaDB is running: `sudo systemctl status mariadb`
  - Check `.env` credentials
  - Verify database exists and user has permissions
  - Check firewall rules

#### Memory Issues
- **Symptom**: Out of memory errors
- **Solutions**:
  - Increase PHP memory limit: Edit `php.ini`, set `memory_limit = 256M`
  - Use swap file if needed
  - Optimize code to reduce memory usage

### Performance Benchmarks

Expected performance on Raspberry Pi 4B (4GB RAM):

- **Page Load Time**: < 2 seconds for simple pages
- **Database Queries**: < 1 second for simple queries
- **Video Streaming**: Smooth playback for videos < 100MB
- **Queue Processing**: Jobs process within 5 seconds
- **Background Jobs**: Run every minute without issues

### Success Criteria Summary

A test phase is considered successful when:

1. All checklist items are completed
2. No critical errors occur
3. Performance meets benchmarks
4. All functionality works as expected
5. System is stable and ready for next phase

### Next Steps After Testing

After completing each test phase:

1. Document any issues found
2. Fix critical issues before proceeding
3. Note any performance concerns
4. Update documentation if needed
5. Proceed to next development phase

---

## Additional Resources

- Laravel Documentation: https://laravel.com/docs
- Raspberry Pi Documentation: https://www.raspberrypi.com/documentation/
- MariaDB Documentation: https://mariadb.com/kb/en/documentation/
- Nginx Documentation: https://nginx.org/en/docs/

---

**Last Updated**: 2024-01-15
**Version**: 1.0

