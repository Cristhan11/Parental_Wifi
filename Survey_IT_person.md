# IT Specialist Review Pack — Parental Wi‑Fi System

Purpose: Support Section B (IT Specialist Security Questionnaire) in `revise_questionaire.md` (items B1–B6). A reviewer can read this file, spot-check paths if needed, and score without a full code audit.

Response scale (from questionnaire):  
5 = Strongly Agree · 4 = Agree · 3 = Neutral · 2 = Disagree · 1 = Strongly Disagree · N/A = Not Applicable

---

## How to use this document

1. Read each B1–B6 section in order (roughly one minute per item).
2. Use the repository list only when you want to verify behavior in code.
3. Write the reviewer score using the scale above.
4. Use reviewer notes for deployment or process caveats.

---

## B1 — User authentication controls (internal and external access) [R7]

Questionnaire basis: Authentication methods for internal and external access.

### What the system does (summary)

Parents and admins use Laravel session login (email + password), optional remember-me, middleware `auth` and `verified`, and extra gates: `parent.dashboard` for the parent UI, `role.admin` for `/admin`, and `role.parent` where routes require it. Login attempts are rate-limited. Email verification uses a six-digit code (hashed, expiry) on throttled routes. Password update and confirm-password routes sit behind auth. The child portal under `/portal` is not a user login; it identifies the handset by MAC in the query (captive Wi‑Fi model), which differs from parent authentication.

### Evidence in repository (what to open)

- `routes/web.php` — See which route groups use `auth`, `verified`, `parent.dashboard`, `role.admin`, `audit.sensitive`, and how `/admin` is isolated from the parent dashboard.
- `routes/auth.php` — Guest vs authenticated auth routes; login POST; verify-email code POST with throttle; password confirm and password update.
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — Login creates session; `regenerate()` after successful auth; logout invalidates session and rotates CSRF token.
- `app/Http/Requests/Auth/LoginRequest.php` — Credential check, rate limit, lockout behavior.
- `app/Http/Controllers/Auth/VerifyEmailCodeController.php` — Six-digit code validation, expiry, hash check, redirect by role after verification.
- `app/Http/Middleware/EnsureParentDashboardAccess.php` — Who may enter the parent dashboard (verified email, approval, vs redirect to admin or pending flows).
- `app/Http/Middleware/EnsureUserIsAdmin.php`, `EnsureUserIsParent.php` — Role enforcement for specific route groups.
- `app/Http/Controllers/PortalController.php` (constructor / `landing`) — Portal entry by MAC; documented captive-portal trust model.
- `docs/AUTHENTICATION_IMPLEMENTATION.md` — Longer narrative of flows (optional read).

### Copy-paste — system position for your report

> Parents and administrators sign in with email and password. Laravel issues a server-side session cookie (and an optional encrypted remember-me cookie). Routes that serve dashboards, devices, quizzes, logs, and reports are wrapped in middleware so only authenticated users with verified email reach parent features, and only admin-capable users reach `/admin` where `role.admin` applies. Login is throttled to limit brute force. Email ownership is proven with a time-limited six-digit code stored as a hash. The child learning portal is deliberately not a second user-account login: in captive-portal use the gateway already knows the client MAC, so the app scopes activities using that MAC from the request. Reviewers should score parent/admin controls separately from the MAC-scoped portal.

Reviewer score: ___  
Reviewer notes (optional):  
Example: TLS and hosting define “external” safety; profile may expose Tailscale-related actions—see deployment docs.

---

## B2 — Firewall default-deny; only necessary traffic allowed [R8]

Questionnaire basis: Firewalls configured default-deny, allowing only necessary traffic.

### What the system does (summary)

On the Pi gateway, blocking and allowing child traffic uses iptables on INPUT and FORWARD with MAC match. Scripts append DROP for blocked devices and insert ACCEPT at the top of the chain for whitelisted MACs. NoDogSplash handles captive states. This matches a home router plus parental-control pattern: explicit per-MAC DROP and priority ACCEPT, documented NAT/FORWARD setup in Pi guides. It is not the same as documenting enterprise-wide default-deny on every interface; live default policies should be checked with `iptables -L` and NAT tables on the deployed unit.

### Evidence in repository (what to open)

- `docs/NETWORK_CONTROL_SYSTEM_ARCHITECTURE.md` — How iptables, FORWARD, DROP, and services fit together.
- `scripts/block_device.sh`, `scripts/unblock_device.sh`, `scripts/whitelist_device.sh` — Exact chain actions (DROP append, ACCEPT insert at position 1).
- `app/Services/NetworkService.php` — PHP entry points that invoke those scripts safely.
- `docs/RASPBERRY_PI_ACCESS_POINT_SETUP.md`, `docs/RASPBERRY_PI_SERVICES_SETUP.md` — AP, NAT, and iptables operational notes for installers.

### Copy-paste — system position for your report

> Internet access for child devices on the gateway is enforced with Linux iptables using the client MAC. Blocking adds MAC-specific DROP rules on INPUT and FORWARD; whitelisting adds MAC-specific ACCEPT rules evaluated before typical block rules. Captive portal handling coordinates with NoDogSplash. That gives explicit deny-by-policy at the child-device level on this subnet. Whether the deployment satisfies a strict “default deny everywhere” questionnaire item depends on the full installed rulebase and upstream network; the repository documents the parental-control pieces and the Pi baseline guides, not a corporate perimeter standard by itself.

Reviewer score: ___  
Reviewer notes (optional):  
Confirm chain default policy and rule order with NoDogSplash on the real appliance.

---

## B3 — MAC whitelisting / device allowlist; unauthorized devices [R7]

Questionnaire basis: How unauthorized or unknown devices are identified and handled.

### What the system does (summary)

Devices are rows in the database with MAC, status (active, blocked, whitelisted, etc.), and role. Whitelist flows call iptables ACCEPT-first rules. Session logic refuses approved-only operations when status is not active or whitelisted and writes a warning log with MAC and IP. New hardware can use the throttled public device-request routes. The portal landing explains missing/unknown MAC when the captive flow has not supplied one. MAC is normal for Wi‑Fi L2 control; spoofing is a separate threat note.

### Evidence in repository (what to open)

- `app/Models/Device.php` — Fillable fields include `mac_address`, `status`, `role`; scopes for dashboard usage.
- `app/Services/NetworkService.php` — `whitelistDevice` and related calls into shell scripts.
- `scripts/whitelist_device.sh` — Inserts ACCEPT at chain head for the given MAC.
- `app/Services/TimeTrackingService.php` — Rejects session start when device is not approved; logs unauthorized attempt metadata.
- `routes/web.php` — `device-request` GET/POST with throttle middleware.
- `app/Http/Controllers/PortalController.php` — `landing` when device is null vs resolved by MAC.
- `app/Jobs/MonitorDeviceConnections.php`, `docs/PARENT_DEVICE_REDIRECT_FIX.md` — Whitelisted parent devices and NoDogSplash auto-auth behavior.

### Copy-paste — system position for your report

> Child units are enrolled with a MAC and a lifecycle status. Policy uses that MAC in iptables: blocked MACs get DROP rules; whitelisted MACs get early ACCEPT rules so they bypass child restrictions. Application logic that depends on an approved device will not start sessions for other statuses and logs structured warnings. Unknown clients can start registration through a rate-limited HTTP endpoint, and the portal UX guides users when the MAC cannot be resolved. This answers “allowlist and handling of unauthorized devices” at the Wi‑Fi integration layer; MAC spoofing and devices that never join this SSID are outside this app’s visibility.

Reviewer score: ___  
Reviewer notes (optional):  
Shadow IT off-network is out of scope; spoofing depends on assumed attacker model.

---

## B4 — CSRF protection for state-changing requests (server-side tokens) [R11]

Questionnaire basis: CSRF tokens generated server-side (per session or per request).

### What the system does (summary)

Shipped browser routes load through Laravel’s `web` stack, which validates the CSRF token on POST, PUT, PATCH, and DELETE. Blade forms in the child portal include the hidden `_token` field via `@csrf`. Stateless JSON APIs are not the focus of this questionnaire’s web surface.

### Evidence in repository (what to open)

- `bootstrap/app.php` — Standard Laravel application wiring; `web` routes use the default global middleware stack (includes CSRF verification in framework defaults).
- `resources/views/portal/quiz.blade.php`, `resources/views/portal/video.blade.php`, `resources/views/portal/landing.blade.php` — Forms include `@csrf` on mutating posts.
- `routes/web.php`, `routes/auth.php` — Large set of authenticated mutating routes protected by the same stack once they sit under `web`.

### Copy-paste — system position for your report

> Browser-facing state changes go through Laravel’s web middleware group, so CSRF verification applies to unsafe HTTP methods using the framework’s server-issued session token. Blade-rendered forms, including the captive child portal, emit the standard CSRF hidden input. That matches OWASP’s expectation of server-side token issuance for classic session-based web apps. Any future separate API should be assessed on its own token model; this report covers the delivered Blade and route setup.

Reviewer score: ___  
Reviewer notes (optional):  
If you add tokenless JSON endpoints later, document them apart from this pack.

---

## B5 — Session management: renew / regenerate session ID after privilege changes [R12]

Questionnaire basis: Session ID renewed or regenerated after privilege level changes.

### What the system does (summary)

After a successful login the controller calls `session()->regenerate()` to rotate the session id (mitigates fixation at sign-in). Logout invalidates the session and calls `regenerateToken()` for the CSRF cookie. The email-verification success path and the password-change controller do not show an extra `session()->regenerate()` in the current code, so strict “regenerate on every privilege transition” reviewers may note a gap or request a small follow-up patch.

### Evidence in repository (what to open)

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — `store()` after authenticate; `destroy()` for logout.
- `app/Http/Controllers/Auth/VerifyEmailCodeController.php` — Successful verify redirects without an explicit session id rotation in this file.
- `app/Http/Controllers/Auth/PasswordController.php` — Updates password hash without shown session id rotation.

### Copy-paste — system position for your report

> Session fixation risk at authentication is addressed by regenerating the session identifier immediately after a successful login, and logout clears the session and rotates the form token. Those two boundaries match common OWASP guidance for sign-in and sign-out. The codebase reviewed here does not obviously regenerate the session identifier right after email verification succeeds or right after a password change; teams that interpret “privilege change” to include those moments may treat R12 as partially met unless that hardening is added.

Reviewer score: ___  
Reviewer notes (optional):  
Consider adding `session()->regenerate()` after verify and after password update if policy demands it.

---

## B6 — Security logs reviewed regularly (suspicious / unauthorized activity) [R8]

Questionnaire basis: Network or security logs reviewed regularly for suspicious activity.

### What the system does (summary)

Security-relevant events are written to the `security_audit_events` table: successful and failed login, logout, lockout, successful sensitive mutations (POST/PUT/PATCH/DELETE) on audited route groups, and special cases such as Tailscale auth link requests. Listeners register in `AppServiceProvider`; `AuditSensitiveAction` middleware records successful mutating requests by route name. The Logs screen merges these rows for authorized viewers with IP and remote/local hints; exports exist. “Reviewed regularly” is still an operational habit; the software supplies retention, query, UI, and export to support reviews, not a 24/7 SOC unless you forward data elsewhere.

### Evidence in repository (what to open)

- `app/Models/SecurityAuditEvent.php` — Event name constants and fillable audit fields.
- `app/Services/SecurityAuditLogger.php` — Central insert helper with IP, user agent, route, metadata.
- `app/Listeners/RecordSecurityAuditOnLogin.php`, `RecordSecurityAuditOnFailedLogin.php`, `RecordSecurityAuditOnLogout.php`, `RecordSecurityAuditOnLockout.php` — Auth event hooks.
- `app/Providers/AppServiceProvider.php` — Registers the auth-related listeners.
- `app/Http/Middleware/AuditSensitiveAction.php` — Which methods and routes are skipped or recorded after success.
- `bootstrap/app.php` — Alias `audit.sensitive` for attaching middleware to route groups.
- `app/Http/Controllers/LogsController.php` — Merges `SecurityAuditEvent` rows into the logs timeline with summaries and filters.
- `routes/web.php` — `logs` routes live inside the authenticated parent dashboard group.
- `tests/Feature/SecurityAuditLoggingTest.php` — Automated checks that rows appear for login and sensitive actions.

### Copy-paste — system position for your report

> The product stores authentication and sensitive-action telemetry in `security_audit_events` with timestamps, user linkage where applicable, IP address, user agent, route name for sensitive actions, and a flag for remote-origin requests. Parents and admins view these lines inside the authenticated Logs area alongside other operational streams and can export for offline review. That gives households or small deployments a concrete place to perform periodic reviews. Cadence and escalation (daily review, alerting, SIEM forwarding) remain organizational choices; the application provides the structured dataset and UI, while Pi-side traffic scripts remain complementary network evidence.

Reviewer score: ___  
Reviewer notes (optional):  
State retention, backup, and any external log shipping explicitly in your deployment policy.

---

## Optional: Philippine policy cross-reference (discussion only)

As in `revise_questionaire.md`: NPC Circular 16-01 (security of personal data), DICT National Cybersecurity Plan 2023–2028. This pack does not replace legal review.

---

## Video demo outline (for interviews when live testing is not possible)

One continuous run (about 10–18 minutes) or two clips (parent vs child). Use a test account and anonymized devices.

### A. Opening (30–60 seconds)

- Name the product: parent-controlled Wi‑Fi time plus learning activities on child devices.
- One-sentence architecture: Laravel web app plus Raspberry Pi gateway (iptables, captive portal); optional diagram.

### B. Parent or admin — authentication and roles (2–4 minutes)

- Login page; mention TLS in production.
- One deliberate failed login (blur password), then success; mention throttling.
- Show email verification with the six-digit code if you use it in demo data.
- Contrast parent dashboard vs admin area so middleware separation is visible.

### C. Parent dashboard — security-relevant actions (4–6 minutes)

- Devices list: MAC and status (active, blocked, whitelisted).
- Block, unblock, or schedule if the UI supports it; say iptables applies on the Pi.
- Optional: whitelist a trusted device and explain ACCEPT-before-DROP idea.
- Logs: scroll to security-class rows (login fail, lockout, sensitive action with route and IP); show export if available.
- Profile: password change; optional remote access / Tailscale controls and their throttle.

### D. Child portal — captive flow (3–5 minutes)

- Open `/portal?mac=…` with a test MAC.
- Run a quiz or video and submit; note CSRF on POST.
- Narrate time grant or post-activity internet behavior if live traffic is not shown.

### E. Closing (30–60 seconds)

- Where audit rows live and how a household reviews them.
- Point to this file and `revise_questionaire.md` for the formal instrument.

### Production tips

- On-screen labels: Parent dashboard, Child portal, Security event.
- Dummy names and MACs only.
- If the Pi is flaky on camera, record the web UI and use slides for iptables / NoDogSplash.

---

Document for the parental_wifi Laravel project; paths are relative to the repository root.
