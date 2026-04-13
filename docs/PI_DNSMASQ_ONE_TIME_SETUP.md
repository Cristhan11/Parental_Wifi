# One-time Pi setup: dnsmasq block list (no SSH for parents)

Parents only use the **web app**. The Pi must allow the PHP user (usually `www-data`) to run **`update_dnsmasq_global_blocklist.sh`** as root **once**, via sudoers. This document is for **you** (installer/admin), not for non-technical parents.

## What this fixes

If sudoers is missing this rule, the database can show the correct blocked sites while **dnsmasq keeps old domains** (e.g. Facebook still blocked after you removed it in the UI).

## Prerequisites

- App deployed on the Pi (Laravel + scripts directory present).
- You can log in with **SSH** and use **`sudo`** once.

## Step A — Pull or copy the latest project onto the Pi

Ensure the repo includes `scripts/pi-setup-dnsmasq-global-sudo.sh` and `scripts/update_dnsmasq_global_blocklist.sh`. Adjust the path below if your install is not `/var/www/parental_wifi`.

## Step B — Run the automated setup (recommended)

```bash
cd /var/www/parental_wifi
sudo bash scripts/pi-setup-dnsmasq-global-sudo.sh
```

**What it does:**

1. Resolves the **absolute path** to `scripts/update_dnsmasq_global_blocklist.sh`.
2. Sets **`chmod +x`** on that script.
3. **Backs up** `/etc/sudoers.d/parental-wifi-scripts` if it already exists.
4. **Appends** a single **NOPASSWD** line for that script and user `www-data` (unless already present).
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

## Step E — Confirm dnsmasq config (optional)

```bash
sudo cat /etc/dnsmasq.d/parental-global-blocklist.conf
ls -la /etc/dnsmasq.d/blocked-domains-*.conf 2>/dev/null || true
```

The global file should match what you expect from the app. Legacy `blocked-domains-*.conf` files may be removed when the global script runs successfully.

## After this

**Parents do not need SSH.** They change blocked sites in the UI; Laravel calls the script with sudo as allowed by sudoers.

## More detail

See `docs/SUDOERS_UPDATE_DNS_BLOCKING.md` for manual sudoers editing and troubleshooting.
