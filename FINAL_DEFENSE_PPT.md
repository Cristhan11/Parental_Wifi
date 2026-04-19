# Final Defense — PowerPoint Script (Aligned to System Scope)

**Primary focus:** The system **helps parents monitor and control assigned child devices** on a dedicated child network, with rules, learning checks, alerts, and reports—all from a **web-based parental dashboard**.

**Audience:** Beginners + panel  
**Target length:** ~10 minutes (~45–55 seconds per slide; tighten Slides 4–8 if you run over)  
**How to use this file:** Each `## Slide N` is **one PowerPoint slide**. Copy **On-slide (PPT)** into the slide; paste **Speaker notes (for us)** into PowerPoint’s Notes pane.

---

## Scope → slide map (quick reference)

| # | Requirement (summary) | Main slide |
|--:|------------------------|------------|
| 1 | Monitor visits; flag & block sites per assigned child device | Slide 4 |
| 2 | Redirect to quiz / educational video; must pass to continue internet | Slide 5 |
| 3 | Schedules & duration of internet use from parent side | Slide 6 |
| 4 | Real-time parent notifications (limits, flags, blocks, new devices) | Slide 7 |
| 5 | Monitor total online time per child device | Slide 6 |
| 6 | Daily / weekly / monthly usage reports (sites, flags, blocks, bandwidth) | Slide 8 |
| 7 | Web dashboard: configure access, flags, blocks, portal content, reports | Slide 4 |
| 8 | Manage devices: blocking & whitelisting | Slide 4 |
| 9 | Basic security: auth, firewall, MAC whitelist, sessions, log monitoring | Slide 9 |

Slides **2–3** introduce the nine goals and how the system is built around them; **Slide 10** ties runtime; **Slide 11** closes.

---

## Timing cheat sheet (~10 minutes)

| Slide | Topic | ~Time |
|------:|--------|------:|
| 1 | Title + purpose | 45 s |
| 2 | Nine capabilities (spec list) | 1 min |
| 3 | Architecture (brief) | 1 min |
| 4 | (1)(7)(8) Monitor, flag, block + dashboard + lists | 1 min 15 s |
| 5 | (2) Quiz / video / continuation | 1 min |
| 6 | (3)(5) Schedules, duration, total online time | 1 min |
| 7 | (4) Real-time notifications | 1 min |
| 8 | (6) Reports (daily / weekly / monthly) | 55 s |
| 9 | (9) Security | 55 s |
| 10 | How everything runs together | 1 min |
| 11 | Limits & closing | 40 s |

If long on time: shorten Slide 2 (read only numbers 1–3 aloud, skim 4–9) and Slide 10.

---

## Slide 1 — Title & purpose

### On-slide (PPT)

- **Title:** Parental Wi‑Fi — Monitoring & Control System  
- **Subtitle:** Helping parents **supervise and manage internet use** of **assigned child devices**  
- **Names / course / date** (fill in)

### Speaker notes (for us)

Open with one clear sentence: **“Our system is built so parents can see what children do online, set boundaries, and get alerts—without needing to be network experts.”** Everything else in the talk maps to the **nine concrete capabilities** on the next slide.

---

## Slide 2 — What the system delivers (nine objectives)

### On-slide (PPT)

**The system supports parents by:**

1. **Monitor** visited sites; **flag** and **block** sites on **assigned child devices**  
2. **Redirect** children to a **quiz** or **educational video**; must **pass / complete** to **continue** internet  
3. **Schedules** and **duration** of internet use—set from the **parent** side  
4. **Real-time** notices to the parent: **time limit reached**, **flagged site visited**, **blocked access attempt**, **new device** connected  
5. **Total online time** tracked per child device  
6. **Reports:** **daily**, **weekly**, **monthly** — usage, visited sites, flagged visits, block attempts, **bandwidth**  
7. **Web dashboard** for parents: access rules, flags, blocks, **portal** content (quizzes/videos), **reports**  
8. **Device management:** **blocking** and **whitelisting**  
9. **Security basics:** login, firewall, **MAC** controls, sessions, **log** review  

### Speaker notes (for us)

This slide is your **checklist with the panel**. Read it calmly: you are not promising “perfect parenting,” you are promising **these nine functions** wired into one system. **Beginner framing:** items **1–3** are **rules**, **4–6** are **awareness and history**, **7–8** are **how parents operate the system**, **9** is **why strangers cannot hijack it**.

---

## Slide 3 — Big picture (how the nine goals are possible)

### On-slide (PPT)

```
Internet → Home router → Raspberry Pi (child Wi‑Fi / gateway)
                         ├── DNS & firewall (enforce blocks, see domains)
                         └── Laravel app + MariaDB
                                  ↑
                         Parent browser (dashboard)
```

- **Assigned child devices** use the **child network** → traffic passes the **Pi**  
- **Laravel** stores **devices, rules, logs, quizzes, reports**  
- Parents only need the **dashboard** (Scope **7**)

### Speaker notes (for us)

**Simple idea:** if the child’s tablet uses **this Wi‑Fi**, the house rule is “**everything goes through our box**.” That is why **monitoring (1,5)**, **blocking (1,8)**, **redirects (2)**, and **alerts (4)** are technically realistic—the Pi is on the **path** of the traffic. The **dashboard** is the friendly control panel so parents never type Linux commands for normal tasks (**7**).

---

## Slide 4 — (1) Monitoring & blocking · (7) Dashboard · (8) Lists

### On-slide (PPT)

- **(1)** **Visited websites** → logs / history (e.g. DNS-based visibility); **flag** for warnings; **block** (URL / domain / app-style groups) **per assigned device**  
- **(7)** Parent **web dashboard**: review history, set flags/blocks, manage portal learning content  
- **(8)** **Per-device** control: **blocklist** & **whitelist** / allow–deny lists  

### Speaker notes (for us)

**One-liner:** Devices must **look up domain names** first—so we see **which site/app**, not the whole encrypted page. **Flag** = warn; **block** = stop; **whitelist** = allow rules per device.

**How (≈15 s):** **dnsmasq** on the Pi logs DNS → **`ParseNetworkLogs`** turns that into **history**. Blocks: **dnsmasq** returns a **dummy IP** for blocked names; saving from the dashboard **syncs blocklists** to the Pi (scripts). **iptables** + **MAC** adds **allow/deny** at the network layer.

---

## Slide 5 — (2) Quiz / educational video & continuation of internet

### On-slide (PPT)

- Child is steered to **portal** flows: **quiz** and/or **educational video**  
- **Pass or complete** activity → system can **grant** or **restore** internet time / access (**continuation**)  
- Parents **choose** content from the dashboard (**7**)

### Speaker notes (for us)

**Story (10 s):** “Homework gate”—finish quiz/video → **more access**; progress is stored **on the server**, not only in the browser.

**What steers to portal (≈20 s):** **NoDogSplash** on the Pi is the **captive portal**: it **redirects** the child (via splash → our **Laravel `/portal`**). **`NoDogSplashService`** + short **Pi scripts** tell NDS to **trap** or **release** the device by **MAC** after **`PortalController`** records **pass/complete**.

---

## Slide 6 — (3) Schedules & duration · (5) Total time online

### On-slide (PPT)

- **(3)** **Schedules** (when Wi‑Fi / access is allowed) + **duration** / daily limits from **parent** account  
- **(5)** **Total online time** per device tracked and shown (charts / summaries)  
- Enforcement lines up with **network + device state** + stored limits  

### Speaker notes (for us)

**Beginner (10 s):** **Schedule** = allowed hours/days; **duration** = minutes budget; **online time** = **was the device actually on the Wi‑Fi**.

**How (≈20 s):** **`EnforceSchedules`** (≈every **minute**) applies DB rules via **`NetworkService`** + **`NoDogSplashService`** on the Pi—wrong window or over limit → **block or splash**. **`MonitorDeviceConnections`** tracks **who is connected** → **charts / total time**.

---

## Slide 7 — (4) Real-time notifications to the parent

### On-slide (PPT)

Parent gets **timely** updates when:

- **Usage limit reached** (time expired)  
- **Flagged website visited**  
- **Attempt** to reach **blocked** site  
- **New device** connects to the child network  

**Channels:** **live** updates on the **dashboard** (WebSockets / broadcasting) + optional **email** alerts when configured  

### Speaker notes (for us)

**Meaning (10 s):** Dashboard **pings live** (no refresh); **email** if parents enabled it—like alerts for “time up,” “flagged site,” “blocked try,” “new gadget.”

**How (≈20 s):** Jobs detect state → Laravel **broadcasts** (`TimeExpired`, `FlaggedWebsiteVisited`, `BlockedWebsiteAccessed`, `DeviceConnected`) on **`user.{id}`**; **Echo + Reverb** show toasts; **mail listeners** send instant alerts when configured.

---

## Slide 8 — (6) Daily, weekly & monthly reports

### On-slide (PPT)

- **Automated digests:** **daily**, **weekly**, **monthly**  
- Summaries include: **internet usage**, **visited sites**, **flagged** activity, **blocked access attempts**, **bandwidth**  
- Parents configure **preferences** and **recipients** in the dashboard  

### Speaker notes (for us)

**Why:** Digests = **habit summary** when parents are offline.

**How (≈15 s):** **Scheduler** calls **`reporting:send-digest`** (daily / weekly / monthly slots). Command **rolls up DB data** (visits, flags, blocks, usage/bandwidth) and **emails** saved **recipients** per **preferences**.

---

## Slide 9 — (9) Basic security

### On-slide (PPT)

- **User authentication** (login) + **verified** accounts where used  
- **Role separation** (e.g. parent vs admin)  
- **Firewall / MAC** concepts on the **Pi** (traffic rules; device identity)  
- **Session management** for web access  
- **Audit / sensitive** routes + **log monitoring** for accountability and debugging  

### Speaker notes (for us)

**Goal of this slide:** show you thought about **abuse cases**—stolen laptop with dashboard open, rogue admin, etc. **Do not** claim “unhackable.” Say **layered basics**: strong passwords, HTTPS in production, **least privilege**, **logs** when something fails. MAC whitelist = “**only known devices** get treated as full members” if that matches your deployment story.

---

## Slide 10 — How the pieces run (one slide, technical glue)

### On-slide (PPT)

- **Background jobs & scheduler:** parse network logs → **browsing history**; monitor connections → **new device** events; check expiration → **time limit** events  
- **Events → broadcasting / mail:** drive **real-time dashboard** + **immediate emails**  
- **Portal + dashboard:** same app (**Laravel**), different routes for **parent** vs **child** flows  

### Speaker notes (for us)

This slide answers **“does it actually work automatically?”** **Scheduler** = alarm clock; **queue** = worker that runs heavy steps without freezing clicks. One sentence: **“The network produces signals; Laravel turns them into history, alerts, and reports.”**

---

## Slide 11 — Delimitations & closing

### On-slide (PPT)

- Built around a **gateway / child-network** model—not every possible home topology  
- DNS-level insight: strong for **domains**; **encrypted page contents** are not the target  
- Needs correct deployment: **cron/queue**, **log paths**, **Reverb/broadcasting** if using live alerts  
- **Thank you** — optional **30 s demo:** dashboard notification + portal quiz  

### Speaker notes (for us)

End by **mapping back**: “We implemented the **nine parent-focused capabilities** through **Pi placement + Laravel + database + scheduled reporting + real-time events**.” Invite questions on **privacy** (household consent) and **limitations** (e.g. VPN-aware teens—honest, brief).

---

## Optional backup slide — “Where is requirement X in the app?”

### On-slide (PPT)

| Area | Examples (high level) |
|------|------------------------|
| History / blocks / flags | Browsing logs, blocked & flagged sections, access attempts |
| Learning | `/portal/...` quizzes & videos; parent CRUD under authenticated routes |
| Time & schedules | Device time + schedule screens; expiration / grant flows |
| Alerts & reports | Dashboard Echo listeners; reporting preferences & digest commands |
| Security | Auth, middleware, admin-only routes, Pi firewall/MAC story |

### Speaker notes (for us)

Only if the panel asks for a **menu → screen** mapping. Do not read the table line-by-line unless they want detail.

---

## Q&A one-liners (tied to the nine items)

- **(1)** DNS + logs give **site/app visibility**; parents apply **flag vs block**.  
- **(2)** Portal proves **learning** before **more access**.  
- **(3)(5)** Rules + counters answer **when** and **how much**.  
- **(4)** Websockets for **instant** UI; email for **away-from-dashboard** parents.  
- **(6)** Scheduler sends **daily / weekly / monthly** digests.  
- **(7)(8)** One dashboard for **rules + devices + lists**.  
- **(9)** Defense in depth: **accounts, roles, network rules, logs.**

---

**End — copy slides into PowerPoint; rehearse once with a timer.**
