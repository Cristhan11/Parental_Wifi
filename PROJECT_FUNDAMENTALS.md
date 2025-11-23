# Project Fundamentals - Complete Beginner's Guide

## Table of Contents
1. [What This Project Does](#what-this-project-does)
2. [Laravel Framework Basics](#laravel-framework-basics)
3. [Project Architecture](#project-architecture)
4. [Database Structure](#database-structure)
5. [How Files Connect](#how-files-connect)
6. [Authentication System](#authentication-system)
7. [Request Flow](#request-flow)
8. [Key Concepts for Debugging](#key-concepts-for-debugging)
9. [Common File Locations](#common-file-locations)
10. [Troubleshooting Guide](#troubleshooting-guide)

---

## What This Project Does

### The Big Picture

Imagine you're a parent who wants to:
- Control how long your child can use the internet
- Make sure they learn something (quiz/video) before getting more internet time
- Monitor what websites they visit
- Block inappropriate websites
- Set schedules for when they can use the internet

**This project does all of that!**

### How It Works (Simple Version)

1. **Raspberry Pi** = A small computer that creates a WiFi network
2. **Child's Device** = Connects to the Raspberry Pi's WiFi
3. **Laravel Application** = The "brain" that controls everything
4. **Database** = Stores all information (devices, time, quizzes, etc.)

**The Flow:**
```
Child's Device → Connects to Raspberry Pi WiFi → 
Laravel checks time → If time expired → 
Redirects to quiz/video → Child completes → 
Gets more time → Can use internet again
```

---

## Laravel Framework Basics

### What is Laravel?

Laravel is a PHP framework - think of it as a **toolkit** that makes building websites easier. It provides:
- **Routing** - Maps URLs to code
- **Database** - Easy way to work with databases
- **Authentication** - Login/logout system
- **Views** - HTML templates
- **Security** - Built-in protection

### MVC Pattern (Model-View-Controller)

Laravel uses **MVC** pattern. Think of it like a restaurant:

- **Model** = The Kitchen (Database)
  - Stores and retrieves data
  - Example: `User`, `Device`, `Quiz` models
  
- **View** = The Menu (What user sees)
  - HTML pages, forms, buttons
  - Example: `login.blade.php`, `dashboard.blade.php`
  
- **Controller** = The Waiter (Logic)
  - Handles requests, processes data
  - Example: `AuthenticatedSessionController`, `DeviceController`

**How They Work Together:**
```
User clicks button (View) → 
Controller receives request → 
Controller asks Model for data → 
Model gets data from database → 
Controller sends data to View → 
View displays to user
```

### Key Laravel Concepts

#### 1. Routes (`routes/web.php`, `routes/auth.php`)

**What it is:** Maps URLs to code

**Example:**
```php
Route::get('/login', [AuthenticatedSessionController::class, 'create']);
```

**Translation:** "When someone visits `/login`, run the `create()` method in `AuthenticatedSessionController`"

**In your project:**
- `/login` → Shows login form
- `/dashboard` → Shows dashboard (if logged in)
- `/register` → **Disabled** (returns 404 for security)

#### 2. Controllers (`app/Http/Controllers/`)

**What it is:** Contains the logic (the "waiter")

**Example:**
```php
class AuthenticatedSessionController {
    public function create() {
        return view('auth.login');  // Show login page
    }
    
    public function store(LoginRequest $request) {
        // Validate login
        // Check password
        // Log user in
        // Redirect to dashboard
    }
}
```

**In your project:**
- `AuthenticatedSessionController` - Handles login/logout
- `RegisteredUserController` - Handles registration (disabled for public)
- `ProfileController` - Handles user profile

#### 3. Models (`app/Models/`)

**What it is:** Represents database tables

**Example:**
```php
class User extends Model {
    // This represents the 'users' table in database
    // Can do: User::find(1), User::create([...]), etc.
}
```

**In your project:**
- `User` - Represents parents/admins
- `Device` - Represents child devices
- `Quiz` - Represents quizzes
- `Video` - Represents educational videos
- And 10+ more models

#### 4. Views (`resources/views/`)

**What it is:** HTML templates (what users see)

**Example:**
```blade
<x-guest-layout>
    <h2>Login</h2>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <input type="email" name="email">
        <button>Log in</button>
    </form>
</x-guest-layout>
```

**In your project:**
- `auth/login.blade.php` - Login page
- `auth/register.blade.php` - Registration (exists but route disabled)
- `dashboard.blade.php` - Main dashboard
- `layouts/guest.blade.php` - Layout for auth pages

#### 5. Migrations (`database/migrations/`)

**What it is:** Creates database tables

**Example:**
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
});
```

**Translation:** "Create a table called 'users' with these columns"

**In your project:**
- 17 migration files create all database tables
- Run with: `php artisan migrate`

#### 6. Middleware (`app/Http/Middleware/`)

**What it is:** Code that runs BEFORE the controller

**Example:**
```php
Route::get('/dashboard', ...)->middleware('auth');
```

**Translation:** "Before showing dashboard, check if user is logged in"

**In your project:**
- `auth` middleware - Checks if logged in
- `role.parent` middleware - Checks if user is a parent
- `role.admin` middleware - Checks if user is an admin

---

## Project Architecture

### The Three Layers

```
┌─────────────────────────────────────┐
│   FRONTEND (What Users See)         │
│   - Blade Templates                 │
│   - Forms, Buttons, Pages           │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   LARAVEL APPLICATION (Logic)        │
│   - Controllers                      │
│   - Models                           │
│   - Services                         │
│   - Middleware                       │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   DATABASE (Storage)                 │
│   - MariaDB                          │
│   - Tables: users, devices, etc.    │
└─────────────────────────────────────┘
```

### How Laravel Controls Raspberry Pi

**Important:** Laravel runs ON the Raspberry Pi, so it can execute Linux commands directly.

```
Laravel Controller → 
Calls Shell Script → 
Shell Script runs Linux command → 
System changes (block device, redirect, etc.)
```

**Example:**
```php
// In a controller
exec('sudo iptables -A INPUT -m mac --mac-source AA:BB:CC:DD:EE:FF -j DROP');
// This blocks a device from internet
```

---

## Database Structure

### Main Tables and What They Store

#### 1. `users` Table
**Purpose:** Stores parent/admin accounts

**Columns:**
- `id` - Unique ID
- `name` - User's name
- `email` - Login email
- `password` - Hashed password
- `role` - 'parent' or 'admin'

**Example Data:**
```
id: 1
name: "System Administrator"
email: "admin@parentalwifi.local"
password: "$2y$10$..." (hashed)
role: "admin"
```

#### 2. `devices` Table
**Purpose:** Stores child devices

**Columns:**
- `id` - Unique ID
- `user_id` - Which parent owns this device
- `name` - Device name (e.g., "John's iPhone")
- `mac_address` - Device's MAC address (unique identifier)
- `status` - 'active', 'blocked', 'whitelisted'
- `remaining_time_minutes` - How much time left
- `total_time_allocated` - Total time given

**Example Data:**
```
id: 1
user_id: 1
name: "Sarah's Tablet"
mac_address: "AA:BB:CC:DD:EE:FF"
status: "active"
remaining_time_minutes: 15
total_time_allocated: 60
```

#### 3. `quizzes` Table
**Purpose:** Stores quizzes created by parents

**Columns:**
- `id` - Unique ID
- `user_id` - Which parent created it
- `title` - Quiz name
- `questions` - JSON with questions/answers
- `passing_score` - Minimum score to pass
- `time_reward_minutes` - Time granted if passed

**Example Data:**
```
id: 1
user_id: 1
title: "Math Quiz"
questions: {"q1": {"question": "2+2?", "answer": "4"}}
passing_score: 70
time_reward_minutes: 15
```

#### 4. `videos` Table
**Purpose:** Stores educational videos

**Columns:**
- `id` - Unique ID
- `user_id` - Which parent added it
- `title` - Video name
- `video_path` - File location
- `duration_seconds` - Video length
- `time_reward_minutes` - Time granted if completed
- `dictionary_words_enabled` - Show words during video?

**And 10+ more tables** for:
- Quiz attempts
- Video completions
- Blocked websites
- Flagged websites
- Schedules
- Browsing logs
- etc.

### Database Relationships

**Think of relationships like connections between tables:**

#### One-to-Many (1-to-Many)

**Example:** One User has Many Devices
```
User (Parent) ──has many──> Devices
    John                    ├─ Sarah's Tablet
                            ├─ Mike's Phone
                            └─ Emma's Laptop
```

**In Code:**
```php
$user = User::find(1);
$devices = $user->devices;  // Gets all devices for this user
```

#### Many-to-Many (Many-to-Many)

**Example:** Devices can have Many Quizzes, Quizzes can be for Many Devices
```
Device ──can take──> Quiz
  Device 1 ────────── Quiz A
  Device 1 ────────── Quiz B
  Device 2 ────────── Quiz A
```

**In Code:**
```php
$device = Device::find(1);
$quizzes = $device->quizzes;  // Gets all quizzes for this device
```

---

## How Files Connect

### Complete Request Flow Example

**User visits `/login`:**

```
1. Browser: http://localhost/login
   ↓
2. routes/auth.php: Route::get('login', ...)
   ↓
3. AuthenticatedSessionController@create
   ↓
4. Returns view('auth.login')
   ↓
5. resources/views/auth/login.blade.php
   ↓
6. Extends layouts/guest.blade.php
   ↓
7. Uses components (x-text-input, x-primary-button)
   ↓
8. Browser displays login form
```

**User submits login form:**

```
1. Browser: POST /login (with email/password)
   ↓
2. routes/auth.php: Route::post('login', ...)
   ↓
3. AuthenticatedSessionController@store
   ↓
4. LoginRequest validates input
   ↓
5. Auth::attempt() checks database
   ↓
6. User model finds matching user
   ↓
7. Password verified → Create session
   ↓
8. Redirect to /dashboard
   ↓
9. Dashboard shows (if middleware allows)
```

### File Structure Explained

```
parental_wifi/
│
├── app/                          # Application code
│   ├── Http/
│   │   ├── Controllers/          # Controllers (logic)
│   │   │   ├── Auth/            # Authentication controllers
│   │   │   └── ProfileController.php
│   │   └── Middleware/           # Middleware (guards)
│   │       ├── EnsureUserIsParent.php
│   │       └── EnsureUserIsAdmin.php
│   └── Models/                   # Models (database)
│       ├── User.php
│       ├── Device.php
│       ├── Quiz.php
│       └── ... (14 models total)
│
├── database/
│   ├── migrations/               # Database table definitions
│   │   ├── create_users_table.php
│   │   ├── create_devices_table.php
│   │   └── ... (17 migrations)
│   └── seeders/                 # Initial data
│       ├── DatabaseSeeder.php
│       ├── DefaultUserSeeder.php
│       └── DictionaryWordSeeder.php
│
├── resources/
│   ├── views/                   # HTML templates
│   │   ├── auth/
│   │   │   ├── login.blade.php
│   │   │   └── register.blade.php
│   │   ├── layouts/
│   │   │   ├── app.blade.php
│   │   │   └── guest.blade.php
│   │   ├── components/          # Reusable UI pieces
│   │   │   ├── primary-button.blade.php
│   │   │   └── text-input.blade.php
│   │   └── dashboard.blade.php
│   └── css/
│       └── app.css             # Tailwind CSS config
│
├── routes/
│   ├── web.php                 # Main routes
│   └── auth.php                # Authentication routes
│
├── bootstrap/
│   └── app.php                 # Application configuration
│
└── .env                        # Environment variables (database, etc.)
```

---

## Authentication System

### How Authentication Works

#### 1. Login Process

```
User enters email/password
    ↓
LoginRequest validates input
    ↓
Auth::attempt() checks database
    ↓
If correct:
    - Create session (store user ID)
    - Set cookie in browser
    - User is "logged in"
    ↓
Redirect to dashboard
```

#### 2. Session Management

**What is a session?**
- Like a "temporary ID card" stored in browser
- Contains user's ID
- Laravel uses it to remember who's logged in

**How it works:**
```php
// When user logs in
Auth::login($user);
// Laravel stores user ID in session

// On next request
auth()->check();  // Checks if session exists
auth()->user();   // Gets user from session
```

#### 3. Middleware Protection

**Example:**
```php
Route::get('/dashboard', ...)->middleware('auth');
```

**What happens:**
1. User visits `/dashboard`
2. `auth` middleware runs FIRST
3. Checks: Is user logged in?
   - If YES → Continue to controller
   - If NO → Redirect to `/login`

#### 4. Role-Based Access

**Example:**
```php
Route::get('/devices', ...)->middleware(['auth', 'role.parent']);
```

**What happens:**
1. `auth` middleware checks login
2. `role.parent` middleware checks role
3. If user is parent → Allow access
4. If user is admin → Block (403 error)
5. If not logged in → Redirect to login

### Default Admin Account

**Created by:** `DefaultUserSeeder`

**Credentials:**
- Email: `admin@parentalwifi.local`
- Password: `admin123`
- Role: `admin`

**Security:**
- Public registration is **disabled**
- Only this account exists initially
- New accounts must be created by admins (future feature)

---

## Request Flow

### Complete Example: User Logs In

```
┌─────────────────────────────────────────────────────────┐
│ STEP 1: User visits /login                              │
│ Browser sends: GET http://localhost/login               │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ STEP 2: Route matches                                    │
│ routes/auth.php finds:                                   │
│ Route::get('login', [AuthenticatedSessionController...]) │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ STEP 3: Middleware checks                               │
│ 'guest' middleware: Is user logged in?                   │
│ If YES → Redirect to dashboard                           │
│ If NO → Continue                                         │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ STEP 4: Controller runs                                 │
│ AuthenticatedSessionController@create()                 │
│ return view('auth.login');                              │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ STEP 5: View renders                                    │
│ resources/views/auth/login.blade.php                    │
│ Uses layout: layouts/guest.blade.php                    │
│ Uses components: x-text-input, x-primary-button        │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ STEP 6: HTML sent to browser                            │
│ User sees login form                                    │
└─────────────────────────────────────────────────────────┘
```

### User Submits Login Form

```
┌─────────────────────────────────────────────────────────┐
│ STEP 1: User submits form                               │
│ Browser sends: POST /login (with email/password)       │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ STEP 2: Route matches                                    │
│ Route::post('login', [AuthenticatedSessionController...])│
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ STEP 3: Request validation                              │
│ LoginRequest validates:                                 │
│ - Email format correct?                                 │
│ - Password provided?                                    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ STEP 4: Authentication                                  │
│ Auth::attempt(['email' => ..., 'password' => ...])     │
│ - Finds user in database                                │
│ - Checks password hash                                  │
│ - If correct: Creates session                          │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│ STEP 5: Redirect                                        │
│ return redirect()->route('dashboard');                  │
│ Browser goes to /dashboard                              │
└─────────────────────────────────────────────────────────┘
```

---

## Key Concepts for Debugging

### 1. Error Types

#### 404 Not Found
**Meaning:** Route doesn't exist or file not found

**Common causes:**
- Typo in URL
- Route not defined
- File doesn't exist

**How to debug:**
```bash
php artisan route:list  # See all routes
```

#### 500 Internal Server Error
**Meaning:** PHP code has an error

**Common causes:**
- Syntax error
- Missing file/class
- Database connection failed

**How to debug:**
```bash
tail -f storage/logs/laravel.log  # Check error log
```

#### 403 Forbidden
**Meaning:** Access denied

**Common causes:**
- Wrong role (not parent/admin)
- Middleware blocking access

**How to debug:**
- Check middleware in route
- Check user's role in database

### 2. Common Debugging Commands

```bash
# Check routes
php artisan route:list

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check if user exists
>>> App\Models\User::where('email', 'admin@parentalwifi.local')->first();

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check migrations
php artisan migrate:status

# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed
```

### 3. Database Debugging

```php
// In tinker or controller
// Check if table exists
DB::table('users')->count();

// See all users
App\Models\User::all();

// Find specific user
$user = App\Models\User::find(1);
$user->email;

// Check relationships
$user = App\Models\User::find(1);
$user->devices;  // Should return devices
```

### 4. View Debugging

**Check if view exists:**
```bash
ls resources/views/auth/login.blade.php
```

**Check for syntax errors:**
```bash
php artisan view:clear
php artisan view:cache
```

**Common Blade errors:**
- Missing `@csrf` in forms
- Undefined variable
- Missing component

### 5. Authentication Debugging

```php
// Check if user is logged in
auth()->check();  // true or false

// Get logged-in user
auth()->user();  // User model or null

// Check user role
auth()->user()->isParent();  // true or false
auth()->user()->isAdmin();   // true or false

// Manually log in (for testing)
$user = App\Models\User::find(1);
Auth::login($user);
```

---

## Common File Locations

### When You Need to...

**Add a new route:**
→ `routes/web.php` or `routes/auth.php`

**Add a new page:**
→ `resources/views/` (create new `.blade.php` file)

**Add database table:**
→ `database/migrations/` (create new migration)

**Add business logic:**
→ `app/Http/Controllers/` (create new controller)

**Add database model:**
→ `app/Models/` (create new model)

**Add security check:**
→ `app/Http/Middleware/` (create new middleware)

**Change styling:**
→ `resources/css/app.css` (Tailwind config)

**Change layout:**
→ `resources/views/layouts/` (app.blade.php or guest.blade.php)

**Change environment:**
→ `.env` file (database credentials, etc.)

---

## Troubleshooting Guide

### Problem: "Route not found"

**Check:**
1. Does route exist in `routes/web.php` or `routes/auth.php`?
2. Run `php artisan route:list` to see all routes
3. Clear route cache: `php artisan route:clear`

### Problem: "Class not found"

**Check:**
1. Is file in correct namespace?
2. Is class name correct?
3. Run `composer dump-autoload`

### Problem: "Database connection failed"

**Check:**
1. Is MariaDB running? `sudo systemctl status mariadb`
2. Check `.env` file credentials
3. Does database exist? `mysql -u root -p` then `SHOW DATABASES;`

### Problem: "Permission denied"

**Check:**
1. File permissions: `chmod -R 755 storage/`
2. Ownership: `chown -R www-data:www-data storage/`
3. Check `storage/logs/laravel.log` for details

### Problem: "Can't log in"

**Check:**
1. Does user exist? `App\Models\User::where('email', '...')->first()`
2. Is password correct? (check in database - it's hashed)
3. Check `storage/logs/laravel.log` for errors
4. Try default admin: `admin@parentalwifi.local` / `admin123`

### Problem: "Styles not loading"

**Check:**
1. Run `npm run build` to compile CSS
2. Check `public/build/` directory exists
3. Clear cache: `php artisan view:clear`

---

## Summary: The Big Picture

### What You've Built So Far

1. **Database Structure** ✅
   - 17 tables for all features
   - Relationships between tables
   - Migrations to create tables

2. **Models** ✅
   - 15 models representing database tables
   - Relationships (hasMany, belongsTo, etc.)
   - Helper methods (isParent(), hasRemainingTime(), etc.)

3. **Authentication** ✅
   - Login system
   - Default admin account
   - Role-based access (parent/admin)
   - Public registration disabled (security)

4. **Views** ✅
   - Login page
   - Dashboard
   - Layouts and components
   - Custom design (Montserrat font, color palette)

### What's Next

1. **Time Tracking Service** - Monitor device time
2. **Quiz System** - Create and take quizzes
3. **Video System** - Upload and watch videos
4. **Captive Portal** - Redirect expired devices
5. **Device Management** - Add/edit devices
6. **And more...**

### Key Takeaways

1. **Laravel = MVC Pattern**
   - Models (database)
   - Views (HTML)
   - Controllers (logic)

2. **Routes = URLs → Code**
   - Maps browser requests to controllers

3. **Middleware = Guards**
   - Runs before controllers
   - Checks authentication, roles, etc.

4. **Database = Storage**
   - Tables store data
   - Models access data
   - Relationships connect tables

5. **Authentication = Sessions**
   - Login creates session
   - Session remembers user
   - Middleware checks session

---

**Remember:** When debugging, always check:
1. Error logs: `storage/logs/laravel.log`
2. Routes: `php artisan route:list`
3. Database: Use tinker to check data
4. Cache: Clear all caches if issues persist

This foundation will help you understand and debug the project as it grows!

