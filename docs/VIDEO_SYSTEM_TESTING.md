
# Video System Testing Guide (Todo #8)

## Raspberry Pi Initial Setup

Before running the critical tests, you need to set up the Laravel application on your Raspberry Pi. This section covers the essential setup steps including cloning the repository from GitHub.

**Important**: If you completed **Test Phase 1 & 2**, you may have already installed some software and completed some setup steps. Each step includes verification commands - check if something is already installed/configured before reinstalling.

### Prerequisites

- ✅ Raspberry Pi 4B with Raspberry Pi OS Lite (64-bit) installed
- ✅ SSH access enabled
- ✅ Internet connection (for cloning repository and installing packages)
- ✅ GitHub repository access (SSH key or HTTPS credentials)

---

### Step 1: Install Required Software

**Note**: If you completed Test Phase 1 & 2, you may have already installed some of these. Check each item and skip if already installed.

**Update package list:**
```bash
sudo apt update
```

**Check and Install Git (if not already installed):**
```bash
# Check if Git is installed
git --version

# If not installed, install it:
sudo apt install -y git
```

**Check and Install PHP and Required Extensions:**
```bash
# Check if PHP is installed
php -v

# If not installed or wrong version, install PHP 8.2:
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip

# Verify PHP extensions are installed:
php -m | grep -E "mysql|xml|mbstring|curl|zip"
```

**Check and Install Composer (if not already installed):**
```bash
# Check if Composer is installed
composer --version

# If not installed, install it:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version  # Verify installation
```

**Check and Install MariaDB (if not already installed):**
```bash
# Check if MariaDB is installed and running
sudo systemctl status mariadb --no-pager

# If not installed, install it:
sudo apt install -y mariadb-server
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Verify it's running:
sudo systemctl status mariadb --no-pager
```

**Check and Install Nginx (if not already installed):**
```bash
# Check if Nginx is installed and running
sudo systemctl status nginx --no-pager

# If not installed, install it:
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Verify it's running:
sudo systemctl status nginx --no-pager
```

---

### Step 2: Set Up SSH Keys for GitHub (Optional but Recommended)

**Why SSH keys?** Allows passwordless access to GitHub repositories.

**Generate SSH Key:**
```bash
ssh-keygen -t ed25519 -C "your-email@example.com"
# Press Enter to accept default location (~/.ssh/id_ed25519)
# Press Enter twice for no passphrase (or set one if you prefer)
```

**Add Key to SSH Agent:**
```bash
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
```

**Copy Public Key:**
```bash
cat ~/.ssh/id_ed25519.pub
# Copy the entire output
```

**Add to GitHub:**
1. Go to GitHub.com → Settings → SSH and GPG keys
2. Click "New SSH key"
3. Paste your public key
4. Save

**Test Connection:**
```bash
ssh -T git@github.com
# Should see: "Hi [username]! You've successfully authenticated..."
```

---

### Step 3: Clone Repository (Skip if Already Done)

**Check if repository is already cloned:**
```bash
# Check if project directory exists
ls -la /var/www/parental_wifi
# Or check your preferred directory location
```

**If repository is NOT cloned yet:**

**Navigate to web root directory:**
```bash
cd /var/www
# Or use your preferred directory (e.g., /home/pi/projects)
```

**Clone the repository:**
```bash
# Using SSH (recommended if SSH keys are set up)
sudo git clone git@github.com:Cristhan11/Parental_Wifi.git parental_wifi

# OR using HTTPS (if SSH keys not set up)
# sudo git clone https://github.com/Cristhan11/Parental_Wifi.git parental_wifi
```

**If repository is already cloned, navigate to it:**
```bash
cd /var/www/parental_wifi
# Or your project directory location
```

**Set proper permissions (run this even if already cloned):**
```bash
sudo chown -R $USER:www-data .
sudo chmod -R 775 storage bootstrap/cache
sudo mkdir -p storage/app/public/videos
sudo chmod -R 775 storage/app/public/videos
```

---

### Step 4: Install Dependencies

**Check if dependencies are already installed:**
```bash
# Check if vendor directory exists (Composer dependencies)
ls -la vendor/

# Check if node_modules exists (Node.js dependencies)
ls -la node_modules/
```

**Install PHP dependencies (if vendor/ doesn't exist):**
```bash
composer install --no-dev --optimize-autoloader
```

**Check and Install Node.js and npm (if needed for frontend assets):**
```bash
# Check if Node.js is installed
node --version
npm --version

# If not installed, install Node.js:
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install frontend dependencies (if node_modules/ doesn't exist)
npm install

# Build frontend assets
npm run build
```

---

### Step 5: Configure Environment

**Check if `.env` file already exists:**
```bash
ls -la .env
```

**If `.env` doesn't exist, create it:**
```bash
cp .env.example .env
```

**Check if application key is set:**
```bash
# Check if APP_KEY is set in .env
grep APP_KEY .env
# If empty or not set, generate it:
php artisan key:generate
```

**If `.env` already exists, verify settings:**
```bash
nano .env
```

**Required settings (verify these are correct):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://[YOUR_RASPBERRY_PI_IP]

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=parental_wifi
DB_USERNAME=parental_wifi_user
DB_PASSWORD=your_secure_password
```

**Replace `[YOUR_RASPBERRY_PI_IP]` with your Raspberry Pi's IP address** (find it with `hostname -I`)

---

### Step 6: Set Up Database

**Check if database already exists:**
```bash
# Test database connection
php artisan migrate:status
# If this works, database is already set up
```

**If database is NOT set up yet:**

**Create database and user:**
```bash
sudo mysql -u root
```

**In MariaDB prompt:**
```sql
CREATE USER IF NOT EXISTS 'parental_wifi_user'@'localhost' IDENTIFIED BY 'your_secure_password';
CREATE DATABASE IF NOT EXISTS parental_wifi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON parental_wifi.* TO 'parental_wifi_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Run migrations (if not already run):**
```bash
php artisan migrate
```

**Check migration status:**
```bash
php artisan migrate:status
# Should show all migrations as "Ran"
```

**Seed initial data (optional, for testing):**
```bash
php artisan db:seed --class=VideoTestDataSeeder
```

---

### Step 7: Create Storage Symlink

**Check if symlink already exists:**
```bash
ls -la public/storage
# If it shows a symlink, it's already created
```

**If symlink doesn't exist, create it:**
```bash
php artisan storage:link
```

**Verify symlink:**
```bash
ls -la public/storage
# Should show: public/storage -> ../storage/app/public
```

---

### Step 8: Configure Nginx

**Check if Nginx configuration already exists:**
```bash
ls -la /etc/nginx/sites-available/parental_wifi
ls -la /etc/nginx/sites-enabled/parental_wifi
```

**If configuration doesn't exist, create it:**

**Create Nginx configuration file:**
```bash
sudo nano /etc/nginx/sites-available/parental_wifi
```

**Add configuration (replace `[YOUR_RASPBERRY_PI_IP]` with your Pi's IP):**
```nginx
server {
    listen 80;
    server_name [YOUR_RASPBERRY_PI_IP];
    root /var/www/parental_wifi/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Enable site:**
```bash
sudo ln -s /etc/nginx/sites-available/parental_wifi /etc/nginx/sites-enabled/
sudo nginx -t  # Test configuration
sudo systemctl reload nginx
```

---

### Step 9: Set File Permissions

**Set proper ownership and permissions:**
```bash
cd /var/www/parental_wifi
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chmod -R 775 storage/app/public/videos
```

---

### Step 10: Verify Setup

**Check Laravel is accessible:**
```bash
# From another device on the network
curl http://[YOUR_RASPBERRY_PI_IP]/
# Should return HTML (not error)
```

**Check storage directory:**
```bash
ls -la storage/app/public/videos/
# Directory should exist and be writable
```

**Check symlink:**
```bash
ls -la public/storage
# Should show symlink to storage/app/public
```

**Get your Raspberry Pi IP address:**
```bash
hostname -I
# Use this IP in URLs and .env file
```

---

### Quick Setup Checklist

- [ ] Git installed
- [ ] PHP 8.2+ and extensions installed
- [ ] Composer installed
- [ ] MariaDB installed and database created
- [ ] Nginx installed and configured
- [ ] Repository cloned from GitHub
- [ ] Dependencies installed (`composer install`)
- [ ] `.env` file configured with correct IP and database credentials
- [ ] Database migrations run
- [ ] Storage symlink created
- [ ] File permissions set correctly
- [ ] Nginx configuration tested and reloaded
- [ ] Application accessible via browser

---

### Troubleshooting Setup Issues

**Issue: Permission denied when cloning**
- Solution: Use `sudo` or ensure you have write permissions to the directory

**Issue: Composer not found**
- Solution: Check PATH or use full path: `/usr/local/bin/composer`

**Issue: Database connection failed**
- Solution: Verify database credentials in `.env`, check MariaDB is running: `sudo systemctl status mariadb`

**Issue: 502 Bad Gateway**
- Solution: Check PHP-FPM is running: `sudo systemctl status php8.2-fpm`, check Nginx error logs: `sudo tail -f /var/log/nginx/error.log`

**Issue: Storage symlink not working**
- Solution: Remove old symlink: `rm public/storage`, recreate: `php artisan storage:link`

**Issue: Cannot access application from network**
- Solution: Check firewall: `sudo ufw status`, allow HTTP: `sudo ufw allow 80/tcp`

---

## Raspberry Pi Critical Tests (Test Phase 3)

This section focuses on **Raspberry Pi-specific tests** for the Video System. These tests verify critical file system and storage functionality that must work correctly on Raspberry Pi OS Lite.

**Note**: Basic functionality (video upload, playback, word validation, etc.) has already been tested on Windows/XAMPP. This section only includes tests that are specific to Raspberry Pi deployment, such as:
- File permissions (Linux vs Windows)
- Symlink functionality (Linux symlinks)
- Web server file serving (Nginx/Apache)
- Network access via Raspberry Pi WiFi
- Storage constraints (limited Raspberry Pi storage)

---

### Pre-Testing Checklist

Before starting, ensure:
- [ ] Laravel application is running on Raspberry Pi
- [ ] Storage directory exists: `storage/app/public/videos/`
- [ ] Symlink exists: `php artisan storage:link` (run if needed)
- [ ] Available storage space: `df -h` (should have at least 100MB free)
- [ ] Web server (Nginx/Apache) is running
- [ ] PHP upload limits: `php -i | grep upload_max_filesize` (should be >= 512M)

---

### ✅ Test 1: Storage Directory Permissions

**Why Critical**: Raspberry Pi uses Linux file permissions. Web server must be able to write video files.

**Steps**:
1. Check directory exists and permissions:
   ```bash
   ls -la storage/app/public/videos/
   ```
2. Test write access:
   ```bash
   touch storage/app/public/videos/test.txt
   rm storage/app/public/videos/test.txt
   ```

**Expected Result**:
- ✅ Directory exists
- ✅ Web server user (www-data) can write files
- ✅ Files can be created and deleted

**If Failed**:
```bash
# Fix permissions
chmod -R 775 storage/app/public/videos/
chown -R www-data:www-data storage/app/public/videos/
```

---

### ✅ Test 2: Symlink Functionality

**Why Critical**: Laravel uses symlinks to serve files from `storage/app/public/` via `public/storage/`. Linux symlinks behave differently than Windows.

**Steps**:
1. Check symlink exists:
   ```bash
   ls -la public/storage
   # Should show: public/storage -> ../storage/app/public
   ```
2. Verify symlink points to correct location
3. Test file access via URL: `http://[RASPBERRY_PI_IP]/storage/videos/[filename].mp4`

**Expected Result**:
- ✅ Symlink exists and points to `../storage/app/public`
- ✅ Files accessible via `/storage/` URL
- ✅ No 404 errors when accessing files

**If Failed**:
```bash
# Recreate symlink
php artisan storage:link
```

---

### ✅ Test 3: Video File Upload (Raspberry Pi)

**Why Critical**: Tests realistic file handling on Raspberry Pi with limited resources. 20MB+ files test upload performance and storage handling.

**Steps**:
1. Login to Parent Dashboard: `http://[RASPBERRY_PI_IP]/videos`
2. Create new video or edit existing video
3. Upload test video file (at least 20MB)
4. Verify file appears in storage:
   ```bash
   ls -la storage/app/public/videos/
   ```
5. Check file permissions:
   ```bash
   ls -la storage/app/public/videos/[filename].mp4
   # Should be readable by web server
   ```
6. Test direct file access: `http://[RASPBERRY_PI_IP]/storage/videos/[filename].mp4`

**Expected Result**:
- ✅ Upload succeeds without errors
- ✅ File appears in `storage/app/public/videos/`
- ✅ File permissions allow web server to read
- ✅ File accessible via direct URL

**If Failed**:
- Check PHP upload limits: `php -i | grep upload_max_filesize`
- Check directory permissions (see Test 1)
- Check available storage: `df -h`

---

### ✅ Test 4: Video File Serving via Web Server

**Why Critical**: Videos must be served by Nginx/Apache (not Laravel serve). Tests web server configuration and MIME types.

**Steps**:
1. Upload a test video (20MB+) via Parent Dashboard
2. Access video file directly via web server:
   ```
   http://[RASPBERRY_PI_IP]/storage/videos/[filename].mp4
   ```
3. Check browser network tab for:
   - HTTP status (should be 200 OK)
   - MIME type (should be `video/mp4` or similar)
   - Response headers

**Expected Result**:
- ✅ Video loads and plays in browser
- ✅ No 403 Forbidden errors
- ✅ Correct MIME type served
- ✅ Video streams correctly (not downloading entire file)

**If Failed**:
- Check web server configuration (Nginx/Apache)
- Verify symlink exists (see Test 2)
- Check file permissions (see Test 1)
- Verify MIME type configuration in web server

---

### ✅ Test 5: Video Playback via Portal (Network Access)

**Why Critical**: Children access portal via Raspberry Pi WiFi network, not localhost. Tests network access and performance.

**Steps**:
1. Connect a test device (phone/tablet) to Raspberry Pi WiFi network
2. Access portal landing page:
   ```
   http://[RASPBERRY_PI_IP]/portal?mac=AA:BB:CC:DD:EE:FF
   ```
3. Select a video from the list
4. Play video and observe:
   - Video loads within 5-10 seconds (reasonable for 20MB+ file)
   - Playback is smooth (no significant stuttering)
   - Words appear at correct timestamps
   - Video controls work (play, pause, volume)

**Expected Result**:
- ✅ Portal accessible from WiFi-connected device
- ✅ Video loads within reasonable time
- ✅ Playback is smooth
- ✅ All features work (words, form, submission)

**If Failed**:
- Check Raspberry Pi IP address
- Verify device is on same network
- Check firewall settings
- Check CPU/memory usage: `htop` (may need to reduce video quality)

---

### ✅ Test 6: Storage Space Management

**Why Critical**: Raspberry Pi has limited storage. Must verify space is tracked correctly and freed when videos are deleted.

**Steps**:
1. Check available storage before upload:
   ```bash
   df -h  # Check total available space
   du -sh storage/app/public/videos/  # Check video directory size
   ```
2. Upload 20MB+ video via Parent Dashboard
3. Check storage after upload:
   ```bash
   df -h
   du -sh storage/app/public/videos/
   # Should show increased usage
   ```
4. Delete video via Parent Dashboard
5. Check storage after deletion:
   ```bash
   df -h
   du -sh storage/app/public/videos/
   # Should show decreased usage (space freed)
   ```

**Expected Result**:
- ✅ Storage space decreases after upload
- ✅ Storage space increases after deletion
- ✅ Space is properly tracked and freed

**If Failed**:
- Check file deletion in controller (should use `Storage::disk('public')->delete()`)
- Verify file actually deleted: `ls storage/app/public/videos/`
- Check for orphaned files: `php artisan video:cleanup-orphaned`

---

### ✅ Test 7: File Size Limit Enforcement

**Why Critical**: 512MB limit prevents storage from filling up. Must verify limit is enforced and PHP is configured correctly.

**Steps**:
1. Check PHP upload configuration:
   ```bash
   php -i | grep upload_max_filesize  # Should show >= 512M
   php -i | grep post_max_size  # Should show >= 512M
   ```
2. Verify validation rule exists in `StoreVideoRequest`:
   - Check `'max:512000'` rule (512MB in KB)
3. (Optional) Attempt to upload file > 512MB if available
   - Should be rejected with validation error
   - Error message: "Video file size cannot exceed 512MB"

**Expected Result**:
- ✅ PHP configuration allows 512MB uploads
- ✅ Validation rule exists and enforces limit
- ✅ Error message displayed if file too large

**Note**: Don't need to actually upload 512MB file - just verify limit is checked and PHP config is correct. Uploading 512MB files would consume significant storage on Raspberry Pi.

**If Failed**:
- Update `php.ini`:
  ```
  upload_max_filesize = 512M
  post_max_size = 512M
  ```
- Restart PHP-FPM: `sudo systemctl restart php8.2-fpm` (or your PHP version)

---

### Quick Test Summary

**Minimum Viable Test (5 minutes)**:
1. Upload one test video (at least 20MB)
2. Verify file exists and is accessible
3. Access portal from WiFi device
4. Play video - verify it loads and plays
5. Complete video - verify word submission works

**If all 5 steps work, the system is functional on Raspberry Pi.**

---

### Troubleshooting Quick Reference

| Issue | Solution |
|-------|----------|
| Permission errors | `chmod -R 775 storage/app/public/videos/`<br>`chown -R www-data:www-data storage/app/public/videos/` |
| Symlink missing | `php artisan storage:link` |
| 403 Forbidden | Check file permissions and web server config |
| Slow playback | Check CPU/memory: `htop`<br>Consider reducing video quality |
| Storage full | Delete old videos<br>Check disk usage: `df -h`<br>Run cleanup: `php artisan video:cleanup-orphaned --delete` |
| Upload fails | Check PHP limits: `php -i \| grep upload_max_filesize`<br>Update `php.ini` if needed |
| Video doesn't load | Check symlink: `ls -la public/storage`<br>Check file exists: `ls storage/app/public/videos/`<br>Check web server is running |

---

### What's Excluded (Already Tested on Windows/XAMPP)

The following functionality has already been verified on Windows/XAMPP and does not need Raspberry Pi testing:
- ✅ Basic video upload functionality
- ✅ Video playback in browser (localhost)
- ✅ Database operations
- ✅ Word validation logic
- ✅ UI/UX elements
- ✅ Form submissions
- ✅ Basic CRUD operations
- ✅ Duration detection
- ✅ Word display timing
- ✅ Time granting logic

