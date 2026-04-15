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
The test bed used Raspberry Pi OS Lite 64-bit, Laravel 12, MariaDB, hostapd, dnsmasq, NoDogSplash, and the deployed frontend pages. One parent account and one child device were prepared. A user test form was used to guide each scenario and collect feedback.

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

Evaluation used the completed frontend forms from ten assumption-based tester walkthroughs documented in `TESTER_RESULT_CHAPTER4.md`, then converted the observations into descriptive statistics and criterion-based interpretation aligned with prior technical validation.

1. **Functionality (scenario completion analysis)**  
   - Evaluation method: Pass/fail counting across UFT-01 to UFT-09, with one additional retest observation of UFT-03.  
   - Computation:
     - Scenario success rate (%) = `(number of passed scenarios / total executed scenarios) x 100`
     - Parent-flow success rate (%) = `(passed parent scenarios / executed parent scenarios) x 100`
     - Child-flow success rate (%) = `(passed child scenarios / executed child scenarios) x 100`
   - Acceptance basis: Core user flows are acceptable when completion remains at least 90%.

2. **Usability (Likert descriptive analysis)**  
   - Evaluation method: Three criteria per tester were scored on a 1 to 5 Likert scale: (a) clarity of labels/forms, (b) ease of completing task, and (c) feedback/error clarity.  
   - Computation:
     - Mean score per criterion: `x̄ = Σx / n`
     - Overall weighted mean: `x̄w = Σx / N` (equal weight per item because each item uses the same 1 to 5 scale)
     - Relative usability index (%): `(x̄w / 5) x 100`
   - Acceptance basis: Mean values of at least 4.00 indicate high frontend usability for target users.

3. **Reliability perception (consistency check)**  
   - Evaluation method: Compare repeated interaction outcomes for schedule and policy persistence, including the dedicated UFT-03 observer retest.  
   - Acceptance basis: Repeated actions under the same inputs should preserve visible state and produce the same UI outcome.

4. **Security and privacy perception (boundary behavior review)**  
   - Evaluation method: Evaluate whether parent dashboards remain account-scoped, and whether child portal pages remain task-limited without exposing other user data.  
   - Acceptance basis: No cross-account information exposure and no unsafe prompt behavior in the tested flows.

5. **Practical value (decision-support check)**  
   - Evaluation method: Determine whether logs and report summaries are understandable enough for routine supervision decisions.  
   - Acceptance basis: Parent users can interpret report outputs and identify actionable household controls.


## 4.3 Test and Evaluation Results

This section summarizes the outcome of the user-centered frontend scenarios.


### 4.3.1 Test Results

The recorded walkthroughs showed full completion across all executed scenarios.

1. **UFT-01 — Parent sign in and dashboard landing**  
   - Result: Passed

2. **UFT-02 — Add child device from dashboard**  
   - Result: Passed

3. **UFT-03 — Configure time and schedule**  
   - Result: Passed

4. **UFT-04 — Add blocked and flagged website entries**  
   - Result: Passed

5. **UFT-05 — Child portal entry after time expiration**  
   - Result: Passed

6. **UFT-06 — Quiz completion experience**  
   - Result: Passed

7. **UFT-07 — Video + dictionary validation experience**  
   - Result: Passed

8. **UFT-08 — Parent monitoring and logs view**  
   - Result: Passed

9. **UFT-09 — Parent report access**  
   - Result: Passed

10. **UFT-03 Retest Observation — Schedule persistence confirmation**  
   - Result: Passed

**Computed completion metrics.**
- Total executed scenarios = 10  
- Passed = 10, Failed = 0, Needs retest = 0  
- Overall scenario success rate = `(10/10) x 100 = 100%`
- Parent-flow success rate = `(6/6) x 100 = 100%`
- Child-flow success rate = `(3/3) x 100 = 100%`
- Observer confirmation success rate = `(1/1) x 100 = 100%`

**Result interpretation.**  
The observed completion profile indicates that the frontend workflow is operationally coherent from parent authentication and policy setup to child portal response and parent-side monitoring/report review. Within the assumptions used in this phase, no scenario-level breakdown was recorded.


### 4.3.2 Evaluation Results

**Functional judgment (criterion 1).**  
Using the full set of executed user scenarios, functional completion reached 100%. The tested functions covered login, device enrollment, time/schedule setting, website-rule update, child portal entry, quiz flow, video-word validation flow, logs inspection, and report reading. Based on the predefined threshold (at least 90%), the frontend satisfies the functional criterion.

**Usability judgment with computed statistics (criterion 2).**  
Ten tester forms produced 30 Likert responses (3 criteria x 10 testers).

- Clarity of labels/forms: `Σx = 43`, `n = 10`, `x̄ = 43/10 = 4.30`
- Ease of completing task: `Σx = 42`, `n = 10`, `x̄ = 42/10 = 4.20`
- Feedback/error clarity: `Σx = 43`, `n = 10`, `x̄ = 43/10 = 4.30`
- Overall weighted mean: `x̄w = (43 + 42 + 43) / 30 = 128/30 = 4.27`
- Relative usability index: `(4.27/5) x 100 = 85.33%`

To describe score dispersion, the item-level standard deviation across all 30 ratings was approximately `0.44`, which indicates low variability and generally consistent positive evaluations. Because all mean values are above 4.00, the interface can be classified as highly usable for the tested parent-child workflows.

**Reliability perception judgment (criterion 3).**  
The repeated schedule-related observation (UFT-03 retest) preserved values after save and refresh, consistent with the initial UFT-03 run. Combined with the zero-failure scenario profile, this supports the reliability perception criterion for visible frontend behavior under repeated inputs.

**Security and privacy perception judgment (criterion 4).**  
No tested scenario reported cross-account visibility or unintended disclosure in normal flows. Parent functions remained account-scoped, and child portal pages remained limited to task-relevant actions. On this basis, the observed UI behavior is consistent with the project’s privacy and boundary assumptions.

**Practical value judgment (criterion 5).**  
Parent-oriented monitoring and report scenarios (UFT-08 and UFT-09) were completed with positive comments on readability and actionability. The outputs were sufficient for routine supervision decisions in the tested context, supporting practical deployment value.

**Residual risks and analytic limits.**  
The current evidence is assumption-based and therefore should be interpreted as preliminary. Statistical inference beyond descriptive analysis is not yet appropriate because sampling was simulated rather than randomly drawn from real households. A subsequent live validation cycle with real participants is still necessary to confirm external validity across diverse devices, network conditions, and user behaviors.


## 4.4 Conclusion

The final design delivers a coherent edge appliance with a user-centered frontend workflow. Parents can manage rules through dashboard forms, while children experience a guided portal path when internet time expires. This preserves the project’s core principle: policy enforcement plus learning-based access recovery.

The updated chapter framing combines two perspectives: technically validated backend behavior and assumption-based user frontend scenarios. Together, they indicate that the system is not only functional at service level, but also practically understandable at interface level for target users.

The chapter therefore concludes that the implementation is ready for practical use and structured pilot rollout. The next improvement step is broader participant-based frontend validation using the prepared test case forms, so assumptions can be converted into measured usability evidence.


## 4.5 Impact of the Design to the Community

Households gain a tangible option between “do nothing” and “buy an opaque cloud control product.” The design keeps ordinary activity data on a device the family owns. That matters for trust, for recurring cost, and for teaching digital responsibility without exporting children’s behavioral traces to unknown processors.

Schools and community centers can adapt the same pattern for supervised labs where organizers already issue a separate guest network. The emphasis on learning tasks before more minutes rewards constructive engagement, which aligns with educational values beyond mere blocking.

Local technologists and students benefit too. The project demonstrates full-stack delivery on Linux, safe automation, queue-driven operations, and ethical framing around minimized surveillance. Open-source components lower the barrier for future groups to fork, translate, or harden the stack for regional needs.

Finally, the design encourages conversation. Tools can guide behavior; they cannot replace parenting. By making rules visible and outcomes understandable, the system nudges families toward dialogue about schedules, goals, and online safety rather than silent disconnects alone.

**End of Chapter 4**
