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

## Raspberry Pi Complete Setup Guide

This section provides a comprehensive, beginner-friendly guide to setting up your Laravel application on a Raspberry Pi. Each component is explained in detail, including **what it is**, **why we need it**, and **how it works**.

### Overview: What is a Web Application?

A web application needs four main components to work:

1. **Web Server** - Receives requests from browsers and serves web pages
2. **PHP Processor** - Executes PHP code (Laravel is written in PHP)
3. **Database** - Stores all your data (users, devices, quizzes, etc.)
4. **Frontend Assets** - CSS and JavaScript files that make the website look and work properly

We'll install and configure all of these components on your Raspberry Pi.

---

### Part 1: Web Server (Nginx)

#### What is Nginx?

Nginx (pronounced "engine-x") is a **web server** - software that listens for requests from web browsers and serves files back to them. Think of it as a waiter in a restaurant: browsers (customers) make requests, and Nginx (waiter) brings them the files they need.

#### Why We Need It

- **On Windows:** You used `php artisan serve` which is fine for development but not suitable for production
- **On Raspberry Pi:** We need a production-grade web server that can handle multiple requests efficiently
- **Nginx is:** Fast, reliable, and commonly used in production environments
- **It handles:** HTTP requests, serves static files (images, CSS, JS), and forwards PHP requests to PHP-FPM

#### How It Works

1. Browser sends request: `http://192.168.1.173/login`
2. Request travels over network to Raspberry Pi
3. Nginx receives the request on port 80 (HTTP port)
4. Nginx checks if it's a PHP file or static file
5. If PHP: Nginx forwards to PHP-FPM for processing
6. If static: Nginx serves the file directly
7. Nginx sends response back to browser

#### Installation

```bash
# Update package list (get latest package information)
sudo apt update

# Install Nginx
sudo apt install -y nginx
```

**Explanation:**
- `sudo apt update` = Refresh the list of available packages from repositories (like updating an app store catalog)
- `sudo apt install -y nginx` = Install Nginx package (`-y` automatically answers "yes" to prompts)
- **Why update first?** Ensures you get the latest version and all dependencies

#### Verify Installation

```bash
# Check if Nginx is running
sudo systemctl status nginx --no-pager

# Start Nginx if not running
sudo systemctl start nginx

# Enable Nginx to start automatically on boot
sudo systemctl enable nginx
```

**Explanation:**
- `systemctl status` = Check if service is running, stopped, or has errors
- `--no-pager` = Show output without opening a pager (easier to read)
- `start` = Start the service now
- `enable` = Make service start automatically when Raspberry Pi boots

#### How to Access

- Nginx runs on **port 80** (standard HTTP port)
- Accessible from any device on your network via Raspberry Pi's IP address
- Example: `http://192.168.1.173/` (replace with your Pi's IP)

---

### Part 2: PHP Processor (PHP-FPM)

#### What is PHP-FPM?

PHP-FPM stands for **PHP FastCGI Process Manager**. It's a program that runs PHP code. Nginx can't execute PHP directly - it needs PHP-FPM to process PHP files and return HTML.

**Think of it like this:**
- Nginx = Waiter (takes orders, serves food)
- PHP-FPM = Chef (prepares the food/executes PHP code)

#### Why We Need It

- **Laravel is written in PHP** - All your application logic is PHP code
- **Nginx can't run PHP** - It only serves static files and forwards PHP requests
- **PHP-FPM processes PHP** - It executes your Laravel code and generates HTML
- **They work together** - Nginx receives requests, PHP-FPM processes PHP, Nginx sends response

#### How It Works

1. Browser requests a PHP page (e.g., `/login`)
2. Nginx receives the request
3. Nginx sees it's a PHP file, forwards it to PHP-FPM via a socket
4. PHP-FPM executes the PHP code (Laravel framework)
5. PHP-FPM returns generated HTML to Nginx
6. Nginx sends HTML back to browser

#### Installation

```bash
# Install PHP 8.4 and PHP-FPM with all required extensions
sudo apt install -y php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-cli
```

**Explanation of Each Extension:**

- **`php8.4-fpm`** = PHP FastCGI Process Manager (the PHP processor itself)
- **`php8.4-mysql`** = MySQL/MariaDB database extension (Laravel needs this to connect to database)
- **`php8.4-mbstring`** = Multi-byte string functions (handles international characters, emojis, etc.)
- **`php8.4-xml`** = XML parsing support (Laravel uses XML for various features)
- **`php8.4-curl`** = HTTP client library (for making API calls, downloading files)
- **`php8.4-zip`** = ZIP file handling (for file uploads, exports)
- **`php8.4-gd`** = Image processing library (for resizing, cropping images)
- **`php8.4-cli`** = Command-line interface (allows running `php artisan` commands)

**Why all these?** Laravel requires these extensions to function properly. Without them, you'll get errors when trying to use certain features.

#### Verify Installation

```bash
# Check PHP version
php -v

# Check if PHP-FPM is running
sudo systemctl status php8.4-fpm --no-pager

# Start PHP-FPM if not running
sudo systemctl start php8.4-fpm

# Enable PHP-FPM to start automatically on boot
sudo systemctl enable php8.4-fpm
```

---

### Part 3: Database (MariaDB)

#### What is MariaDB?

MariaDB is a **database server** - software that stores and manages data. It's a fork of MySQL (they're compatible). Think of it as a digital filing cabinet that stores all your application's data in an organized way.

#### Why We Need It

- **Laravel needs a database** - To store users, devices, quizzes, videos, logs, etc.
- **MariaDB stores everything** - All your application data lives here
- **It's reliable** - Production-grade database used by millions of websites
- **It's compatible** - Works perfectly with Laravel's database features

#### How It Works

1. Laravel application needs data (e.g., "get user with email admin@parentalwifi.local")
2. Laravel sends SQL query to MariaDB
3. MariaDB searches its database
4. MariaDB returns the data
5. Laravel uses the data to display the page

#### Installation

```bash
# Install MariaDB server
sudo apt install -y mariadb-server

# Start MariaDB service
sudo systemctl start mariadb

# Enable MariaDB to start automatically on boot
sudo systemctl enable mariadb
```

**Explanation:**
- `mariadb-server` = The database server software
- `start` = Start the database service now
- `enable` = Make database start automatically when Pi boots

#### Create Database and User

**Why create a separate user?** MariaDB's root user uses socket authentication (no password), but Laravel needs password authentication. Creating a dedicated user solves this.

```bash
# Access MariaDB as root
sudo mysql -u root
```

In the MariaDB prompt, run:

```sql
-- Create a database user for Laravel
CREATE USER IF NOT EXISTS 'parental_wifi_user'@'localhost' IDENTIFIED BY 'your_secure_password';

-- Create the database
CREATE DATABASE IF NOT EXISTS parental_wifi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant all privileges on the database to the user
GRANT ALL PRIVILEGES ON parental_wifi.* TO 'parental_wifi_user'@'localhost';

-- Apply the changes
FLUSH PRIVILEGES;

-- Exit MariaDB
EXIT;
```

**Explanation:**
- `CREATE USER` = Create a new database user (replace `your_secure_password` with a strong password)
- `CREATE DATABASE` = Create the database where all tables will be stored
- `CHARACTER SET utf8mb4` = Supports full Unicode (emojis, special characters from any language)
- `COLLATE utf8mb4_unicode_ci` = Case-insensitive sorting (treats 'A' and 'a' the same)
- `GRANT ALL PRIVILEGES` = Give the user full access to the database
- `FLUSH PRIVILEGES` = Apply the permission changes

**Why utf8mb4?** Laravel uses this by default. It supports the widest range of characters, including emojis and international text.

---

### Part 4: Git and SSH Keys Setup

#### What is Git?

Git is a **version control system** - it tracks changes to your code and allows you to download code from repositories (like GitHub). Think of it as a time machine for your code.

#### Why We Need It

- **To get your code** - Download your Laravel project from GitHub
- **Version control** - Track changes, update code easily
- **SSH keys** - Allow passwordless access to GitHub (more secure and convenient)

#### Installation

```bash
# Install Git
sudo apt install -y git
```

#### Set Up SSH Keys (Passwordless GitHub Access)

**Why SSH keys?** They allow you to clone and push to GitHub without entering a password every time. More secure and convenient than HTTPS.

**Step 1: Generate SSH Key**

```bash
# Generate SSH key (replace email with your GitHub email)
ssh-keygen -t ed25519 -C "your-email@example.com"

# Press Enter to accept default file location (~/.ssh/id_ed25519)
# Press Enter twice for no passphrase (or set one if you prefer)
```

**Explanation:**
- `ssh-keygen` = Tool to generate SSH keys
- `-t ed25519` = Use Ed25519 encryption algorithm (modern and secure)
- `-C "email"` = Add a comment (your email) to identify the key
- Default location is `~/.ssh/id_ed25519` (your home directory)

**Step 2: Start SSH Agent and Add Key**

```bash
# Start the SSH agent
eval "$(ssh-agent -s)"

# Add your SSH key to the agent
ssh-add ~/.ssh/id_ed25519
```

**Step 3: Copy Public Key**

```bash
# Display your public key (copy the entire output)
cat ~/.ssh/id_ed25519.pub
```

**Step 4: Add Key to GitHub**

1. Go to GitHub.com and sign in
2. Click your profile picture → **Settings**
3. In left sidebar, click **SSH and GPG keys**
4. Click **New SSH key**
5. **Title:** "Raspberry Pi" (or any name)
6. **Key:** Paste the public key you copied
7. Click **Add SSH key**

**Step 5: Test SSH Connection**

```bash
# Test connection to GitHub
ssh -T git@github.com
```

You should see: `Hi Cristhan11! You've successfully authenticated, but GitHub does not provide shell access.`

**Step 6: Set Up SSH Key for Root (for sudo commands)**

Since we'll use `sudo` for git commands, we need to copy the SSH key to root:

```bash
# Copy SSH keys to root user
sudo mkdir -p /root/.ssh
sudo cp ~/.ssh/id_ed25519* /root/.ssh/
sudo chmod 600 /root/.ssh/id_ed25519
sudo chmod 644 /root/.ssh/id_ed25519.pub

# Test root's SSH connection
sudo ssh -T git@github.com
```

---

### Part 5: Clone Repository

#### Get Your Project Code

```bash
# Navigate to web directory
cd /var/www

# Clone your repository using SSH
sudo git clone git@github.com:Cristhan11/Parental_Wifi.git parental_wifi
```

**Explanation:**
- `/var/www/` = Standard location for web applications on Linux
- `git clone` = Download repository from GitHub
- `git@github.com:...` = SSH URL format (uses SSH keys we just set up)
- `parental_wifi` = Directory name where code will be stored

**Why /var/www/?** It's the standard location for web applications. Nginx is configured to look here by default.

#### Set Initial Permissions

```bash
# Set ownership to www-data (web server user)
sudo chown -R www-data:www-data /var/www/parental_wifi

# Set directory permissions
sudo chmod -R 755 /var/www/parental_wifi
```

---

### Part 6: Composer (PHP Dependency Manager)

#### What is Composer?

Composer is a **dependency manager for PHP**. It downloads and installs all the PHP packages (libraries) that Laravel needs to run. Think of it as an app store for PHP packages.

#### Why We Need It

- **Laravel has dependencies** - It needs many other PHP packages (Symfony, Guzzle, etc.)
- **Composer manages them** - Downloads correct versions, handles conflicts
- **Creates autoloader** - Makes all packages available to Laravel
- **76 packages installed** - That's how many dependencies Laravel has!

#### How It Works

1. Reads `composer.json` file (lists all required packages)
2. Downloads packages from the internet
3. Installs them in `vendor/` directory
4. Creates autoload files (so PHP knows where to find each package)

#### Installation

```bash
# Install Composer to /tmp (writable location)
cd /tmp
curl -sS https://getcomposer.org/installer | php

# Move Composer to system location
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verify installation
composer --version
```

**Explanation:**
- `curl` = Downloads files from the internet
- `https://getcomposer.org/installer` = Official Composer installer script
- `| php` = Pipes the downloaded script to PHP to execute it
- `/usr/local/bin/composer` = System-wide location (available everywhere)
- `chmod +x` = Make the file executable

#### Install PHP Dependencies

```bash
# Navigate to project directory
cd /var/www/parental_wifi

# Temporarily change ownership to your user (for npm install)
sudo chown -R snasna:snasna /var/www/parental_wifi

# Install all PHP dependencies
composer install --no-dev --optimize-autoloader

# Change ownership back to www-data
sudo chown -R www-data:www-data /var/www/parental_wifi
```

**Explanation:**
- `composer install` = Install all packages listed in `composer.json`
- `--no-dev` = Don't install development packages (smaller, faster for production)
- `--optimize-autoloader` = Create optimized autoloader (faster class loading)
- **What gets created:** `vendor/` directory with 76+ packages

**Why change ownership?** Composer needs to write to the directory. We temporarily change ownership, install, then change back.

---

### Part 7: Node.js and npm (Frontend Build Tools)

#### What are Node.js and npm?

- **Node.js** = JavaScript runtime (allows running JavaScript outside browsers)
- **npm** = Node Package Manager (manages JavaScript packages)

#### Why We Need Them

- **Laravel uses Vite** - Modern build tool for frontend assets (CSS, JavaScript)
- **Vite needs Node.js** - To compile and bundle your frontend code
- **Builds production assets** - Creates optimized CSS/JS files for production
- **Creates manifest file** - Laravel needs this to know which assets to load

#### How It Works

1. `npm install` = Downloads frontend dependencies (React, Vue, etc. if used)
2. `npm run build` = Compiles CSS/JS, optimizes them, creates `public/build/` directory
3. Creates `manifest.json` = Maps original file names to built file names
4. Laravel reads manifest = Knows which built files to serve

#### Installation

```bash
# Install Node.js and npm
sudo apt install -y nodejs npm

# Verify installation
node --version
npm --version
```

#### Build Frontend Assets

```bash
# Navigate to project directory
cd /var/www/parental_wifi

# Temporarily change ownership (npm needs write access)
sudo chown -R snasna:snasna /var/www/parental_wifi

# Install frontend dependencies
npm install

# Build production assets
npm run build

# Change ownership back to www-data
sudo chown -R www-data:www-data /var/www/parental_wifi

# Set correct permissions
sudo chmod -R 755 /var/www/parental_wifi
sudo chmod -R 775 /var/www/parental_wifi/storage
sudo chmod -R 775 /var/www/parental_wifi/bootstrap/cache
```

**Explanation:**
- `npm install` = Install packages from `package.json` (creates `node_modules/` directory)
- `npm run build` = Run the build script (creates `public/build/` with compiled assets)
- **What gets created:** `public/build/manifest.json` and optimized CSS/JS files

**Why change ownership?** npm needs to create `node_modules/` and `public/build/` directories. We temporarily change ownership, build, then restore.

---

### Part 8: Configure Nginx for Laravel

#### Create Nginx Configuration File

```bash
# Create and edit Nginx configuration file
sudo nano /etc/nginx/sites-available/parental_wifi
```

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

**Detailed Explanation:**

- **`listen 80`** = Listen for HTTP requests on port 80 (standard web port)
- **`server_name _`** = Accept requests from any domain/IP (underscore means "any")
- **`root /var/www/parental_wifi/public`** = Where Nginx looks for files (Laravel's public directory)
- **`index index.php`** = Default file to serve if no file is specified
- **`location /`** = Handles all URL paths
- **`try_files`** = Tries multiple options: 1) serve file if exists, 2) serve directory if exists, 3) pass to Laravel
- **`location ~ \.php$`** = Regex pattern matching files ending in `.php`
- **`fastcgi_pass`** = Sends PHP files to PHP-FPM via Unix socket
- **`fastcgi_param SCRIPT_FILENAME`** = Tells PHP-FPM which file to execute
- **`include fastcgi_params`** = Includes standard FastCGI configuration

**Why `/public`?** Laravel's `public/` directory is the web root. It contains `index.php` which bootstraps Laravel. All web requests should go through this directory.

**Save:** Press `Ctrl+X`, then `Y`, then `Enter`

#### Enable the Site

```bash
# Create symbolic link to enable the site
sudo ln -s /etc/nginx/sites-available/parental_wifi /etc/nginx/sites-enabled/

# Remove default Nginx site
sudo rm /etc/nginx/sites-enabled/default

# Test Nginx configuration for syntax errors
sudo nginx -t

# Reload Nginx to apply changes
sudo systemctl reload nginx
```

**Explanation:**
- `ln -s` = Create symbolic link (shortcut pointing to configuration file)
- `sites-available/` = Available configurations (not active)
- `sites-enabled/` = Active configurations (Nginx uses these)
- `nginx -t` = Test configuration (checks for syntax errors)
- `systemctl reload` = Reload without stopping (zero downtime)

---

### Part 9: Configure Environment File

#### Create .env File

```bash
# Navigate to project directory
cd /var/www/parental_wifi

# Copy .env.example to .env
sudo cp .env.example .env

# Generate application key
sudo php artisan key:generate
```

**Explanation:**
- `.env` = Environment configuration file (contains secrets and settings)
- `key:generate` = Creates `APP_KEY` for encryption (required for sessions, cookies)

#### Edit .env File

```bash
# Edit .env file
sudo nano .env
```

Update these important settings:

```
APP_ENV=production          # Production environment (optimized, no debug info)
APP_DEBUG=false             # Don't show errors to users (security)
APP_URL=http://192.168.1.173  # Your Raspberry Pi's IP address

DB_CONNECTION=mysql         # Database type (MariaDB uses MySQL driver)
DB_HOST=127.0.0.1          # Database host (localhost)
DB_PORT=3306               # MariaDB default port
DB_DATABASE=parental_wifi  # Database name (created earlier)
DB_USERNAME=parental_wifi_user  # Database user (created earlier)
DB_PASSWORD=your_secure_password  # Database password (the one you set)
```

**Explanation of Each Setting:**

- **`APP_ENV=production`** = Production mode (optimized, caching enabled, no debug)
- **`APP_DEBUG=false`** = Hide error details from users (security best practice)
- **`APP_URL`** = Base URL for generating links (use your Pi's IP address)
- **`DB_CONNECTION=mysql`** = Use MySQL driver (works with MariaDB)
- **`DB_HOST=127.0.0.1`** = Localhost (database is on same machine)
- **`DB_DATABASE`** = Name of database we created
- **`DB_USERNAME`** = Database user we created
- **`DB_PASSWORD`** = Password you set for the database user

**Save:** Press `Ctrl+X`, then `Y`, then `Enter`

---

### Part 10: Set File Permissions

#### Why Permissions Matter

Linux uses a permission system to control who can read, write, or execute files. The web server runs as the `www-data` user, so it needs proper permissions to:
- **Read files** - To serve your Laravel application
- **Write to storage/** - To create log files, cache files
- **Write to bootstrap/cache/** - To create configuration cache

#### Set Permissions

```bash
# Set ownership to www-data (web server user)
sudo chown -R www-data:www-data /var/www/parental_wifi

# Set directory permissions (owner: read/write/execute, others: read/execute)
sudo chmod -R 755 /var/www/parental_wifi

# Set writable permissions for storage (owner and group: read/write/execute)
sudo chmod -R 775 /var/www/parental_wifi/storage

# Set writable permissions for cache (owner and group: read/write/execute)
sudo chmod -R 775 /var/www/parental_wifi/bootstrap/cache
```

**Explanation:**

- **`chown -R www-data:www-data`** = Change owner to www-data user and group (recursive)
- **`chmod -R 755`** = 
  - `7` (owner) = Read (4) + Write (2) + Execute (1) = Full access
  - `5` (group) = Read (4) + Execute (1) = Read and execute
  - `5` (others) = Read (4) + Execute (1) = Read and execute
- **`chmod -R 775`** = 
  - `7` (owner) = Full access
  - `7` (group) = Full access (allows www-data group to write)
  - `5` (others) = Read and execute

**Why 775 for storage/cache?** These directories need to be writable by the web server for logs and cache files.

---

### Part 11: Run Migrations and Seeders

#### What are Migrations?

Migrations are files that define your database structure (tables, columns, indexes). They're like blueprints for your database.

#### Run Migrations

```bash
# Navigate to project directory
cd /var/www/parental_wifi

# Run all migrations (create all database tables)
php artisan migrate
```

**What happens:**
- Creates 19 tables (users, devices, quizzes, videos, etc.)
- Sets up relationships between tables
- Creates indexes for performance

#### What are Seeders?

Seeders populate your database with initial/default data. They're like filling your database with starter content.

#### Run Seeders

```bash
# Run all seeders (create default admin account and dictionary words)
php artisan db:seed
```

**What gets created:**
- Default admin account: `admin@parentalwifi.local` / `admin123`
- Dictionary words for content filtering

#### Verify Admin Account

```bash
# Open Laravel's interactive console
php artisan tinker

# Check if admin exists
>>> $admin = App\Models\User::where('email', 'admin@parentalwifi.local')->first();
>>> $admin->name;  // Should return "System Administrator"
>>> $admin->role;  // Should return "admin"
>>> exit
```

---

### Part 12: Create Storage Symlink

#### What is a Symlink?

A symlink (symbolic link) is a shortcut that points to another location. It allows accessing files in one location from another location.

#### Why We Need It

- **Laravel stores files** in `storage/app/public/` (videos, images, etc.)
- **Web needs access** via `public/storage/` URL
- **Symlink connects them** - Makes `storage/app/public/` accessible as `/storage/` in URLs

#### Create Symlink

```bash
# Create symlink (must run as www-data user)
sudo -u www-data php artisan storage:link

# Verify symlink was created
ls -la /var/www/parental_wifi/public/storage
```

**You should see:**
```
lrwxrwxrwx ... storage -> ../storage/app/public
```

The `l` at the start and `->` arrow indicate it's a symlink.

**Why run as www-data?** The symlink needs to be owned by www-data so the web server can access it.

---

### Part 13: Cache Configuration

#### Why Cache?

Caching stores frequently used data in memory/files for faster access. In production, caching:
- **Speeds up requests** - Config, routes, views load faster
- **Reduces file reads** - Less disk I/O
- **Improves performance** - Especially important on Raspberry Pi

#### Cache Everything

```bash
# Cache configuration (stores .env settings in files)
sudo -u www-data php artisan config:cache

# Cache routes (stores route definitions)
sudo -u www-data php artisan route:cache

# Cache views (pre-compiles Blade templates)
sudo -u www-data php artisan view:cache
```

**Explanation:**
- `config:cache` = Reads `.env` and stores in `bootstrap/cache/config.php`
- `route:cache` = Stores all routes in `bootstrap/cache/routes-v7.php`
- `view:cache` = Pre-compiles Blade templates (faster rendering)

**Why run as www-data?** Cache files need to be writable by the web server.

---

### Part 14: Network Access - How Other Devices Can Connect

#### What is an IP Address?

An IP address is a unique identifier for your Raspberry Pi on your network. Think of it as a house address, but for computers.

- **Example:** `192.168.1.173`
- **Format:** Four numbers separated by dots
- **Local network:** Usually starts with `192.168.x.x` or `10.x.x.x`

#### How Network Access Works

**Same Network:**
- All devices connected to the same Wi-Fi/router are on the same network
- They can communicate with each other using IP addresses
- Your Raspberry Pi and your phone/laptop are on the same network

**Request Flow:**
1. You type `http://192.168.1.173/login` on your phone
2. Request goes to your router
3. Router forwards to Raspberry Pi (IP 192.168.1.173)
4. Nginx receives request on port 80
5. Response comes back through the same path

#### Find Your Raspberry Pi's IP Address

```bash
# Get IP address
hostname -I
```

**Output example:** `192.168.1.173`

#### Access from Another Device

**Requirements:**
- Device must be on the same Wi-Fi network
- Raspberry Pi must be running
- Nginx must be running

**Steps:**
1. Open a web browser on your phone/laptop
2. Type: `http://192.168.1.173/` (replace with your Pi's IP)
3. You should see your Laravel application!

**Test these URLs:**
- `http://192.168.1.173/` - Homepage
- `http://192.168.1.173/login` - Login page
- Login with: `admin@parentalwifi.local` / `admin123`

#### Why It Works

- **Nginx listens on all interfaces** - Accepts requests from any device on network
- **Port 80 is open** - Standard HTTP port (no firewall blocking)
- **Router allows local traffic** - Devices on same network can communicate
- **No internet required** - Works entirely on your local network

#### Security Note

- **Local network only** - Not accessible from the internet (unless you configure port forwarding)
- **No HTTPS by default** - Traffic is not encrypted (fine for local network)
- **For production internet access** - You'd need to set up SSL/TLS certificates

---

### Part 15: How Everything Works Together

#### Complete Request Flow

Here's what happens when you visit `http://192.168.1.173/login`:

1. **Browser** - You type the URL and press Enter
2. **Network** - Request travels over Wi-Fi to your router
3. **Router** - Forwards request to Raspberry Pi (IP 192.168.1.173)
4. **Raspberry Pi** - Receives request on port 80
5. **Nginx** - Web server receives the request
6. **Nginx** - Checks `/var/www/parental_wifi/public/` directory
7. **Nginx** - Sees it's a Laravel route, sends to `index.php`
8. **PHP-FPM** - Executes Laravel PHP code
9. **Laravel** - Processes the route, connects to MariaDB
10. **MariaDB** - Returns user data, quiz data, etc.
11. **Laravel** - Renders HTML using Blade templates
12. **PHP-FPM** - Returns generated HTML to Nginx
13. **Nginx** - Sends HTML response back through network
14. **Browser** - Receives HTML and displays the login page

#### Visual Flow Diagram

```
[Your Phone/Laptop]
       |
       | HTTP Request: http://192.168.1.173/login
       |
       v
[Your Router/Wi-Fi]
       |
       | Routes to IP 192.168.1.173
       |
       v
[Raspberry Pi]
       |
       | Port 80
       |
       v
[Nginx Web Server]
       |
       | PHP files
       |
       v
[PHP-FPM]
       |
       | SQL queries
       |
       v
[MariaDB Database]
       |
       | Returns data
       |
       v
[PHP-FPM] -> [Laravel] -> [Nginx] -> [Your Phone/Laptop]
                    (HTML Response)
```


### Summary: What We Installed

| Component | Purpose | How to Access |
|-----------|---------|---------------|
| **Nginx** | Web server - receives HTTP requests | Listens on port 80, serves your app |
| **PHP 8.4 + PHP-FPM** | Runs PHP code - executes Laravel | Processes PHP requests via socket |
| **MariaDB** | Database - stores all data | Laravel connects via MySQL driver |
| **Composer** | PHP package manager | Installed Laravel dependencies |
| **Node.js + npm** | Frontend build tools | Built CSS/JS assets |
| **Git** | Version control | Cloned code from GitHub |

---

### Complete Setup Sequence (Quick Reference)

Here are all the commands in order:

```bash
# 1. Install Nginx
sudo apt update
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# 2. Install PHP and PHP-FPM
sudo apt install -y php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-cli
sudo systemctl start php8.4-fpm
sudo systemctl enable php8.4-fpm

# 3. Install MariaDB
sudo apt install -y mariadb-server
sudo systemctl start mariadb
sudo systemctl enable mariadb

# 4. Create database and user
sudo mysql -u root
# Then run: CREATE USER 'parental_wifi_user'@'localhost' IDENTIFIED BY 'password';
# CREATE DATABASE parental_wifi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# GRANT ALL PRIVILEGES ON parental_wifi.* TO 'parental_wifi_user'@'localhost';
# FLUSH PRIVILEGES; EXIT;

# 5. Install Git and set up SSH keys
sudo apt install -y git
ssh-keygen -t ed25519 -C "your-email@example.com"
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
cat ~/.ssh/id_ed25519.pub  # Copy this and add to GitHub
sudo mkdir -p /root/.ssh
sudo cp ~/.ssh/id_ed25519* /root/.ssh/
sudo chmod 600 /root/.ssh/id_ed25519

# 6. Clone repository
cd /var/www
sudo git clone git@github.com:Cristhan11/Parental_Wifi.git parental_wifi

# 7. Install Composer
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# 8. Install PHP dependencies
cd /var/www/parental_wifi
sudo chown -R snasna:snasna /var/www/parental_wifi
composer install --no-dev --optimize-autoloader
sudo chown -R www-data:www-data /var/www/parental_wifi

# 9. Install Node.js and build assets
sudo apt install -y nodejs npm
sudo chown -R snasna:snasna /var/www/parental_wifi
npm install
npm run build
sudo chown -R www-data:www-data /var/www/parental_wifi

# 10. Configure Nginx
sudo nano /etc/nginx/sites-available/parental_wifi
# Add Nginx configuration (see Part 8)
sudo ln -s /etc/nginx/sites-available/parental_wifi /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

# 11. Configure environment
cd /var/www/parental_wifi
sudo cp .env.example .env
sudo php artisan key:generate
sudo nano .env  # Update database credentials and settings

# 12. Set permissions
sudo chown -R www-data:www-data /var/www/parental_wifi
sudo chmod -R 755 /var/www/parental_wifi
sudo chmod -R 775 /var/www/parental_wifi/storage
sudo chmod -R 775 /var/www/parental_wifi/bootstrap/cache

# 13. Run migrations and seeders
php artisan migrate
php artisan db:seed

# 14. Create storage symlink
sudo -u www-data php artisan storage:link

# 15. Cache configuration
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 16. Get IP address
hostname -I

# 17. Test from browser
# Open http://<raspberry-pi-ip>/login on another device
```

---

### Troubleshooting Common Setup Issues

#### Issue: Filesystem Errors (I/O Errors, Read-Only)

**Symptoms:** `Input/output error`, `Permission denied`, system becomes read-only

**Causes:**
- SD card failure or corruption
- Power loss during writes
- Bad USB-to-SATA adapter

**Solutions:**
```bash
# Check filesystem status
mount | grep sda2

# If read-only, try to remount
sudo mount -o remount,rw /

# Check for errors
dmesg | tail -30

# If persistent, replace SD card/USB adapter
```

#### Issue: Permission Denied Errors

**Symptoms:** `Permission denied`, can't create files, can't write to directories

**Solutions:**
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/parental_wifi

# Fix permissions
sudo chmod -R 755 /var/www/parental_wifi
sudo chmod -R 775 /var/www/parental_wifi/storage
sudo chmod -R 775 /var/www/parental_wifi/bootstrap/cache
```

#### Issue: Database Connection Failed

**Symptoms:** `SQLSTATE[HY000] [1698] Access denied`, can't connect to database

**Solutions:**
```bash
# Check MariaDB is running
sudo systemctl status mariadb

# Verify database user exists
sudo mysql -u root
SELECT User, Host FROM mysql.user WHERE User = 'parental_wifi_user';
EXIT;

# Check .env database credentials
sudo grep DB_ /var/www/parental_wifi/.env
```

#### Issue: Vite Manifest Not Found

**Symptoms:** `500 error`, `Vite manifest not found at: /var/www/parental_wifi/public/build/manifest.json`

**Solutions:**
```bash
# Build frontend assets
cd /var/www/parental_wifi
sudo chown -R snasna:snasna /var/www/parental_wifi
npm install
npm run build
sudo chown -R www-data:www-data /var/www/parental_wifi
```

#### Issue: SSH Key Authentication Failed

**Symptoms:** `Permission denied (publickey)` when cloning

**Solutions:**
```bash
# Verify SSH key is added to GitHub
ssh -T git@github.com

# If fails, regenerate and add to GitHub
ssh-keygen -t ed25519 -C "your-email@example.com"
cat ~/.ssh/id_ed25519.pub  # Add this to GitHub

# Copy to root if using sudo
sudo mkdir -p /root/.ssh
sudo cp ~/.ssh/id_ed25519* /root/.ssh/
sudo chmod 600 /root/.ssh/id_ed25519
```

#### Issue: Service Not Starting

**Symptoms:** Nginx, PHP-FPM, or MariaDB won't start

**Solutions:**
```bash
# Check service status
sudo systemctl status nginx --no-pager
sudo systemctl status php8.4-fpm --no-pager
sudo systemctl status mariadb --no-pager

# Check error logs
sudo journalctl -u nginx -n 50
sudo journalctl -u php8.4-fpm -n 50
sudo journalctl -u mariadb -n 50

# Try starting manually
sudo systemctl start nginx
sudo systemctl start php8.4-fpm
sudo systemctl start mariadb
```

#### Issue: Can't Access from Other Devices

**Symptoms:** Website works on Pi but not from phone/laptop

**Solutions:**
```bash
# Verify IP address
hostname -I

# Check Nginx is listening on all interfaces
sudo netstat -tlnp | grep :80

# Check firewall (if enabled)
sudo ufw status

# Verify devices are on same network
# Check router settings if needed
```

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

