# TODO finals — RPI test adjustments (2)

## Time usage / session closure

**Note:** The scheduler should keep `TrackActiveSessions` and `CheckTimeExpiration` running (as in your `routes/console.php`). If those jobs don’t run, closing sessions on expiry will be delayed; the dashboard guard in (4) still avoids counting expired active time in most cases.

---

## How to test the dashboard timer on the Raspberry Pi

### 1. Prerequisites on the Pi

- **Laravel scheduler** must run every minute (crontab), e.g.  
  `* * * * * cd /path/to/parental_wifi && php artisan schedule:run >> /dev/null 2>&1`
- **Queue worker** (if jobs are queued): `php artisan queue:work` (or your systemd service), so `CheckTimeExpiration`, `TrackActiveSessions`, and `MonitorDeviceConnections` actually execute.
- Confirm schedules in `routes/console.php` — at minimum for this feature:
  - `MonitorDeviceConnections` — ensures devices on the LAN get **sessions** when appropriate.
  - `TrackActiveSessions` — deducts granted minutes and **closes sessions when quota hits 0**.
  - `CheckTimeExpiration` — blocks expired devices and **closes any remaining open sessions**.

Without the scheduler + jobs, **“Time left”** and **“Today”** may look wrong or frozen because **no `DeviceSession`** is created or updated.

### 2. What to verify on the parent dashboard

Open the dashboard as the parent user and check the **TIME USAGE** card:

- **Time left** — Counts down (per second when a session exists, or **page-load–based** countdown when connected but no session row yet — see UI behavior).
- **Today** — Increases while an **active `DeviceSession`** overlaps the current day and granted time is not exhausted; **not** “only when time expires.”
- **After quota is used** — **Time left** shows expired / 0; **Today** stops growing when the session ends (no idle Wi‑Fi tail counted as usage).
- **Subtitle** — Copy explains usage is **while granted internet time** applies, not idle Wi‑Fi after expiry.

### 3. Manual job runs (SSH on the Pi)

Useful when you don’t want to wait for cron:

```bash
cd /path/to/parental_wifi
php artisan schedule:run
```

Or dispatch the specific jobs (if your app exposes them; otherwise use `schedule:run` or Tinker to run services — see project patterns).

### 4. Optional: reset / grant time via Tinker (local or Pi)

If you need to **grant minutes** or **clear stuck “Expired”** after DB edits:

```php
use App\Models\Device;
use App\Models\DeviceSession;

// Example: grant time and re-activate
Device::where('name', 'Peter')->update([
    'remaining_time_minutes' => 10,
    'total_time_allocated' => 10,
    'status' => 'active',
]);

// Close open sessions so “Time left” matches DB (active session otherwise subtracts wall time)
DeviceSession::where('device_id', Device::where('name', 'Peter')->value('id'))
    ->whereNull('ended_at')
    ->update(['ended_at' => now()]);
```

Then **refresh the dashboard**. When the Pi monitor runs again, new sessions can start for connected devices.

### 5. Quick checklist

- [ ] Cron + `schedule:run` active on Pi  
- [ ] Queue worker running if jobs are queued  
- [ ] Child device **connected** on LAN / portal as designed  
- [ ] After testing expiry: confirm **Today** does **not** keep climbing while only Wi‑Fi is connected with **no** grant  

For deeper setup (systemd, logs), see `docs/RASPBERRY_PI_SERVICES_SETUP.md`.

---
## Dashboard graph (child device usage time) — RPI manual test

This is the new **GRAPHICAL REPRESENTATION** chart on the parent dashboard:
- One line per child device
- Filters: **Daily / Weekly / Monthly / Yearly**
- Real-time refresh using Reverb/Echo websocket events

### 1. Preconditions on the Pi
- [ ] Pi scheduler + queue worker are running (same as section above), so `DeviceSession` rows are created/closed and the broadcast events are emitted.
- [ ] Reverb is reachable from the parent browser (no firewall blocks). Verify `REVERB_PORT` / `REVERB_SCHEME` from `.env`.

### 2. Validate chart basics
Open `/dashboard` as a parent and locate **CHILD'S DEVICE USAGE TIME**.

- [ ] Chart shows multiple series (one per child device you expect).
- [ ] Legend labels match the child devices.
- [ ] Tooltip unit matches the active filter:
  - Daily: minutes (`min`)
  - Weekly/Monthly/Yearly: hours (`hr` / `hours`)

### 3. Validate axis caps (logical max values)
For each filter, confirm the graph’s Y-axis doesn’t show impossible totals:
- [ ] **Daily**: Y-axis max is capped to **60 minutes** per hour bucket.
- [ ] **Weekly**: Y-axis max is capped to **168 hours** total for the 7-day window.
- [ ] **Monthly**: Y-axis max is capped to **daysInMonth × 24 hours**.
- [ ] **Yearly**: X-axis starts at **January**, and Y-axis max matches annual capacity.

### 4. Validate X-axis labels
- [ ] **Weekly**: X-axis starts on **Monday** and shows **Mon → Sun**; each tick shows:
  - date on top (e.g. `Mar 23`)
  - weekday below (e.g. `Mon`)
- [ ] **Monthly**: X-axis shows only the available weeks inside the month (Week 1 .. Week 4 or Week 5).
- [ ] **Yearly**: X-axis always runs **Jan … Dec**.

### 5. Validate filter switching
- [ ] Tap **Daily** and confirm values + labels update.
- [ ] Tap **Weekly** and confirm Mon..Sun values + labels update.
- [ ] Tap **Monthly** and confirm Week 1..Week 4/5 values + labels update.
- [ ] Tap **Yearly** and confirm Jan..Dec values + labels update.

### 6. Validate real-time refresh (websockets)
Keep the dashboard open (do not reload). Then:
- [ ] Trigger `device.connected` by connecting a child device.
  - Expectation: chart refreshes automatically.
- [ ] Trigger `device.disconnected`.
  - Expectation: chart refreshes automatically.
- [ ] Trigger `time.granted` (grant minutes via parent action).
  - Expectation: chart refreshes automatically and usage reflects the updated sessions.
- [ ] Trigger `time.expired` by letting a device run out of quota.
  - Expectation: chart updates and does not keep counting past expiry rules.

Daily-specific note:
- [ ] Daily should also keep moving via the throttled timer while the page is visible (even if the session is still open).

### 7. If it doesn’t refresh
- [ ] Check backend logs for the emitted event types: `device.connected`, `device.disconnected`, `time.granted`, `time.expired`.
- [ ] Confirm `.env`: `BROADCAST_CONNECTION=reverb` and Reverb server is reachable.
- [ ] Confirm parent is authenticated so the frontend subscribes to `Echo.private(user.{id})`.
