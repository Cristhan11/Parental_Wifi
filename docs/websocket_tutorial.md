# WebSocket Tutorial — Step 22: Real-Time Broadcasting with Laravel Reverb & Echo

> **Companion document:** See [`docs/websockets.md`](websockets.md) for the concise deployment guide,
> runtime commands, and verification checklist. This tutorial focuses on the *why*, *what*, and
> *how* of every decision made during the implementation.

---

## Table of Contents

1. [What Are WebSockets and Why Do We Need Them?](#1-what-are-websockets-and-why-do-we-need-them)
2. [The Traditional Alternative: Polling](#2-the-traditional-alternative-polling)
3. [What Is Laravel Echo?](#3-what-is-laravel-echo)
4. [What Is Laravel Reverb?](#4-what-is-laravel-reverb)
5. [How Echo and Reverb Work Together](#5-how-echo-and-reverb-work-together)
6. [Project Context: Why Step 22 Needs Real-Time](#6-project-context-why-step-22-needs-real-time)
7. [Architecture Overview](#7-architecture-overview)
8. [Step-by-Step Implementation](#8-step-by-step-implementation)
   - [Step 1: Install Dependencies](#step-1-install-dependencies)
   - [Step 2: Configure the Broadcasting Driver](#step-2-configure-the-broadcasting-driver)
   - [Step 3: Configure Reverb Environment Values](#step-3-configure-reverb-environment-values)
   - [Step 4: Register Private Channel Authorization](#step-4-register-private-channel-authorization)
   - [Step 5: Create Broadcast Event Classes](#step-5-create-broadcast-event-classes)
   - [Step 6: Dispatch Events from Backend Hook Points](#step-6-dispatch-events-from-backend-hook-points)
   - [Step 7: Initialize Echo in the Frontend](#step-7-initialize-echo-in-the-frontend)
   - [Step 8: Subscribe and Render Notifications in the Dashboard](#step-8-subscribe-and-render-notifications-in-the-dashboard)
   - [Step 9: Build Frontend Assets](#step-9-build-frontend-assets)
   - [Step 10: Run the Reverb Server](#step-10-run-the-reverb-server)
9. [Event Payload Reference](#9-event-payload-reference)
10. [Private Channel Security Model](#10-private-channel-security-model)
11. [ShouldBroadcastNow vs ShouldBroadcast](#11-shouldbroadcastnow-vs-shouldbroadcast)
12. [Troubleshooting Guide](#12-troubleshooting-guide)
13. [Production Deployment on Raspberry Pi](#13-production-deployment-on-raspberry-pi)
14. [Files Added / Modified in Step 22](#14-files-added--modified-in-step-22)

---

## 1. What Are WebSockets and Why Do We Need Them?

HTTP — the protocol your browser uses for most requests — is **request/response**. The browser
asks for something, the server replies, and the connection closes. The server can never reach out
to the browser on its own.

**WebSockets** change that. After an initial HTTP handshake, the connection *upgrades* to a
persistent, full-duplex (two-way) channel. The server can now push messages to the browser at
any moment, without the browser having to ask first.

**Traditional HTTP (polling) — browser must keep asking:**

```
  Browser ──── "Any updates?" ────► Server
  Browser ◄─── "No."               Server

  Browser ──── "Any updates?" ────► Server
  Browser ◄─── "No."               Server

  Browser ──── "Any updates?" ────► Server
  Browser ◄─── "Yes! Device expired." ── Server
```

**WebSockets (push) — server speaks whenever something happens:**

```
  Browser ──── "Let's open a connection" ────► Server
  Browser ◄─── "OK, connected."               Server

  (connection stays open — no more requests needed)

  Browser ◄─── "Device expired!" ─────────── Server   (pushed instantly)
  Browser ◄─── "Blocked site attempt!" ────── Server   (pushed instantly)
  Browser ◄─── "Time granted: 30 min" ──────  Server   (pushed instantly)
```

For a **parental WiFi control dashboard**, the difference is critical:

**Child device connects**
- Without WebSockets: Parent sees it only after manually refreshing the page.
- With WebSockets: A green notification appears in the dashboard within seconds.

**Child time expires**
- Without WebSockets: Parent might not notice until they check the device list.
- With WebSockets: A red alert fires instantly the moment time runs out.

**Child tries a blocked website**
- Without WebSockets: Only visible if the parent manually checks the access logs.
- With WebSockets: A live danger alert appears in the dashboard immediately.

**Quiz or video reward granted**
- Without WebSockets: Parent has no real-time feedback that time was added.
- With WebSockets: A green success notification shows the minutes granted and the source.

---

## 2. The Traditional Alternative: Polling

Before WebSockets, developers used **polling**: the browser sends a request every few seconds
asking "any new data?"

```javascript
// Polling example — DO NOT use this pattern
setInterval(() => {
    fetch('/api/notifications')
        .then(res => res.json())
        .then(data => renderNotifications(data));
}, 3000); // Every 3 seconds
```

Problems with polling:
- **Wastes server resources** — most requests return empty responses.
- **Latency** — an event at second 0 might not be seen until second 3.
- **Scales poorly** — 50 parent dashboards × 20 req/min = 1,000 requests/min, all mostly empty.
- **On a Raspberry Pi**, unnecessary CPU cycles reduce reliability.

WebSockets eliminate all of these by pushing data only when something actually happens.

---

## 3. What Is Laravel Echo?

**Laravel Echo** is the JavaScript client library that handles WebSocket subscriptions on the
frontend. It provides a clean, expressive API to subscribe to channels and listen to events.

```javascript
// Subscribe to a private channel and listen to a named event
window.Echo.private('user.5')
    .listen('.device.connected', (event) => {
        console.log(event.device_name, 'just came online!');
    });
```

Key Echo concepts:

| Concept            | Description |
|--------------------|-------------|
| **Channel**        | A named stream you subscribe to (e.g. `user.5`) |
| **Private channel** | Requires server-side authorization — only the right user can join |
| **Event alias**    | The name of the message within a channel (e.g. `.device.connected`) |
| **Payload**        | The data object sent with the event |

Echo is **transport-agnostic**: it can talk to Pusher.com (cloud), Ably, Soketi, or — in our case
— **Reverb** (self-hosted, built into Laravel). The API stays the same regardless.

---

## 4. What Is Laravel Reverb?

**Laravel Reverb** is a first-party, self-hosted WebSocket server for Laravel applications,
introduced in Laravel 11. It is written in PHP and runs as a separate long-lived process
alongside your regular web server.

Why Reverb over Pusher.com or other options:

| Option           | Cost       | Hosting       | Privacy        | RPi compatible? |
|------------------|------------|---------------|----------------|-----------------|
| Pusher.com       | Paid/free tier | Cloud SaaS  | Data leaves LAN | No (cloud required) |
| Ably             | Paid/free tier | Cloud SaaS  | Data leaves LAN | No (cloud required) |
| Soketi           | Free       | Self-hosted   | LAN only       | Yes |
| **Laravel Reverb** | **Free** | **Self-hosted** | **LAN only** | **Yes** |

For a **Raspberry Pi running on a home LAN**, Reverb is the ideal choice:

- No external dependencies or API keys to pay for.
- Events never leave the local network.
- Runs as a managed systemd service.
- Speaks the **Pusher protocol** so it is compatible with the Pusher JS client (which Echo uses
  internally), without requiring a Pusher account.

### How Reverb Works Internally

```
Browser (Echo/Pusher JS)
    │
    │  WebSocket handshake (ws://192.168.4.1:8080)
    ▼
Reverb Server (php artisan reverb:start)
    │
    │  Channel subscription requests
    │  Authorization via POST /broadcasting/auth
    ▼
Laravel Application
    │
    │  Background jobs / services fire events
    │  Events implement ShouldBroadcastNow
    │  Laravel pushes event to Reverb
    ▼
Reverb Server
    │
    │  Pushes payload to all subscribed browser connections
    ▼
Browser (event handler runs, DOM updates)
```

---

## 5. How Echo and Reverb Work Together

The browser initializes Echo once with connection settings:

```javascript
window.Echo = new Echo({
    broadcaster: 'reverb',    // tells Echo to use the Reverb/Pusher protocol
    key: 'your-app-key',      // must match REVERB_APP_KEY in .env
    wsHost: '192.168.4.1',    // Reverb server host (LAN IP on RPi)
    wsPort: 8080,             // Reverb server port
    forceTLS: false,          // no TLS on local LAN
    enabledTransports: ['ws'],
});
```

From that point:

1. Echo opens a persistent WebSocket connection to Reverb.
2. When the dashboard calls `Echo.private('user.5')`, Echo sends a subscription request to Reverb
   and simultaneously POSTs to `/broadcasting/auth` on your Laravel app to verify the user is
   authorized.
3. If authorization passes (the `routes/channels.php` callback returns `true`), Reverb marks that
   connection as subscribed to `private-user.5`.
4. When Laravel fires a broadcast event for channel `user.5`, Laravel sends it to Reverb via an
   internal HTTP call.
5. Reverb relays the payload to all browser connections subscribed to that channel.

---

## 6. Project Context: Why Step 22 Needs Real-Time

By step 21, the following **background jobs** run on a cron-driven queue:

| Job                         | Interval | Action                                     |
|-----------------------------|----------|--------------------------------------------|
| `MonitorDeviceConnections`  | 2 min    | Detects devices going online / offline     |
| `CheckTimeExpiration`       | 2 min    | Detects when a child's time runs out       |
| `TrackActiveSessions`       | 5 min    | Deducts time from connected devices        |
| `EnforceSchedules`          | 1 min    | Applies time-of-day access restrictions    |
| `ParseNetworkLogs`          | 10 min   | Parses DNS logs for browsing history       |

And the following **services** fire during portal completion:

- `TimeGrantingService` — adds minutes after quiz or video.

And a **model creation hook**:

- `AccessAttempt::created` — fires whenever a blocked/flagged access is logged.

All of these generate state changes a **parent wants to know about instantly**. Without WebSockets,
the dashboard is a static snapshot that never updates until the parent refreshes the page.

Step 22 wires all of these hook points to broadcast events, so the dashboard becomes a live feed.

---

## 7. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                          Raspberry Pi                               │
│                                                                     │
│  ┌─────────────────────────────┐    ┌──────────────────────────┐   │
│  │    Laravel Application      │    │   Reverb WebSocket Server │   │
│  │    (PHP-FPM / web server)   │    │   php artisan reverb:start│   │
│  │                             │    │   listening :8080         │   │
│  │  Background Jobs            │    │                          │   │
│  │  ├─ MonitorDeviceConnections│───►│  Receives broadcast events│   │
│  │  ├─ CheckTimeExpiration     │    │  and pushes to subscribed │   │
│  │  └─ ...                     │    │  WebSocket connections    │   │
│  │                             │    └──────────────┬───────────┘   │
│  │  Services                   │                   │               │
│  │  └─ TimeGrantingService     │                   │               │
│  │                             │                   │               │
│  │  Model Hooks                │                   │               │
│  │  └─ AccessAttempt::created  │                   │               │
│  └─────────────────────────────┘                   │               │
│                                                     │               │
└─────────────────────────────────────────────────────────────────────┘
                                                      │
                               WebSocket connection   │
                               (ws://192.168.4.1:8080) │
                                                      ▼
                            ┌──────────────────────────────────┐
                            │  Parent Browser — Dashboard       │
                            │                                   │
                            │  Echo.private('user.{id}')        │
                            │  .listen('.device.connected')     │
                            │  .listen('.time.expired')         │
                            │  .listen('.time.granted')         │
                            │  .listen('.website.blocked_...')  │
                            │  .listen('.website.flagged_...')  │
                            │                                   │
                            │  → Live notification panel        │
                            └──────────────────────────────────┘
```

### Channel Strategy

All events use a **private user channel**: `private-user.{id}`

This means:
- Each parent only receives events for their own devices.
- The channel requires authentication (cannot be joined anonymously).
- A parent logged in as user ID 3 subscribes to `private-user.3`; they can never see events for
  user ID 5.

---

## 8. Step-by-Step Implementation

### Step 1: Install Dependencies

**Backend — Reverb server:**

```bash
composer require laravel/reverb
php artisan reverb:install --no-interaction
```

`reverb:install` publishes two config files and registers the broadcasting service provider:
- `config/broadcasting.php`
- `config/reverb.php`

**Frontend — Echo and the Pusher JS transport:**

```bash
npm install --save-dev laravel-echo pusher-js
```

`pusher-js` is the underlying WebSocket transport library. Reverb speaks the Pusher WebSocket
protocol, so Echo uses `pusher-js` to communicate with it — even though you have no Pusher account.

---

### Step 2: Configure the Broadcasting Driver

In your `.env` file, set:

```dotenv
BROADCAST_CONNECTION=reverb
```

Laravel supports multiple broadcast drivers (`log`, `null`, `pusher`, `reverb`, `ably`). Setting
this to `reverb` tells Laravel's broadcasting system to route all `broadcast()` / `event()` calls
through the Reverb connection defined in `config/broadcasting.php`.

The default is `log` (writes events to the log file but does not broadcast), so **this change is
required** for real WebSocket delivery.

---

### Step 3: Configure Reverb Environment Values

Add the following to your `.env` (these should already be in `.env.example` after step 22):

```dotenv
# Reverb server identity
REVERB_APP_ID=parental-wifi
REVERB_APP_KEY=local-reverb-key
REVERB_APP_SECRET=local-reverb-secret

# Reverb server network binding
REVERB_HOST=127.0.0.1          # host clients connect TO (or LAN IP if browser is remote)
REVERB_PORT=8080
REVERB_SCHEME=http

REVERB_SERVER_HOST=0.0.0.0     # bind ALL interfaces so LAN clients can reach it
REVERB_SERVER_PORT=8080

# Vite env vars — exposed to the browser bundle at build time
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**Important distinction:**
- `REVERB_HOST` is what the **browser** uses to connect (the address of the RPi on the LAN).
- `REVERB_SERVER_HOST` is what Reverb binds to when starting its listener. `0.0.0.0` means it
  accepts connections on all network interfaces.

If the parent's browser is running on the same machine as the server (local dev), `127.0.0.1`
works. If the parent's browser is a device on the LAN (the RPi use-case), `REVERB_HOST` must be
the RPi's LAN IP (e.g. `192.168.4.1`).

---

### Step 4: Register Private Channel Authorization

**File:** `routes/channels.php`

```php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Parent-scoped dashboard stream.
// Why private channel:
// - each parent should only receive events for devices they own.
// How it works:
// - frontend subscribes to private-user.{id}
// - this callback authorizes only if auth user id matches channel id.
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

When Echo calls `Echo.private('user.5')`, the browser automatically POSTs to
`/broadcasting/auth`. Laravel calls the callback above with the authenticated user and the channel
ID from the URL. If the callback returns `true` (or a truthy value), the subscription is allowed.
If it returns `false`, the subscription is rejected with a 403.

**The `bootstrap/app.php` also needs to load this route file.** After `reverb:install`, a
`withBroadcasting()` or manual channel route registration line should be present. Verify:

```php
// bootstrap/app.php excerpt
->withRouting(
    web: __DIR__.'/../routes/web.php',
    // ...
    channels: __DIR__.'/../routes/channels.php',   // ← must be present
)
```

---

### Step 5: Create Broadcast Event Classes

Each real-time notification is its own PHP class in `app/Events/`. Every class:

1. Implements `ShouldBroadcastNow` (synchronous broadcast, no queue delay).
2. Uses the `Dispatchable`, `InteractsWithSockets`, `SerializesModels` traits.
3. Defines `broadcastOn()` — which channel(s) to send to.
4. Defines `broadcastAs()` — the event name alias the frontend listens to.
5. Defines `broadcastWith()` — the payload sent to the browser.

**DeviceConnected** (`app/Events/DeviceConnected.php`):

```php
class DeviceConnected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $deviceId,
        public string $deviceName,
        public string $macAddress,
        public ?string $ipAddress
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'device.connected';
    }

    public function broadcastWith(): array
    {
        return [
            'type'        => 'device_connected',
            'user_id'     => $this->userId,
            'device_id'   => $this->deviceId,
            'device_name' => $this->deviceName,
            'mac_address' => $this->macAddress,
            'ip_address'  => $this->ipAddress,
            'timestamp'   => now()->toIso8601String(),
        ];
    }
}
```

All six event classes follow this exact same pattern. The only differences are the constructor
parameters and the `broadcastAs()` alias:

**`DeviceConnected`**
- Alias: `.device.connected`
- Payload: `device_name`, `mac_address`, `ip_address`

**`DeviceDisconnected`**
- Alias: `.device.disconnected`
- Payload: `device_name`, `mac_address`

**`TimeExpired`**
- Alias: `.time.expired`
- Payload: `device_name`, `mac_address`

**`TimeGranted`**
- Alias: `.time.granted`
- Payload: `device_name`, `minutes_granted`, `remaining_minutes`, `source`

**`BlockedWebsiteAccessed`**
- Alias: `.website.blocked_accessed`
- Payload: `device_name`, `url`, `domain`

**`FlaggedWebsiteVisited`**
- Alias: `.website.flagged_visited`
- Payload: `device_name`, `url`, `domain`

---

### Step 6: Dispatch Events from Backend Hook Points

There are four distinct places in the backend where events are fired:

---

#### 6a. MonitorDeviceConnections Job

**File:** `app/Jobs/MonitorDeviceConnections.php`

This job runs every 2 minutes and compares the live ARP table to the database.

**Connection event** — fires only on the offline → online transition to avoid spamming a
notification every 2 minutes for devices that remain connected:

```php
// Broadcast connection event only on offline -> online transitions.
if (!$wasConnected && !empty($ipAddress) && $device->user_id) {
    event(new DeviceConnected(
        userId:     $device->user_id,
        deviceId:   $device->id,
        deviceName: $device->name,
        macAddress: $device->mac_address,
        ipAddress:  $ipAddress
    ));
}
```

**Disconnection event** — fires only when a device had a non-null IP (was previously online):

```php
// Broadcast disconnection only if device was previously online.
if ($wasConnected && $device->user_id) {
    event(new DeviceDisconnected(
        userId:     $device->user_id,
        deviceId:   $device->id,
        deviceName: $device->name,
        macAddress: $device->mac_address
    ));
}
```

---

#### 6b. CheckTimeExpiration Job

**File:** `app/Jobs/CheckTimeExpiration.php`

After a device is blocked and redirected to the portal, the parent is notified:

```php
if ($device->user_id) {
    event(new TimeExpired(
        userId:     $device->user_id,
        deviceId:   $device->id,
        deviceName: $device->name,
        macAddress: $device->mac_address
    ));
}
```

---

#### 6c. TimeGrantingService

**File:** `app/Services/TimeGrantingService.php`

After minutes are added to a device (following a successful quiz or video completion), and after
the unblock flow is complete, the parent sees the reward:

```php
if ($device->user_id) {
    event(new TimeGranted(
        userId:           $device->user_id,
        deviceId:         $device->id,
        deviceName:       $device->name,
        minutesGranted:   $minutes,
        remainingMinutes: (int) ($device->remaining_time_minutes ?? 0),
        source:           $source   // 'quiz', 'video', or 'manual'
    ));
}
```

The `source` field tells the parent *why* the time changed — quiz or video — without them having
to check the logs.

---

#### 6d. AccessAttempt Model Created Hook

**File:** `app/Models/AccessAttempt.php`

The model's `booted()` method listens to the Eloquent `created` event. This is the **most
reliable** place to fire security alerts because it triggers regardless of which code path creates
the access attempt record (background job, controller, or external script import):

```php
protected static function booted(): void
{
    static::created(function (AccessAttempt $attempt): void {
        $attempt->loadMissing('device');

        if (!$attempt->device || !$attempt->device->user_id) {
            return;
        }

        if ($attempt->type === 'blocked_website') {
            event(new BlockedWebsiteAccessed(
                userId:     $attempt->device->user_id,
                deviceId:   $attempt->device_id,
                deviceName: $attempt->device->name,
                url:        $attempt->url,
                domain:     $attempt->domain
            ));
        }

        if ($attempt->type === 'flagged_website') {
            event(new FlaggedWebsiteVisited(
                userId:     $attempt->device->user_id,
                deviceId:   $attempt->device_id,
                deviceName: $attempt->device->name,
                url:        $attempt->url,
                domain:     $attempt->domain
            ));
        }
    });
}
```

**Why the model hook rather than the controller or job?**
If the access attempt is ever created from a script, an import, or a future feature, the
notification will still fire automatically. It cannot be forgotten.

---

### Step 7: Initialize Echo in the Frontend

**File:** `resources/js/bootstrap.js`

```javascript
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

// Only initialize realtime connection when env is configured.
// This keeps local/dev/test environments from failing hard if Reverb is disabled.
if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost:  import.meta.env.VITE_REVERB_HOST  ?? window.location.hostname,
        wsPort:  Number(import.meta.env.VITE_REVERB_PORT  ?? 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT  ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
```

**Key decisions:**

- `window.Pusher = Pusher` — Echo's Reverb broadcaster relies on `pusher-js` internally and
  expects it as a global.
- The `if (reverbKey)` guard prevents a crash in environments where Reverb is not running (e.g.
  running `php artisan test` in CI).
- `wsHost` falls back to `window.location.hostname` so local development works without extra
  configuration.

**Passing the app key to the browser:**

Vite substitutes `import.meta.env.VITE_*` variables at build time. That's why the `.env` file
has:

```dotenv
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
```

After `npm run build`, the compiled JS bundle contains the literal key value baked in.

---

### Step 8: Subscribe and Render Notifications in the Dashboard

**File:** `resources/views/dashboard/index.blade.php`

The dashboard needs to know the authenticated user's ID to subscribe to the correct private
channel. This is passed through a `<meta>` tag in the layout:

```html
<!-- resources/views/layouts/app.blade.php -->
@auth
<meta name="auth-user-id" content="{{ auth()->id() }}">
@endauth
```

In the dashboard's inline script, Echo is wired up:

```javascript
(function () {
    if (!window.Echo) return;  // graceful exit if Reverb is not configured

    const userId = document.querySelector('meta[name="auth-user-id"]')?.content;
    if (!userId) return;

    const list = document.getElementById('notifications-list');

    const addNotification = (message, type = 'info') => {
        const empty = list.querySelector('[data-empty]');
        if (empty) empty.remove();

        const li = document.createElement('li');
        const colors = {
            info:    'border-blue-200 bg-blue-50 text-blue-900',
            warning: 'border-yellow-200 bg-yellow-50 text-yellow-900',
            danger:  'border-red-200 bg-red-50 text-red-900',
            success: 'border-green-200 bg-green-50 text-green-900',
        };

        li.className = `rounded-lg border px-3 py-2 ${colors[type] ?? colors.info}`;
        li.textContent = `${new Date().toLocaleTimeString()} - ${message}`;
        list.prepend(li);

        // Keep the list from growing unbounded
        while (list.children.length > 12) {
            list.removeChild(list.lastChild);
        }
    };

    // Listen to backend broadcast aliases and map them to human-friendly alerts.
    // These aliases are defined in app/Events/* via broadcastAs().
    window.Echo.private(`user.${userId}`)
        .listen('.device.connected', (event) => {
            addNotification(`${event.device_name} connected (${event.ip_address ?? 'unknown IP'})`, 'success');
        })
        .listen('.device.disconnected', (event) => {
            addNotification(`${event.device_name} disconnected`, 'warning');
        })
        .listen('.time.expired', (event) => {
            addNotification(`Time expired for ${event.device_name}. Device redirected to portal.`, 'danger');
        })
        .listen('.time.granted', (event) => {
            addNotification(`${event.minutes_granted} minutes granted to ${event.device_name} via ${event.source}.`, 'success');
        })
        .listen('.website.blocked_accessed', (event) => {
            const target = event.domain || event.url || 'blocked website';
            addNotification(`${event.device_name} attempted blocked site: ${target}`, 'danger');
        })
        .listen('.website.flagged_visited', (event) => {
            const target = event.domain || event.url || 'flagged website';
            addNotification(`${event.device_name} visited flagged site: ${target}`, 'warning');
        });
})();
```

**Note the leading dot in `.device.connected`:**
When you define `broadcastAs(): string { return 'device.connected'; }` in PHP, Laravel automatically
namespaces the event under the app's root namespace when broadcasting. The leading dot in Echo's
`listen('.device.connected')` bypasses the automatic namespace and listens to the alias exactly
as defined in `broadcastAs()`. Without the dot, Echo would look for
`App\Events\DeviceConnected` and not find a match.

---

### Step 9: Build Frontend Assets

Every time you change JavaScript (bootstrap.js, inline scripts in Blade) you must rebuild:

```bash
npm run build
```

For local development with hot module reloading:

```bash
npm run dev
```

> After rebuilding, the Reverb app key and connection settings are baked into the JS bundle.
> If you change `.env` Reverb/Vite values, always rebuild.

---

### Step 10: Run the Reverb Server

**Development:**

```bash
php artisan reverb:start
```

This starts a blocking process that listens on `REVERB_SERVER_HOST:REVERB_SERVER_PORT`. Keep this
terminal open while testing.

**Production on Raspberry Pi:**

Configure a systemd service so Reverb starts on boot and auto-restarts on failure:

```ini
# /etc/systemd/system/parental-wifi-reverb.service
[Unit]
Description=Parental WiFi Reverb WebSocket Server
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/parental_wifi
ExecStart=/usr/bin/php artisan reverb:start
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable parental-wifi-reverb.service
sudo systemctl start parental-wifi-reverb.service
sudo systemctl status parental-wifi-reverb.service --no-pager
```

---

## 9. Event Payload Reference

All events broadcast to `private-user.{userId}` and include a `timestamp` in ISO 8601 format.

### `device.connected`

```json
{
  "type": "device_connected",
  "user_id": 1,
  "device_id": 7,
  "device_name": "iPad Mini",
  "mac_address": "E6:6A:8F:19:BE:B1",
  "ip_address": "192.168.4.25",
  "timestamp": "2025-12-07T14:30:00+00:00"
}
```

### `device.disconnected`

```json
{
  "type": "device_disconnected",
  "user_id": 1,
  "device_id": 7,
  "device_name": "iPad Mini",
  "mac_address": "E6:6A:8F:19:BE:B1",
  "timestamp": "2025-12-07T14:45:00+00:00"
}
```

### `time.expired`

```json
{
  "type": "time_expired",
  "user_id": 1,
  "device_id": 7,
  "device_name": "iPad Mini",
  "mac_address": "E6:6A:8F:19:BE:B1",
  "timestamp": "2025-12-07T15:00:00+00:00"
}
```

### `time.granted`

```json
{
  "type": "time_granted",
  "user_id": 1,
  "device_id": 7,
  "device_name": "iPad Mini",
  "minutes_granted": 30,
  "remaining_minutes": 30,
  "source": "quiz",
  "timestamp": "2025-12-07T15:05:00+00:00"
}
```

`source` values: `"quiz"`, `"video"`, `"manual"`

### `website.blocked_accessed`

```json
{
  "type": "blocked_website_accessed",
  "user_id": 1,
  "device_id": 7,
  "device_name": "iPad Mini",
  "url": "https://facebook.com/login",
  "domain": "facebook.com",
  "timestamp": "2025-12-07T15:10:00+00:00"
}
```

### `website.flagged_visited`

```json
{
  "type": "flagged_website_visited",
  "user_id": 1,
  "device_id": 7,
  "device_name": "iPad Mini",
  "url": "https://reddit.com",
  "domain": "reddit.com",
  "timestamp": "2025-12-07T15:12:00+00:00"
}
```

---

## 10. Private Channel Security Model

All events use `PrivateChannel("user.{$this->userId}")`. This is not just a name convention —
it activates Laravel's channel authentication pipeline:

```
Frontend: Echo.private('user.5')
    │
    └─► POST /broadcasting/auth
         body: { channel_name: "private-user.5", socket_id: "..." }
         │
         └─► routes/channels.php
              Broadcast::channel('user.{id}', function ($user, $id) {
                  return (int) $user->id === (int) $id;
              });
              │
              ├─ if authorized → Reverb grants subscription
              └─ if not → 403 Forbidden, subscription blocked
```

This means:
- An unauthenticated user cannot subscribe to any `private-user.*` channel.
- A user logged in as ID 3 cannot subscribe to `private-user.5`.
- Events for user 5's devices will **never** be delivered to user 3's browser connection.

This is the correct approach for parent-specific notifications in a multi-parent system.

---

## 11. ShouldBroadcastNow vs ShouldBroadcast

Laravel offers two broadcast interfaces:

| Interface              | Queue?    | When to use                                      |
|------------------------|-----------|--------------------------------------------------|
| `ShouldBroadcast`      | Yes       | Low-urgency events where a brief delay is fine   |
| `ShouldBroadcastNow`   | **No**    | **Urgent events that must reach the browser immediately** |

All six events in this project use `ShouldBroadcastNow`.

**Why?** Consider the alternative. With `ShouldBroadcast`, the event is pushed onto the Laravel
queue. The queue worker picks it up on its next cycle (which may be seconds later). On a Raspberry
Pi under load, the queue might be busy with other jobs (ParseNetworkLogs, TrackActiveSessions),
adding even more latency.

For events like `time.expired`, `website.blocked_accessed`, and `device.connected`, the parent
needs to see these *as soon as they happen*, not 5–10 seconds later. `ShouldBroadcastNow` skips
the queue entirely and sends the event to Reverb synchronously within the same PHP process that
fired it.

---

## 12. Troubleshooting Guide

### Dashboard shows no real-time notifications

1. Open browser DevTools → **Network** tab → filter for `WS` (WebSocket).
   - You should see an active WebSocket connection to `ws://host:8080/app/your-key`.
   - If no connection appears: Reverb is not running, or the host/port is wrong.

2. Check `.env`:
   ```dotenv
   BROADCAST_CONNECTION=reverb
   VITE_REVERB_APP_KEY=local-reverb-key
   VITE_REVERB_HOST=127.0.0.1   # must be reachable from the browser
   VITE_REVERB_PORT=8080
   ```

3. Confirm Reverb is running:
   ```bash
   php artisan reverb:start
   # or
   sudo systemctl status parental-wifi-reverb.service
   ```

4. Rebuild JS after any `.env` change:
   ```bash
   npm run build
   ```

5. Open DevTools → **Console**. Look for Echo/Pusher connection errors.

### Events are fired but notifications don't appear

- Open DevTools → **Console** → look for channel authorization errors (403).
- Confirm `routes/channels.php` has the `user.{id}` rule.
- Confirm `bootstrap/app.php` loads the channels route.
- Confirm the `<meta name="auth-user-id">` tag is present and correct in the page source.
- Confirm `npm run build` was run after changing `bootstrap.js`.

### Events are not fired at all

- Check `storage/logs/laravel.log` for job/service execution errors.
- Run the job manually in tinker:
  ```bash
  php artisan tinker
  > App\Jobs\CheckTimeExpiration::dispatch();
  ```
- Verify `BROADCAST_CONNECTION` is `reverb` (not `log` or `null`).

### Works locally but not on Raspberry Pi

- `REVERB_HOST` in `.env` must be the RPi's LAN IP (e.g. `192.168.4.1`), not `127.0.0.1`,
  because the parent's browser is on a different machine on the network.
- Rebuild after changing `VITE_REVERB_HOST`:
  ```bash
  npm run build
  ```
- Check firewall / iptables for port 8080:
  ```bash
  sudo iptables -L INPUT -n | grep 8080
  ```
- Verify Reverb is bound to `0.0.0.0` (`REVERB_SERVER_HOST=0.0.0.0`).

---

## 13. Production Deployment on Raspberry Pi

After a `git pull` on the RPi, run:

```bash
# Clear compiled config/route cache (picks up new .env values)
php artisan optimize:clear

# Rebuild JS bundle with new Vite env vars
npm run build

# Restart queue worker so it picks up new job classes
sudo systemctl restart parental-wifi-queue.service

# Start/restart Reverb (if running as service)
sudo systemctl restart parental-wifi-reverb.service

# Verify scheduler is running
crontab -l
```

**Verification after deploy:**

1. Open the parent dashboard in a browser.
2. Connect a child device to the WiFi.
3. Within 2 minutes (next `MonitorDeviceConnections` cycle) a green "connected" notification
   should appear without page refresh.
4. Let a child device's time expire → red "time expired" notification.
5. Complete a quiz on the portal → green "X minutes granted" notification.

Full checklist is in [`docs/websockets.md`](websockets.md#verification-checklist).

---

## 14. Files Added / Modified in Step 22

### New Files

| File | Purpose |
|------|---------|
| `app/Events/DeviceConnected.php` | Broadcast event — device comes online |
| `app/Events/DeviceDisconnected.php` | Broadcast event — device goes offline |
| `app/Events/TimeExpired.php` | Broadcast event — child's time runs out |
| `app/Events/TimeGranted.php` | Broadcast event — quiz/video reward added |
| `app/Events/BlockedWebsiteAccessed.php` | Broadcast event — blocked site access attempt |
| `app/Events/FlaggedWebsiteVisited.php` | Broadcast event — flagged site visited |
| `config/broadcasting.php` | Published by `reverb:install`; defines broadcaster configs |
| `config/reverb.php` | Published by `reverb:install`; Reverb server settings |
| `routes/channels.php` | Private channel authorization callbacks |
| `docs/websockets.md` | Concise deployment guide and runtime reference |

### Modified Files

| File | Change |
|------|--------|
| `bootstrap/app.php` | Added channels route loading |
| `.env.example` | Added Reverb + Vite WebSocket env vars |
| `app/Jobs/MonitorDeviceConnections.php` | Added `DeviceConnected` / `DeviceDisconnected` dispatch |
| `app/Jobs/CheckTimeExpiration.php` | Added `TimeExpired` dispatch |
| `app/Services/TimeGrantingService.php` | Added `TimeGranted` dispatch |
| `app/Models/AccessAttempt.php` | Added `created` model hook with security event dispatch |
| `resources/js/bootstrap.js` | Added Echo + Pusher initialization |
| `resources/views/layouts/app.blade.php` | Added `auth-user-id` meta tag |
| `resources/views/dashboard/index.blade.php` | Added real-time notification panel + Echo listeners |
| `composer.json` / `composer.lock` | Added `laravel/reverb` dependency |
| `package.json` / `package-lock.json` | Added `laravel-echo` and `pusher-js` dependencies |

---

*See [`docs/websockets.md`](websockets.md) for the quick-reference deployment guide.*
