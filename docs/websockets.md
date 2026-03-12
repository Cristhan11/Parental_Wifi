# WebSockets Implementation Guide (TODO 22)

## Purpose

This document explains how WebSockets were implemented in the Parental WiFi project, how the real-time pipeline works, and how to run and verify it on local and Raspberry Pi environments.

The main goal of TODO 22 is to provide real-time dashboard updates for critical events:

- Device connected / disconnected
- Time expired
- Time granted
- Blocked website access attempt
- Flagged website visit

## Why WebSockets

WebSockets are used to push events instantly from backend to frontend without page refreshes or frequent polling requests.

For this project, that means the parent dashboard can immediately receive live status updates from background jobs and services.

## Technology Used

- Backend framework: Laravel 12
- WebSocket server: Laravel Reverb
- Broadcasting driver: `reverb`
- Frontend client: Laravel Echo + Pusher JS protocol client
- Channel type: Private user-scoped channels (`private-user.{id}`)

## Architecture Overview

### Event Flow

1. A backend process triggers a state change (job/service/model).
2. A broadcast event is fired (`ShouldBroadcastNow`).
3. Laravel sends the event through Reverb.
4. Browser dashboard is subscribed via Echo to `private-user.{id}`.
5. Dashboard receives event and adds a live notification entry.

### Current Hook Points

- `MonitorDeviceConnections` broadcasts:
  - `device.connected`
  - `device.disconnected`
- `CheckTimeExpiration` broadcasts:
  - `time.expired`
- `TimeGrantingService` broadcasts:
  - `time.granted`
- `AccessAttempt` model (`created` hook) broadcasts:
  - `website.blocked_accessed`
  - `website.flagged_visited`

## Files Added / Updated

### New Event Classes

- `app/Events/DeviceConnected.php`
- `app/Events/DeviceDisconnected.php`
- `app/Events/TimeExpired.php`
- `app/Events/TimeGranted.php`
- `app/Events/BlockedWebsiteAccessed.php`
- `app/Events/FlaggedWebsiteVisited.php`

### Broadcasting / Reverb Configuration

- `config/broadcasting.php` (published)
- `config/reverb.php` (published)
- `routes/channels.php` (published + `user.{id}` channel rule)
- `bootstrap/app.php` (loads channels route)
- `.env.example` (Reverb + Vite websocket variables)

### Frontend Integration

- `resources/js/bootstrap.js` (Echo + Reverb client setup)
- `resources/views/layouts/app.blade.php` (auth user id meta tag)
- `resources/views/dashboard/index.blade.php` (real-time notification panel + Echo listeners)

## How It Works (Tutorial)

### Step 1: Install dependencies

Backend:

```bash
composer require laravel/reverb
php artisan reverb:install --no-interaction
```

Frontend:

```bash
npm install --save-dev laravel-echo pusher-js
```

### Step 2: Enable broadcasting driver

In `.env`:

```dotenv
BROADCAST_CONNECTION=reverb
```

### Step 3: Configure Reverb environment values

Use practical LAN-safe defaults:

```dotenv
REVERB_APP_ID=parental-wifi
REVERB_APP_KEY=local-reverb-key
REVERB_APP_SECRET=local-reverb-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Step 4: Register private channels

In `routes/channels.php`, authorize user-owned channel subscription:

- Channel name: `user.{id}`
- Rule: authenticated user id must match channel id

### Step 5: Create and dispatch broadcast events

Events implement `ShouldBroadcastNow` and publish to:

- `PrivateChannel("user.{userId}")`

Broadcast aliases used:

- `.device.connected`
- `.device.disconnected`
- `.time.expired`
- `.time.granted`
- `.website.blocked_accessed`
- `.website.flagged_visited`

### Step 6: Initialize Echo in frontend

In `resources/js/bootstrap.js`:

- Create `window.Echo` with `broadcaster: 'reverb'`
- Use Vite env vars for host/port/scheme

### Step 7: Subscribe in dashboard and render notifications

In dashboard script:

- Read authenticated user id from meta tag
- Subscribe to `Echo.private("user.{id}")`
- Listen to each event alias
- Prepend messages in live notification list

### Step 8: Build frontend assets

```bash
npm run build
```

For development:

```bash
npm run dev
```

### Step 9: Start Reverb server

Development:

```bash
php artisan reverb:start
```

Production (recommended via systemd service on RPi):
- Run Reverb as a managed service (auto-restart)
- Keep queue worker and scheduler running as already configured

## Runtime Commands (Local / RPi)

After deployment:

```bash
php artisan optimize:clear
php artisan queue:restart
```

If using service manager:

```bash
sudo systemctl restart parental-wifi-queue.service
sudo systemctl status parental-wifi-queue.service --no-pager
```

Ensure scheduler exists:

```bash
crontab -l
```

## Verification Checklist

1. Open dashboard as parent user.
2. Connect/disconnect child device.
3. Confirm live notification appears instantly.
4. Let device time expire; confirm `time.expired` notification.
5. Grant time by quiz/video flow; confirm `time.granted` notification.
6. Trigger blocked/flagged website attempt (if available) and confirm alert.

## Troubleshooting

### No real-time messages on dashboard

- Confirm `.env` has `BROADCAST_CONNECTION=reverb`
- Confirm Reverb process is running
- Confirm frontend was rebuilt after JS changes (`npm run build`)
- Confirm browser connects to correct `VITE_REVERB_HOST` / port
- Confirm authenticated user id matches private channel id

### Events not emitted

- Check `storage/logs/laravel.log` for job/service execution
- Ensure triggering job/service path actually runs
- Confirm event dispatch lines are reached (no early returns/exceptions)

### Works locally but not on RPi

- Verify Reverb bind host/port and firewall rules
- Use LAN-reachable host in Vite env (if browser is remote device)
- Restart Reverb/queue processes after env changes

## Security Notes

- Private channel scoping ensures users only receive their own events.
- Do not use broad public channels for parent-specific notifications.
- Keep app keys/secrets in `.env` (never in source control).

---

## Reference Steps We Will Do (Execution Plan)

1. Install Reverb + Echo dependencies.
2. Publish and verify broadcasting/reverb configs.
3. Configure `.env` with Reverb and Vite websocket values.
4. Register private user channel authorization in `routes/channels.php`.
5. Create event classes for all required real-time notifications.
6. Dispatch events from jobs/services/model hooks:
   - connection changes
   - time expiration
   - time grants
   - blocked/flagged access attempts
7. Initialize Echo in frontend bootstrap.
8. Add dashboard real-time notifications panel and listeners.
9. Build assets and restart relevant services (queue, scheduler, reverb).
10. Validate end-to-end on Raspberry Pi with live device actions.

