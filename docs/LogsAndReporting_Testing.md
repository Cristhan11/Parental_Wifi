# Logs & Reporting — Raspberry Pi Testing Guide

This guide walks you through **deploying and verifying** the **unified Logs** module and **Reporting / email** features on your Raspberry Pi, starting from `git pull`.

**Assumes your Pi matches** [`RASPBERRY_PI_SERVICES_SETUP.md`](./RASPBERRY_PI_SERVICES_SETUP.md):

| Setting | Value (your setup) |
|--------|----------------------|
| Linux user | `snasna` |
| Project path | `/var/www/parental_wifi` |
| Git remote | `git@github.com:Cristhan11/Parental_Wifi.git` |
| Branch | `main` |
| Web stack | Nginx + PHP 8.4-FPM + MariaDB |

If Nginx/MariaDB/PHP-FPM are not working, fix that first using the **Check Service Status** and **Troubleshooting** sections in [`RASPBERRY_PI_SERVICES_SETUP.md`](./RASPBERRY_PI_SERVICES_SETUP.md).

---

## What you are testing

1. **Unified logs** — Web UI at `/logs` (`LogsController`), filters, exports.
2. **Reporting** — Web UI at `/reports`, preferences, recipients, dispatch history.
3. **Automated checks** — PHPUnit + Artisan commands via `scripts/pi_verify_reporting_and_logs.sh`.
4. **Optional** — Real SMTP + queue digest (matches production behavior).

---

## Raspberry Pi — verified test matrix (reference)

Use this section as a **single checklist** of what was exercised on the Pi during development. Repeat it after future deploys when you change reporting, logs, or email.

1. **Git** — Verify a clean tree or stash before pull. Run `git status`. If only `composer.json` was edited locally for `process-timeout`, prefer matching `origin/main` (`git restore composer.json`) then `git pull` — the repo now includes `process-timeout` in `composer.json`.
2. **Config files** — Verify `config/network.php` comes from the repo. It is pulled with `main`; optional override via `.env` `NETWORK_LOG_PATH`.
3. **Dependencies** — Verify `vendor/` is up to date: `composer install --no-interaction`.
4. **Migrations** — Verify all reporting + logs-related tables: `php artisan migrate --force` — includes `reporting_preferences`, `reporting_recipients`, `report_dispatch_logs`, and **`reporting_recipient_events`** (recipient audit for Parent/Admin log stream).
5. **Config cache** — Verify `.env` is loaded: `php artisan config:cache` after `.env` edits.
6. **Queue worker** — Verify digests & jobs run: `sudo systemctl restart laravel-queue.service` after code/config changes. `sudo systemctl status laravel-queue.service` → **active (running)**.
7. **Cron** — Verify the scheduler runs: `crontab -l` shows `schedule:run` every minute. `php artisan schedule:list` shows `reporting:send-digest` tasks.
8. **Automated tests** — PHPUnit (no real email): `php artisan test tests/Feature/ReportingEmailConfigTest.php` → **PASS**. `php artisan test tests/Feature/PiCriticalLogsReportingTest.php` → **PASS**. Optional: `php artisan test tests/Feature/LogsParentAdminReportingTest.php` (recipient audit + logs UI wiring).
9. **Bundled script** — One-shot smoke test: `./scripts/pi_verify_reporting_and_logs.sh` → **Verification script finished OK** (runs `ReportingEmailConfigTest` + `PiCriticalLogsReportingTest` by default).
10. **Digest (queued)** — Verify the job completes: `php artisan reporting:send-digest daily` → “Queued … job(s)”. Then `sudo journalctl -u laravel-queue.service -n 30` → `DispatchDigestReportJob` **RUNNING** → **DONE**.
11. **Digest (manual test)** — SMTP + distinct Gmail thread: Reports UI **Send test digest** or `php artisan reporting:send-test <parent_id>`. Subject includes **`[Test …]`** suffix. Inbox receives mail; Reports **Dispatch history** shows `sent` (or `skipped` if empty period + skip empty).
12. **Gmail vs history** — Threading vs row count: same subject → one **thread** may show “2” while history shows more **sends** — open thread / check Spam / Promotions.
13. **Immediate alerts (preview)** — Two template emails: `php artisan reporting:send-dummy-immediate-alerts <parent_id>` — does **not** use the same audit rows as live events (see command comments).
14. **Immediate alerts (real pipeline)** — Events → listeners → mail: `php artisan tinker` → `AccessAttempt::create([...])` with `type` `blocked_website` / `flagged_website` for a **child** `device_id` owned by the parent. Inbox + `report_dispatch_logs` / Reports history.
15. **Logs → Parent/Admin** — Reporting recipients & prefs: `/logs` → **Parent/Admin Changes**, **Device: All devices**, widen **From/To**. Recipient add/update/enable/disable/remove appear via **`reporting_recipient_events`** (after migration). Preference rows still derived from `reporting_preferences` timestamps.
16. **Shutdown** — Safe power-off: `sudo shutdown -h now` or `sudo poweroff`, wait for activity to stop, then unplug power.

**Find a parent user id (for Artisan):**

```bash
php artisan tinker --execute="echo \App\Models\User::where('role','parent')->get(['id','name','email'])->toJson(JSON_PRETTY_PRINT);"
```

---

## Step 1 — SSH into the Pi

```bash
ssh snasna@<PI_IP_ADDRESS>
```

Use your Pi’s LAN IP (e.g. `192.168.x.x` on your home network, or `192.168.4.1` if you are connected to the Pi’s AP per the main doc).

---

## Step 2 — Go to the project and pull latest code

Same as **Git Operations → Pull Latest Changes** in [`RASPBERRY_PI_SERVICES_SETUP.md`](./RASPBERRY_PI_SERVICES_SETUP.md):

```bash
cd /var/www/parental_wifi
git status
```

If you have **no local changes** you care about:

```bash
git pull origin main
```

**Normal:** `git pull` may print a long list of changed files (paths with `+` / line counts). That only means the update applied — you are **not** stuck.

If Git reports conflicts or permission issues, use **Fix Permission Issues** / **Fix "Dubious Ownership"** in that doc, then pull again.

Confirm the new files exist:

```bash
ls -la scripts/pi_verify_reporting_and_logs.sh
ls -la tests/Feature/PiCriticalLogsReportingTest.php
ls -la docs/LogsAndReporting_Testing.md
```

Optional — see recent commits:

```bash
git log --oneline -5
```

---

## Step 3 — Install / update PHP dependencies

PHPUnit and `php artisan test` need `vendor/` (including **dev** dependencies).

```bash
cd /var/www/parental_wifi
composer install --no-interaction
```

- First run or after `composer.json` changes can take **several minutes** on a Pi — wait until Composer prints “Nothing to install” / “Generating autoload files” and returns to the shell prompt.
- If Composer warns about memory: `COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction`
- If you normally deploy with `composer install --no-dev`, run **full** `composer install` **while testing** so `phpunit` is available.

**How you know Step 3 finished OK:** no error at the end, and `ls vendor/bin/phpunit` shows the file (or `php artisan test --version` works).

---

## Step 4 — Environment file (`.env`)

Ensure `.env` exists (not committed to Git). Copy from `.env.example` if needed:

```bash
cp -n .env.example .env   # only if .env missing; -n = don't overwrite
php artisan key:generate  # only if APP_KEY empty
```

**Minimum checks for reporting:**

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Correct base URL (used in emails: `route('dashboard')`). |
| `DB_*` | MariaDB on Pi — must match Laravel `config/database.php`. |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM` | Required for **real** digest/alert emails (not for PHPUnit, which uses `MAIL_MAILER=array` from `phpunit.xml`). |
| `QUEUE_CONNECTION` | For production-style digests: **`database`** or **`redis`** + a **queue worker** (see Step 6). `sync` runs jobs inline (OK for quick smoke tests). |

**Reporting timezone default** (optional):

```env
REPORTING_DEFAULT_TIMEZONE=Asia/Manila
```

After editing `.env`:

```bash
php artisan config:clear
php artisan config:cache
```

---

## Step 5 — Run database migrations

```bash
cd /var/www/parental_wifi
php artisan migrate --force
```

Confirm reporting tables exist:

```bash
php artisan migrate:status | grep -E 'reporting_|report_dispatch'
```

You should see migrations such as:

- `2026_03_13_100001_create_reporting_preferences_table`
- `2026_03_13_100002_create_reporting_recipients_table`
- `2026_03_13_100003_create_report_dispatch_logs_table`
- `2026_03_22_120000_create_reporting_recipient_events_table` (audit rows for Parent/Admin log stream — add / edit / enable-disable / remove recipient)

Confirm:

```bash
php artisan migrate:status | grep -E 'reporting_|report_dispatch|reporting_recipient_events'
```

---

## Step 6 — Scheduler & queue (production-like behavior)

**Automated PHPUnit** (next step) does **not** require cron or a queue worker — it uses in-memory SQLite per `phpunit.xml`.

For **real** scheduled digests and **queued** `DispatchDigestReportJob`:

### 6a — Laravel scheduler (cron)

Laravel expects **one** cron entry that runs every minute:

```cron
* * * * * cd /var/www/parental_wifi && php artisan schedule:run >> /dev/null 2>&1
```

Install with `crontab -e` as user `snasna` (or root if that’s how you run it — be consistent).

Verify scheduled reporting commands are registered:

```bash
cd /var/www/parental_wifi
php artisan schedule:list | grep reporting
```

You should see `reporting:send-digest` (daily / weekly / monthly) as defined in `routes/console.php`.

### 6b — Queue worker (recommended: **systemd** on the Pi)

If `QUEUE_CONNECTION=database` (or `redis`), something must run **`php artisan queue:work`** continuously. **Customers should not run this by hand** — use **systemd** so it starts on boot and restarts on failure.

**Prerequisites**

- `.env` has `QUEUE_CONNECTION=database`
- Migrations have created the `jobs` / `failed_jobs` tables (`php artisan migrate --force`)
- Project path: `/var/www/parental_wifi`, app user: `snasna` (see [`RASPBERRY_PI_SERVICES_SETUP.md`](./RASPBERRY_PI_SERVICES_SETUP.md))

**1) Confirm PHP binary path**

```bash
which php
# e.g. /usr/bin/php
```

**2) Create a systemd unit** (run as root)

```bash
sudo nano /etc/systemd/system/laravel-queue.service
```

Paste (adjust `User=` / `Group=` / `ExecStart=` PHP path if yours differs):

```ini
[Unit]
Description=Laravel Queue Worker (parental_wifi)
After=network.target mariadb.service
# If MySQL/MariaDB must be up first; remove After= line if you use SQLite only

[Service]
Type=simple
User=snasna
Group=snasna
WorkingDirectory=/var/www/parental_wifi
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --no-interaction
Restart=always
RestartSec=5

# Optional: load .env via login shell (usually not needed; Laravel reads .env)
# Environment=APP_ENV=production

[Install]
WantedBy=multi-user.target
```

**3) Enable and start**

```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue.service
sudo systemctl start laravel-queue.service
sudo systemctl status laravel-queue.service
```

**4) Logs**

```bash
sudo journalctl -u laravel-queue.service -f
```

**5) After `.env` changes**

```bash
cd /var/www/parental_wifi && php artisan config:cache
sudo systemctl restart laravel-queue.service
```

**One-off test (no systemd)** — second SSH session:

```bash
cd /var/www/parental_wifi && php artisan queue:work
```

---

## Step 7 — Run the bundled verification script

From project root:

```bash
cd /var/www/parental_wifi
chmod +x scripts/pi_verify_reporting_and_logs.sh
./scripts/pi_verify_reporting_and_logs.sh
```

This script:

- Checks `.env` and `php artisan` bootstrap  
- Lists `reporting:*` Artisan commands  
- Greps `schedule:list` for digest tasks  
- Shows migration status for reporting tables  
- Runs PHPUnit:
  - `tests/Feature/ReportingEmailConfigTest.php`
  - `tests/Feature/PiCriticalLogsReportingTest.php`

To also run **logs + reporting recipient audit** tests (not in the script by default):

```bash
php artisan test tests/Feature/LogsParentAdminReportingTest.php
```

**Faster check** (skip PHPUnit, only Artisan + schedule):

```bash
PI_SKIP_PHPUNIT=1 ./scripts/pi_verify_reporting_and_logs.sh
```

---

## Step 8 — Run the same tests manually (optional)

```bash
cd /var/www/parental_wifi
php artisan test \
  tests/Feature/ReportingEmailConfigTest.php \
  tests/Feature/PiCriticalLogsReportingTest.php \
  tests/Feature/LogsParentAdminReportingTest.php
```

All tests should **pass**. They use the **testing** environment (`phpunit.xml`: SQLite `:memory:`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`) — **no real email** is sent.

---

## Step 9 — Browser tests (logged-in parent)

1. Open the app in a browser: `http://<PI_IP>/` or your configured domain (see Nginx in [`RASPBERRY_PI_SERVICES_SETUP.md`](./RASPBERRY_PI_SERVICES_SETUP.md)).
2. Log in as a **parent** user.
3. **Unified logs:** open **`/logs`** — page should load; try filters and tabs (`child_activity` / `parent_admin_changes`). On **Parent/Admin Changes**, reporting recipient actions (add, email/label change, enable/disable, remove) are stored in **`reporting_recipient_events`** and appear with summaries — widen **From/To** if you don’t see a recent change.
4. **Reporting:** open **`/reports`** — set timezone, toggles, add at least one **enabled recipient** with a real email you can check.

If you get 403 or redirect loops, confirm middleware and that the user role is `parent` (or `admin` where allowed).

---

## Step 10 — Optional: live digest test (real DB + SMTP + queue)

Prerequisites:

- Parent user exists in **MariaDB** (note their `users.id`).
- At least one row in `reporting_recipients` for that user with `is_enabled = 1`.
- `MAIL_*` in `.env` correct for your SMTP provider.
- Queue worker running if `QUEUE_CONNECTION` is not `sync`.

**Option A — Script helper** (after Step 7 succeeded):

```bash
export PI_LIVE_DIGEST_TEST=1
export PI_PARENT_USER_ID=<parent_id_from_database>
./scripts/pi_verify_reporting_and_logs.sh
```

**Option B — Artisan only**

```bash
cd /var/www/parental_wifi
php artisan reporting:send-test <parent_id>
```

Then check:

- Inbox for the digest email  
- `report_dispatch_logs` table (or **Reports** page history) for `sent` / `failed`

**Gmail / “only 2 messages” vs many `sent` rows:** Gmail often **threads** messages that share the same subject (same digest date range). The **conversation list** can show a count of **threads**, not individual messages — open the thread and use **Expand all** to see every send. Check **Spam** and **Promotions**, and search `in:anywhere from:your-from-address`.  
**Manual test sends** (Reports page **Send test digest** or `reporting:send-test`) append a unique `[Test YYYY-mm-dd …]` suffix to the subject so each send appears as its **own** thread. Scheduled production digests keep the normal subject so one thread per day is expected.

---

## Step 11 — Optional: preview emails without real events

**Dummy digest (fake numbers):**

```bash
php artisan reporting:send-dummy-digest <parent_id> daily
```

**Dummy immediate alerts (blocked + flagged templates):**

```bash
php artisan reporting:send-dummy-immediate-alerts <parent_id>
```

These send through real `MAIL_*` but do **not** pollute `report_dispatch_logs` the same way as production jobs (see command comments in code).

---

## Troubleshooting (quick)

| Symptom | Check |
|--------|--------|
| `git pull` fails | [`RASPBERRY_PI_SERVICES_SETUP.md`](./RASPBERRY_PI_SERVICES_SETUP.md) — Git section, permissions, `safe.directory` |
| `php artisan` errors | `storage/logs/laravel.log`, `php -v`, ownership on `storage` / `bootstrap/cache` |
| PHPUnit fails | `composer install` (with dev), `php artisan test` from `/var/www/parental_wifi` |
| Digest never sends | Cron `schedule:run`, `QUEUE_CONNECTION`, `queue:work`, parent has recipients + enabled digest |
| SMTP errors | `.env` `MAIL_*`, firewall, provider app-password / TLS port |
| History shows `sent` but inbox shows fewer messages | Gmail threading (same subject); Spam/Promotions; search `in:anywhere from:…`; use test-send unique subject (see Step 10) |
| Parent/Admin log missing a reporting change | Widen **From/To**; use **All devices** (recipient events are account-level); ensure migration `reporting_recipient_events` ran; actions only logged **after** that migration |

### Composer: `curl error 28` / timeout while downloading (common on Pi)

**What you saw:** `Failed to download … from dist: curl error 28 … Connection timed out`, then `Now trying to download from source` / `Syncing … into cache`.

That is **not always a failure** — Composer is switching to a slower Git clone instead of the GitHub zipball. **Let it run**; first install can take 10–30+ minutes on a slow link.

If it **keeps failing** or hangs forever:

1. **Increase timeouts** (then run `composer install` again from project root):

   ```bash
   export COMPOSER_PROCESS_TIMEOUT=0
   composer config --global process-timeout 2000
   ```

2. **Retry** (safe to run multiple times; resumes partial work):

   ```bash
   cd /var/www/parental_wifi
   composer install --no-interaction
   ```

3. **Prefer source** up front (fewer zip downloads via `api.github.com`):

   ```bash
   composer install --no-interaction --prefer-source
   ```

4. **Network:** ensure the Pi can reach GitHub (`ping github.com`, try another DNS like `1.1.1.1` in `/etc/resolv.conf` if needed), or run Composer when the network is less busy.

5. **Memory** (if Composer dies with OOM):

   ```bash
   COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction
   ```

6. **`git clone` exceeded the timeout of 300 seconds** (after many `curl error 28` fallbacks):

   Composer’s default **process** timeout kills long `git clone` operations. From the project root:

   ```bash
   composer config process-timeout 0
   export COMPOSER_PROCESS_TIMEOUT=0
   composer install --no-interaction
   ```

   Then retry; cache may already contain most packages.

---

## Related files in this repo

| File | Role |
|------|------|
| `scripts/pi_verify_reporting_and_logs.sh` | Pi verification script |
| `tests/Feature/PiCriticalLogsReportingTest.php` | HTTP + Artisan smoke tests |
| `tests/Feature/ReportingEmailConfigTest.php` | Reporting routes, prefs, recipients, digest job, mail fakes |
| `tests/Feature/LogsParentAdminReportingTest.php` | Parent/Admin log stream + `reporting_recipient_events` |
| `app/Observers/ReportingRecipientObserver.php` | Writes recipient audit rows for `/logs` |
| `docs/reporting_code_map.md` | How reporting + logs connect in code |

---

## Reference

- **Pi services & Git workflow:** [`RASPBERRY_PI_SERVICES_SETUP.md`](./RASPBERRY_PI_SERVICES_SETUP.md)
- **This guide’s checklist:** use **Raspberry Pi — verified test matrix (reference)** at the top when validating a deploy end-to-end.
