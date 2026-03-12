# WebSockets Raspberry Pi Deployment and Testing Runbook

## Purpose

This runbook covers:

1. How to deploy websocket changes to Raspberry Pi safely.
2. How to verify Step 22 websocket behavior on real AP/device conditions.
3. The remaining Raspberry Pi tests after local verification is complete.

Use this together with:

- `docs/websockets_testing.md` (local + generic checklist)
- `docs/websockets.md` (implementation overview)

---

## Current Status Before Raspberry Pi

Local validation already completed:

- WebSocket handshake visible (`101`)
- Echo connection state is `connected`
- All six Step 22 notifications verified locally:
  - `device.connected`
  - `device.disconnected`
  - `time.expired`
  - `time.granted`
  - `website.blocked_accessed`
  - `website.flagged_visited`

Remaining work is Raspberry Pi production-like validation.

---

## A) Git Workflow (Local Machine -> GitHub -> Raspberry Pi)

### 1) Local machine: commit and push changes

From your project root on your development machine:

```bash
git status
git add .
git commit -m "Update websocket UI layout and add Raspberry Pi websocket runbook"
git push origin <your-branch>
```

If you deploy from `main`, merge your branch first (PR recommended), then:

```bash
git checkout main
git pull origin main
git merge <your-branch>
git push origin main
```

### 2) Raspberry Pi: pull latest code

SSH into Raspberry Pi, then:

```bash
cd /var/www/parental_wifi
git status
git pull origin <deploy-branch>
```

If deploying from `main`:

```bash
git pull origin main
```

---

## B) Raspberry Pi Setup Checklist (One-time/verify each deployment)

## 1) Verify `.env` websocket settings

Set/confirm:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_SCHEME=http
REVERB_PORT=8080
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

Important host rule:

- `REVERB_HOST` and `VITE_REVERB_HOST` must be reachable by the parent browser.
- If browser is another LAN device, use Raspberry Pi LAN IP (example `192.168.4.1`), not `127.0.0.1`.

## 2) Install/update dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install
```

## 3) Rebuild app caches/assets

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

## 4) Database and queue prerequisites

```bash
php artisan migrate --force
php artisan queue:restart
```

---

## C) Service Management on Raspberry Pi

## 1) Queue worker service

```bash
sudo systemctl restart parental-wifi-queue.service
sudo systemctl status parental-wifi-queue.service --no-pager
```

## 2) Reverb service

```bash
sudo systemctl restart parental-wifi-reverb.service
sudo systemctl status parental-wifi-reverb.service --no-pager
```

If service is not configured yet, temporary foreground run:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

## 3) Scheduler check (cron)

```bash
crontab -l
```

Expected entry (or equivalent):

```bash
* * * * * cd /var/www/parental_wifi && php artisan schedule:run >> /dev/null 2>&1
```

---

## D) Raspberry Pi WebSocket Verification (Remaining Tests)

These are the remaining production-like tests that should be marked PASS/FAIL.

## 1) Browser-level realtime connection test

From a LAN client (not necessarily on Pi), login as parent and open dashboard.

In browser DevTools:

- Network: confirm websocket handshake (`status 101`, type websocket)
- Confirm `/broadcasting/auth` returns `200`
- Console: `window.Echo.connector.pusher.connection.state` should be `connected`

## 2) Event tests on real network behavior

Run these in order and confirm one matching live notification each time:

1. Connect child device to AP -> `device.connected`
2. Disconnect child device from AP -> `device.disconnected`
3. Let remaining time hit 0 and portal redirect occurs -> `time.expired`
4. Complete quiz on portal and grant time -> `time.granted` (`source: quiz`)
5. Complete video flow and grant time -> `time.granted` (`source: video`)
6. Trigger blocked website attempt in real browsing -> `website.blocked_accessed`
7. Trigger flagged website visit in real browsing -> `website.flagged_visited`

## 3) Dashboard UI behavior tests

Validate:

- Realtime card appears as the 5th grid item (full-width `col-span-12`)
- Grid section scrolls correctly
- Realtime list is scrollable and retains history (no forced cap deletion)
- Welcome-row popup shows latest event and auto-hides
- No duplicate notification for single trigger

## 4) Resilience tests

1. Restart Reverb service while dashboard is open.
2. Confirm client reconnects automatically.
3. Trigger one event and verify notification still arrives.

---

## E) Quick Test Commands (Raspberry Pi)

If needed for controlled event simulation:

```bash
php artisan tinker --execute "event(new \App\Events\DeviceConnected(2,1,'Test Device','AA:BB:CC:DD:EE:FF','192.168.4.50'));"
php artisan tinker --execute "event(new \App\Events\DeviceDisconnected(2,1,'Test Device','AA:BB:CC:DD:EE:FF'));"
php artisan tinker --execute "event(new \App\Events\TimeExpired(2,1,'Test Device','AA:BB:CC:DD:EE:FF'));"
php artisan tinker --execute "event(new \App\Events\TimeGranted(2,1,'Test Device',5,30,'manual'));"
php artisan tinker --execute "App\Models\AccessAttempt::create(['device_id'=>1,'type'=>'blocked_website','url'=>'https://facebook.com','domain'=>'facebook.com','ip_address'=>'192.168.4.50','attempted_at'=>now()]);"
php artisan tinker --execute "App\Models\AccessAttempt::create(['device_id'=>1,'type'=>'flagged_website','url'=>'https://reddit.com','domain'=>'reddit.com','ip_address'=>'192.168.4.50','attempted_at'=>now()]);"
```

Adjust user/device IDs to match actual Raspberry Pi database data.

---

## F) Post-deployment Evidence Template

After finishing Pi tests, document this in your test notes:

- Date/time:
- Git branch + commit hash deployed:
- Raspberry Pi hostname/IP:
- Parent user ID tested:
- WS handshake 101: PASS/FAIL
- `/broadcasting/auth` 200: PASS/FAIL
- `device.connected`: PASS/FAIL
- `device.disconnected`: PASS/FAIL
- `time.expired`: PASS/FAIL
- `time.granted (quiz)`: PASS/FAIL
- `time.granted (video)`: PASS/FAIL
- `website.blocked_accessed`: PASS/FAIL
- `website.flagged_visited`: PASS/FAIL
- UI scroll/layout checks: PASS/FAIL
- Reverb restart recovery: PASS/FAIL
- Notes/issues:

