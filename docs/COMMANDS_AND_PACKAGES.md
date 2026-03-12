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

### Laravel Reverb (`laravel/reverb`)

**Type:** PHP Package (Composer)

**Purpose:** Self-hosted WebSocket server for Laravel

**What it provides:**
- A long-running PHP process that acts as a WebSocket server
- Speaks the Pusher WebSocket protocol so it works with `pusher-js` and Laravel Echo
- Private channel authentication integrated with Laravel's session/auth system
- Scales well on a Raspberry Pi — no cloud dependencies

**How it works:**
- Laravel fires broadcast events (`ShouldBroadcastNow`)
- Laravel sends the event payload to Reverb via an internal HTTP call
- Reverb pushes the payload to all browser connections subscribed to that channel

**Configuration files:**
- `config/reverb.php` — app ID, key, secret, host, port
- `.env` — `REVERB_*` and `VITE_REVERB_*` variables

**Why we use it:**
- Free and self-hosted — no monthly fees, no external API
- Runs entirely on the Raspberry Pi LAN
- First-party Laravel package — well maintained and documented

---

### Laravel Echo (`laravel-echo`)

**Type:** JavaScript Package (npm)

**Purpose:** Browser-side WebSocket client for Laravel Broadcasting

**What it provides:**
- Clean API to subscribe to channels and listen to events
- Private channel support with automatic server-side authorization
- Transport-agnostic — works with Reverb, Pusher.com, Ably, Soketi

**How it's used:**
```javascript
// Subscribe to a private channel and listen to an event
window.Echo.private(`user.${userId}`)
    .listen('.device.connected', (event) => {
        addNotification(`${event.device_name} connected`, 'success');
    });
```

**Where it runs:** In the parent's browser — compiled into `public/build/assets/app-*.js` by `npm run build`. It does not run on the server.

**Why we use it:**
- Abstracts the raw WebSocket protocol behind a simple, readable API
- Handles private channel authentication automatically (posts to `/broadcasting/auth`)
- Maintained by the Laravel team alongside Reverb

---

### Pusher JS (`pusher-js`)

**Type:** JavaScript Package (npm)

**Purpose:** WebSocket transport layer used internally by Laravel Echo

**What it provides:**
- The actual WebSocket connection to the Reverb server
- Implements the Pusher WebSocket protocol that Reverb speaks
- Fallback transport support (WebSocket → HTTP long-poll if WS is unavailable)

**Important note:**
- Despite the name, no Pusher.com account or API key is needed
- Reverb implements the same wire protocol as Pusher, so the same JS client works
- You configure it to point to your own Reverb server instead of Pusher.com

**How it's referenced:**
```javascript
// bootstrap.js — must be set as a global before Echo initializes
import Pusher from 'pusher-js';
window.Pusher = Pusher;
```

**Why we use it:**
- Required by Laravel Echo when `broadcaster: 'reverb'` is configured
- Zero cost — reuses the Pusher protocol without the Pusher SaaS

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

Laravel Reverb (PHP — runs on server)
    ├── Requires Laravel Broadcasting
    ├── Speaks the Pusher WebSocket protocol
    └── Receives events from Laravel and pushes to browsers

Laravel Echo (JS — runs in browser)
    ├── Requires pusher-js as transport
    ├── Subscribes to private channels
    └── Calls /broadcasting/auth for channel authorization

pusher-js (JS — runs in browser)
    ├── Used internally by Laravel Echo
    ├── Opens the WebSocket connection to Reverb
    └── No Pusher.com account required
```

### WebSocket Event Flow

```
Background Job / Service / Model Hook
    │
    │  event(new DeviceConnected(...))
    ▼
Laravel Broadcasting (ShouldBroadcastNow)
    │
    │  HTTP call to Reverb server (internal)
    ▼
Reverb Server (php artisan reverb:start)
    │
    │  Pushes payload to subscribed connections
    ▼
Parent Browser (Laravel Echo + pusher-js)
    │
    │  .listen('.device.connected', handler)
    ▼
Dashboard notification panel updates in real time
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

## WebSocket Commands (Step 22 — Real-Time Broadcasting)

### 5. `composer require laravel/reverb`

**What it does:**
- Installs the Laravel Reverb package via Composer
- Reverb is the self-hosted WebSocket server built into the Laravel ecosystem

**What it installs:**
- The Reverb PHP server package
- All required dependencies (ReactPHP HTTP server, etc.)

**Files affected:**
- `composer.json` — `laravel/reverb` added to the `require` section
- `composer.lock` — version locked
- `vendor/laravel/reverb/` — package files downloaded

**Why we need it:**
- Provides a self-hosted WebSocket server that runs on the Raspberry Pi
- No external cloud services or paid accounts required
- Stays entirely on the local LAN — parent events never leave the network

---

### 6. `php artisan reverb:install --no-interaction`

**What it does:**
- Publishes Reverb's configuration files into the project
- Registers the broadcasting service provider
- `--no-interaction` = uses sensible defaults without prompting

**Files created:**
- `config/broadcasting.php` — defines all broadcast driver connections
- `config/reverb.php` — Reverb server settings (host, port, app key/secret)
- `routes/channels.php` — private channel authorization callbacks

**Files modified:**
- `bootstrap/app.php` — registers the channels route so Laravel loads it on boot

**Why we need it:**
- Without this, Laravel does not know how to route broadcast events
- The channel authorization file (`routes/channels.php`) must exist for private channels to work

---

### 7. `npm install --save-dev laravel-echo pusher-js`

**What it does:**
- Installs two JavaScript packages needed for the browser-side WebSocket client
- `--save-dev` = development dependency; compiled into the production bundle at build time

**What it installs:**
- `laravel-echo` — the JavaScript client that subscribes to channels and listens to events
- `pusher-js` — the underlying WebSocket transport library (Reverb speaks the Pusher protocol)

**Files affected:**
- `package.json` — both packages added to `devDependencies`
- `package-lock.json` — versions locked
- `node_modules/` — packages downloaded locally

**Why we need both:**
- `laravel-echo` provides the clean `.private().listen()` API used in the dashboard
- `pusher-js` is required internally by Echo when `broadcaster: 'reverb'` is set — Reverb uses the Pusher WebSocket protocol so no Pusher account is needed, but the JS client is still required

---

### 8. `php artisan reverb:start`

**What it does:**
- Starts the Reverb WebSocket server as a foreground process
- Listens on the host and port defined by `REVERB_SERVER_HOST` and `REVERB_SERVER_PORT` in `.env`

**Default behavior:**
- Binds to `0.0.0.0:8080` (all interfaces, port 8080)
- Accepts WebSocket connections from any device on the network

**Use when:** Local development or manual testing

**Production alternative (Raspberry Pi):**
- Run as a systemd service so it starts on boot and auto-restarts on failure
```bash
sudo systemctl enable parental-wifi-reverb.service
sudo systemctl start parental-wifi-reverb.service
```

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
1. `composer require laravel/breeze` — Installed auth package
2. `php artisan breeze:install blade` — Created auth files
3. `npm install tailwindcss@^4.0.0` — Updated Tailwind version
4. `npm run build` — Compiled assets
5. NoDogSplash installation commands (see above)
6. `composer require laravel/reverb` — Installed WebSocket server package
7. `php artisan reverb:install --no-interaction` — Published Reverb config and channel routes
8. `npm install --save-dev laravel-echo pusher-js` — Installed browser WebSocket client
9. `php artisan reverb:start` — Starts the Reverb WebSocket server (dev) or via systemd (production)

**Packages Summary:**
- **Laravel Breeze** — Authentication system
- **Tailwind CSS** — Styling framework
- **@tailwindcss/vite** — Build integration
- **Alpine.js** — Interactive components
- **NoDogSplash** — Captive portal solution (installed from source)
- **Laravel Reverb** (`laravel/reverb`) — Self-hosted WebSocket server (PHP, runs on RPi)
- **Laravel Echo** (`laravel-echo`) — Browser WebSocket client (compiled into JS bundle)
- **Pusher JS** (`pusher-js`) — WebSocket transport layer used internally by Echo

**Result:**
- Complete authentication system
- Custom design matching color palette
- All assets compiled and ready
- NoDogSplash captive portal integration
- Real-time parent dashboard with live WebSocket notifications
- Ready for development and production

