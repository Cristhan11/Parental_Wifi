# Remote dashboard access
To access the dashboard remotely, install **Tailscale** on the Raspberry Pi and on your phone or laptop, join them to the same tailnet (`sudo tailscale up` on the Pi, then sign in), open `http://<Pi-tailnet-hostname-or-100.x.y.z>/` in the browser using the same HTTP port as on your LAN (often 80, or 443 if you use HTTPS), and sign in at `/login` like you do at home.

The rest of this file lists **only what this repository configures** in Laravel. Tailscale is installed on devices outside this repo.

## Phone: Tailscale for remote dashboard access

1. Install **Tailscale** from the Google Play Store or Apple App Store (publisher **Tailscale Inc.**).
2. Open the app and sign in with **the same account / tailnet** you use for the Raspberry Pi (the identity you used when you first enrolled the Pi with `tailscale up`).
3. Turn **Tailscale on** so the phone joins the tailnet. In the app’s device list, confirm the Pi is **online**.
4. Find the Pi’s address: a **100.x.y.z** address, or the Pi’s **MagicDNS** name (for example `raspberrypi`). You can copy it from the Tailscale app or [login.tailscale.com](https://login.tailscale.com) under **Machines**.
5. Open **Chrome** (or any browser) on the phone. Go to `http://100.x.y.z/` or `http://<pi-magicdns-hostname>/`, **adding the port** if your home dashboard needs it (for example `http://100.x.y.z:8080`). Use **http** or **https** to match how you open the dashboard on your LAN. You should land on `/login` after the same redirect as at home.
6. If nothing loads, check that the Pi’s web server is running, that **Tailscale is enabled** on the phone (not paused), and that a firewall on the Pi still allows the HTTP/HTTPS port on the interfaces that receive tailnet traffic.

---

## On the Raspberry Pi (after `git pull`)

1. SSH into the Pi and `cd` to the Laravel app root (the directory that contains `artisan`).
2. `git pull`
3. `composer install` (add `--no-dev` on the Pi if you never install dev dependencies there).
4. `php artisan migrate` — adds or updates `security_audit_events`. Use `php artisan migrate --force` only if your deploy is non-interactive and you already use `--force` for other migrations.
5. If you cache config in production: `php artisan config:clear` then `php artisan config:cache` after any `.env` change (`APP_URL`, `TRUSTED_PROXIES`, `TRUSTED_LOCAL_CIDRS`, etc.).
6. Restart whatever serves PHP for this app (for example `sudo systemctl reload php8.2-fpm` — adjust the PHP version to match the Pi) and reload nginx/Caddy if you changed their config.
7. If you run queue workers: `php artisan queue:restart`.
8. Install Tailscale on the Pi if it is not already there, then `sudo tailscale up` and sign the node into your tailnet. Set `.env` to match how traffic reaches Laravel (see **`.env` keys this stack reads** below); set `TRUSTED_PROXIES` when nginx/Caddy sits in front of PHP.

---

## What this project configures

- **`App\Http\Middleware\ForceRootUrlFromRequest`** — Prepended on the **`web`** stack in `bootstrap/app.php`. Sets `URL::forceRootUrl()` from the incoming request’s scheme and host so redirects (for example to `/login`) use **the same host you opened** (`100.x`, MagicDNS, or `192.168.x.x`), not only `APP_URL`. Deploy with `git pull` on the Pi so Tailscale browsers are not sent to a fixed LAN IP in the `Location` header.
- **`config/remote_access.php`** — Reads `.env`: `TRUSTED_PROXIES`, optional `TRUSTED_PROXY_HEADERS`, `TRUSTED_LOCAL_CIDRS` (defaults: `192.168.0.0/16,10.0.0.0/8,172.16.0.0/12`; Tailscale `100.x` is not in the default list so tailnet traffic is stored as **remote** in audits).
- **Trusted proxies** — `App\Providers\AppServiceProvider` calls `TrustProxies::at(...)` when `TRUSTED_PROXIES` is set so `$request->ip()` is correct behind nginx/Caddy or a tunnel.
- **LAN vs remote for logs** — `App\Support\RequestSource` sets `is_remote` on each audit row from `$request->ip()` and `TRUSTED_LOCAL_CIDRS`.
- **Database** — Table `security_audit_events` (migration `database/migrations/2026_04_19_120000_create_security_audit_events_table.php`). Run **`php artisan migrate`** on the Pi if it is not applied yet.
- **Writer** — `App\Services\SecurityAuditLogger` and model `App\Models\SecurityAuditEvent`.
- **Auth logging** — Listeners in `app/Listeners/RecordSecurityAuditOn*.php`, registered in `App\Providers\AppServiceProvider` for `Login`, `Failed`, `Logout`, and `Lockout` on the **`web`** guard.
- **Sensitive actions** — Middleware `App\Http\Middleware\AuditSensitiveAction`, route alias **`audit.sensitive`**, on groups in `routes/web.php` (profile, parent dashboard, admin) and `routes/auth.php` (authenticated routes including password update). It logs after successful `POST`/`PUT`/`PATCH`/`DELETE` except routes named `logout`, `verification.send`, `verification.verify`.
- **Unified logs UI** — `LogsController` adds these rows to **Parent/Admin Changes** as event type **`security-access`** when no device filter is set. Parents see rows where `user_id` is theirs or `attempted_identifier` is their email; admins see all. Filter: **security-access** in `resources/views/logs/index.blade.php`.

---

## `.env` keys this stack reads

- **`APP_URL`** — Fallback for CLI, mail, and asset helpers; **web redirects follow the request host** via `ForceRootUrlFromRequest`. Keep `SESSION_DOMAIN` empty (null) unless you know you need a shared cookie domain across hostnames.
- **`TRUSTED_PROXIES`** — Comma-separated IPs, or `*`, when a reverse proxy sets `X-Forwarded-*`. Empty when the app sees the client IP directly.
- **`TRUSTED_PROXY_HEADERS`** — Optional; only if you need a non-default forwarded-header bitmask.
- **`TRUSTED_LOCAL_CIDRS`** — Comma-separated CIDRs counted as “local” for `is_remote = false` in `security_audit_events`.

See comments in `.env.example` under the remote-dashboard block.

---

## `security_audit_events` columns

- **`event`** — One of: `login_success`, `login_failure`, `logout`, `lockout`, `sensitive_action` (constants on `SecurityAuditEvent`).
- **`user_id`** — Set when the actor is logged in; null on failed anonymous login.
- **`attempted_identifier`** — Email from the login form on failure or lockout (stored as submitted).
- **`ip_address`** — Client IP after trusted-proxy handling.
- **`user_agent`** — Browser user agent when present.
- **`is_remote`** — Boolean from `RequestSource::isRemote()`.
- **`route_name`** — Laravel route name when useful.
- **`metadata`** — JSON (e.g. `remember` on login; `method` on sensitive actions).
- **`created_at` / `updated_at`** — Standard Eloquent timestamps for when the row was written.

`admin_action_logs` is unchanged for admin moderation actions; security rows add IP, `is_remote`, and failed logins in one stream.

---

## Viewing security rows in the app

1. Sign in as parent or admin.
2. Open **Logs**.
3. Choose **Parent/Admin Changes**.
4. Set **Event type** to **security-access** (optional) and adjust the time range.

---

## Reverb (live dashboard)

The app does not change Reverb for you. If live updates fail over Tailscale, set **`REVERB_HOST`** (and related vars) so the browser can reach the Pi on the tailnet; see `docs/websocket_tutorial.md`.

---

## Tests (local)

- **`tests/Feature/SecurityAuditLoggingTest.php`** — Covers login success/failure, lockout, sensitive profile update, and `is_remote` when the IP is outside `TRUSTED_LOCAL_CIDRS`.
- **`phpunit.xml`** — Sets `TRUSTED_LOCAL_CIDRS=127.0.0.1/32,::1/128` for PHPUnit.

---

## Quick fixes

- **Cannot load the site on Tailscale** — Tailscale only provides the path; something on the Pi must **listen on `0.0.0.0`** (all interfaces) or the request never reaches Laravel. On the Pi over SSH, run:
  - **`sudo ss -tlnp`** — Look for `LISTEN` on `:80`, `:443`, or `:8000`. If nothing listens on **80**, `http://100.x.x.x/` will never load (try the port you actually see, e.g. **8000**).
  - **`curl -sS -o /dev/null -w "%{http_code}\n" http://127.0.0.1/`** and the same against **`http://$(tailscale ip -4)/`** — If localhost works but the Tailscale IP fails, nginx/Caddy may be bound only to a LAN address; set **`listen`** to **`0.0.0.0:80`** (and **`443`** if used), not only `192.168.x.x`.
  - **`sudo ufw status`** — If `ufw` is active, allow the web port, e.g. **`sudo ufw allow 80/tcp`** (and **`443/tcp`** if you use HTTPS). Rules that only allow `eth0` can still block; allowing the port globally is simplest for home Pi.
  - **`php artisan serve`** (dev only) binds to **127.0.0.1:8000** by default, so **Tailscale cannot reach it**. Use **`php artisan serve --host=0.0.0.0 --port=8000`** and open **`http://<tailscale-ip>:8000/`** on the phone, or use **nginx/Caddy** on port 80 in production.
  - **HTTPS to a raw IP** often fails (no certificate for `100.x.x.x`). Use **`http://`** for tests, or use a name + valid cert / tailnet-only HTTP.
- **Wrong IP in security rows** — Set `TRUSTED_PROXIES` to your proxy (or `*` only if you understand single-hop trust).
- **LAN logins show as remote** — Add your home subnet to `TRUSTED_LOCAL_CIDRS`.
- **Tailnet shows as local** — Remove `100.64.0.0/10` from `TRUSTED_LOCAL_CIDRS` if you added it and want tailnet labeled remote.

---

## Traceability

- Scope: [docs/scope_todo_finals.md](scope_todo_finals.md) — section **remote-dashboard-access** (`remote-01`–`remote-05`).
