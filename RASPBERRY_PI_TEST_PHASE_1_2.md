# Raspberry Pi Test Phase 1 & 2 - Critical Tests Only

## Overview

This document focuses on **critical Raspberry Pi-specific tests** for Phase 1 & 2. Tests already verified on Windows (models, seeders, basic functionality) are skipped. We focus on **production environment setup** and **Raspberry Pi-specific configurations**.

---

## Prerequisites

Before starting, ensure:
- ✅ Raspberry Pi 4B with Raspberry Pi OS Lite (64-bit) installed
- ✅ SSH access enabled
- ✅ Internet connection (for initial setup)
- ✅ Project files transferred to Raspberry Pi

---

## Linux Command Basics (For Beginners)

**Understanding Command Syntax:**

1. **`sudo`** = "Super User Do"
   - Runs command with administrator privileges
   - Required for system-level operations (installing packages, changing permissions)
   - Example: `sudo apt install nginx` (install as admin)

2. **Command Structure:**
   ```
   command [options/flags] [arguments]
   ```
   - `command` = The program to run
   - `[options]` = Flags that modify behavior (usually start with `-` or `--`)
   - `[arguments]` = Additional information (file paths, names, etc.)

3. **Common Flags:**
   - `-R` = Recursive (apply to all files/folders inside)
   - `-a` = All (include hidden files)
   - `-l` = Long format (detailed information)
   - `-y` = Yes (auto-confirm prompts)
   - `-f` = Follow (keep watching for changes)

4. **File Paths:**
   - `/` = Root directory (top-level)
   - `/var/www/` = Standard web application directory
   - `~` = Home directory (shortcut for `/home/username/`)
   - `.` = Current directory
   - `..` = Parent directory

5. **File Permissions (chmod numbers):**
   - `7` = Read (4) + Write (2) + Execute (1) = Full access
   - `5` = Read (4) + Execute (1) = Read and execute
   - `4` = Read only
   - `755` = Owner: 7, Group: 5, Others: 5
   - `775` = Owner: 7, Group: 7, Others: 5

6. **Common Commands:**
   - `cd` = Change directory
   - `ls` = List files
   - `mkdir` = Make directory
   - `rm` = Remove/delete
   - `cp` = Copy
   - `mv` = Move/rename
   - `nano` = Text editor
   - `cat` = Display file contents
   - `grep` = Search text in files

7. **Text Editors (nano):**
   - `Ctrl+X` = Exit
   - `Ctrl+O` = Save (Output)
   - `Ctrl+W` = Search
   - `Ctrl+K` = Cut line
   - `Ctrl+U` = Paste

8. **Service Management (systemctl):**
   - `status` = Check if service is running
   - `start` = Start service
   - `stop` = Stop service
   - `restart` = Restart service
   - `reload` = Reload configuration (no downtime)
   - `enable` = Start automatically on boot

---

## Test Phase 1: Production Environment Setup

### ✅ Test 1.1: Web Server Configuration (Critical)

**Why:** Windows used `php artisan serve`. Raspberry Pi needs production web server (Nginx/Apache).

**Steps:**

1. **Check if Nginx is installed:**
   ```bash
   sudo systemctl status nginx
   ```
   **Explanation:**
   - `sudo` = "Super User Do" - runs command with administrator privileges (required for system commands)
   - `systemctl` = System Control - Linux tool to manage services (like Windows Services)
   - `status` = Check if service is running, stopped, or has errors
   - `nginx` = The web server we're checking
   - **Purpose:** Verify if Nginx web server is installed and running

2. **If not installed, install Nginx:**
   ```bash
   sudo apt update
   sudo apt install -y nginx
   ```
   **Explanation:**
   - `apt` = Advanced Package Tool - package manager for Debian/Ubuntu (like App Store for Linux)
   - `update` = Refresh list of available packages from repositories
   - `install` = Install a package
   - `-y` = "Yes" flag - automatically answers "yes" to prompts (non-interactive)
   - `nginx` = The web server package to install
   - **Purpose:** Download and install Nginx web server

3. **Check if PHP-FPM is running:**
   ```bash
   sudo systemctl status php8.4-fpm
   ```
   **Explanation:**
   - `php8.4-fpm` = PHP FastCGI Process Manager for PHP 8.4
   - FPM = Handles PHP processing for web servers (Nginx can't run PHP directly)
   - **Purpose:** Verify PHP processor is running (needed to execute Laravel PHP code)

4. **If not installed, install PHP-FPM:**
   ```bash
   sudo apt install -y php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd
   ```
   **Explanation:**
   - `php8.4-fpm` = PHP processor for web servers
   - `php8.4-mysql` = MySQL/MariaDB database extension
   - `php8.4-mbstring` = Multi-byte string functions (for international characters)
   - `php8.4-xml` = XML parsing support
   - `php8.4-curl` = HTTP client library (for API calls)
   - `php8.4-zip` = ZIP file handling
   - `php8.4-gd` = Image processing library
   - **Purpose:** Install PHP and all extensions Laravel requires

5. **Configure Nginx for Laravel:**
   ```bash
   sudo nano /etc/nginx/sites-available/parental_wifi
   ```
   **Explanation:**
   - `nano` = Simple text editor (easier than `vi` for beginners)
   - `/etc/nginx/sites-available/` = Directory where Nginx site configurations are stored
   - `parental_wifi` = Name of our configuration file (can be any name)
   - **Purpose:** Create/edit Nginx configuration file for our Laravel application
   - **Note:** After editing, press `Ctrl+X`, then `Y`, then `Enter` to save

   Add this configuration:
   ```nginx
   server {
       listen 80;                                    # Listen on port 80 (HTTP)
       server_name _;                                # Accept any domain name (use _ for IP access)
       root /var/www/parental_wifi/public;          # Laravel's public directory (entry point)
       
       # Security headers
       add_header X-Frame-Options "SAMEORIGIN";     # Prevent clickjacking attacks
       add_header X-Content-Type-Options "nosniff"; # Prevent MIME type sniffing
       
       index index.php;                              # Default file to serve
       charset utf-8;                                # Character encoding
       
       # Main location block - handles all requests
       location / {
           # Try to serve file, then directory, then pass to Laravel
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       # PHP file handler - passes PHP files to PHP-FPM
       location ~ \.php$ {
           # ~ = regex match, \.php$ = files ending in .php
           fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;  # Socket to PHP-FPM
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;                   # Include standard FastCGI parameters
       }
       
       # Block access to hidden files (except .well-known for SSL)
       location ~ /\.(?!well-known).* {
           deny all;                                 # Deny all hidden files
       }
   }
   ```
   **Explanation:**
   - `server {}` = Defines a virtual server (website)
   - `listen 80` = HTTP port (standard web port)
   - `root` = Document root directory (where files are served from)
   - `location /` = Handles all URL paths
   - `try_files` = Tries multiple options: file exists? directory exists? pass to Laravel
   - `location ~ \.php$` = Regex pattern matching PHP files
   - `fastcgi_pass` = Sends PHP files to PHP-FPM for processing
   - **Purpose:** Configure Nginx to serve Laravel application correctly

6. **Enable the site:**
   ```bash
   sudo ln -s /etc/nginx/sites-available/parental_wifi /etc/nginx/sites-enabled/
   sudo rm /etc/nginx/sites-enabled/default  # Remove default site
   sudo nginx -t  # Test configuration
   sudo systemctl reload nginx
   ```
   **Explanation:**
   - `ln -s` = Create symbolic link (shortcut)
     - `-s` = Symbolic link (points to another file)
     - First path = Source file
     - Second path = Link location
   - `sites-available/` = Available configurations (not active)
   - `sites-enabled/` = Active configurations (Nginx uses these)
   - `rm` = Remove/delete file
   - `nginx -t` = Test Nginx configuration for syntax errors
   - `systemctl reload` = Reload service without stopping (zero downtime)
   - **Purpose:** Activate the site configuration and restart Nginx

7. **Set correct permissions:**
   ```bash
   sudo chown -R www-data:www-data /var/www/parental_wifi
   sudo chmod -R 755 /var/www/parental_wifi
   sudo chmod -R 775 /var/www/parental_wifi/storage
   sudo chmod -R 775 /var/www/parental_wifi/bootstrap/cache
   ```
   **Explanation:**
   - `chown` = Change ownership (who owns the files)
     - `-R` = Recursive (apply to all files/folders inside)
     - `www-data:www-data` = User:Group (www-data is the web server user)
   - `chmod` = Change permissions (who can read/write/execute)
     - `755` = Owner: read/write/execute (7), Group: read/execute (5), Others: read/execute (5)
     - `775` = Owner: read/write/execute (7), Group: read/write/execute (7), Others: read/execute (5)
   - **Purpose:** Give web server permission to read files and write to storage/cache directories

**Expected Result:**
- ✅ Nginx is running
- ✅ PHP-FPM is running
- ✅ Configuration test passes
- ✅ Application accessible via Raspberry Pi IP address

**Test Access:**
```bash
# Get Raspberry Pi IP address
hostname -I
```
**Explanation:**
- `hostname` = Command to get system hostname/network info
- `-I` = Show all IP addresses (space-separated)
- **Purpose:** Find the IP address to access the web server from other devices

```bash
# Test from another device on same network:
# http://<raspberry-pi-ip>/          # Homepage
# http://<raspberry-pi-ip>/login     # Login page
```
**Explanation:**
- Replace `<raspberry-pi-ip>` with the IP address from `hostname -I`
- Example: If IP is `192.168.1.100`, use `http://192.168.1.100/login`
- **Purpose:** Verify the application is accessible over the network (not just localhost)

---

### ✅ Test 1.2: Environment Configuration (Critical)

**Why:** Production environment needs proper configuration.

**Steps:**

1. **Navigate to project directory:**
   ```bash
   cd /var/www/parental_wifi
   ```
   **Explanation:**
   - `cd` = Change directory
   - `/var/www/` = Standard location for web applications on Linux
   - **Purpose:** Move to the project root directory

2. **Check `.env` file exists:**
   ```bash
   ls -la .env
   ```
   **Explanation:**
   - `ls` = List files
   - `-l` = Long format (detailed info)
   - `-a` = All files (including hidden files starting with `.`)
   - `.env` = Environment configuration file (contains database credentials, app settings)
   - **Purpose:** Verify the `.env` file exists (required for Laravel)

3. **Generate application key (if not set):**
   ```bash
   php artisan key:generate
   ```
   **Explanation:**
   - `php artisan` = Laravel's command-line tool
   - `key:generate` = Generate encryption key for the application
   - **Purpose:** Create `APP_KEY` in `.env` (required for encryption, sessions, cookies)

4. **Update `.env` for production:**
   ```bash
   sudo nano .env
   ```
   **Explanation:**
   - `nano` = Text editor
   - `.env` = Environment file to edit
   - **Purpose:** Update configuration for production environment

   Verify these settings:
   ```
   APP_ENV=production          # Production environment (vs 'local' for development)
   APP_DEBUG=false             # Disable debug mode (hides errors from users)
   APP_URL=http://<raspberry-pi-ip>  # Base URL of your application
   
   DB_CONNECTION=mysql         # Database type (MariaDB uses MySQL driver)
   DB_HOST=127.0.0.1          # Database host (localhost)
   DB_PORT=3306               # MariaDB default port
   DB_DATABASE=parental_wifi  # Database name
   DB_USERNAME=your_db_user   # Database username (replace with actual)
   DB_PASSWORD=your_db_password  # Database password (replace with actual)
   ```
   **Explanation:**
   - `APP_ENV=production` = Production mode (optimized, no debug info)
   - `APP_DEBUG=false` = Don't show error details to users (security)
   - `APP_URL` = Base URL for generating links
   - `DB_*` = Database connection settings
   - **Purpose:** Configure application for production use

5. **Cache configuration:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   **Explanation:**
   - `config:cache` = Cache configuration files (faster loading)
   - `route:cache` = Cache route definitions (faster routing)
   - `view:cache` = Pre-compile Blade templates (faster rendering)
   - **Purpose:** Optimize performance by caching (production best practice)

**Expected Result:**
- ✅ `.env` file exists and configured
- ✅ `APP_KEY` is set
- ✅ Configuration cached successfully
- ✅ No errors

---

### ✅ Test 1.3: Database Connection (Critical)

**Why:** Verify MariaDB works on Raspberry Pi.

**Steps:**

1. **Check MariaDB is running:**
   ```bash
   sudo systemctl status mariadb
   ```
   **Explanation:**
   - `mariadb` = MariaDB database service name
   - **Purpose:** Verify database server is running

2. **If not running, start it:**
   ```bash
   sudo systemctl start mariadb
   sudo systemctl enable mariadb
   ```
   **Explanation:**
   - `start` = Start the service now
   - `enable` = Start automatically on boot (survives reboots)
   - **Purpose:** Start database and ensure it starts on system boot

3. **Create database (if not exists):**
   ```bash
   sudo mysql -u root -p
   ```
   **Explanation:**
   - `mysql` = MySQL/MariaDB command-line client
   - `-u root` = Login as root user
   - `-p` = Prompt for password (enter MariaDB root password)
   - **Purpose:** Access MySQL command prompt to create database

   In MySQL prompt:
   ```sql
   CREATE DATABASE IF NOT EXISTS parental_wifi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```
   **Explanation:**
   - `CREATE DATABASE` = Create new database
   - `IF NOT EXISTS` = Only create if it doesn't exist (prevents errors)
   - `CHARACTER SET utf8mb4` = Support full Unicode (emojis, special characters)
   - `COLLATE utf8mb4_unicode_ci` = Case-insensitive Unicode sorting
   - `EXIT;` = Leave MySQL prompt (semicolon required)
   - **Purpose:** Create database with proper character encoding for Laravel

4. **Test connection from Laravel:**
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   >>> exit
   ```
   **Explanation:**
   - `tinker` = Laravel's interactive REPL (Read-Eval-Print Loop) - like a PHP console
   - `>>>` = Tinker prompt (type PHP code here)
   - `DB::connection()->getPdo()` = Get PDO database connection object
   - `exit` = Leave tinker
   - **Purpose:** Verify Laravel can connect to the database (returns PDO object if successful)

**Expected Result:**
- ✅ MariaDB is running
- ✅ Database exists
- ✅ Connection successful (no errors)
- ✅ PDO object returned

---

### ✅ Test 1.4: Migrations (Critical)

**Why:** Verify all tables are created on Raspberry Pi.

**Steps:**

1. **Run migrations:**
   ```bash
   php artisan migrate
   ```
   **Explanation:**
   - `migrate` = Run all pending database migrations
   - Migrations = Database schema definitions (create tables, columns, indexes)
   - **Purpose:** Create all database tables defined in migration files

2. **Check migration status:**
   ```bash
   php artisan migrate:status
   ```
   **Explanation:**
   - `migrate:status` = Show which migrations have run
   - Shows: Migration name, Batch number, Status (Ran/Pending)
   - **Purpose:** Verify all migrations completed successfully

**Expected Result:**
- ✅ All 19 migrations run successfully
- ✅ All tables created
- ✅ No migration errors

---

### ✅ Test 1.5: Seeders (Critical)

**Why:** Verify default admin account is created on Raspberry Pi.

**Steps:**

1. **Run seeders:**
   ```bash
   php artisan db:seed
   ```
   **Explanation:**
   - `db:seed` = Run all database seeders
   - Seeders = Populate database with initial/default data
   - **Purpose:** Create default admin account and dictionary words

2. **Verify default admin exists:**
   ```bash
   php artisan tinker
   >>> $admin = App\Models\User::where('email', 'admin@parentalwifi.local')->first();
   >>> $admin->name;  // Should return "System Administrator"
   >>> $admin->role;  // Should return "admin"
   >>> exit
   ```
   **Explanation:**
   - `App\Models\User` = User model class
   - `where('email', '...')` = Find user with matching email
   - `first()` = Get first matching record (or null)
   - `$admin->name` = Access the `name` property
   - `//` = PHP comment (not executed)
   - **Purpose:** Verify default admin account was created successfully

**Expected Result:**
- ✅ Seeders run without errors
- ✅ Default admin account exists
- ✅ Dictionary words are seeded

---

### ✅ Test 1.6: Web Access (Critical)

**Why:** Verify application is accessible via network (not just localhost).

**Steps:**

1. **Get Raspberry Pi IP address:**
   ```bash
   hostname -I
   ```
   **Explanation:**
   - `hostname -I` = Get all IP addresses assigned to this machine
   - Returns space-separated IP addresses (e.g., "192.168.1.100")
   - **Purpose:** Find the IP address to access the web application from other devices

2. **Test from another device (phone/laptop on same network):**
   - Open browser
   - Go to: `http://<raspberry-pi-ip>/`
   - Go to: `http://<raspberry-pi-ip>/login`
   - Go to: `http://<raspberry-pi-ip>/register` (should show 404)

3. **Test login:**
   - Go to login page
   - Enter: `admin@parentalwifi.local` / `admin123`
   - Should redirect to dashboard

**Expected Result:**
- ✅ Homepage loads
- ✅ Login page loads
- ✅ Registration returns 404
- ✅ Can log in with default admin
- ✅ Dashboard loads after login

---

## Test Phase 2: Production-Specific Verification

### ✅ Test 2.1: File Permissions (Critical)

**Why:** Linux file permissions are different from Windows. Critical for Laravel to work.

**Steps:**

1. **Check storage permissions:**
   ```bash
   ls -la storage/
   ls -la bootstrap/cache/
   ```
   **Explanation:**
   - `ls -la` = List files with detailed permissions
   - Shows: permissions, owner, group, size, date
   - **Purpose:** Verify current permissions before fixing

2. **Fix permissions if needed:**
   ```bash
   sudo chown -R www-data:www-data /var/www/parental_wifi
   sudo chmod -R 755 /var/www/parental_wifi
   sudo chmod -R 775 /var/www/parental_wifi/storage
   sudo chmod -R 775 /var/www/parental_wifi/bootstrap/cache
   ```
   **Explanation:**
   - `chown -R www-data:www-data` = Change owner to www-data user and group (recursive)
   - `chmod -R 755` = Read/write/execute for owner, read/execute for others (recursive)
   - `chmod -R 775` = Read/write/execute for owner and group, read/execute for others
   - `775` for storage/cache = Allows web server to write logs, cache files
   - **Purpose:** Set correct ownership and permissions for Laravel to function

3. **Test write permission:**
   ```bash
   sudo -u www-data touch /var/www/parental_wifi/storage/test.txt
   sudo -u www-data rm /var/www/parental_wifi/storage/test.txt
   ```
   **Explanation:**
   - `sudo -u www-data` = Run command as www-data user (web server user)
   - `touch` = Create empty file (or update timestamp if exists)
   - `rm` = Remove/delete file
   - **Purpose:** Verify www-data user can write to storage directory (if this fails, permissions are wrong)

**Expected Result:**
- ✅ Storage directory writable by www-data
- ✅ Bootstrap/cache writable by www-data
- ✅ Write test succeeds

---

### ✅ Test 2.2: Storage Symlink (Critical)

**Why:** Required for file access via web.

**Steps:**

1. **Create storage symlink:**
   ```bash
   php artisan storage:link
   ```
   **Explanation:**
   - `storage:link` = Create symbolic link from `public/storage` to `storage/app/public`
   - Symlink = Shortcut/pointer to another location
   - **Purpose:** Make files in `storage/app/public` accessible via web URL (e.g., `/storage/videos/file.mp4`)

2. **Verify symlink exists:**
   ```bash
   ls -la public/storage
   ```
   **Explanation:**
   - `ls -la` = List with details
   - Look for `->` arrow pointing to `storage/app/public`
   - If you see `lrwxrwxrwx` (starts with 'l'), it's a symlink
   - **Purpose:** Confirm symlink was created correctly

**Expected Result:**
- ✅ Symlink created
- ✅ Points to `storage/app/public`

---

### ✅ Test 2.3: Production Performance (Important)

**Why:** Verify application performs well on Raspberry Pi hardware.

**Steps:**

1. **Test page load time:**
   ```bash
   # From another device, measure time to load:
   # http://<raspberry-pi-ip>/login
   ```

2. **Test database query speed:**
   ```bash
   php artisan tinker
   >>> $start = microtime(true);
   >>> App\Models\User::with('devices')->get();
   >>> $end = microtime(true);
   >>> echo ($end - $start) . " seconds";
   >>> exit
   ```
   **Explanation:**
   - `microtime(true)` = Get current time in seconds (with microseconds)
   - `$start` = Store start time before query
   - `App\Models\User::with('devices')` = Load users with their related devices (eager loading)
   - `get()` = Execute query and get all results
   - `$end` = Store end time after query
   - `echo ($end - $start)` = Calculate and display elapsed time
   - **Purpose:** Measure query performance (should be < 1 second on Raspberry Pi)

**Expected Result:**
- ✅ Page loads in < 2 seconds
- ✅ Database queries complete in < 1 second

---

## Critical Test Checklist

### Must Pass (System Won't Work Without These)

- [ ] **Nginx/Apache is running** - Web server works
- [ ] **PHP-FPM is running** - PHP processing works
- [ ] **Database connection works** - Can access data
- [ ] **Migrations ran successfully** - All tables exist
- [ ] **Default admin account exists** - Can log in
- [ ] **File permissions correct** - Laravel can write files
- [ ] **Application accessible via network** - Can access from other devices
- [ ] **Login works** - Can authenticate

### Should Pass (Important for Production)

- [ ] **Storage symlink works** - Files accessible via web
- [ ] **Performance is acceptable** - Pages load quickly
- [ ] **Registration route returns 404** - Security feature works

---

## Quick Test Procedure

**Run these commands in order:**

```bash
# 1. Check services - Verify web server, PHP, and database are running
sudo systemctl status nginx        # Web server status
sudo systemctl status php8.4-fpm   # PHP processor status
sudo systemctl status mariadb      # Database status

# 2. Set permissions - Give web server access to files
sudo chown -R www-data:www-data /var/www/parental_wifi              # Change ownership
sudo chmod -R 755 /var/www/parental_wifi                            # Standard permissions
sudo chmod -R 775 /var/www/parental_wifi/storage                    # Writable for logs/cache
sudo chmod -R 775 /var/www/parental_wifi/bootstrap/cache            # Writable for cache

# 3. Run migrations and seeders - Create database tables and initial data
cd /var/www/parental_wifi          # Navigate to project directory
php artisan migrate                 # Create all database tables
php artisan db:seed                # Create default admin and dictionary words

# 4. Create symlink - Make storage files accessible via web
php artisan storage:link           # Link public/storage -> storage/app/public

# 5. Cache configuration - Optimize performance for production
php artisan config:cache           # Cache .env configuration
php artisan route:cache            # Cache route definitions
php artisan view:cache             # Pre-compile Blade templates

# 6. Get IP address - Find Raspberry Pi's network address
hostname -I                         # Display IP address(es)

# 7. Test from browser (on another device on same network)
# Open browser and go to: http://<raspberry-pi-ip>/login
# Login credentials:
#   Email: admin@parentalwifi.local
#   Password: admin123
```

---

## What's NOT Tested (Already Verified on Windows)

These were already tested on Windows and don't need retesting:
- ❌ PHP version check (already verified 8.4+)
- ❌ PHP extensions check (already verified)
- ❌ Model CRUD operations (already verified)
- ❌ Model relationships (already verified)
- ❌ Blade template syntax (already verified)
- ❌ Authentication logic (already verified)
- ❌ Role-based access logic (already verified)

---

## Troubleshooting

### Issue: "403 Forbidden" or "Permission Denied"
```bash
sudo chown -R www-data:www-data /var/www/parental_wifi
sudo chmod -R 755 /var/www/parental_wifi
sudo chmod -R 775 /var/www/parental_wifi/storage
```
**Explanation:**
- `403 Forbidden` = Web server can't access files (permission issue)
- Fix by setting correct ownership and permissions
- **Purpose:** Resolve file access permission errors

### Issue: "500 Internal Server Error"
```bash
# Check Laravel logs - Shows PHP/Laravel errors
tail -f /var/www/parental_wifi/storage/logs/laravel.log
# tail = Show last lines of file
# -f = Follow (keep showing new lines as they're added)
# Press Ctrl+C to stop

# Check Nginx error logs - Shows web server errors
sudo tail -f /var/log/nginx/error.log
```
**Explanation:**
- `500 Internal Server Error` = Server-side error (PHP exception, configuration issue)
- Laravel logs = Application errors (PHP exceptions, database errors)
- Nginx logs = Web server errors (PHP-FPM connection, file access)
- **Purpose:** Find the exact error causing the 500 response

### Issue: "Database Connection Failed"
```bash
# Check MariaDB is running
sudo systemctl status mariadb

# Check .env database credentials
cat .env | grep DB_
# cat = Display file contents
# | = Pipe (send output to next command)
# grep DB_ = Show only lines containing "DB_"
```
**Explanation:**
- `Database Connection Failed` = Can't connect to MariaDB
- Check if service is running and credentials are correct
- **Purpose:** Diagnose database connection issues

### Issue: "Route Not Found"
```bash
# Clear route cache - Remove cached routes
php artisan route:clear

# Rebuild route cache - Recreate cached routes
php artisan route:cache
```
**Explanation:**
- `Route Not Found` = URL doesn't match any defined route
- Sometimes cached routes are outdated
- Clear and rebuild cache to fix
- **Purpose:** Resolve routing issues caused by stale cache

---

## Success Criteria

✅ **All critical tests pass**  
✅ **Application accessible via network**  
✅ **Can log in with default admin**  
✅ **No permission errors**  
✅ **Performance is acceptable**

**If all critical tests pass, you're ready to continue development!**

---

## Next Steps After Testing

Once Test Phase 1 & 2 passes on Raspberry Pi:
1. Continue development (Todo #5: Time Tracking Service)
2. Test again when Video System is built (Test Phase 3)
3. Test again when Shell Scripts are created (Test Phase 4)
4. Test again when Background Jobs are implemented (Test Phase 5)
5. Final test when Portal Core is complete (Test Phase 6)

