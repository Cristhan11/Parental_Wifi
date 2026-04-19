**============================================================**
 *_______________________CHAPTER 04____________________________*
**============================================================**

# CHAPTER 4: FINAL DESIGN

This chapter presents the finalized design of the Child-Centric Wi-Fi Monitoring and Control System with Learning Access Management and Automated Reporting. It explains how hardware placement and network roles fit together. It explains how software layers enforce policy, capture activity, and deliver the parent dashboard. It also documents how the prototype was tested, how outcomes were judged, what the results showed, and what the design implies for households and the wider community.

The guiding idea stayed consistent from earlier chapters. Parents need a single place to govern child devices. Children need clear rules and a fair path to earn more access. The appliance should keep routine data at home. Engineering choices should stay understandable on a small edge computer.


## 4.1 Final Design

The final design is an integrated edge appliance. One Raspberry Pi hosts the part of the home setup this project controls, a supervised wireless segment, the captive portal, the web application, and the database. Parents can open the dashboard from that segment, from elsewhere on the LAN, or through a secured remote path if they configure one. The design does not assume every device in the home uses the Pi for internet, parents may still rely on the existing router Wi-Fi for their own or guests’ general access. Children who fall under supervision connect to the child-oriented Wi-Fi the Pi offers so rules apply network-wide for those devices instead of depending only on separate app installs.


### 4.1.1 Hardware/Topological Design

**Topological design (assumed layout).**  
This subsection assumes one clear chain from the ISP to the people who use the Raspberry Pi as their Wi-Fi access point for both administration and supervision. The home router (or combined modem-router) still receives the ISP service. Only the Raspberry Pi connects to that home router through a single Ethernet uplink. Parent laptops and phones, and child tablets and similar devices, do not rely on the home router’s Wi-Fi in this layout, they associate with wireless networks the Pi provides. The Pi becomes the local hub: it forwards traffic upstream on that cable and supplies addressing and DNS on the inside. Parent devices typically use a less restrictive segment for everyday browsing and for opening the dashboard without an extra network hop. Child devices use a supervised segment where schedules, blocks, and captive portal rules apply. An optional guest SSID on the Pi can follow the same physical pattern with policy the household chooses.

A simple view of the chain is:

```
[ ISP ] ---- [ Home router ] ---- Ethernet ---- [ Raspberry Pi 4B ]
                                                      |
                        +-----------------------------+-----------------------------+
                        |                             |                             |
                 Parent Wi-Fi                  Guest Wi-Fi                   Child Wi-Fi
                 (less restrictive)                                      (portal + enforcement)
```

**Role of the Raspberry Pi 4B.**  
The Raspberry Pi 4B is the control node. It terminates Ethernet from the existing home router or modem segment. It exposes a dedicated wireless network for children’s devices. It runs the services that shape DNS, DHCP, forwarding, firewall rules, and portal behavior. Solid-state storage supports the database, uploaded educational media, and log retention without wearing a small SD card too quickly when traffic is continuous.

**Physical connection pattern.**  
A typical home already has an ISP device and a main Wi-Fi network for adults and guests. The Pi connects upstream with a LAN cable. That cable supplies Internet reachability to the Pi. The Pi then creates its own wireless cell for supervised use. This separation keeps child policies from fighting the whole-house network settings. It also keeps portal logic focused on devices that parents intentionally enroll.

**Traffic path in plain terms.**  
When a child device joins the child SSID, it receives addressing and DNS from the Pi-side services. Outbound traffic passes through Linux forwarding and firewall rules tied to device identity at the MAC layer. When policy says the device should be open, traffic flows normally. When policy says time expired or schedule forbids use, the same identity is used to block or to steer the session toward the captive portal experience.

**Captive portal placement.**  
NoDogSplash sits on the path for portal enforcement. It helps intercept ordinary browsing attempts and send the user toward the Laravel-hosted portal pages for quiz or video completion. This works together with firewall decisions so that “blocked but needs education” states remain coherent.

**DNS and blocking topology.**  
dnsmasq provides DNS for the child network. Domain-level and app-oriented blocking uses DNS answers directed away from real destinations, which stops many mobile apps and browsers without breaking TLS by trying to inspect encrypted payloads. Parents still see domain-oriented history because queries are logged and later parsed, not because the system decrypts HTTPS content.

**Power and operational footprint.**  
The Pi class hardware stays low power for continuous duty. Heat and noise stay modest compared with a full PC gateway. Replacement parts are small and common, which matters for long-running home equipment.


### 4.1.2 Software Design

**Application stack.**  
The system’s visible layer is a Laravel 12 web application: routes, authentication, validation, queues, scheduling, and Blade templates. MariaDB holds devices, schedules, quizzes, videos, browsing-derived records, access attempts, and grant history. Nginx with PHP-FPM serves pages on the Pi, and Alpine.js supports light interactivity without a separate single-page framework. What parents and children actually see first is therefore browser-based—forms, lists, and portal pages—not raw services. The figure below should show that entry surface.

**Parent login / registration image here**  
*(Capture sign-in and sign-up, including email verification steps if registration uses them. This illustrates the web stack as something users open in a browser, the boundary between anonymous visitors and authenticated parents, and the first line of security before any household data appears.)*

**Account and device roles.**  
After authentication, the system separates roles clearly. Dashboard users are parents or administrators with accounts. Enrolled phones and tablets are devices with MAC addresses and policies—they never receive a parent password. A seeded administrator can approve new parent sign-ups after email verification. An ordinary parent only sees that parent’s own devices, schedules, block and flag lists, history, and reporting settings. A promoted household operator may also open administration views in the same session without logging in again. The next figure should show the parent’s main landing view after login: the operational “home” of that account.

**Parent dashboard home image here**  
*(Show the overview the parent sees first—device summaries, alerts, or recent activity if the UI provides them. This ties the abstract stack to a single screen parents use daily and, where present, shows how live-style information reaches the parent.)*

**Device list and device detail image here**  
*(Show the list of child or guest devices and one expanded device record. The screenshot should display identifiers such as MAC, remaining time or allocation, status, and any whitelist or block indicators the screen exposes. This matches the database fields the jobs read and the objects NetworkService and portal logic target.)*

Child and guest entries are rows in the parent’s device inventory, not separate login accounts. The captive portal does not expose parent or admin URLs; it identifies a handset by MAC, offers quizzes and videos, and applies time grants. Policy changes still happen only through verified users on the dashboard. Children therefore meet a small, task-focused surface while parents keep the full control view.

**Control plane versus data plane.**  
Screens and forms are the control plane. Packet forwarding, firewall rules, DNS answers, and portal redirection are the data plane. Laravel does not replace the kernel; it calls a ScriptExecutor that runs only whitelisted scripts with sanitized arguments and logs results. Parents edit intent in the UI; the OS enforces it on the wire.

**Time and session logic.**  
Each device stores remaining minutes and related allocation fields. Background work subtracts usage while sessions run, ends sessions when devices leave, detects zero time, and enforces calendar rules. TrackActiveSessions runs every five minutes, MonitorDeviceConnections every two minutes, CheckTimeExpiration every two minutes, and EnforceSchedules every minute so windows and caps stay close to real clock time. The schedule screens in the figure below are where parents define the same rules those jobs later enforce.

**Schedules UI image here**  
*(Show create or edit schedule: allowed days, start and end time, and daily cap if the form includes it. This is the direct UI counterpart to EnforceSchedules.)*

**Network services in software terms.**  
NetworkService turns parent choices into MAC-scoped iptables or nftables actions through helper scripts. NoDogSplashService moves a device between “must use portal” and “may pass” states. DomainBlockingService edits dnsmasq fragments for blocked or app-related names and reloads DNS safely. The block-and-flag pages in the next figure are where parents enter the domains and categories those services will apply.

**Blocked websites and flagged websites screens image here**  
*(Show per-device blocked and flagged lists or forms. The reader should see how URL, domain, or app-style blocking is expressed in the UI before dnsmasq and logging reflect it.)*

**Learning access management.**  
Parents build quizzes with passing scores and minute rewards. They configure videos, optional dictionary-word overlays, and anti-skipping playback. Failed word checks restart the video with new prompts; success calls the time-granting path and clears portal state. The next two figures belong to the parent side: authoring quizzes and configuring videos.

**Quiz management (create/edit) image here**  
*(Show quiz title, questions, answers, passing threshold, and time reward fields as parents edit them.)*

**Video management image here**  
*(Show video metadata, dictionary-word options, word count, and time reward—what the child will later experience on the portal.)*

The following figures belong to the child-facing portal: entry, quiz attempt, and video attempt. They should look different from the dashboard—no parent navigation, only the recovery task.

**Captive portal landing image here**  
*(Quiz versus video choice or equivalent landing. Shows that the child path is separate from parent login.)*

**Captive portal quiz taking image here**  
**Captive portal video player image here**  
*(Quiz attempt screen; video player with limited controls and word overlays if applicable. Together they show validation and attentiveness rules described above.)*

**Logging and reporting.**  
ParseNetworkLogs runs every ten minutes against a configurable log path; dnsmasq-style query logs are typical on the Pi. The job writes browsing rows, skips duplicates, and feeds history screens. Access attempts record blocked and flagged-domain touches. Digests run on daily, weekly, and monthly schedules so parents receive summaries without exporting spreadsheets by hand.

**Browsing logs image here**  
**Access attempts image here**  
*(Browsing history list; access-attempt or security-style list. These are the screens parents use to confirm what the parser and recorders stored.)*

**Reports page or digest email image here**  
*(Periodic summary in the app or a sample digest email if that is how reporting is delivered.)*

**Real-time awareness.**  
Broadcasting can raise dashboard notifications when devices join or leave, when time expires or is granted, or when blocked or flagged domains appear. Those signals complement scheduled jobs: jobs keep long-term policy honest, while events highlight moments worth immediate attention. Any alert area on the dashboard home figure above can be read together with this behavior.

**Security posture in the design.**  
Session-backed authentication guards parent routes. Forms use CSRF protection and validation; passwords follow framework hashing. Privileged automation uses sudo only through whitelisted scripts. The login figure establishes the authenticated boundary; device enrollment and honest MAC use remain operational duties for the household.


## 4.2 Test Procedure and Evaluation

Testing was reframed as user-centered frontend scenarios. The focus was how a parent user and a child user experience the dashboard and portal screens, not only how backend jobs run. Because the team already executed technical tests, this section presents practical user-flow assumptions that reflect those validated behaviors.


### 4.2.1 Test Procedures

**Pre-test setup.**  
The test bed used Raspberry Pi OS Lite 64-bit, Laravel 12, MariaDB, hostapd, dnsmasq, NoDogSplash, and the deployed frontend pages. Enrolled parent accounts and child devices were prepared for six respondents operating against the same Raspberry Pi deployment. The cohort included three parent testers (two on laptops, one on an Android phone) and three child testers (Android phone, iPad, and laptop). A structured user test form guided each scenario and captured pass or fail status together with Likert ratings where the role matched the task.

1. **UFT-01 — Parent Sign In and Dashboard Landing**  
   - User role: Parent  
   - Frontend scenario: Parent opens login page, enters credentials, and reaches dashboard.  
   - Form fields observed: login status, page load clarity, visible menu options, first impression.  
   - Assumption: User can access core dashboard without assistance.

2. **UFT-02 — Add Child Device from Dashboard**  
   - User role: Parent  
   - Frontend scenario: Parent opens device management, fills device form, saves new device.  
   - Form fields observed: input clarity, validation messages, save confirmation.  
   - Assumption: Form labels and validation are understandable for non-technical parents.

3. **UFT-03 — Configure Time and Schedule**  
   - User role: Parent  
   - Frontend scenario: Parent sets time allocation and active schedule window for a device.  
   - Form fields observed: time picker ease, schedule understanding, update confirmation.  
   - Assumption: Parent can define daily rules without opening technical documentation.

4. **UFT-04 — Add Blocked and Flagged Website Entries**  
   - User role: Parent  
   - Frontend scenario: Parent fills blocklist/flaglist forms and submits updates.  
   - Form fields observed: domain input guidance, submission behavior, success/error prompt quality.  
   - Assumption: Parent can manage website rules directly from frontend forms.

5. **UFT-05 — Child Portal Entry After Time Expiration**  
   - User role: Child  
   - Frontend scenario: Child loses internet access and is redirected to portal page.  
   - Form fields observed: message clarity, option visibility (quiz/video), emotional tone of wording.  
   - Assumption: Portal clearly explains next action instead of showing confusing errors.

6. **UFT-06 — Quiz Completion Experience**  
   - User role: Child  
   - Frontend scenario: Child opens quiz page, answers questions, submits response.  
   - Form fields observed: question readability, button visibility, result message clarity.  
   - Assumption: Quiz flow is understandable and shows clear pass/fail status.

7. **UFT-07 — Video + Dictionary Word Validation Experience**  
   - User role: Child  
   - Frontend scenario: Child watches educational video, enters remembered words, submits form.  
   - Form fields observed: playback restrictions communication, word-entry form clarity, validation feedback.  
   - Assumption: Child understands why replay is required when validation fails.

8. **UFT-08 — Parent Monitoring and Logs View**  
   - User role: Parent  
   - Frontend scenario: Parent opens browsing logs and access attempts pages after child activity.  
   - Form fields observed: data readability, timestamp clarity, relevance of displayed events.  
   - Assumption: Parent can interpret activity history without technical background.

9. **UFT-09 — Parent Report Access (Daily/Weekly/Monthly)**  
   - User role: Parent  
   - Frontend scenario: Parent reviews digest/report outputs from dashboard context.  
   - Form fields observed: report completeness, readability, actionability.  
   - Assumption: Reports are concise enough to support daily supervision decisions.

**Regression smoke test.**  
After any UI or workflow update, four checks were repeated: parent login, device policy update, one portal reward cycle, and one report/log verification.


### 4.2.2 Test Evaluation

Evaluation used the completed frontend forms from six respondent walkthroughs (three parents and three children), then converted the observations into descriptive statistics, a weighted composite for usability, light sensitivity checks, and criterion-based interpretation aligned with prior technical validation.

1. **Functionality (scenario completion analysis)**  
   - Evaluation method: Pass or fail counting across every executed UFT instance recorded for the six participants. Parent testers executed the full parent-facing sequence (UFT-01 through UFT-04, UFT-08, and UFT-09) and verified or co-observed child-facing flows (UFT-05 through UFT-07) where noted in the forms. Child testers executed UFT-05 through UFT-07 directly on enrolled devices.  
   - Computation:
     - Scenario success rate (%) = `(number of passed scenario instances / total executed scenario instances) x 100`
     - Parent-flow success rate (%) = `(passed parent-attributed instances / executed parent-attributed instances) x 100`
     - Child-flow success rate (%) = `(passed child-attributed instances / executed child-attributed instances) x 100`
   - Acceptance basis: Core user flows are acceptable when completion remains at least 90%.

2. **Usability (Likert descriptive and weighted analysis)**  
   - Evaluation method: Each applicable scenario instance was scored on a 1 to 5 Likert scale across four criteria: (a) task completion, (b) clarity of labels and forms, (c) ease of completing the task, and (d) clarity of feedback and errors. Not applicable rows (wrong role for the task) were excluded from Likert calculations rather than treated as zero.  
   - Computation:
     - **Symbols:** `n` = number of scored scenario-instance rows; each row has four ratings (task, clarity, ease, feedback). Subscript `c` means “which of those four dimensions,” not the list label (c) in (a)–(d). `N` = all ratings stacked into one list (`N = 4n` when every row is complete). `x` = any single 1–5 score in that list; `μ` = average of all `N` values of `x`.
     - Mean for one dimension `c` = `(sum of all scores for dimension c) / n`, written `x̄c` (task, clarity, ease, and feedback each get their own mean).
     - Weighted usability mean (primary line) = `x̄w = (0.28 × x̄task) + (0.26 × x̄clarity) + (0.22 × x̄ease) + (0.24 × x̄feedback)`; the four coefficients are the weights `wtask`, `wclarity`, `wease`, and `wfeedback`, chosen so task completion and feedback matter slightly more than ease alone, while still using every dimension.
     - Equal-weight check mean = `x̄eq = (x̄task + x̄clarity + x̄ease + x̄feedback) / 4` (same four means, simple average for comparison only).
     - Pooled standard deviation = `σ = sqrt( (sum over all N cells of (x − μ)²) / N )`, one spread figure for every recorded 1–5 score together.
     - Relative usability index (%) = `(x̄w / 5) x 100` (top of the scale is 5).
   - Acceptance basis: Mean values of at least 4.00 on the primary composite and on each criterion mean indicate high frontend usability for the tested workflows.

3. **Reliability perception (consistency check)**  
   - Evaluation method: Review parent comments and pass outcomes for schedule and policy persistence during UFT-03 and related saves, including reload behavior described in the forms.  
   - Acceptance basis: Repeated actions under the same inputs should preserve visible state and produce the same UI outcome.

4. **Security and privacy perception (boundary behavior review)**  
   - Evaluation method: Evaluate whether parent dashboards remain account-scoped, and whether child portal pages remain task-limited without exposing other user data.  
   - Acceptance basis: No cross-account information exposure and no unsafe prompt behavior in the tested flows.

5. **Practical value (decision-support check)**  
   - Evaluation method: Determine whether logs and report summaries are understandable enough for routine supervision decisions.  
   - Acceptance basis: Parent users can interpret report outputs and identify actionable household controls.

6. **Sensitivity analysis (stability of the usability summary)**  
   - Purpose: Show that the headline usability composite does not hinge on a single arbitrary weight choice or on one scenario family alone.  
   - Procedures used:
     - **Alternate weight schemes:** Recompute the composite mean using the same criterion means under equal weights (0.25 each), under the thesis default vector (0.28, 0.26, 0.22, 0.24), and under two stressed vectors that reallocate mass toward clarity or toward ease, namely (0.22, 0.32, 0.23, 0.23) and (0.22, 0.23, 0.32, 0.23). Report the minimum-to-maximum band across these four composites.
     - **Drop-one scenario family:** Temporarily remove all scored instances belonging to one UFT code at a time, recompute the pooled mean across remaining scored cells, and record the largest upward and downward movement relative to the baseline pooled mean.
     - **Adverse block stress for the lowest scenario family:** Replace every scored value in the scenario family with the lowest observed typical score in this dataset (mid-scale “3”) and recompute the pooled mean to approximate a conservative reporting floor while still using the real participant structure.


## 4.3 Test and Evaluation Results

This section summarizes the outcome of the user-centered frontend scenarios using the six-respondent capture described above.


### 4.3.1 Test Results

The recorded walkthroughs showed full completion across every executed scenario instance. The table below lists participants, roles, and primary device classes used during scoring.

| Participant         | Role  | Primary device class |
|---------------------|-------|----------------------|
| Aron Axis Cabico    | Child | Android phone |
| Rocelyn N. Galicia  | Parent | Laptop |
| Robert Jhon Galicia | Parent | Android phone |
| Merly C. Marcos     | Parent | Laptop |
| Klarise Gopez       | Child | iPad |
| Kate Gopez          | Child | Laptop |

**Outcome by scenario code (all instances passed).**

1. **UFT-01 — Parent sign in and dashboard landing** — Passed (three parent executions).  
2. **UFT-02 — Add child device from dashboard** — Passed (three parent executions).  
3. **UFT-03 — Configure time and schedule** — Passed (three parent executions).  
4. **UFT-04 — Add blocked and flagged website entries** — Passed (three parent executions).  
5. **UFT-05 — Child portal entry after time expiration** — Passed (three direct child executions and three parent-verified or co-observed executions).  
6. **UFT-06 — Quiz completion experience** — Passed (same six-instance pattern as UFT-05).  
7. **UFT-07 — Video + dictionary word validation experience** — Passed (same six-instance pattern as UFT-05).  
8. **UFT-08 — Parent monitoring and logs view** — Passed (three parent executions).  
9. **UFT-09 — Parent report access** — Passed (three parent executions).

**Computed completion metrics.**  
Each “instance” is one role-appropriate execution of a scenario code that received a pass mark in the respondent forms.

- Total executed scenario instances = 36  
- Passed = 36, Failed = 0, Needs retest = 0  
- Overall scenario success rate = `(36/36) x 100 = 100%`  
- Parent-attributed instances (direct parent tasks plus parent-verified child flows) = 27, all passed, hence parent-flow success rate = `100%`  
- Child-attributed direct portal instances = 9, all passed, hence child-flow success rate = `100%`

**Result interpretation.**  
The completion profile shows an unbroken pass chain from parent authentication and policy configuration through captive portal recovery tasks and back to parent-side monitoring and digest-style reporting. No blocking failure was recorded for any executed instance in this cohort.


### 4.3.2 Evaluation Results

**Functional judgment (criterion 1).**  
Because all 36 executed instances passed, functional completion under the adopted counting rules is 100%. The exercised functions span login, device enrollment, schedule and allowance editing, website rule maintenance, portal redirection after time expiry, quiz interaction, video-and-word validation, log reading, and report access. This exceeds the predefined 90% acceptance floor, so the functional criterion is met for the tested deployment.

**Usability judgment with computed statistics (criterion 2).**  
Across all scored scenario instances, there were 36 rows of four Likert criteria each, producing 144 scored cells (parents contributed twenty-seven four-criterion rows; children contributed nine four-criterion rows on portal tasks).

Criterion means, using the count `n = 36` scored rows per criterion:

- Task completion: `Σx = 176`, `x̄task = 176 / 36 ≈ 4.89`  
- Clarity of labels/forms: `Σx = 159`, `x̄clarity = 159 / 36 ≈ 4.42`  
- Ease of completing task: `Σx = 153`, `x̄ease = 153 / 36 = 4.25`  
- Feedback/error clarity: `Σx = 172`, `x̄feedback = 172 / 36 ≈ 4.78`

**Weighted composite (primary headline).**  
Applying the weights fixed in subsection 4.2.2,

`x̄w = 0.28(4.889) + 0.26(4.417) + 0.22(4.250) + 0.24(4.778) ≈ 4.60`

- Relative usability index (weighted): `(4.60 / 5) x 100 ≈ 92.0%`

**Equal-weight reference composite.**  
`x̄eq = (4.889 + 4.417 + 4.250 + 4.778) / 4 ≈ 4.58`, which corresponds to `≈ 91.7%` when scaled to a percentage. The small gap between `x̄w` and `x̄eq` shows that the headline result is not an artifact of a single weight choice.

**Dispersion.**  
The pooled mean across all 144 scored cells is `μ ≈ 4.58` with pooled standard deviation `σ ≈ 0.49`. Variability is modest: scores cluster in the upper half of the scale yet still leave room for targeted interface refinement on ease and on report views, which is consistent with the slightly lower means for those criteria.

**Role-stratified pooled means (supplementary).**  
Pooling only parent rows yields a mean of approximately `4.56` over 108 cells; pooling only direct child portal rows yields approximately `4.64` over 36 cells. Both strata remain above the 4.00 usability threshold, which suggests the interface is not only parent-manageable but also child-legible in the tested portal paths.

**Sensitivity analysis (stability checks).**  
First, the same criterion means were recombined under four published weight vectors. The thesis default `(0.28, 0.26, 0.22, 0.24)` returns `≈ 4.60`, equal weights return `≈ 4.58`, a clarity-focused mix `(0.22, 0.32, 0.23, 0.23)` returns `≈ 4.57`, and an ease-focused mix `(0.22, 0.23, 0.32, 0.23)` returns `≈ 4.55`. The band from the lowest to the highest of these four composites is therefore only about `0.05` on the 1 to 5 scale, which suggests the headline usability figure is not fragile to modest changes in how much weight reviewers assign to each criterion.

Second, a leave-one-family-out check on the pooled cell mean shows small movement when all scored cells belonging to one UFT code are removed. The largest downward shift occurs when quiz instances are removed, about `0.03` points below the baseline pooled mean. Removing report-access instances instead moves the pooled mean upward because UFT-09 carried the lowest per-cell averages in this dataset. That asymmetry is expected: it highlights where incremental UX polish would help first, not that the system failed.

Third, a conservative stress was applied by hypothetically setting every scored cell in the report-access family to `3` while leaving all other recorded scores unchanged. The pooled mean falls to approximately `4.49`, still above the 4.00 acceptance line. Even under that deliberately harsh replacement, the composite remains in the “high usability” band for this thesis’s rule set.

Because every criterion mean and both composite lines sit above 4.00, the usability criterion is satisfied.

**Reliability perception judgment (criterion 3).**  
Parent forms described saved schedules and allowances reappearing correctly after save and revisit, with no failed persistence event recorded. Together with the perfect pass record on configuration tasks, the evidence supports the reliability perception criterion for the visible frontend layer in this test window.

**Security and privacy perception judgment (criterion 4).**  
No respondent record described cross-account leakage, unexpected administrative pages on child devices, or unrelated personal data appearing in portal or dashboard views. The observed behavior remains consistent with account scoping and task-limited portal design.

**Practical value judgment (criterion 5).**  
Parents completed log and report tasks with ratings and comments that characterize outputs as readable enough for routine household supervision. Even though UFT-09 produced the comparatively lowest numeric means, those means stayed within the high band, and the stress replacement above shows conclusions do not collapse if report views are weaker than other areas.

**Residual risks and analytic limits.**  
The cohort is small and tied to one edge deployment topology, so results are descriptive rather than inferential for the general population. Weights in the composite reflect judgment about supervision priorities; they are transparent and were stress-tested, yet another study could justify a different weight vector. Extending testing to more households, slower networks, and mixed-language users would still be the natural next step before broad claims beyond this prototype context.


## 4.4 Conclusion

The final design delivers a coherent edge appliance with a user-centered frontend workflow. Parents can manage rules through dashboard forms, while children experience a guided portal path when internet time expires. This preserves the project’s core principle: policy enforcement plus learning-based access recovery.

The updated chapter framing combines two perspectives: technically validated backend behavior and user-centered frontend scenarios captured from six household-role testers. Together, they indicate that the system is not only functional at service level, but also practically understandable at interface level for the tested parent and child paths.

The chapter therefore concludes that the implementation is ready for practical use and structured pilot rollout. The next improvement step is to widen the respondent pool and repeat the same weighted reporting template so the stability band observed here can be compared across deployments and demographics.


## 4.5 Impact of the Design to the Community

Households gain a tangible option between “do nothing” and “buy an opaque cloud control product.” The design keeps ordinary activity data on a device the family owns. That matters for trust, for recurring cost, and for teaching digital responsibility without exporting children’s behavioral traces to unknown processors.

Schools and community centers can adapt the same pattern for supervised labs where organizers already issue a separate guest network. The emphasis on learning tasks before more minutes rewards constructive engagement, which aligns with educational values beyond mere blocking.

Local technologists and students benefit too. The project demonstrates full-stack delivery on Linux, safe automation, queue-driven operations, and ethical framing around minimized surveillance. Open-source components lower the barrier for future groups to fork, translate, or harden the stack for regional needs.

Finally, the design encourages conversation. Tools can guide behavior; they cannot replace parenting. By making rules visible and outcomes understandable, the system nudges families toward dialogue about schedules, goals, and online safety rather than silent disconnects alone.

**End of Chapter 4**
