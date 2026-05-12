# Raspberry Pi gateway: “no internet” debugging reference

**Context:** Parental WiFi runs on a Raspberry Pi as a router: Wi‑Fi clients on `wlan0` (captive portal / NoDogSplash), upstream internet on `eth0`. Laravel drives blocklists, time limits, and scripts under `scripts/`.

**Audience:** Future you (or anyone) when phones show “no internet”, `ndsctl` looks fine, or only some devices work.

**Related:** Application-level child access (jobs, quiz, `allow_device_through`) is documented in [CHILD_DEVICE_INTERNET_ACCESS_FIX.md](CHILD_DEVICE_INTERNET_ACCESS_FIX.md). This document focuses on **OS / firewall / DHCP / Tailscale** interactions on the Pi.

---

## 1. Mental model (what must be true)

| Layer | What “good” looks like |
|--------|-------------------------|
| **WAN (`eth0`)** | One IPv4 on the home LAN, one default route via the home router. Pi can `ping 8.8.8.8` and `curl -I https://example.com`. |
| **Forwarding** | `net.ipv4.ip_forward = 1`. `nat` `POSTROUTING` has `MASQUERADE` (or equivalent) out `eth0`. |
| **AP (`wlan0`)** | `hostapd` + `dnsmasq` up; gateway `192.168.4.1`; clients get DHCP. |
| **NoDogSplash** | `nodogsplash` / `ndsctl`: child **Authenticated** when they should have access. |
| **`filter` FORWARD** | Child traffic from `wlan0` hits **`ndsNET`** (unless iptables-whitelisted). Inside **`ndsNET`**, packets with NDS mark **`0x30000/0x30000`** jump to **`ndsAUT`** and are accepted. |
| **`mangle` PREROUTING** | **`ndsOUT`** sets marks for client traffic. Nothing later should **overwrite** those marks before `FORWARD` sees them. |

A failure at **WAN** breaks everyone. A failure at **marks / `ndsNET`** often breaks **only children** (parent may use iptables bypass). **`ndsctl` “Authenticated”** can still lie if **iptables** is dropping traffic for another reason.

---

## 2. Issue A — Two DHCP clients on `eth0` (dual IP / dual default route)

### Symptoms

- `ip -4 addr show dev eth0` shows **two** addresses on the same `/24` (e.g. `192.168.1.173` and `192.168.1.174`).
- `ip -4 route` shows **two** `default via … dev eth0` with different **metrics** / **src**.
- Inconsistent NAT: some clients or flows fail; “DNS works but nothing loads” is common.
- **Tailscale** `netcheck` may log gateway/self IP changing.

### Cause

**Both** `dhcpcd` and **NetworkManager** were obtaining a DHCP lease on **`eth0`** (Debian / Raspberry Pi OS with NM enabled).

### Fix

In `/etc/dhcpcd.conf`, near the top:

```text
denyinterfaces eth0
```

Then:

```bash
sudo systemctl restart dhcpcd
sudo nmcli connection down "Wired connection 1" && sudo nmcli connection up "Wired connection 1"
```

Confirm **one** `inet` on `eth0` and **one** default route. Keep **`wlan0`** managed by dhcpcd or your AP setup; **`99-unmanaged-devices.conf`** often has `unmanaged-devices=interface-name:wlan0` so NM does not fight `hostapd`.

### Optional check

```bash
systemctl is-active dhcpcd NetworkManager
```

---

## 3. Issue B — Iptables “whitelist” never matched (NoDogSplash won rule order)

### Symptoms

- `scripts/whitelist_device.sh` was run for the parent, but the parent still had no (or flaky) internet.
- `sudo iptables -S FORWARD | head -n5` showed **`ndsNET` before** the per-MAC **`ACCEPT`**.

### Cause

**iptables evaluates rules in order.** If line 1 is `-i wlan0 -j ndsNET`, **every** packet from Wi‑Fi enters **`ndsNET`** first. A second-line **`ACCEPT`** for a specific MAC is **never** used for forwarded traffic.

**NoDogSplash** (re)start can insert **`ndsNET` / `ndsRTR`** at position **1** after whitelist rules were added, undoing the intended “bypass portal” behaviour.

### Fix (in repo)

`scripts/whitelist_device.sh` now calls **`ensure_whitelist_before_portal_jump`**, which loops (short sleep) until the **first** `iptables -S` rule that contains **`-i wlan0`** is the whitelist **`ACCEPT`** for that MAC.

### Operational note

After **`sudo systemctl restart nodogsplash`**, re-run **`whitelist_device.sh`** for each MAC that must bypass **`ndsNET`** at the firewall (e.g. parent laptop).

Stale bypass rules when a device is no longer whitelisted in the app are removed via **`scripts/remove_whitelist_accept_rules.sh`** and Laravel hooks (see `NetworkService`, `DeviceController`).

---

## 4. Issue C — `ndsctl` = Authenticated but `ndsNET` final `REJECT` counters climb (Tailscale `CONNMARK`)

### Symptoms

- Child (or all non-bypass clients) **no internet**.
- **`sudo ndsctl status`** shows **Authenticated**.
- **`sudo iptables -L ndsNET -n -v --line-numbers`**: the last **`REJECT`** rule packet count **increases** when the phone browses; **`ndsAUT`** (mark `0x30000/0x30000`) gets comparatively little traffic.
- **`sudo ./scripts/whitelist_device.sh <child-mac>`** immediately restores internet → proves **WAN/AP OK**, **`ndsNET`/marks** path broken.

### Cause

**`mangle` `PREROUTING`** contained a **global** rule (commonly installed by **Tailscale**) along the lines of:

```text
-m conntrack --ctstate RELATED,ESTABLISHED -j CONNMARK --restore-mark --nfmask 0xff0000 --ctmask 0xff0000
```

with **no** `! -i wlan0`. That **restores** connection marks on **all** interfaces. For forwarded traffic from **`wlan0`**, it can **overwrite** NoDogSplash’s packet mark (**`0x30000`**) after **`ndsOUT`** has set it. **`FORWARD` → `ndsNET`** then does not match **`ndsAUT`**, and traffic hits the catch-all **`REJECT`**.

### Fix (in repo)

Run on the Pi (as root):

```bash
cd /var/www/parental_wifi
sudo bash scripts/fix_mangle_connmark_skip_wlan0.sh
```

The script **deletes** the broad **`CONNMARK` restore** (exact mask match) and **appends** the same restore with **`! -i wlan0`** so **`wlan0`** keeps NDS marks; **`eth0`** (and Tailscale’s expectations for non-AP traffic) still get restore where appropriate.

### After Tailscale / reboot

Tailscale may **re-insert** the global rule. **Re-run** the fix script, or hook it from **`tailscaled.service`** (`ExecStartPost=`) and/or persist rules with **netfilter-persistent** once verified.

---

## 5. Diagnostic commands (quick kit)

Run from `/var/www/parental_wifi` on the Pi unless noted.

| Goal | Command |
|------|---------|
| One-shot bundle | `sudo bash scripts/dump_gateway_state.sh` (includes **mangle PREROUTING**, **`ndsNET`**, full `iptables-save`, dnsmasq, `ndsctl`, routes) |
| WAN from Pi | `ping -c 3 192.168.1.1` then `ping -c 3 8.8.8.8` then `curl -sI --max-time 5 https://example.com \| head -n1` |
| `eth0` duplicates | `ip -4 addr show dev eth0` and `ip -4 route \| grep default` |
| FORWARD / INPUT order | `sudo iptables -S FORWARD \| head -n8` and `sudo iptables -S INPUT \| head -n8` |
| Portal state | `sudo ndsctl status` |
| `ndsNET` verdicts | `sudo iptables -L ndsNET -n -v --line-numbers` |
| Mangle / CONNMARK | `sudo iptables -t mangle -L PREROUTING -n -v --line-numbers \| head -n20` |
| Force NDS auth | `sudo ./scripts/allow_device_through.sh <MAC>` |
| Emergency full bypass (debug only) | `sudo ./scripts/whitelist_device.sh <MAC>` |
| Remove iptables bypass | `sudo ./scripts/remove_whitelist_accept_rules.sh <MAC>` |

---

## 6. Triage order (recommended)

1. **Pi itself reaches the internet?** If no, fix **`eth0`** / router first (including **Issue A**).
2. **`FORWARD` first `wlan0` rule** — parent bypass must be **`ACCEPT`** before **`ndsNET`** if you rely on **`whitelist_device.sh`** (**Issue B**).
3. **`ndsNET` `REJECT` vs `ndsAUT` counters** while a child loads a page — if **`REJECT`** grows but **`ndsctl`** is Authenticated, check **`mangle` `CONNMARK`** (**Issue C**).
4. **Phone “Private DNS”** (Android strict hostname) — can mimic “no internet”; test with Private DNS **Off**.
5. **Application layer** — quiz / time / jobs: see [CHILD_DEVICE_INTERNET_ACCESS_FIX.md](CHILD_DEVICE_INTERNET_ACCESS_FIX.md), [TIME_GRANTING_SERVICE.md](TIME_GRANTING_SERVICE.md).

---

## 7. Script index (this incident)

| Script | Role |
|--------|------|
| `scripts/dump_gateway_state.sh` | Collect gateway state to a `/tmp` file for sharing or diffing. |
| `scripts/fix_mangle_connmark_skip_wlan0.sh` | Replace global Tailscale-style **`CONNMARK` restore** with **`! -i wlan0`** variant. |
| `scripts/whitelist_device.sh` | Iptables **`ACCEPT`** before **`ndsRTR`/`ndsNET`**, with reorder loop. |
| `scripts/remove_whitelist_accept_rules.sh` | Remove stray **`ACCEPT`** bypass rules for a MAC. |
| `scripts/allow_device_through.sh` | **`ndsctl auth`** / portal side allow for a MAC. |

---

## 8. Changelog (high level)

| When | What |
|------|------|
| 2026-05 | Documented **dual DHCP on `eth0`**, **whitelist vs `ndsNET` order**, and **Tailscale `CONNMARK` vs NoDogSplash marks**; added `fix_mangle_connmark_skip_wlan0.sh` and extended `dump_gateway_state.sh`. |

---

**Last updated:** 2026-05-13
