# Simplify Project Plan (User-Friendly Adjustments)

## Goal
Make the system easier for non-technical parents and very young children (around 6 years old and below) by reducing technical steps, improving defaults, and speeding up response time.

---

## Guiding Principles
- Remove technical terms from parent and child flows.
- Prefer one-click actions and clear labels.
- Use safe defaults so setup works without advanced networking knowledge.
- Keep Raspberry Pi workload lightweight (event-driven, minimal polling).
- Use offline-first design: all required UI assets and core services must run locally on Raspberry Pi.

---

## Offline-First Resource Policy (Raspberry Pi Local-Only)
- [ ] Host all frontend resources locally on Raspberry Pi:
  - CSS frameworks
  - JavaScript libraries
  - icon sets
  - fonts (or safe system fallback)
- [ ] Do not depend on CDN links for critical pages (parent dashboard, child portal, login, device request pages).
- [ ] Bundle and serve all app images/icons from local storage (`public` assets).
- [ ] Ensure all critical user flows work without internet:
  - login/logout
  - account recovery email trigger (queued locally and sent when available)
  - device request/approval
  - quiz assignment and child quiz flow
  - policy apply and time grants
  - report generation and local viewing
- [ ] Add graceful fallback for external services:
  - if internet is down, continue local operations
  - retry email/report send in background when connection returns
- [ ] Add deployment checklist item: verify no critical route loads remote assets.

---

## Priority Todo List

## 1) Simplify Initial Setup and First Account
### Objective
Replace the current default admin concept with a parent-first setup.

### Plan
- [ ] Keep one seeded `Parent Owner` account as the default main account (no first-account creation).
- [ ] On first login, require:
  - Step 1: Set and verify parent email address.
  - Step 2: Force password change.
  - Step 3: Set home profile (timezone, optional language, child-friendly defaults).
  - Step 4: Confirm system is ready.
- [ ] Hide the word `Admin` in the UI for non-technical users and replace with `Parent Owner`.
- [ ] Keep role permissions in code (`admin`) but map display text to parent-friendly naming.
- [ ] Prevent multiple owner accounts unless explicitly transferred by current owner.
- [ ] Auto-subscribe the verified parent email as default digest recipient (daily/weekly/monthly reports).
- [ ] Ensure onboarding/login/reset pages use only locally hosted assets (no external CDN dependency).

### Recommendations / Alternatives
- Recommended (selected): Keep a seeded `Parent Owner` account (no first-account creation), require email verification at first login, use the same verified email for password reset recovery, and auto-register that email as the default recipient for daily/weekly/monthly digest reports.


### Correction Notes
- Use consistent wording: `Parent Owner` (main account), `Parent Member` (optional secondary parent).
- Add a recovery flow (security question or backup recovery code) to avoid lockout.

---

## 2) Simplify New Device Registration (No MAC Input by Parent)
### Objective
Parents should register devices without typing MAC addresses.

### Plan
- [ ] Child/new device portal screen:
  - One button: `Request to Register`
  - One input field: `Device Name` (example: "Miguel Tablet")
- [ ] On click, system automatically captures device fingerprint data (MAC, hostname, IP metadata) in backend.
- [ ] Create `Pending Device Requests` list in parent `Accounts` page.
- [ ] Parent actions for each request:
  - `Assign Device Role` (required before approval):
    - `Child Device`
    - `Parent Account`
    - `Guest Account`
  - `Approve` (enabled only after role is assigned)
  - `Reject`
- [ ] If selected role is `Parent Account` or `Guest Account`, automatically add device to whitelist upon approval.
- [ ] Remove MAC address manual input from standard parent UI; keep it only in advanced/debug view.
- [ ] Keep request queue + parent approval as the default registration flow for non-technical users.
- [ ] Add anti-spam guard: rate-limit repeated registration requests from the same device.
- [ ] Show quick verification badge `Seen on Home Wi-Fi` on pending requests to improve parent trust.
- [ ] Ensure device request and pending-approval pages/icons/scripts are served from local Pi assets only.

### Recommendations / Alternatives
- Recommended: Request queue with parent approval (best for non-technical users).
- Add anti-spam guard: rate-limit repeated requests from same device.
- Add quick verification badge like `Seen on Home Wi-Fi` to help parent trust the request.


### Correction Notes
- Fix flow naming:
  - Old: `Accounts > New > Select connected device > MAC field`
  - New: `Device Portal > Request to Register` then `Parent Accounts > Pending Requests > Approve`

---

## 3) Make Quiz Assignment Easier with Built-In Question Bank
### Objective
Parents should assign quizzes quickly without manually creating all questions.

### Plan
- [ ] Add a seeded `Question Bank` with default questions at installation.
- [ ] Add categories/levels:
  - `Elementary`
  - `High School`
  - `Senior High School`
- [ ] After selecting category/level, parent must select subject:
  - `Math`
  - `English`
  - `Science`
- [ ] Parent can assign a prepared quiz set in a few clicks after category + subject selection.
- [ ] Add `Quiz Instance` model:
  - pulls random questions from selected category pool
  - configurable item count (example: 5, 10, 15)
  - supports scoring mode selection:
    - `Time Reward Mode (Random Quiz)`: no pass-score requirement; each correct answer grants configurable minutes to the child account
- [ ] Keep optional custom question creation, but move it to secondary/advanced tab.
- [ ] Add Excel import for question bank so parent can bulk-add/edit questions using a spreadsheet.
- [ ] Provide downloadable `Quiz Question Import Template (.xlsx)` directly in parent UI.
- [ ] Add Excel export of question bank so parent can edit questions offline and re-import updates.
- [ ] Support two import modes:
  - `Add New` (insert new questions)
  - `Update Existing` (match by `Question ID` from exported file)
- [ ] Show beginner-friendly import validation summary (row number + plain-language error).
- [ ] Ensure quiz import/export pages and template download are fully functional offline using local assets/content.
- [ ] Ensure quiz pages (parent and child) are fully functional offline using local assets/content.

### Recommendations / Alternatives
- Recommended: Hybrid model (prebuilt bank + optional custom questions).
- Alternative A (selected): Curriculum packs with age band tags and only these subjects: `Math`, `English`, `Science`.
- Add beginner-friendly spreadsheet workflow:
  - parent downloads template
  - fills questions in Excel
  - imports file
  - exports anytime for bulk edits
- Add content quality guard:
  - review and tag questions as age-appropriate
  - avoid ambiguous wording for young children

### Excel Import/Export Format (Parent-Friendly)
- [ ] Use one sheet named: `Questions`
- [ ] Keep only these simple columns in order:
  - `Question ID` (optional for new rows; required for update mode)
  - `Level` (`Elementary`, `High School`, `Senior High School`)
  - `Subject` (`Math`, `English`, `Science`)
  - `Question Text`
  - `Option A`
  - `Option B`
  - `Option C`
  - `Option D`
  - `Correct Option` (`A`, `B`, `C`, or `D`)
  - `Explanation` (optional, short)
  - `Status` (`Active` or `Inactive`)
- [ ] Add data-validation dropdowns in template for:
  - `Level`
  - `Subject`
  - `Correct Option`
  - `Status`
- [ ] Include one sample row in template as guide and keep header labels human-readable.
- [ ] Reject unsupported files with clear message:
  - Allowed: `.xlsx` only
  - Max file size: define practical limit (example: 5 MB)
- [ ] On export, keep the same format so parent can round-trip edit (export -> edit -> import) without technical steps.

### Correction Notes
- Keep separation clear:
  - `Question Bank` (single source of questions)
  - `Quiz Instance` (runtime random selection for a child/device/session)

---

## 4) Immediate Action After Registration and Time Grants (Reduce 2-Min Delay)
### Objective
System changes should apply immediately after key events while staying lightweight on Raspberry Pi.

### Plan
- [ ] Trigger policy update directly after these events:
  - new user registration
  - device approval/assignment
  - child time grant/extension
- [ ] Replace heavy periodic waiting with event-driven commands:
  - dispatch targeted jobs immediately
  - run minimal scripts only for affected device/profile
- [ ] Add a fast-path sync service:
  - refresh allow/block rules
  - update captive portal/session state
  - verify apply result and return status to UI
- [ ] Add debounced batch apply (3-5 seconds) to group quick successive updates and reduce repeated script execution load.
- [ ] Keep scheduled jobs as fallback/self-healing, not primary UX path.
- [ ] Ensure status/loading UI resources for apply flow are local so feedback works even without internet.

### Recommendations / Alternatives (Raspberry Pi Friendly)
- Recommended:
  - event-driven queue with small, scoped jobs
  - use Redis or lightweight queue backend already used by project
  - avoid full rule rebuild each time; do incremental updates
- Add user feedback:
  - show `Applying changes...` then `Applied` or `Retry`
  - set target response under 3-5 seconds for visible actions

### Correction Notes
- Do not remove cron/scheduler completely; keep it for recovery and consistency checks.
- Ensure script execution remains whitelisted and secure.
- Avoid externally hosted fonts/icons/scripts on critical pages; keep local copies on Raspberry Pi.

---

## Suggested UX Copy Improvements
- Replace `MAC Address` with `Device ID (auto-detected)`.
- Replace `Admin` with `Parent Owner`.
- Replace technical alerts with plain language:
  - `New device wants to join`
  - `Time added successfully`
  - `Access is paused until quiz is completed`

---

## Implementation Phases
### Copy-Paste Agent Task (Phase 1 - Core UX + Accounts + Devices)
- [ ] Copy and use this exact prompt in agent:
  - ```text
    Implement Phase 1 of Simplify_project.md in this Laravel project.

    Scope:
    1) Seeded Parent Owner account flow (no first-account creation)
       - Keep one seeded owner account with admin privileges.
       - On first login: require email set + email verification + forced password change.
       - Use verified email for password reset.
       - Auto-subscribe verified email to digest report recipients.
       - UI wording should display "Parent Owner" instead of "Admin" for non-technical screens.

    2) Device registration request queue (no MAC input in parent UI)
       - Child/new device page: button "Request to Register" + field "Device Name".
       - Capture device metadata in backend automatically (MAC/hostname/IP/source).
       - Add Pending Device Requests list in Accounts page.
       - Parent must assign role BEFORE approve:
         - Child Device
         - Parent Account
         - Guest Account
       - Approve is disabled until role assignment is complete.
       - If role = Parent Account or Guest Account, auto-whitelist on approval.
       - Keep MAC input only in advanced/debug area.
       - Add anti-spam rate limit for repeated requests from same device.
       - Add badge "Seen on Home Wi-Fi" when request is detected from local home network.

    3) Offline-first requirement for all Phase 1 pages
       - No CDN dependency on critical pages (login, onboarding, accounts, device request pages).
       - Serve icons/fonts/css/js locally from Raspberry Pi.

    Deliverables:
    - Database changes (migrations/models if needed)
    - Backend logic (controllers/services/events/jobs)
    - UI updates (Blade/Inertia/Vue depending on project)
    - Validation + authorization updates
    - Basic tests for core flow (feature tests preferred)
    - Short implementation summary with file list changed
    ```

### Copy-Paste Agent Task (Phase 2 - Quiz Bank + Assignment Flow)
- [ ] Copy and use this exact prompt in agent:
  - ```text
    Implement Phase 2 of Simplify_project.md in this Laravel project.

    Scope:
    1) Seeded Question Bank
       - Add prebuilt questions during setup/seed.
       - Categories/levels: Elementary, High School, Senior High School.
       - Subjects must be only: Math, English, Science.
       - Use public curriculum standards as the truth source for coverage/alignment:
         - DepEd learning competencies / curriculum guides (PH-aligned)
         - Government or school-published reviewer materials that are publicly reusable
       - Questions must be generated/curated internally based on those sources (no direct copyrighted copy-paste).
       - Draft and include a starter seeded bank now with this initial distribution:
         - Elementary: 150 items total (Math/English/Science split evenly when possible)
         - High School: 100 items total (Math/English/Science split evenly when possible)
         - Senior High School: 60 items total (Math/English/Science split evenly when possible)
       - Ensure each seeded item has level, subject, clear question text, 4 choices, correct answer, and status.

    2) Parent quiz assignment flow
       - Parent first selects category/level, then selects subject.
       - Parent assigns prepared quiz quickly from filtered bank.

    3) Quiz Instance behavior
       - Randomly pull questions from selected category + subject pool.
       - Configurable item count (e.g., 5/10/15).
       - Support scoring mode:
         - Time Reward Mode (Random Quiz): no pass score; each correct answer grants configurable minutes to child account.

    4) Question bank Excel import/export (parent-friendly)
       - Add Excel import so parent can bulk-add and bulk-edit quiz questions.
       - Add downloadable template: "Quiz Question Import Template (.xlsx)" in parent UI.
       - Add Excel export of question bank for offline editing and re-import.
       - Support import modes:
         - Add New (insert rows as new questions)
         - Update Existing (match by Question ID from exported file)
       - Show beginner-friendly validation summary (row number + plain-language error).
       - Reject unsupported files:
         - Allowed extension: .xlsx only
         - Max size: 5 MB (or project-configurable value)
       - Keep round-trip consistency: export format must match import format exactly.

    5) Excel template format requirements
       - Use one sheet named: Questions
       - Required column order:
         1. Question ID (optional for Add New, required for Update Existing)
         2. Level (Elementary, High School, Senior High School)
         3. Subject (Math, English, Science)
         4. Question Text
         5. Option A
         6. Option B
         7. Option C
         8. Option D
         9. Correct Option (A/B/C/D)
         10. Explanation (optional)
         11. Status (Active/Inactive)
       - Add dropdown validation in template for Level, Subject, Correct Option, Status.
       - Include one sample row and human-readable header labels for non-technical parents.

    6) Offline-first requirement for quiz pages
       - Parent and child quiz pages, template download, import/export screens, and related UI assets must work locally on Raspberry Pi (no critical CDN dependency).

    Deliverables:
    - Schema/model changes for question bank + quiz instances (and import/export tracking if needed)
    - Seeder updates for default question bank (including 150/100/60 starter distribution)
    - Backend selection/randomization logic
    - Parent UI flow updates (category -> subject -> assign)
    - Backend import/export services and validation rules for Excel workflow
    - Template generator or stored template asset with required dropdowns/format
    - Source-mapping note that documents how seeded items map to DepEd/public curriculum competencies
    - Tests for random selection, time reward computation, import validation, and export/import round-trip
    - Short implementation summary with file list changed
    ```

### Copy-Paste Agent Task (Phase 3 - Fast Apply + Lightweight Performance)
- [ ] Copy and use this exact prompt in agent:
  - ```text
    Implement Phase 3 of Simplify_project.md in this Laravel project.

    Scope:
    1) Immediate policy apply on key events
       - Trigger apply after: user registration, device approval/assignment, child time grant/extension.
       - Use event-driven targeted updates (affected device/profile only).

    2) Debounced batch apply
       - Group rapid consecutive updates within 3-5 seconds.
       - Avoid repeated heavy script executions.

    3) Fast-path sync service
       - Refresh allow/block rules
       - Update captive portal/session state
       - Return apply status to UI ("Applying changes...", "Applied", "Retry")

    4) Keep scheduler as fallback
       - Scheduled jobs remain for recovery/self-healing, not as primary UX path.

    5) Offline-first + Raspberry Pi constraints
       - Critical status UI must use local assets.
       - Minimize CPU, memory, and disk writes.
       - Avoid full rule rebuild when incremental update is possible.

    Deliverables:
    - Event/job/service updates
    - Script execution optimization and safeguards
    - UI feedback updates for apply status
    - Performance checks (response time and resource usage notes)
    - Tests for key event triggers and debounce behavior
    - Short implementation summary with file list changed
    ```

---

## Acceptance Criteria
- Parent completes initial setup in under 5 minutes without networking terms.
- Parent never types MAC address in normal flow; registration uses request queue + role assignment before approval.
- Parent/Guest role approvals auto-whitelist devices.
- Parent assigns quiz in under 1 minute using category -> subject (`Math`, `English`, `Science`) -> assign flow.
- In random quiz time-reward mode, each correct answer grants configured minutes (no pass-score requirement).
- Policy updates from registration/device approval/time grants become visible/effective within a few seconds.
- Critical pages remain usable when internet is slow or unavailable because required assets are served locally from Raspberry Pi.

---

## Notes for Next Technical Breakdown
- Convert this plan into:
  - database schema updates
  - backend service/event changes
  - UI wireflow adjustments
  - performance test checklist for Raspberry Pi
