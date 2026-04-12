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

### 3. Validate axis caps (per bucket — logical max values)
The Y-axis max applies to **one chart point** (one bucket), not the sum of the whole range:
- [ ] **Daily**: Y-axis max is **60 minutes** (one bucket = one clock hour).
- [ ] **Weekly**: Y-axis max is **24 hours** (one bucket = one calendar day).
- [ ] **Monthly**: Y-axis max is **168 hours** (one bucket = up to 7 days in that month slice).
- [ ] **Yearly**: X-axis starts at **January**; Y-axis max is **744 hours** (31 × 24 — longest calendar month per bucket).

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

### 8. Optional: dummy data for a visible graph (Pi or dev machine)
If all lines are flat at zero, seed ended sessions used only for UI verification:

```bash
cd /path/to/parental_wifi
php artisan db:seed --class=DummyUsageChartDataSeeder
```

- [ ] After seeding, reload `/dashboard` and confirm **multiple child lines** with **clearly different** heights (light / heavy / medium pattern when devices match names **John**, **Peter**, **Test Device**, or hash-based profiles otherwise).
- [ ] Re-run the seeder is safe for dummy rows (it cleans prior dummy `device_sessions` with 0 bytes in the same window).

---

## Child devices page — TIME USAGE graph (`/child_devices`) — RPI manual test

This page shows **one selected child** at a time. The chart uses the **same bucket logic and units** as the dashboard graph, loaded from  
`GET /child_devices/{device}/usage-chart?range=daily|weekly|monthly|yearly` (authenticated parent, must own the device).

### 1. Preconditions
- [ ] Parent can log in on the Pi-hosted app (HTTPS or your LAN URL).
- [ ] At least one **child** device exists under that parent (dropdown **CHILD:**).
- [ ] Same optional pieces as the dashboard chart: scheduler/queue if you want live sessions; Reverb if you want websocket refresh on this page.

### 2. Navigation and layout
- [ ] Open **Child devices** from the dashboard (or go directly to `/child_devices`).
- [ ] **TIME USAGE** card is **full width** (no TIME OFFLINE / TIME ONLINE table beside the chart).
- [ ] Chart area is tall enough to read; canvas resizes with the window.

### 3. Child selector + chart scope
- [ ] Change **CHILD:** in the dropdown; page reloads and the chart should reflect **only that device** (one line in the legend).
- [ ] Pick a second child and confirm values/line **change** (not the same as the first if usage differs).

### 4. Filters (parity with dashboard)
With each filter, confirm labels, units, and that values look plausible (compare with dashboard for the same child if needed):

- [ ] **Daily** — 24 hourly labels (e.g. `00`–`23`), tooltip in **minutes**, Y max **60**.
- [ ] **Weekly** — **Mon → Sun** with date + weekday labels; tooltip in **hours**; Y max **24** per day.
- [ ] **Monthly** — **Week 1 … Week n** for the current month; tooltip in **hours**; Y max **168** per week bucket.
- [ ] **Yearly** — **Jan … Dec**; tooltip in **hours**; Y max **744** per month bucket.

### 5. API and authorization (quick check from Pi or laptop on same LAN)
If something fails silently in the browser, verify the JSON endpoint (replace `{id}`):

```bash
# After logging in via browser, or use curl with session cookie — easiest is DevTools → Network
# while toggling filters and confirm 200 on:
# GET /child_devices/{id}/usage-chart?range=yearly
```

- [ ] Response is **200** for your own child’s `id`.
- [ ] JSON includes `range`, `unit`, `labels`, `series` (for one child, `series` has **one** object).
- [ ] Using another parent’s session / wrong device id returns **403** (not a leak of data).

### 6. Refresh behavior on `/child_devices`
- [ ] Switching **Daily / Weekly / Monthly / Yearly** refetches and redraws without a full page reload (except changing the child dropdown, which submits the form).
- [ ] **Daily** + tab visible: chart may refresh on a **~60s** timer (same idea as dashboard).
- [ ] If Reverb is running: connect/disconnect child, grant time, expire time — chart should refresh when those events fire (optional; same channel as dashboard).

### 7. Dummy data (optional)
Same as dashboard §8:

```bash
php artisan db:seed --class=DummyUsageChartDataSeeder
```

- [ ] On `/child_devices`, select each seeded child and confirm **non-flat** lines where sessions exist for that device.

### 8. Checklist summary (child page)
- [ ] Full-width chart, filters work, units and Y caps match §4  
- [ ] Dropdown switches device; chart is single-series for that device  
- [ ] `usage-chart` JSON OK for owner, forbidden for others  
- [ ] (Optional) Reverb + dummy seeder for easier visual confirmation  
