# WebSockets Testing Guide (Step 22)

## Purpose

This document verifies TODO 22 (`websockets`) from `docs/scope.md` and provides a repeatable test procedure.

It is designed to be:

- local-first (fast feedback),
- followed by Raspberry Pi validation for network/device-realistic behavior.

## What Step 22 Must Deliver

Realtime notifications on dashboard for these event aliases:

1. `.device.connected`
2. `.device.disconnected`
3. `.time.expired`
4. `.time.granted`
5. `.website.blocked_accessed`
6. `.website.flagged_visited`

All events are expected on private channel `private-user.{id}` (Echo subscription: `Echo.private("user.{id}")`).

## Preconditions

- App dependencies installed (`composer install`, `npm install`)
- Database accessible for the chosen environment
- `BROADCAST_CONNECTION=reverb`
- Reverb/Vite websocket env values configured
- Frontend built or dev server running

Recommended local env values:

```dotenv
BROADCAST_CONNECTION=reverb
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

## Important Reliability Fixes Applied

Before testing, two issues were fixed:

1. `resources/views/layouts/app.blade.php`
   - Removed duplicate `@stack('scripts')` render in `<head>`.
   - Prevents duplicate dashboard script execution and duplicate websocket subscriptions.

2. `app/Jobs/MonitorDeviceConnections.php`
   - Removed early return when connected device scan is empty.
   - Disconnection reconciliation now still runs, so offline transitions are handled and can emit `DeviceDisconnected`.

## Local Verification (Completed)

### A) Configuration and route checks

Commands used:

```powershell
php artisan channel:list
php artisan route:list --path=broadcasting
php artisan route:list --name=dashboard
```

Expected:

- Channel list includes `user.{id}` private channel authorization callback.
- Broadcasting auth route exists: `GET|POST|HEAD broadcasting/auth`.
- Dashboard route exists.

Result: PASS

### B) Reverb process check

Notes:

- Initial Reverb start failed when local MySQL was unavailable.
- Reverb started successfully using local sqlite command-scoped overrides.

Command used:

```powershell
$env:DB_CONNECTION='sqlite'; $env:DB_DATABASE='database/database.sqlite'; php artisan reverb:start --host=127.0.0.1 --port=8080
```

Expected:

- `INFO  Starting server on 127.0.0.1:8080 (localhost).`

Result: PASS

### C) Backend event dispatch smoke test (all 6 events)

Command used:

```powershell
$env:DB_CONNECTION='sqlite'; $env:DB_DATABASE='database/database.sqlite'; php artisan tinker --execute "event(new \App\Events\DeviceConnected(1,1,'Local Test Device','AA:BB:CC:DD:EE:FF','192.168.4.10')); event(new \App\Events\DeviceDisconnected(1,1,'Local Test Device','AA:BB:CC:DD:EE:FF')); event(new \App\Events\TimeExpired(1,1,'Local Test Device','AA:BB:CC:DD:EE:FF')); event(new \App\Events\TimeGranted(1,1,'Local Test Device',15,45,'manual')); event(new \App\Events\BlockedWebsiteAccessed(1,1,'Local Test Device','https://example-blocked.test','example-blocked.test')); event(new \App\Events\FlaggedWebsiteVisited(1,1,'Local Test Device','https://example-flagged.test','example-flagged.test'));"
```

Expected:

- Command exits without broadcast exceptions.

Result: PASS

## Local Manual Browser Validation (Run Checklist)

Use this when verifying end-to-end dashboard rendering:

1. Start Laravel app + queue worker + scheduler + Reverb.
2. Open dashboard as authenticated parent.
3. Open browser DevTools:
   - Network -> WS: verify websocket connection.
   - Network -> `/broadcasting/auth`: verify successful auth (not 401/403/419).
4. Trigger each event path and confirm UI notification appears exactly once:
   - connect child device -> `.device.connected`
   - disconnect child device -> `.device.disconnected`
   - exhaust device time -> `.time.expired`
   - grant time (quiz/video/manual) -> `.time.granted`
   - create blocked access attempt -> `.website.blocked_accessed`
   - create flagged access attempt -> `.website.flagged_visited`
5. Confirm list behavior:
   - newest notifications prepend to top
   - list remains bounded (max 12 items)
   - no duplicate notifications for single event.

### Local Manual Browser Validation Result

Result: PASS

Verified items:

- WebSocket handshake observed in browser network tab with status `101` (`Type: websocket`, pending open connection).
- Echo connection state confirmed as `connected` in browser console.
- All six Step 22 notification types received in the real-time dashboard panel:
  - `.device.connected`
  - `.device.disconnected`
  - `.time.expired`
  - `.time.granted`
  - `.website.blocked_accessed`
  - `.website.flagged_visited`

### Local Evidence Note

- Timestamp: 2026-03-12 local testing session
- Parent user id: `2`
- Event list tested:
  - `DeviceConnected`
  - `DeviceDisconnected`
  - `TimeExpired`
  - `TimeGranted`
  - `BlockedWebsiteAccessed`
  - `FlaggedWebsiteVisited`

## Raspberry Pi Follow-up Validation

Run this after local checks pass, because RPi reflects real AP/device conditions.

### Service and process checks

```bash
php artisan optimize:clear
npm run build
sudo systemctl restart parental-wifi-queue.service
sudo systemctl status parental-wifi-queue.service --no-pager
sudo systemctl restart parental-wifi-reverb.service
sudo systemctl status parental-wifi-reverb.service --no-pager
crontab -l
```

### Environment checks (RPi)

- `BROADCAST_CONNECTION=reverb`
- `REVERB_SERVER_HOST=0.0.0.0`
- `REVERB_HOST=<RPi_LAN_IP>` when dashboard opens from another device
- matching `VITE_REVERB_*` values rebuilt into frontend bundle

### End-to-end scenario checks

1. Open parent dashboard from LAN client.
2. Connect child device to AP -> expect connected notification.
3. Disconnect child device -> expect disconnected notification.
4. Let child time expire -> expect danger notification.
5. Complete quiz/video to grant time -> expect grant notification.
6. Trigger blocked/flagged website attempts -> expect security notifications.

Acceptance: all six events appear in realtime without page refresh.

## Troubleshooting Matrix

1. No websocket connection:
   - Reverb not running
   - wrong host/port/scheme
   - firewall/network path blocked

2. WS connects but no private events:
   - `/broadcasting/auth` failing (401/403/419)
   - auth session/csrf mismatch
   - channel authorization mismatch (`user.{id}`)

3. Events dispatch fails:
   - broadcast driver not `reverb`
   - Reverb process down
   - backend exception in job/service/model path

4. Notifications duplicated:
   - duplicate script initialization
   - multiple dashboard tabs for same user (expected multi-client fanout)

## Final Verification Status (Current)

- Code wiring: PASS
- Reliability fixes: PASS
- Local command-level verification: PASS
  - Verified: channels/routes are present and all 6 Step 22 events dispatch successfully while Reverb is running.
- Full browser/device realtime validation: PENDING (execute checklist above)
- Raspberry Pi production-like verification: PENDING (execute RPi section)

