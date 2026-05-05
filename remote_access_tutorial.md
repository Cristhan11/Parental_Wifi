# Remote Access to Raspberry Pi using Tailscale (Beginner Guide)

This guide explains how to remotely access your Raspberry Pi in a simple and safe way using **Tailscale**.

You already know basic networking and IP addresses, so think of Tailscale as:
- A private VPN network for your own devices
- A way to access your Raspberry Pi from anywhere without opening router ports
- A secure "virtual LAN" over the internet

---

## Architecture first (how your setup works)

Your remote access flow is:

`Your Laptop/PC` -> `Tailscale client` -> `Encrypted tunnel over internet` -> `Tailscale client on Raspberry Pi` -> `SSH / Web app on Pi`

What this means in plain words:
- Both your computer and Raspberry Pi run Tailscale
- Both sign in to the same Tailscale network (tailnet)
- Tailscale creates a secure private path between them
- You connect to the Pi's Tailscale IP (`100.x.x.x`) instead of public IP

Yes, this setup uses **tunneling**.

---

## Terminologies (quick and simple)

- **Tailnet**: your private Tailscale network (all your trusted devices)
- **Tailscale IP**: private VPN IP assigned by Tailscale (often `100.x.x.x`)
- **Tunnel / Tunneling**: a protected path that carries your traffic inside encrypted packets across the public internet
- **Encrypted**: data is scrambled so others cannot read it in transit
- **SSH**: secure remote terminal access to Raspberry Pi
- **MagicDNS**: lets you use hostnames (like `raspberrypi`) instead of IP addresses
- **Port forwarding**: router config usually needed for remote access on the public internet (not needed with Tailscale in normal use)

### What is tunneling, exactly?

Normally, internet traffic is exposed to many networks in between source and destination.

With tunneling:
1. Your real traffic (for example, SSH) is wrapped inside another secure packet
2. That packet travels across the internet
3. Only the other endpoint (your Raspberry Pi's Tailscale client) unwraps and reads it

Think of it as putting your message in a locked container before sending it through public roads.

---

## 1) What you need first

Before starting, make sure you have:

1. A Raspberry Pi connected to the internet
2. Another device you will use to connect (laptop/PC/phone)
3. A Tailscale account (Google, Microsoft, GitHub, etc.)
4. SSH enabled on Raspberry Pi (for terminal remote access)

---

## 2) Basic concept (very important)

When both devices (your PC and Raspberry Pi) log into the same Tailscale account:

- Each device gets a **Tailscale IP** (usually `100.x.x.x`)
- You can use that IP to connect directly
- No port forwarding needed on your home router

So instead of using your public IP and router settings, you use the Pi's Tailscale IP.

---

## 3) Install Tailscale on Raspberry Pi

Open terminal on Raspberry Pi and run:

```bash
curl -fsSL https://tailscale.com/install.sh | sh
```

Then start and authenticate:

```bash
sudo tailscale up
```

It will show a login URL. Open that URL in a browser and sign in to your Tailscale account.

After login, verify status:

```bash
tailscale status
```

Check the Pi's Tailscale IP:

```bash
tailscale ip -4
```

Save this IP. Example: `100.88.12.34`

---

## 4) Install Tailscale on your computer

On your laptop/desktop:

1. Download Tailscale from: [https://tailscale.com/download](https://tailscale.com/download)
2. Install it
3. Log in with the **same account** used on Raspberry Pi

Now both devices should appear in your Tailscale admin page:
[https://login.tailscale.com/admin/machines](https://login.tailscale.com/admin/machines)

---

## 5) Test connectivity

From your computer terminal:

```bash
ping <PI_TAILSCALE_IP>
```

Example:

```bash
ping 100.88.12.34
```

If ping works, your tunnel is working.

> Note: Some systems block ping replies by default. If ping fails, still try SSH first.

---

## 6) Connect to Raspberry Pi via SSH (main use)

Use either Tailscale IP or machine name.

### Option A: Using Tailscale IP

```bash
ssh pi@100.88.12.34
```

### Option B: Using Tailscale hostname

Find hostname:

```bash
tailscale status
```

Then connect:

```bash
ssh pi@raspberrypi
```

(Sometimes full name is needed, like `raspberrypi.tailnet-name.ts.net`.)

---

## 7) Enable MagicDNS (optional but recommended)

MagicDNS lets you connect by name instead of IP.

1. Go to Tailscale admin console
2. Open **DNS** settings
3. Enable **MagicDNS**

Then you can SSH using stable names instead of remembering IP addresses.

---

## 8) Access other services (not only SSH)

If your Raspberry Pi runs web apps, you can access them remotely too.

Example: app running on Pi port 3000

```text
http://100.88.12.34:3000
```

Or using MagicDNS name:

```text
http://raspberrypi:3000
```

---

## 9) Apply this to our Laravel codebase

For this project (`parental_wifi`), Tailscale mainly changes **how you reach the server**, not your business logic.
You usually do not need to rewrite controllers/models just for Tailscale.

What you should configure:

### A) Run Laravel so it accepts network connections

If you use Laravel's built-in server for testing, bind to all interfaces:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Then access from your laptop using Pi Tailscale IP:

```text
http://100.x.x.x:8000
```

If you bind only to `127.0.0.1`, remote Tailscale devices cannot open it.

### B) Set correct app URL in `.env`

On Raspberry Pi project `.env`:

```env
APP_URL=http://100.x.x.x:8000
```

Or if you use MagicDNS:

```env
APP_URL=http://raspberrypi:8000
```

Then clear config cache:

```bash
php artisan config:clear
php artisan cache:clear
```

Why this matters: generated URLs, redirects, and some auth flows depend on `APP_URL`.

### C) Session/cookie settings for HTTP testing

If you are using plain HTTP while developing over Tailscale, make sure secure-cookie forcing is not breaking login sessions.

In `.env` (development only):

```env
SESSION_SECURE_COOKIE=false
```

If you later enable HTTPS, set it back to `true`.

### D) If using Nginx/Apache (recommended for stable deployment)

- Keep Laravel behind Nginx/Apache on Pi
- Bind web server to `0.0.0.0` or Pi LAN IP
- Use firewall rules so only trusted sources (like Tailscale interface) can reach app ports

Basic idea: Tailscale provides secure network path; web server still serves Laravel as normal.

### E) Optional: lock app access to Tailscale IP range

For internal-only admin/testing pages, you can allow only Tailscale clients (`100.64.0.0/10`) at web server level.

This is optional but good for safety when app should be private.

### F) Quick verification checklist (for our code/app)

1. `tailscale status` on Pi is healthy
2. Laravel app is running and bound to `0.0.0.0`
3. `APP_URL` matches Tailscale IP/hostname + correct port
4. You can open app from laptop via `http://<PI_TAILSCALE_IP>:<PORT>`
5. Login/session works after setting proper cookie security for your environment

### G) Exact end-to-end setup we did (chronological, with explanation)

This section explains the exact setup flow we used so remote access works in our system, from infrastructure up to Laravel behavior.

#### Step 0 - Download and install Tailscale on the Raspberry Pi

If Tailscale is not installed yet, install it first before continuing with the rest of this flow.

Install command:

```bash
curl -fsSL https://tailscale.com/install.sh | sh
```

Why this matters:
- The Pi needs the `tailscale` and `tailscaled` binaries so it can join your tailnet.
- Without this step, remote access over Tailscale cannot work.

#### Step 1 - Put Raspberry Pi online with a stable app path

We used the app directory:

```bash
cd /var/www/parental_wifi
```

Why this matters:
- All deploy/config commands (`artisan`, `.env`, migrations, cache clear) must run from this project root.

#### Step 2 - Enroll the Pi into our Tailscale tailnet

On Raspberry Pi, we signed the device into our tailnet and kept `tailscaled` running.

Enrollment commands we used:

```bash
sudo tailscale up
```

What happens after running it:
- Tailscale prints a login URL in the terminal (for sign in / sign up).
- Open that URL in a browser, authenticate your account, then approve/add the device.
- After approval, return to the Pi terminal and wait for `tailscale up` to complete.

Verification commands we used:

```bash
hostname
tailscale status
tailscale ip -4
sudo systemctl status tailscaled --no-pager
```

What we confirmed on our deployment:
- Hostname: `parentalpi`
- Tailnet account: `parentalwifi@...`
- Pi Tailscale IPv4: `100.102.52.117`
- `tailscaled` is `active (running)` and enabled on boot

Why this matters:
- This gives the Pi a private reachable address (`100.102.52.117`) without opening router ports.

#### Step 3 - Keep web server reachable from tailnet

We serve Laravel behind Nginx and verified listener ports:

```bash
sudo systemctl status nginx --no-pager
sudo ss -tlnp | grep -E ':22|:80|:443|:8000|:8080'
```

What we observed:
- `0.0.0.0:22` -> SSH reachable remotely
- `0.0.0.0:80` -> Nginx serves dashboard via HTTP
- `0.0.0.0:8080` -> additional PHP service

Why this matters:
- Tailscale provides the network path only.
- Something on the Pi must listen on reachable interfaces (`0.0.0.0`) to answer incoming requests.

#### Step 4 - Configure Laravel env for remote-host-aware behavior

In `.env`/deployment env, we use remote-access related keys documented in `.env.example`:

```env
APP_URL=http://100.102.52.117
# TRUSTED_PROXIES=
# TRUSTED_PROXY_HEADERS=
# TRUSTED_LOCAL_CIDRS=192.168.0.0/16,10.0.0.0/8,172.16.0.0/12
```

And for websocket/reverb server binding:

```env
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

Why this matters:
- `APP_URL` helps URL generation fallback in CLI/mail contexts.
- Trusted proxy and CIDR settings control correct client IP handling and remote/local audit classification.
- Reverb binding on `0.0.0.0` allows remote clients to reach the websocket server when intended.

#### Step 5 - Add host correction middleware so redirects stay on Tailscale host

We enabled this middleware globally on web routes (`bootstrap/app.php`):

```php
$middleware->web(prepend: [
    \App\Http\Middleware\ForceRootUrlFromRequest::class,
]);
```

Core behavior inside `ForceRootUrlFromRequest`:

```php
if ($this->isTailscaleIpv4($listen) && ! $this->isTailscaleIpv4($host)) {
    $host = $listen;
}
```

Why this matters:
- In some proxy/FastCGI paths, Laravel can receive a LAN host header even when request came through Tailscale.
- This middleware prevents broken redirects by forcing generated URLs to the correct reachable host.

#### Step 6 - Centralize proxy/remote policy in code config

We keep remote access policy in `config/remote_access.php` and read from `.env`:

```php
'trusted_proxies' => env('TRUSTED_PROXIES') === '*'
    ? '*'
    : array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),
```

```php
'trusted_local_cidrs' => array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env(
        'TRUSTED_LOCAL_CIDRS',
        '192.168.0.0/16,10.0.0.0/8,172.16.0.0/12'
    ))
))),
```

Also applied during app boot in `AppServiceProvider`:

```php
if (is_string($proxies) && $proxies === '*') {
    TrustProxies::at('*');
} elseif (is_array($proxies) && count($proxies) > 0) {
    TrustProxies::at($proxies);
}
```

Why this matters:
- Correctly preserves real client IP and forwarded headers behind Nginx/proxy.
- Keeps audit behavior consistent (tailnet traffic treated as remote by default policy).

#### Step 7 - Validate remote access from external client

From a device with Tailscale enabled, we tested:

```bash
ssh snasna@100.102.52.117
```

```text
http://100.102.52.117/
```

If both work, the whole chain is functioning:
`Remote client` -> `Tailscale tunnel` -> `Pi 100.102.52.117` -> `Nginx :80` -> `Laravel app`

#### Step 8 - Operational checks after code updates

After pulling new code, we keep this routine:

```bash
cd /var/www/parental_wifi
git pull
composer install
php artisan migrate
php artisan config:clear
php artisan cache:clear
sudo systemctl reload nginx
```

Why this matters:
- Ensures schema/config/code are aligned so remote login/dashboard does not fail due to stale cache or missing migrations.

---

## 10) Troubleshooting quick checklist

If connection fails, check these one by one:

1. **Both devices online in Tailscale admin page**
2. **Both logged into same tailnet/account**
3. **Raspberry Pi has Tailscale running**
   ```bash
   tailscale status
   ```
4. **SSH service is active on Pi**
   ```bash
   sudo systemctl status ssh
   ```
5. **Try reconnecting Tailscale on Pi**
   ```bash
   sudo tailscale up
   ```
6. **Local firewall allows outgoing Tailscale traffic**

---

## 11) Security tips (important)

- Use strong passwords and preferably SSH keys
- Keep Raspberry Pi updated:
  ```bash
  sudo apt update && sudo apt upgrade -y
  ```
- Do not share your Tailscale account credentials
- Remove old/unused devices from Tailscale admin console

---

## 12) Daily workflow (simple)

When you want remote access:

1. Ensure Raspberry Pi is powered and connected to internet
2. Open Tailscale on your computer (logged in)
3. SSH into Pi:
   ```bash
   ssh pi@<PI_TAILSCALE_IP>
   ```
4. Work normally as if on same local network

---

## 13) Useful commands summary

On Raspberry Pi:

```bash
# show connected devices and state
tailscale status

# show this Pi's IPv4 Tailscale address
tailscale ip -4

# reconnect/auth if needed
sudo tailscale up
```

On your computer:

```bash
# test connectivity
ping <PI_TAILSCALE_IP>

# remote terminal access
ssh pi@<PI_TAILSCALE_IP>
```

---

If you want, I can also add a second section later for:
- Remote desktop (VNC/RDP) through Tailscale
- VS Code Remote SSH setup
- Accessing your Laravel app on Raspberry Pi from anywhere
