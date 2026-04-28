Final Defense — PowerPoint Script (Version 2 — Template Slide Order)

Primary focus: Project outline (Slide 2) and approved attributes (Slide 3) set the boundary; Slides 4–5 pair the five study objectives with Chapter IV findings; Slides 6–8 close with Chapter V summary, conclusion, and recommendations.

Audience: Panel + non-specialists  
Target length: 10 minutes or less  
How to use: Copy each On-slide (PPT) block into PowerPoint. Paste Speaker notes into the Notes pane. Slide numbers below match your deck (Slide 1 = title).

---

Slide-to-topic map (your flow)

| Slide | On-screen title (use your template) | What goes in the numbered placeholders |
|------:|---------------------------------------|------------------------------------------|
| 1 | Title slide | Project title, names, date |
| 2 | PROJECT OUTLINE | Five lines: scope/delimitation-style project outline |
| 3 | APPROVED ATTRIBUTES | Four lines: high-level approved system qualities |
| 4 | Objective of the Study / Statement of the Problem — Chapter IV: Presentation, Analysis, and Interpretation of Findings | Five lines: five specific objectives |
| 5 | Chapter IV: Presentation, Analysis, and Interpretation of Findings (Please present your answers to each objective here) | Five lines: answer Objective 1 → 5 in order |
| 6 | Summary | Chapter V summary |
| 7 | Conclusion | Chapter V conclusion |
| 8 | Recommendation | Chapter V recommendations |

---

Timing cheat sheet (~10 minutes)

| Slide | Topic | ~Time |
|------:|--------|------:|
| 1 | Title + one-line purpose | 40 s |
| 2 | Project outline (5 lines) | 1 min |
| 3 | Approved attributes (4 lines) | 45 s |
| 4 | Objectives + Chapter IV framing (5 lines) | 1 min |
| 5 | Chapter IV: answers per objective (5 lines) | 2 min 30 s |
| 6 | Summary | 1 min |
| 7 | Conclusion | 50 s |
| 8 | Recommendation | 50 s |

If over time: Shorten Slide 5 by stating only the pass rate and one usability headline, then say “details are in Chapter IV.”

---

Slide 1 — Title

On-slide (PPT)

- Title: Child-Centric Wi-Fi Monitoring and Control System with Learning Access Management and Automated Reporting (or your official title)
- Subtitle: Edge-deployed parental Wi-Fi control — Raspberry Pi + Laravel
- Names / program / institution / date (fill in)

Speaker notes

Open with one sentence: the system is a home-oriented gateway plus web app that implements the approved outline and attributes you show on Slides 2 and 3.

---

Slide 2 — PROJECT OUTLINE

On-slide (PPT)

Slide title (center): PROJECT OUTLINE

Left subheading: Project Outline

Numbered list (exactly five lines — copy each line next to 1–5 in your template):

1. Supervised child Wi-Fi on a Raspberry Pi gateway: DNS-level visibility of visited domains; parents manually flag or block sites; per-device blocking and whitelisting (MAC-aware lists).
2. Captive portal learning path: redirect assigned devices to a parent-selected quiz or educational video; access continues only after pass or completion rules are met.
3. Parent-defined schedules and duration for internet use on assigned devices; system tracks total online time and enforces limits using background jobs and network services.
4. Real-time notices to the parent device when flagged sites are visited and blocked sites are attempted, plus daily, weekly, and monthly summaries (usage, sites, flags, block attempts, bandwidth).
5. Web-based parental dashboard to configure devices, rules, portal content, and reporting preferences; basic security including authentication, firewall rules, MAC whitelisting, session management, whitelisted privileged scripts, and regular log review.

Speaker notes

Delimitation (say aloud if the panel asks “limits”): child traffic must use the supervised segment; insight is domain-oriented (not decrypted HTTPS pages); reliable operation needs scheduler, queues, log paths, and broadcasting if using live dashboard alerts; user tests used one prototype and six respondents (descriptive, not population-wide).

---

Slide 3 — APPROVED ATTRIBUTES

On-slide (PPT)

Slide title (center): APPROVED ATTRIBUTES

Left subheading: Approved Attributes

Numbered list (exactly four lines):

1. Edge-integrated enforcement: policy is applied on the child network path (dnsmasq, firewall, NoDogSplash) so assigned devices cannot bypass house rules without leaving the supervised Wi-Fi.
2. Learning-first recovery: quiz and video flows with validation and rewards are authored by parents in the dashboard; portal behavior matches documented portal controllers and Pi services.
3. Observable governance: browsing-related history, access attempts, time and schedule state, and digests are produced from scheduled jobs and stored data parents can read in the app.
4. Defensible operations: authenticated, account-scoped dashboard; CSRF-aware forms; hashed passwords; ScriptExecutor-only automation; security and privacy checks showed no cross-account leakage in the tested flows.

Speaker notes

These four attributes are the “quality stamp” behind the outline: they explain why the five objectives on Slide 4 are technically and ethically credible.

If the panel asks what dnsmasq, firewall, and NoDogSplash do (only on Approved Attribute 1): dnsmasq is the Pi’s DNS helper—it answers name lookups for child devices, can send blocked domains to a dummy address so sites do not open, and writes query logs the app turns into “visited site” history. The firewall (iptables-style rules on the Pi) is the traffic gate—it allows or drops packets by device and policy so schedules and blocks are enforced at the network layer, not only in the browser. NoDogSplash is the captive portal—it catches normal browsing on the child Wi-Fi and redirects the user to our Laravel portal pages until the child finishes the quiz or video (or policy says they may pass). Together: DNS sees and filters names, the firewall enforces who may pass data, the portal catches the user for the learning step.

---

Slide 4 — Objective of the Study / Statement of the Problem — Chapter IV: Presentation, Analysis, and Interpretation of Findings

On-slide (PPT)

Slide title (use your long header line from the template):

Objective of the Study/ Statement of the Problem Chapter IV Presentation, Analysis, and Interpretation of Findings

Numbered list (five specific objectives — each will be answered on Slide 5):

1. To implement network-level monitoring with manual flagging and blocking of selected websites for assigned child devices, including block and whitelist management.
2. To redirect assigned child devices to a captive portal quiz or educational video that must be passed or completed before internet use continues or time is extended.
3. To allow parents to define schedules and session duration for assigned devices and to record total online time with automated enforcement.
4. To notify parents in real time when limits, flags, blocks, or new-device events occur, and to generate daily, weekly, and monthly usage reports including bandwidth and access-attempt summaries.
5. To provide a secured web dashboard for configuration and review, supported by basic security measures (authentication, firewall posture, MAC whitelisting, sessions, and log monitoring).

Speaker notes

Slide 4 states what the study sought to prove. Slide 5 is the evidence and interpretation tied line-by-line to these five objectives.

---

Slide 5 — Chapter IV: Presentation, Analysis, and Interpretation of Findings (answers to each objective)

On-slide (PPT)

Slide title (use your template, including the red guidance line if your school requires it):

Chapter IV Presentation, Analysis, and Interpretation of Findings

(Please present your answers to each objective here)

Numbered list (answer 1 → 5 matching Slide 4):

1. Presentation: dnsmasq query logging feeds ParseNetworkLogs into browsing history and access-attempt records; dashboard forms sync blocked and flagged domains to the Pi. Analysis: parent testers completed add-device and block/flag tasks (UFT-02, UFT-04) without failures. Interpretation: Objective 1 is met for domain-level control on the supervised segment.
2. Presentation: NoDogSplash steers clients to the Laravel portal; quiz and video modules grant time after pass or word validation. Analysis: child testers completed portal entry, quiz, and video tasks (UFT-05–UFT-07) with no failed instances. Interpretation: Objective 2 is met for a understandable learning gate.
3. Presentation: schedule UI maps to EnforceSchedules and related services; TrackActiveSessions, MonitorDeviceConnections, and CheckTimeExpiration jobs keep limits near real time. Analysis: schedule configuration tasks passed for all parent runs (UFT-03). Interpretation: Objective 3 is met for the tested deployment.
4. Presentation: the parent dashboard can show quick on-screen notices as things happen; parents may also turn on email for urgent items; the system also builds daily, weekly, and monthly summary sheets from saved activity. Analysis: every parent run of the “read logs” and “open reports” tasks finished successfully (UFT-08, UFT-09). Interpretation: Objective 4 is met—parents could see alerts in the test setup and could read both live-style views and period summaries.
5. Presentation: Laravel authentication, role separation, and guarded routes; Pi-side firewall and MAC concepts; ScriptExecutor for whitelisted scripts. Analysis: all 36 executed scenario instances across UFT-01–UFT-09 passed (100%, above the 90% acceptance rule); Likert means for task, clarity, ease, and feedback were all above 4.00 with weighted composite about 4.60 (about 92% relative index); sensitivity checks in Chapter IV stayed above thresholds. Interpretation: Objective 5 is supported—dashboard and security posture held for tested users; results are descriptive because the cohort is small and tied to one topology.

Speaker notes

If pressed on “analysis,” cite: 36 of 36 passes; weighted usability about 4.60; limits = single prototype, six respondents, DNS-level visibility only.

---

Slide 6 — Summary

On-slide (PPT)

Slide title: Summary

Suggested bullets (short lines for PPT):

- The capstone delivers a local Raspberry Pi–class appliance story: network-level parental control, learning-based time extension, and automated reporting with data kept at home by default.
- It fills the gap between weak router menus and subscription-heavy cloud parental tools using an understandable stack (Laravel, MariaDB, open-source edge components).
- The technopreneurship angle positions kits, documentation, and honest marketing (no false deep-packet claims; clear HTTPS/DNS limits).
- Value is framed around one-time hardware cost, optional support, and integrator-friendly deployment—not mandatory cloud lock-in.

Speaker notes

Pull language from Chapter V (executive summary and products/services): privacy, TCO, Philippine context where you use it, and channel ideas (demos, guides, partners).

---

Slide 7 — Conclusion

On-slide (PPT)

Slide title: Conclusion

Suggested bullets:

- The implemented system matches the approved outline and attributes: edge enforcement, learning portal, observable governance, and defensible operations.
- The business model canvas logic holds: integrated edge workflow, direct and partner channels, revenue from hardware margin and services rather than forced software licenses.
- Intellectual property strategy is conservative: copyright on original work, trademark clearance before scale, no assumed patents, trade secrets for runbooks and automation whitelists.
- Chapter IV user tests and Chapter V positioning together support readiness for structured pilot rollout, not unlimited market claims.

Speaker notes

Tie back to Slides 2–3: “What we promised in the outline is what we tested and what the venture narrative can honestly sell.”

---

Slide 8 — Recommendation

On-slide (PPT)

Slide title: Recommendation

Numbered list (same plain style as “write it down, label it, know who to call”—as if a non-technical parent or barangay adviser suggested them). If the slide feels crowded, show any five and keep the rest as backup bullets.

1. Hand every family a one-page “day one” sheet: what the lights mean, the three safe things to try first when the internet feels slow, and one clear number or group chat to reach for help.
2. Put simple sticky labels on cords and ports (“internet in,” “do not unplug”) so housemates do not accidentally loosen the wrong cable during cleaning or homework panic.
3. Book a short “rules night” with parents before go-live—walk through bedtimes, quizzes, and what the child will see—so nobody is surprised the first school night.
4. Call or message the family about a week after setup; that is usually when small confusions appear, and a five-minute chat fixes more than a long manual.
5. Write down clear setup steps for whoever installs the box, plus a two-page “when the Wi-Fi acts up” guide (restart order, who to call, what not to touch) so families are not left guessing at night.
6. Record very short screen clips for the tasks parents repeat—add a child gadget, turn weekend rules on or off, read the weekly summary—so busy parents can copy what they see instead of reading jargon.
7. Keep on-screen alerts in everyday words (“time is up,” “blocked try,” “new phone joined”) and trim long paragraphs on reports so a tired parent still knows what to do next.

Speaker notes

Voice: practical household advice, not engineering. If time is short, read items 1, 4, 5, and 6 only. Optional closing: brief demo and “Thank you — questions.”

---

Optional backup — Q&A only if needed

On-slide (PPT)

Keep a hidden slide mapping scope items 6–9 to the five outline lines if a panel member asks for the full nine-line checklist from your proposal document.

Speaker notes

Do not read backup slides unless asked.

---

End — rehearse with a timer; Slides 2–3 are the visual contract; Slides 4–5 must stay aligned one-to-five.
