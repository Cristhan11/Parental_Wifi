# One-time Pi setup: dnsmasq block list (no SSH for parents)

Parents only use the **web app**. The Pi must allow the PHP user (usually `www-data`) to run **`update_dnsmasq_global_blocklist.sh`** and **`update_dnsmasq_dhcp_dns_bypass.sh`** as root **once**, via sudoers. This document is for **you** (installer/admin), not for non-technical parents.

## What this fixes

If sudoers is missing this rule, the database can show the correct blocked sites while **dnsmasq keeps old domains** (e.g. Facebook still blocked after you removed it in the UI).

## Prerequisites

- App deployed on the Pi (Laravel + scripts directory present).
- You can log in with **SSH** and use **`sudo`** once.

## Step A — Pull or copy the latest project onto the Pi

Ensure the repo includes `scripts/pi-setup-dnsmasq-global-sudo.sh`, `scripts/update_dnsmasq_global_blocklist.sh`, and `scripts/update_dnsmasq_dhcp_dns_bypass.sh`. Adjust the path below if your install is not `/var/www/parental_wifi`.

## Step B — Run the automated setup (recommended)

```bash
cd /var/www/parental_wifi
sudo bash scripts/pi-setup-dnsmasq-global-sudo.sh
```

**What it does:**

1. Resolves the **absolute paths** to `scripts/update_dnsmasq_global_blocklist.sh` and `scripts/update_dnsmasq_dhcp_dns_bypass.sh`.
2. Sets **`chmod +x`** on both scripts.
3. **Backs up** `/etc/sudoers.d/parental-wifi-scripts` when it appends a new line.
4. **Appends** **NOPASSWD** lines for both scripts and user `www-data` (each line is skipped if already present).
5. Sets sudoers file mode **0440** and owner **root:root**.
6. Runs **`visudo -c`** to validate syntax.

**Custom install path or web user:**

```bash
sudo bash /path/to/parental_wifi/scripts/pi-setup-dnsmasq-global-sudo.sh /path/to/parental_wifi snasna
```

Second argument is the user that runs PHP-FPM (see `docs/SUDOERS_UPDATE_DNS_BLOCKING.md`).

## Step C — Deploy Laravel code (if you have not already)

From the app directory, as the user that owns the deploy (often `www-data` or your deploy user):

```bash
cd /var/www/parental_wifi
php artisan config:clear
php artisan migrate --force
```

Use `sudo -u www-data` if your workflow requires it.

## Step D — Verify sync works

Either:

1. In the browser: add or edit a blocked site and save — dnsmasq should update with **no** warning (if `DNSMASQ_WARN_WHEN_SYNC_FAILS` is enabled on the Pi), or  
2. On the Pi:

```bash
cd /var/www/parental_wifi
sudo -u www-data php artisan dnsmasq:sync-blocklist YOUR_PARENT_USER_ID
```

Replace `YOUR_PARENT_USER_ID` with the parent account’s `users.id` from the database.

That command refreshes both the **global blocklist** and the **DHCP DNS bypass** file (parent/guest/whitelisted MACs get upstream DNS so blocked sites do not apply to them).

## Step E — Confirm dnsmasq config (optional)

```bash
sudo cat /etc/dnsmasq.d/parental-global-blocklist.conf
sudo cat /etc/dnsmasq.d/parental-dhcp-dns-bypass.conf 2>/dev/null || true
ls -la /etc/dnsmasq.d/blocked-domains-*.conf 2>/dev/null || true
```

The global file should match what you expect from the app. Legacy `blocked-domains-*.conf` files may be removed when the global script runs successfully.

### DHCP renew after role or whitelist changes

Devices learn DNS servers from DHCP when they get or renew a lease. After you change a device to **parent**, **guest**, or **whitelisted**, the Pi config updates immediately, but phones/laptops may keep the old DNS until they **renew DHCP** (toggle Wi‑Fi off/on, or wait for the lease to expire).

## After this

**Parents do not need SSH.** They change blocked sites in the UI; Laravel calls the script with sudo as allowed by sudoers.

## Flagged websites (monitoring, not blocking)

Flagged domains are **not** written to dnsmasq. When a child visits a flagged site, the Pi records it from **DNS query logs** (`ParseNetworkLogs` job) and creates `access_attempts` (type `flagged_website`). For that to work on the Pi you still need:

- **dnsmasq query logging** and the log path Laravel uses (`NETWORK_LOG_PATH` in `.env`, often `/var/log/dnsmasq.log`) — see `docs/BROWSING_LOGS_REFERENCE.md`
- **Queue worker** running (`php artisan queue:work` or a systemd service) and/or **scheduler** (`schedule:run` cron) so `ParseNetworkLogs` actually runs

The flagged list in the UI is **household-wide** (`user_id`), same as blocked sites. Any child device under that parent can trigger a flagged visit.

## More detail

See `docs/SUDOERS_UPDATE_DNS_BLOCKING.md` for manual sudoers editing and troubleshooting.  
See `docs/FLAGGED_WEBSITES_QUICK_TEST.md` for quick flagged-site checks.
