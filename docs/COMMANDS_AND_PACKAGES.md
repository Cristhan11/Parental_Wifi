# Commands and Packages Explanation

## Commands Run During Implementation

### 1. `composer require laravel/breeze --dev`

**What it does:**
- Installs Laravel Breeze package via Composer (PHP package manager)
- `--dev` flag means it's only needed during development, not in production

**What it installs:**
- Laravel Breeze package files
- Authentication scaffolding code
- All necessary dependencies

**Files affected:**
- `composer.json` - Package is added to dependencies
- `composer.lock` - Locks specific versions
- `vendor/` directory - Package files are downloaded here

**Why we need it:**
- Provides ready-made authentication system
- Saves hours of coding from scratch
- Follows Laravel best practices

---

### 2. `php artisan breeze:install blade --no-interaction`

**What it does:**
- Runs Breeze installation command
- `blade` = Use Blade templates (not React/Vue)
- `--no-interaction` = Don't ask questions, use defaults

**What it creates:**
- Authentication controllers (Login, Register, Password Reset, etc.)
- Authentication views (login.blade.php, register.blade.php, etc.)
- Authentication routes (routes/auth.php)
- Layout templates (guest.blade.php, app.blade.php)
- Reusable components (buttons, inputs, labels)
- Middleware configuration

**Also runs:**
- `npm install` - Installs JavaScript dependencies
- `npm run build` - Builds CSS and JavaScript assets

**Files created:**
- `app/Http/Controllers/Auth/*.php` - All auth controllers
- `resources/views/auth/*.blade.php` - All auth views
- `resources/views/layouts/*.blade.php` - Layout templates
- `resources/views/components/*.blade.php` - UI components
- `routes/auth.php` - Authentication routes

---

### 3. `npm install tailwindcss@^4.0.0 --save-dev`

**What it does:**
- Installs Tailwind CSS version 4.0.0
- `--save-dev` = Development dependency (not needed in production)

**Why we needed it:**
- Breeze installed Tailwind v3, but our project uses v4
- Version mismatch was causing build errors
- Updated to match our existing Tailwind v4 setup

**Files affected:**
- `package.json` - Tailwind version updated
- `node_modules/` - Tailwind files downloaded

---

### 4. `npm run build`

**What it does:**
- Runs the build script defined in `package.json`
- Compiles CSS and JavaScript
- Processes Tailwind classes
- Generates optimized production files

**Process:**
1. Vite reads `resources/css/app.css`
2. Tailwind scans all Blade files for classes
3. Generates CSS with only used classes
4. Outputs to `public/build/assets/app-*.css`
5. Also compiles JavaScript to `public/build/assets/app-*.js`

**Files created:**
- `public/build/manifest.json` - Asset manifest
- `public/build/assets/app-*.css` - Compiled CSS (43KB)
- `public/build/assets/app-*.js` - Compiled JavaScript (80KB)

**Why we need it:**
- Tailwind classes need to be compiled to actual CSS
- Browser can't read Tailwind classes directly
- Build process creates optimized, minified files

---

## Packages Installed

### Laravel Breeze (`laravel/breeze`)

**Type:** PHP Package (Composer)

**Purpose:** Authentication scaffolding for Laravel

**What it provides:**
- Complete authentication system
- Login, registration, password reset
- Email verification
- Session management
- CSRF protection

**Files it creates:**
- Controllers for all auth actions
- Views for all auth pages
- Routes for all auth URLs
- Components for reusable UI

**Why we use it:**
- Industry standard for Laravel auth
- Secure by default
- Easy to customize
- Well-maintained

---

### Tailwind CSS (`tailwindcss`)

**Type:** JavaScript Package (npm)

**Purpose:** Utility-first CSS framework

**What it provides:**
- Utility classes (e.g., `bg-yellow-500`, `text-black`, `p-4`)
- Custom color system
- Responsive design utilities
- Build-time CSS generation

**How it works:**
- You write HTML with Tailwind classes
- Tailwind scans your files
- Generates CSS with only classes you use
- Outputs optimized CSS file

**Example:**
```html
<!-- HTML with Tailwind classes -->
<button class="bg-yellow-500 text-black px-4 py-2 rounded">
    Click Me
</button>

<!-- Tailwind generates CSS -->
.button {
    background-color: #FFDE15;
    color: #000000;
    padding: 1rem;
    border-radius: 0.25rem;
}
```

**Why we use it:**
- Fast development (no custom CSS needed)
- Consistent design system
- Responsive by default
- Small file size (only used classes)

---

### @tailwindcss/vite (`@tailwindcss/vite`)

**Type:** JavaScript Package (npm)

**Purpose:** Vite plugin for Tailwind CSS v4

**What it does:**
- Integrates Tailwind with Vite build tool
- Processes Tailwind during build
- Replaces old PostCSS approach

**Configuration:**
- Added to `vite.config.js`
- Processes `resources/css/app.css`
- Outputs compiled CSS

**Why we use it:**
- Required for Tailwind v4
- Faster than PostCSS
- Better integration with Vite

---

### Alpine.js (`alpinejs`)

**Type:** JavaScript Package (npm)

**Purpose:** Lightweight JavaScript framework

**What it provides:**
- Interactive UI components
- Dropdown menus
- Mobile navigation toggle
- Form interactions

**How it's used:**
- In navigation.blade.php: `<nav x-data="{ open: false }">`
- Toggles mobile menu
- Handles dropdown interactions

**Why we use it:**
- Included with Breeze
- Lightweight (small file size)
- No build step needed
- Perfect for simple interactions

---

## File Relationships

### How Commands Affect Files

```
composer require laravel/breeze
    ↓
Updates composer.json
    ↓
Downloads to vendor/laravel/breeze/
    ↓
php artisan breeze:install
    ↓
Creates controllers, views, routes
    ↓
npm install (runs automatically)
    ↓
Downloads JavaScript packages
    ↓
npm run build
    ↓
Compiles CSS/JS to public/build/
```

### Package Dependencies

```
Laravel Breeze
    ├── Requires Laravel Framework
    ├── Uses Blade Templates
    ├── Includes Tailwind CSS
    └── Includes Alpine.js

Tailwind CSS
    ├── Requires PostCSS (or Vite plugin)
    ├── Scans Blade files
    └── Generates CSS

Vite
    ├── Bundles CSS
    ├── Bundles JavaScript
    └── Serves assets in development
```

---

## Build Process Explained

### Development Mode

**Command:** `npm run dev`

**What happens:**
1. Vite starts development server
2. Watches for file changes
3. Recompiles CSS/JS on save
4. Serves assets with hot reload

**Use when:** Actively developing

---

### Production Mode

**Command:** `npm run build`

**What happens:**
1. Vite processes all files
2. Tailwind scans for classes
3. Generates optimized CSS
4. Minifies JavaScript
5. Outputs to `public/build/`

**Use when:** Deploying to production

---

## NoDogSplash Installation Commands

### Installation from Source

NoDogSplash is not available in default Raspberry Pi repositories, so it must be compiled from source:

```bash
# Install dependencies
sudo apt update
sudo apt install -y build-essential git libmicrohttpd-dev libnl-3-dev libnl-genl-3-dev libjson-c-dev

# Clone repository
cd ~
git clone https://github.com/nodogsplash/nodogsplash.git
cd nodogsplash

# Compile and install
make
sudo make install
```

### Verification

```bash
# Check installation
which nodogsplash
nodogsplash -version

# Expected output: /usr/bin/nodogsplash and version number (e.g., 5.0.2)
```

### Systemd Service Setup

```bash
# Create service file
sudo nano /etc/systemd/system/nodogsplash.service

# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable nodogsplash
sudo systemctl start nodogsplash

# Check status
sudo systemctl status nodogsplash
```

### Configuration

```bash
# Edit configuration file
sudo nano /etc/nodogsplash/nodogsplash.conf

# Required settings:
# GatewayInterface wlan0
# GatewayAddress 192.168.4.1
# RedirectURL http://192.168.4.1/portal
```

For complete setup instructions, see `docs/NODOGSPLASH_SETUP.md`.

---

## Summary

**Commands Summary:**
1. `composer require laravel/breeze` - Installed auth package
2. `php artisan breeze:install blade` - Created auth files
3. `npm install tailwindcss@^4.0.0` - Updated Tailwind version
4. `npm run build` - Compiled assets
5. NoDogSplash installation commands (see above)

**Packages Summary:**
- **Laravel Breeze** - Authentication system
- **Tailwind CSS** - Styling framework
- **@tailwindcss/vite** - Build integration
- **Alpine.js** - Interactive components
- **NoDogSplash** - Captive portal solution (installed from source)

**Result:**
- Complete authentication system
- Custom design matching color palette
- All assets compiled and ready
- NoDogSplash captive portal integration
- Ready for development and production

