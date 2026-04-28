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
