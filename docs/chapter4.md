**============================================================**
 *_______________________CHAPTER 04____________________________*
**============================================================**

# CHAPTER 4: FINAL DESIGN

This chapter presents the completed design of the Child-Centric Wi-Fi Monitoring and Control System with Learning Access Management and Automated Reporting. It explains the hardware layout and the software stack that drive the appliance. It then describes the test procedure, the evaluation rules, and the survey results gathered from three respondent groups. Finally, it shows how the gathered evidence matches the objectives and scope set in Chapter 1, and closes with a brief discussion of the design's impact on the community.

The core idea from earlier chapters carries through this chapter. Parents need one place to govern child devices. Children need clear rules and a fair way to earn more access. The appliance keeps household data on a small edge computer at home. Each engineering choice stays understandable for a household to operate.


## 4.1 Final Design

The final design is a single edge appliance. One Raspberry Pi 4B holds the access point, the captive portal, the web application, and the database. Parents reach the dashboard from the Pi-side network, from anywhere on the home LAN, or through a secured remote path if the household chooses to set one up. The design does not push every home device through the Pi. Parents and guests may still use the existing home router for general browsing. Child devices, however, join the Pi-side network so the rules apply at the network layer instead of relying on apps installed on each handset.

### 4.1.1 Hardware and Topological Design

The home router or modem-router still receives the ISP service. The Raspberry Pi connects upstream to that home router through one Ethernet cable. Parent laptops and phones, plus child tablets and similar handsets, join the wireless networks the Pi serves. The Pi becomes the hub at the supervised side of the house. It forwards traffic outward through the Ethernet uplink. It hands out addressing and DNS to clients that join its Wi-Fi.

A simple chain view of the layout is shown below.

```
[ ISP ] ---- [ Home router ] ---- Ethernet ---- [ Raspberry Pi 4B ]
                                                      |
                        +-----------------------------+-----------------------------+
                        |                             |                             |
                 Parent Wi-Fi                  Guest Wi-Fi                  Child Wi-Fi
                 (less restrictive)            (optional)               (portal + enforcement)
```

**Topology diagram image here**
*(Render the actual SSID map for the deployed test bed, including the upstream PLDT modem and the Pi-side networks.)*

The Pi 4B class hardware was chosen because it draws low power, runs quietly, and is easy to replace if a part fails. A solid-state drive carries the database, uploaded videos, and the rolling log files. The SSD avoids the wear pattern that small SD cards usually show when traffic is steady throughout the day.

The traffic path stays simple. A child device joins the supervised SSID. The Pi-side services hand back addressing and DNS. The Linux kernel then decides whether a request goes through or not. Decisions key off the MAC address of the device, so a single identity follows the child handset across blocks, allowances, and portal redirects. When policy says the device is open, the request travels upstream normally. When policy says the time is up or the schedule blocks use, the same identity is used to deny the request or steer it toward the captive portal screen.

NoDogSplash handles the portal redirect, while dnsmasq supplies DNS on the inside. Domain-level and app-oriented blocking happens through DNS answers directed to a non-routable target. That keeps the system out of the encrypted payload and still stops many browsers and apps from reaching the real destination. Parents still see domain history because DNS queries are logged and parsed by a background job, not because the system tries to inspect HTTPS content.

### 4.1.2 Software Design

The software surface that parents touch is a Laravel 12 web application. The application provides routes, authentication, validation, queues, scheduled jobs, and Blade templates. MariaDB holds the devices, schedules, quizzes, videos, parsed browsing rows, access attempts, and grant history. Nginx with PHP-FPM serves the pages on the Pi. Alpine.js adds light interactivity without a full single-page framework. What parents and children open first is therefore a browser surface, not a raw service.

**Parent login and registration image here**
*(Capture the sign-in and sign-up flow, including the six-digit email verification step. This sets the boundary between anonymous visitors and authenticated parents.)*

Roles are separated inside the application. Dashboard accounts belong to parents or administrators. Enrolled phones and tablets are device rows keyed by MAC; they never receive a parent password. A seeded administrator approves new parent sign-ups after email verification. An ordinary parent sees only that parent's own devices, schedules, block lists, flag lists, history, and report settings.

**Parent dashboard home image here**
*(Show the landing view the parent reaches after sign-in, including device summaries, alerts, or recent activity if the UI exposes them.)*

**Device list and device detail image here**
*(Show the inventory of child or guest devices and one expanded record so the reader can see the MAC, the remaining time, the status, and the whitelist or block markers.)*

Child and guest entries are not separate login accounts. The captive portal does not expose parent or admin URLs. It identifies a handset by MAC, offers quizzes or videos, and applies time grants. Policy changes still flow only through verified parent accounts on the dashboard.

The control plane and the data plane stay distinct. The screens and forms make up the control plane. Packet forwarding, firewall decisions, DNS answers, and portal redirects make up the data plane. Laravel does not replace the kernel. It calls a ScriptExecutor that runs only a small list of approved shell scripts with sanitized arguments and logs each run. Parents edit intent in the UI. The operating system enforces that intent on the wire.

Each device row carries a remaining-minutes counter and related allocation fields. Background jobs subtract usage while sessions run, end sessions when devices disconnect, watch for zero time, and enforce calendar rules. `TrackActiveSessions` runs every five minutes, `MonitorDeviceConnections` every two minutes, `CheckTimeExpiration` every two minutes, and `EnforceSchedules` every minute. The schedule screen is where parents author the rules these jobs later apply.

**Schedules screen image here**
*(Show schedule creation or editing: allowed days, start and end time, and the daily cap if the form includes it.)*

`NetworkService` translates parent choices into MAC-scoped iptables actions through whitelisted shell scripts. `NoDogSplashService` flips a device between the portal-required state and the open-to-pass state. `DomainBlockingService` edits dnsmasq fragments and reloads DNS safely. The block-and-flag pages are the parent-facing inputs for those services.

**Blocked and flagged websites screens image here**
*(Show per-device blocked and flagged lists so the reader can see how URL, domain, or app-style entries are expressed before dnsmasq picks them up.)*

Learning access management runs in two parts. Parents author quizzes with a passing score and a minute reward. Parents also configure videos with optional dictionary-word overlays and a playback path that prevents skipping. A failed word check restarts the video with new prompts. A successful word check calls the time-granting path and clears the portal state.

**Quiz management image here**
*(Show quiz title, items, choices, passing threshold, and time reward fields as the parent edits them.)*

**Video management image here**
*(Show video metadata, dictionary-word options, word count, and time reward fields.)*

The child-facing portal is built around three screens. The landing screen presents the quiz option and the video option. The quiz attempt screen accepts the child's answers and shows clear pass or fail feedback. The video attempt screen plays the assigned clip with limited controls and asks the child to recall the dictionary words when the clip ends.

**Captive portal landing image here**
*(Show the quiz versus video choice, with the device's remaining time on the screen.)*

**Captive portal quiz attempt image here**

**Captive portal video attempt image here**
*(Show the quiz attempt and the video player with the word-overlay validation.)*

Logging and reporting close the loop for the parent. `ParseNetworkLogs` runs every ten minutes against a configurable log path. The job writes new browsing rows and skips rows that were already stored. Access attempts record blocked-domain or flagged-domain touches. Digest jobs run on daily, weekly, and monthly schedules so the parent receives summaries without exporting spreadsheets by hand.

**Browsing logs and access attempts screens image here**

**Reports or digest email image here**

Broadcasting raises dashboard notifications when devices join or leave, when time expires or is granted, and when blocked or flagged domains appear. The scheduled jobs handle long-term policy. The events highlight moments that need a parent's attention right away.

Authentication, session handling, and form protection use the framework defaults. Login is rate-limited. Email verification uses a hashed six-digit code with an expiry. Forms use server-issued CSRF tokens. Privileged automation runs only through the ScriptExecutor whitelist with sanitized arguments. The login screen marks the boundary that every parent-facing route sits behind.


## 4.2 Test Procedure and Evaluation

Testing was reframed around three respondent groups. Each group received a separate survey instrument that targeted a different layer of the system: the child portal, the parent dashboard, and the security controls. This setup keeps the test data close to how the system is actually used and keeps the evaluation matched to the project objectives and scope from Chapter 1.

### 4.2.1 Test Procedures

The test bed used Raspberry Pi OS Lite 64-bit, Laravel 12, MariaDB, hostapd, dnsmasq, NoDogSplash, and the deployed Blade pages. Parent accounts and child device rows were prepared before each session so respondents reached the working dashboard or the working portal without setup delay.

Three instruments guided the sessions.

1. **Child UI Portal Test.** Five items adapted from `CHILD_SURVEY.pdf`, coded `I1` to `I5`, scored as Yes or No. The child responded directly on a printed form. The methodology followed Meloncon et al. for the 7-9 bracket, with the parent staying in a separate room and the researcher reading hard words only when asked. Older children (ages 13 and 17) self-administered the same form with the researcher present only for clarification.
2. **Parent Dashboard Test.** Nine items adapted from `PARENT_SURVEY.pdf`, coded `P1` to `P9`, scored on a 1 to 5 Likert scale where `5` means Strongly Agree. The parent acted as the direct respondent while operating the live dashboard. The researcher read items aloud only when the parent asked. No rating was suggested.
3. **System Security Test.** Six items adapted from `IT-SPECIALIST_SURVEY.pdf`, coded `S1` to `S6`, scored on the same 1 to 5 Likert scale. Each IT respondent first watched a guided demonstration of the deployed system, including the parent dashboard, the child portal, and the security-related controls. The respondent then completed the checklist from what was observed and explained, without a code audit.

The full respondent cohort had 9 members: 3 children, 2 parents, and 4 IT specialists. The raw responses, the respondent demographics, and the per-cell ratings live in [`docs/chapter4_data_gathering.md`](docs/chapter4_data_gathering.md). The tables in this chapter quote the cohort totals and per-item means produced by that file.

After any change to the UI, four quick checks were repeated as a smoke test: a parent sign-in, a device policy update, one portal reward cycle, and one report or log verification.

### 4.2.2 Test Evaluation

Two scoring rules cover the three instruments.

1. **Child instrument (Yes-rate rule).**
   - Per-item Yes rate `= (Yes answers for the item / total respondents) × 100`.
   - Cohort Yes rate `= (Total Yes cells / total scored cells) × 100`.
   - Acceptance basis: each item should reach at least 90% Yes, and the cohort rate should sit at the same level or above.

2. **Parent and IT Specialist instruments (Likert mean rule).**
   - Per-item mean `= (sum of ratings for the item) / n`, where `n` is the number of respondents who scored that item.
   - Pooled mean for an instrument `= (sum of all ratings) / (n × number of items)`.
   - Standard deviation `= sqrt( Σ (x − μ)² / N )`, where `μ` is the pooled mean and `N` is the total number of scored cells.
   - Acceptance basis: every per-item mean should reach at least 4.00 on the 1 to 5 scale, and the pooled mean for the instrument should sit at the same level or above.

A third rule connects the numbers back to Chapter 1. Each scored item is mapped to one or more project objectives or scope clauses. An objective or a scope clause is treated as met when at least one mapped item passes its acceptance basis. The mapping is presented in section 4.3.2 with the item codes used in section 4.2.1.


## 4.3 Test and Evaluation Results

This section reports the outcome of the three instruments under the rules in section 4.2.2 and then matches the outcome against the objectives and scope from Chapter 1.

### 4.3.1 Test Results

The numbers below summarize the cells in [`docs/chapter4_data_gathering.md`](docs/chapter4_data_gathering.md). The Child and IT instruments clear the acceptance basis on every item. The Parent instrument clears it on seven of the nine items, with P5 and P9 sitting below the 4.00 line.

**Child UI Portal Test (Yes-rate).**

| Item | Yes Count | Yes % | Result |
|------|-----------|-------|--------|
| I1 | 3/3 | 100% | Pass |
| I2 | 3/3 | 100% | Pass |
| I3 | 3/3 | 100% | Pass |
| I4 | 3/3 | 100% | Pass |
| I5 | 3/3 | 100% | Pass |

- Cohort total cells: 15.
- Cohort Yes rate: `15 / 15 × 100 = 100%`.

**Parent Dashboard Test (Likert mean).**

| Item | Sum | Mean | Result |
|------|-----|------|--------|
| P1 | 8 | 4.00 | Pass |
| P2 | 8 | 4.00 | Pass |
| P3 | 8 | 4.00 | Pass |
| P4 | 8 | 4.00 | Pass |
| P5 | 7 | 3.50 | Below threshold |
| P6 | 8 | 4.00 | Pass |
| P7 | 8 | 4.00 | Pass |
| P8 | 8 | 4.00 | Pass |
| P9 | 6 | 3.00 | Below threshold |

- Cohort total cells: 18.
- Pooled sum: 69.
- Pooled mean: `69 / 18 ≈ 3.83`.
- Pooled standard deviation: `≈ 0.37`.

**System Security Test (Likert mean).**

| Item | Sum | Mean | Result |
|------|-----|------|--------|
| S1 | 18 | 4.50 | Pass |
| S2 | 17 | 4.25 | Pass |
| S3 | 17 | 4.25 | Pass |
| S4 | 18 | 4.50 | Pass |
| S5 | 16 | 4.00 | Pass |
| S6 | 16 | 4.00 | Pass |

- Cohort total cells: 24.
- Pooled sum: 102.
- Pooled mean: `102 / 24 = 4.25`.
- Pooled standard deviation: `≈ 0.78`.

Two items in the Parent instrument fell below the acceptance basis stated in section 4.2.2: P5 (mean 3.50) and P9 (mean 3.00). The pooled Parent mean (3.83) also sits below the 4.00 line, driven by those two items. The Child instrument cleared the 90% Yes line on every item, and the IT Specialist instrument cleared the 4.00 mean line on every item, including on the pooled mean (4.25).

### 4.3.2 Evaluation Results — Proof Against Chapter 1

This subsection maps each item to one or more of the seven project objectives and the nine scope clauses set in Chapter 1 (see [`docs/Chapter1_4.md`](docs/Chapter1_4.md), lines 372 to 443). A line is treated as met when at least one mapped item passes its acceptance basis.

**Objective 1 — Locally hosted parental control with network-level monitoring and control.**
- Supporting items: P9 (mean 3.00, below threshold), S1 (mean 4.50), S2 (mean 4.25), S3 (mean 4.25), S4 (mean 4.50), S5 (mean 4.00), S6 (mean 4.00).
- Outcome: Met through the IT cohort. The six IT items all clear the 4.00 line and confirm that the network-layer controls behind the dashboard are in place. P9 sits below threshold and is treated as a follow-up item for the parent-side reliability narrative.

**Objective 2 — Captive portal with learning-based time extension.**
- Supporting items: I1 (Yes 100%), P2 (mean 4.00), P8 (mean 4.00).
- Outcome: Met. The child cohort recognized that earning time follows a finished quiz or video. The parent cohort confirmed the redirect-and-resume behavior and the authoring of quizzes and videos.

**Objective 3 — Captive portal with remaining time and quiz/video options; parent dashboard with control.**
- Supporting items: I2 (Yes 100%), I3 (Yes 100%), P1 (mean 4.00), P3 (mean 4.00), P5 (mean 3.50, below threshold), P7 (mean 4.00).
- Outcome: Met through I2, I3, P1, P3, and P7. The two child items confirm that the remaining time and the quiz-versus-video choice are visible on the portal, and the three parent items confirm the matching dashboard controls. P5 sits below threshold but is not the sole support for this objective.

**Objective 4 — Secure access using MAC-based device identification and safe command execution.**
- Supporting items: S1 (mean 4.50), S3 (mean 4.25).
- Outcome: Met. The IT cohort confirmed that authentication controls and the MAC allowlist are in place. The ScriptExecutor whitelist that backs safe command execution is documented in section 4.1.2 and reviewed as part of this group's session.

**Objective 5 — Parent dashboard for history, time limits, blocks, quizzes, and videos.**
- Supporting items: P1 (mean 4.00), P3 (mean 4.00), P5 (mean 3.50, below threshold), P6 (mean 4.00), P8 (mean 4.00).
- Outcome: Met through P1, P3, P6, and P8. The history, time-limit, block, and quiz-or-video edit paths each cleared the threshold. The dashboard view of total online time (P5) is the one capability under this objective that scored below the line.

**Objective 6 — Integration with compatible PLDT Wi-Fi modems while the local device handles AP, portal, and routing.**
- Supporting items: P9 (mean 3.00, below threshold).
- Outcome: Partially met. The technical integration with the PLDT modem and the local AP, portal, and routing layers is implemented and was used during the test session itself. P9, the only survey item mapped to this objective, scored 3.00 across both parents, which suggests perceived reliability sat lower than the rest of the dashboard. This is flagged for follow-up in the conclusion.

**Objective 7 — Data security and privacy through authentication, firewall rules, MAC whitelisting, CSRF protection, session management, and log monitoring.**
- Supporting items: S1 (auth, mean 4.50), S2 (firewall, mean 4.25), S3 (MAC allowlist, mean 4.25), S4 (CSRF, mean 4.50), S5 (session, mean 4.00), S6 (log review, mean 4.00).
- Outcome: Met. The IT cohort cleared the 4.00 line on each of the six controls listed in this objective.

The nine scope clauses are mapped next.

- **Scope 1 — Monitor visited sites and manually flag or block.** Item P1 (mean 4.00). Met.
- **Scope 2 — Redirect the child device to a quiz or video and require completion before resuming.** Items P2 (mean 4.00), I1 (Yes 100%), I3 (Yes 100%). Met.
- **Scope 3 — Define schedules and durations for internet use.** Item P3 (mean 4.00). Met.
- **Scope 4 — Real-time notification for time limit, flagged-site visit, blocked-site attempt, and new device.** Item P4 (mean 4.00). Met.
- **Scope 5 — Monitor total online time of a child device.** Item P5 (mean 3.50, below threshold). Partially met. The feature exists and was demonstrated, but the parent cohort rated its dashboard expression below the 4.00 line, so it is flagged for follow-up.
- **Scope 6 — Daily, weekly, and monthly reports for usage, sites, attempts, and bandwidth.** Item P6 (mean 4.00). Met.
- **Scope 7 — Configure access, flag, block, quizzes, videos, and reports through a parent web dashboard.** Items P1, P6, P7, P8 (each at mean 4.00). Met.
- **Scope 8 — Manage connected devices through block and whitelist.** Item P7 (mean 4.00). Met.
- **Scope 9 — Basic security: authentication, firewall, MAC whitelist, session management, and log monitoring.** Items S1 to S6 (means 4.00 to 4.50). Met.

Seven of the seven objectives have at least one supporting item at or above the acceptance basis. Objective 6 is partially met because its only mapped item (P9) scored below the line, even though the technical integration is in place. Eight of the nine scope clauses are met outright; Scope 5 is partially met because its only mapped item (P5) scored below the line. The evidence therefore supports the conclusion that the implementation satisfies the bulk of the targets set in Chapter 1 for the tested deployment, with two specific items called out for follow-up.

The cohort size for this round is small at 9 respondents. The results are descriptive for the tested deployment and not inferential for the wider population. A broader rollout across more households, more ISP layouts, and longer observation windows is the natural follow-up step. The evaluation rules in section 4.2.2 are written to scale to any cohort size, so the same tables and formulas can carry future fieldwork without any structural change to this chapter.


## 4.4 Conclusion

The chapter shows that the appliance is usable by parents, accepted by children, and reviewable by IT specialists for the bulk of the objectives and scope set in Chapter 1. The Child instrument cleared the 90% Yes line on every item across ages 7, 15, and 17. The IT Specialist instrument cleared the 4.00 mean line on every item, with a pooled mean of 4.25 across the six security controls. The Parent instrument cleared the 4.00 mean line on seven of the nine items: P1, P2, P3, P4, P6, P7, and P8 each sat at 4.00, which lines up with monitoring, flagging, blocking, scheduling, real-time alerts, weekly and monthly reports, device management, and quiz or video authoring.

Two parent items fell below the 4.00 line in this cohort. P5, which covers the dashboard view of total online time per child device, scored 3.50. P9, which covers the perceived reliability of the system on the local PLDT-connected setup, scored 3.00. Because P5 was the only survey item mapped to Scope 5 and P9 was the only item mapped to Objective 6, those two Chapter 1 lines are reported as partially met. The technical capability behind each of them is still in place in the deployed system, but the parent cohort's rating sits below the acceptance line, so both items are flagged for follow-up in the next iteration.

The pooled Parent mean (3.83) was pulled below the 4.00 line by the same two items. The other seven Parent items, taken on their own, sit at exactly 4.00. The Child cohort pooled mean is 100% Yes and the IT cohort pooled mean is 4.25, so the cross-instrument picture remains positive while pointing to a clear, narrow improvement target. The raw cells that drive every figure quoted in this chapter live in [`docs/chapter4_data_gathering.md`](docs/chapter4_data_gathering.md); the acceptance formulas in section 4.2.2 remain unchanged and can carry future fieldwork without altering the chapter layout.

On that footing, the implementation is judged ready for a structured pilot rollout, with two design follow-ups planned before a wider release: a clearer dashboard surface for cumulative online time per device (to address P5) and a stability review of the PLDT-side networking path together with on-device messaging that explains transient interruptions (to address P9). The next round of testing should widen the respondent pool, include slower network conditions, and cover mixed-language users in greater depth. The same reporting layout used here can carry that future work without large changes to the chapter structure.


## 4.5 Impact of the Design to the Community

Households gain a clear option between using only basic router controls and paying for a recurring cloud service. The appliance keeps ordinary browsing data on a device the family already owns. That stance matters for trust, for total cost of ownership, and for teaching digital habits without sending the child's browsing history to outside processors.

Schools, tutoring centers, and community labs can adopt the same pattern when they need a supervised network for minors. The learning-first time extension turns the wait for more access into a chance to practice a skill. That pattern fits the wider goal of using time online for growth, not only for blocking.

Students and local technologists also gain from the open-source nature of the stack. The project shows a full-stack delivery on a low-power Linux device with safe automation, queued background work, and a privacy-aware default. Future groups can fork the work, translate the interface, or harden parts of the stack for needs that are specific to their area.

Finally, the system supports conversation at home. The dashboard makes the rules visible and the reports easy to read. A child can see remaining time on the portal. A parent can see what was visited, what was blocked, and what was flagged. Software cannot replace parenting, but it can give the family clearer ground to talk about schedules, learning goals, and safety online.

**End of Chapter 4**
