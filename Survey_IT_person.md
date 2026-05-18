# IT Specialist Review Pack — Parental Wi‑Fi System

Purpose: Support Section B (IT Specialist Security Questionnaire) in `revise_questionaire.md` (items B1–B6). A reviewer can read this file, spot-check paths if needed, and score without a full code audit.

Response scale (from questionnaire):  
5 = Strongly Agree · 4 = Agree · 3 = Neutral · 2 = Disagree · 1 = Strongly Disagree · N/A = Not Applicable

---

## How to use this document

1. Read each B1–B6 section in order (roughly one minute per item).
2. In each “system position” paragraph, bracketed numbers (for example [3]) point to the matching numbered item in that section’s key-terms list directly below it.
3. Use the repository list only when you want to verify behavior in code.
4. Write the reviewer score using the scale above.
5. Use reviewer notes for deployment or process caveats.

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

> Parents and administrators sign in with email and password. Laravel [1] issues a server-side session cookie [2] (and an optional encrypted remember-me cookie [3]). Routes [4] that serve dashboards, devices, quizzes, logs, and reports are wrapped in middleware [5] so only authenticated users with verified email [6] reach parent features, and only admin-capable users reach /admin [8] where role.admin [7] applies. Login is throttled [9] to limit brute force. Email ownership is proven with a time-limited six-digit code stored as a hash [10]. The child learning portal is deliberately not a second user-account login: in captive-portal [11] use the gateway already knows the client MAC [12], so the app scopes activities using that MAC [12] from the request. Reviewers should score parent/admin controls separately from the MAC-scoped portal [13].

### Copy-paste — key terms (for reviewers)

1. Laravel: The PHP web framework the application is built on; it provides routing, sessions, middleware, and security helpers used throughout the parent/admin UI.
2. Session cookie: A small browser-held identifier that points to server-side session data (who is logged in). The server validates it on each request; it is not the password itself.
3. Remember-me cookie: Optional long-lived encrypted cookie so a parent can stay signed in across browser restarts without re-entering the password every time.
4. Route: A defined URL path (for example /dashboard, /admin) mapped to application logic. Only registered routes are reachable through the web app.
5. Middleware: Code that runs before a route handler (for example must be logged in, must have verified email, must be admin). Failed checks block access.
6. auth / verified: Middleware names; auth requires a logged-in user, verified requires the account email to have been confirmed.
7. role.admin / role.parent: Role gates; only users with the admin or parent role may enter the matching route groups.
8. /admin: URL prefix for administrator-only screens, separated from the parent dashboard.
9. Throttled / rate-limited: Login and some sensitive actions cap how many attempts are allowed per minute to slow password-guessing (brute force).
10. Hash: A one-way transform of the verification code stored in the database so the plain code is not kept at rest; the app compares hashes on submit.
11. Captive portal: Wi-Fi flow where a device must pass through a gateway page before full internet access; the gateway can see which device is connecting.
12. MAC (Media Access Control address): A hardware-level identifier for a Wi-Fi or Ethernet interface (for example AA:BB:CC:DD:EE:FF), used here to tie a child device to policy without a separate child username and password.
13. MAC-scoped portal: Child quiz and video pages keyed off the MAC in the request, not a child user account; different trust model from parent login.

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

> Internet access for child devices on the gateway [1] is enforced with Linux iptables [2] using the client MAC. Blocking adds MAC-specific DROP rules [5] on INPUT [3] and FORWARD [4] under NAT and packet forwarding [10]; whitelisting adds MAC-specific ACCEPT rules [6] evaluated before typical block rules. Captive portal handling coordinates with NoDogSplash [8]. That gives explicit deny-by-policy [7] at the child-device level on this subnet [9]. Whether the deployment satisfies a strict “default deny everywhere” questionnaire item depends on the full installed rulebase and upstream network; the repository documents the parental-control pieces and the Pi baseline guides, not a corporate perimeter standard by itself.

### Copy-paste — key terms (for reviewers)

1. Gateway (Pi): Raspberry Pi acting as the home Wi-Fi access point and traffic enforcement point between child devices and the internet.
2. iptables: Linux kernel firewall tool; rules here allow or drop packets based on criteria such as source MAC and chain.
3. INPUT chain: Rules for traffic destined to the Pi itself (management, local services).
4. FORWARD chain: Rules for traffic the Pi routes between Wi-Fi clients and the upstream internet (typical path for child browsing).
5. DROP rule: Silently discards matching traffic; used to block a specific child MAC from passing through.
6. ACCEPT rule: Permits matching traffic; whitelisted MACs get ACCEPT inserted early so they are not caught by later DROP rules.
7. Default-deny: Security posture where everything is blocked unless explicitly allowed; here, child control is explicit per MAC, but global chain default policies on the live Pi must still be verified on site.
8. NoDogSplash: Open-source captive portal daemon; redirects unauthenticated clients and works with the app portal flow.
9. Subnet: The IP address range served by this Wi-Fi (for example 192.168.4.0/24); parental rules apply to devices on that LAN segment.
10. NAT / FORWARD: Network Address Translation and packet forwarding; how the Pi shares one upstream connection and decides which client packets may leave.

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

> Child units are enrolled [1] with a MAC and a lifecycle status [2]. Policy uses that MAC in iptables: blocked MACs get DROP rules; whitelisted [3] MACs get early ACCEPT rules so they bypass child restrictions. Application logic that depends on an approved device will not start sessions for other statuses [4] and logs structured warnings. Unknown clients [4] can start registration through a rate-limited HTTP endpoint [5], and the portal UX [6] guides users when the MAC cannot be resolved. This answers “allowlist [3] and handling of unauthorized devices [4]” at the Wi-Fi integration layer; MAC spoofing [8] and devices that never join this SSID [7] are outside this app’s visibility.

### Copy-paste — key terms (for reviewers)

1. Enrolled device: A child handset recorded in the database with its MAC, name, and status (for example active, blocked, whitelisted).
2. Lifecycle status: Administrative state of a device row; only approved statuses may start time-tracking or learning sessions.
3. Allowlist / whitelist: Explicit list of trusted MACs that receive permissive firewall rules and may skip child restrictions.
4. Unauthorized device: A MAC that is unknown, blocked, or not in an approved status; the app refuses session logic and may log a warning.
5. HTTP endpoint: A public URL (GET or POST) in the web app; here, a throttled route for submitting a new device registration request.
6. Portal UX: Child-facing web screens under /portal that explain errors (for example missing MAC) during captive Wi-Fi use.
7. SSID: The Wi-Fi network name children join; only devices on this network are visible to the gateway and app integration.
8. MAC spoofing: Attacker pretends to use another device MAC; layer-2 controls can be weakened if an attacker can join the same radio network.

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

> Browser-facing state changes [2] go through Laravel’s web middleware group [3], so CSRF [1] verification applies to unsafe HTTP methods using the framework’s server-issued session token (CSRF) [7]. Blade [6]-rendered forms, including the captive child portal, emit the standard CSRF hidden input [5]. That matches OWASP [8]’s expectation of server-side token issuance for classic session-based web apps. Any future separate API [9] should be assessed on its own token model; this report covers the delivered Blade [6] and route setup.

### Copy-paste — key terms (for reviewers)

1. CSRF (Cross-Site Request Forgery): Attack where a malicious site tricks a logged-in browser into submitting an unwanted action; tokens prove the form came from your own app.
2. State-changing request: HTTP operations that create, update, or delete data; here POST, PUT, PATCH, and DELETE (not plain GET reads).
3. Web middleware group: Laravel default stack for browser routes; starts session, checks CSRF on unsafe methods, and related steps.
4. CSRF token: Secret value stored in the session and echoed in forms; the server rejects posts where the hidden field does not match.
5. @csrf / _token: Blade helper and hidden form field name that embed the CSRF token in HTML forms (portal quiz and video submits included).
6. Blade: Laravel HTML template engine for server-rendered pages (parent dashboard and child portal).
7. Session token (CSRF): Distinct from the login session id; a per-session value used only to validate form submissions.
8. OWASP: Open Web Application Security Project; widely cited guidance recommending server-issued anti-CSRF tokens for session-based apps.
9. API (future): Stateless JSON interfaces would need a different auth and token design; not the focus of the current questionnaire web surface.

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

> Parent and admin access uses server-side Laravel sessions: the browser holds only a session ID [1] in an http-only cookie while login state stays on the server. Successful sign-in is treated as a privilege change [6]; the login handler then runs session regeneration [2] so the account is tied to a fresh identifier and session fixation [3] risk at sign-in is reduced. Sign-out and account deletion both call invalidate session (logout) [4] and regenerateToken() [5], ending the session completely and stopping old tabs from submitting forms with a still-valid token. Email verification success [7] and password change [8] complete inside the authenticated session while verified-email and role middleware keep enforcing access. Sensitive routes can require password confirmation in-session before proceeding.

### Copy-paste — key terms (for reviewers)

1. Session ID: Opaque identifier in the session cookie; the server uses it to load stored login state.
2. Session regeneration: After login, Laravel issues a new session ID so a pre-login id an attacker planted cannot be reused (session fixation mitigation).
3. Session fixation: Attacker sets or learns a victim session id before login, then reuses it after the victim authenticates.
4. Invalidate session (logout): Destroys server-side session data so the old cookie no longer grants access.
5. regenerateToken(): Rotates the CSRF form token on logout so old pages cannot submit valid forms after sign-out.
6. Privilege change: Any moment access level increases (for example login, email verified, password changed, role promoted); strict policies sometimes require a new session id at each step.
7. Email verification success: User proves email ownership via code; verified middleware then allows full parent dashboard access within the same authenticated session.
8. Password change: Updates the stored password hash for the logged-in account while the session remains active under auth middleware.

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

> Parent and admin security review is built into the web app: sign-in, sign-out, lockout, and successful sensitive changes write telemetry [2] into security_audit_events [1]. Each row stores a timestamp [3], user linkage [4] when someone is signed in, IP address [5], user agent [6], route name [7] for audited POST, PUT, PATCH, or DELETE actions, and a remote-origin flag [8] when access may be off the home LAN. Authorized users open the Logs area [9] to read and filter these lines with other operational history, export [10] them for offline retention, and support reviewed regularly [13] checks in household or small-site deployments. Exported audit data can also feed SIEM [11] tooling where used, and Pi-side traffic scripts [12] on the gateway add network-layer block-and-allow context next to the application records.

### Copy-paste — key terms (for reviewers)

1. security_audit_events: Database table holding security-relevant audit rows (logins, lockouts, sensitive mutations).
2. Telemetry: Recorded events with context (who, when, from where, what action); not full packet capture.
3. Timestamp: Server time when the event was recorded; supports chronological review and export.
4. User linkage: Link to the account when the actor was logged in; may be empty for failed anonymous attempts.
5. IP address: Client network address seen by the app at request time (useful for spotting unusual locations).
6. User agent: Browser or client identification string sent in the HTTP header.
7. Route name: Internal label for which application action ran (for example device block, settings update) on audited POST, PUT, PATCH, or DELETE.
8. Remote-origin flag: Marks requests that may have come from outside the local or LAN context (for example remote admin access).
9. Logs area: Authenticated parent or admin UI screen merging security audit rows with other operational log streams.
10. Export: Download audit data for offline retention or review (spreadsheet-friendly output where implemented).
11. SIEM: Security Information and Event Management; enterprise tools that can aggregate exported audit data for wider monitoring when connected.
12. Pi-side traffic scripts: Shell and iptables tooling on the gateway; adds block-and-allow evidence at the network layer alongside application audit rows.
13. Reviewed regularly: Parents and admins use the Logs area and export on a schedule they choose to spot unusual sign-ins or policy changes.

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
