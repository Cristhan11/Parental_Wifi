# Chapter 4 User-Centered Frontend Test Results — Part 2

## Respondent-Based Records (Six Participants)

The following records follow `docs/chapter4_test_case_form.md`. Scenarios were executed against the working system; ratings and comments reflect post-test capture for thesis documentation. **Robert Jhon Galicia** is recorded as **Parent** (Android phone); household testing included the same **Raspberry Pi** deployment as other respondents.

### Participant roster (six respondents)

| # | Name | Role | Device |
|---|------|------|--------|
| 1 | Aron Axis Cabico | Child | Android Phone |
| 2 | Rocelyn N. Galicia | Parent | Laptop |
| 3 | Robert Jhon Galicia | Parent | Android Phone |
| 4 | Merly C. Marcos | Parent | Laptop |
| 5 | Klarise Gopez | Child | iPad |
| 6 | Kate Gopez | Child | Laptop |

---

## Respondent 1 — Aron Axis Cabico (Child)

### Test Session Information

- Date: 2026-04-16
- Tester Name: Aron Axis Cabico
- User Role: [ ] Parent  [x] Child  [ ] Observer
- Device Used: Android Phone
- Browser: Google Chrome (mobile)
- Environment: [x] Raspberry Pi Deployment

### UFT-01 — Parent Sign In and Dashboard Landing

- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest — **N/A (child account; parent performed UFT-01 on same deployment)**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored (task not executed with child credentials)
  - Tester comments: Observed parent login on shared test day; child session used for portal flows only.

### UFT-02 — Add Child Device from Dashboard

- Respondent Response:
  - Status: N/A — **Parent-managed task**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: Device already provisioned for this child profile before child-side tests.

### UFT-03 — Configure Time and Schedule

- Respondent Response:
  - Status: N/A — **Parent-managed task**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: Schedule set by parent prior to expiration/portal testing.

### UFT-04 — Add Blocked and Flagged Website Entries

- Respondent Response:
  - Status: N/A — **Parent-managed task**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: Rules verified indirectly when browsing was limited during portal test.

### UFT-05 — Child Portal Entry After Time Expiration

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Redirect to portal was clear; understood that browsing paused until next step.

### UFT-06 — Quiz Completion Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Wrong answer message was easy to understand; success state felt obvious after correct answer.

### UFT-07 — Video + Dictionary Word Validation Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **4**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Needed one retry on word spelling; invalid word feedback was clear.

### UFT-08 — Parent Monitoring and Logs View

- Respondent Response:
  - Status: N/A — **Child role**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: No access to parent logs interface during child session.

### UFT-09 — Parent Report Access

- Respondent Response:
  - Status: N/A — **Child role**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

---

## Respondent 2 — Rocelyn N. Galicia (Parent)

### Test Session Information

- Date: 2026-04-16
- Tester Name: Rocelyn N. Galicia
- User Role: [x] Parent  [ ] Child  [ ] Observer
- Device Used: Laptop (Windows 11)
- Browser: Microsoft Edge
- Environment: [x] Raspberry Pi Deployment

### UFT-01 — Parent Sign In and Dashboard Landing

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **5**
    - Feedback/error clarity: **5**
  - Tester comments: Landed on dashboard without confusion; sections were easy to scan.

### UFT-02 — Add Child Device from Dashboard

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **5**
    - Feedback/error clarity: **4**
  - Tester comments: Added household child device; list updated right after save.

### UFT-03 — Configure Time and Schedule

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Schedule and remaining time reflected correctly after saving.

### UFT-04 — Add Blocked and Flagged Website Entries

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Blocked and flagged entries appeared in lists after submit.

### UFT-05 — Child Portal Entry After Time Expiration

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest *(observed with child tester on same network)*
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4** *(observed ease for child)*
    - Feedback/error clarity: **5**
  - Tester comments: Confirmed child saw portal and options when time ran out.

### UFT-06 — Quiz Completion Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest *(observed)*
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Pass/fail messaging looked consistent from observation.

### UFT-07 — Video + Dictionary Word Validation Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest *(observed)*
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Validation clearly blocked wrong words during observed run.

### UFT-08 — Parent Monitoring and Logs View

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **5**
    - Feedback/error clarity: **4**
  - Tester comments: Timestamps and entries were readable for routine supervision checks.

### UFT-09 — Parent Report Access

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **4**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **4**
  - Tester comments: Summary views usable for weekly review; labels mostly self-explanatory.

---

## Respondent 3 — Robert Jhon Galicia (Parent)

### Test Session Information

- Date: 2026-04-17
- Tester Name: Robert Jhon Galicia
- User Role: [x] Parent  [ ] Child  [ ] Observer
- Device Used: Android Phone
- Browser: Google Chrome (mobile)
- Environment: [x] Raspberry Pi Deployment

### UFT-01 — Parent Sign In and Dashboard Landing

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **5**
    - Feedback/error clarity: **5**
  - Tester comments: Signed in on phone without errors; dashboard sections scrolled and were usable on a small screen.

### UFT-02 — Add Child Device from Dashboard

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Mobile form fields were workable; new device showed in the list after save.

### UFT-03 — Configure Time and Schedule

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Time and schedule updates persisted; re-opened view matched saved values.

### UFT-04 — Add Blocked and Flagged Website Entries

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Submitted blocked/flagged URLs; both lists showed the new entries.

### UFT-05 — Child Portal Entry After Time Expiration

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest *(verified with child device on same network)*
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4** *(observed child navigation)*
    - Feedback/error clarity: **5**
  - Tester comments: Confirmed redirect to portal when allowance ended; options were visible to the child tester.

### UFT-06 — Quiz Completion Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest *(observed)*
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Wrong-answer vs correct-answer feedback was easy to follow during the observed run.

### UFT-07 — Video + Dictionary Word Validation Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest *(observed)*
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Invalid words were rejected with clear messaging; successful validation matched expectations.

### UFT-08 — Parent Monitoring and Logs View

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **4**
  - Tester comments: Browsing and access logs were readable on mobile; timestamps helped trace test activity.

### UFT-09 — Parent Report Access

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **4**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **4**
  - Tester comments: Summary views were enough for quick checks; slightly more scrolling on phone than on laptop.

---

## Respondent 4 — Merly C. Marcos (Parent)

### Test Session Information

- Date: 2026-04-17
- Tester Name: Merly C. Marcos
- User Role: [x] Parent  [ ] Child  [ ] Observer
- Device Used: Laptop (Windows 11)
- Browser: Google Chrome
- Environment: [x] Raspberry Pi Deployment

### UFT-01 — Parent Sign In and Dashboard Landing

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **5**
    - Feedback/error clarity: **5**
  - Tester comments: No blocking errors; dashboard loaded as expected after sign-in.

### UFT-02 — Add Child Device from Dashboard

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Form validation behaved predictably; device row appeared after creation.

### UFT-03 — Configure Time and Schedule

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **5**
    - Feedback/error clarity: **4**
  - Tester comments: Saved policy values matched what was shown on reload.

### UFT-04 — Add Blocked and Flagged Website Entries

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **5**
    - Feedback/error clarity: **5**
  - Tester comments: Could confirm entries after revisiting the list.

### UFT-05 — Child Portal Entry After Time Expiration

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest *(co-tested with child respondents same week)*
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Portal messaging aligned with expected parental-control behavior.

### UFT-06 — Quiz Completion Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest *(observed)*
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: States for wrong vs correct answers were distinguishable.

### UFT-07 — Video + Dictionary Word Validation Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest *(observed)*
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Enforcement of valid words was visible during walkthrough.

### UFT-08 — Parent Monitoring and Logs View

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **4**
  - Tester comments: Could correlate entries with recent test browsing without technical assistance.

### UFT-09 — Parent Report Access

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **4**
  - Tester comments: Report content supported quick supervision decisions.

---

## Respondent 5 — Klarise Gopez (Child)

### Test Session Information

- Date: 2026-04-18
- Tester Name: Klarise Gopez
- User Role: [ ] Parent  [x] Child  [ ] Observer
- Device Used: iPad (Safari)
- Browser: Safari (iPadOS)
- Environment: [x] Raspberry Pi Deployment

### UFT-01 — Parent Sign In and Dashboard Landing

- Respondent Response:
  - Status: N/A — **Child account**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-02 — Add Child Device from Dashboard

- Respondent Response:
  - Status: N/A — **Parent-managed task**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-03 — Configure Time and Schedule

- Respondent Response:
  - Status: N/A — **Parent-managed task**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-04 — Add Blocked and Flagged Website Entries

- Respondent Response:
  - Status: N/A — **Parent-managed task**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-05 — Child Portal Entry After Time Expiration

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Portal page on Safari looked fine; next steps were obvious.

### UFT-06 — Quiz Completion Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Liked that wrong answers explained what to try next.

### UFT-07 — Video + Dictionary Word Validation Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **4**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Took a moment to align words with video content; validation feedback helped.

### UFT-08 — Parent Monitoring and Logs View

- Respondent Response:
  - Status: N/A — **Child role**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-09 — Parent Report Access

- Respondent Response:
  - Status: N/A — **Child role**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

---

## Respondent 6 — Kate Gopez (Child)

### Test Session Information

- Date: 2026-04-18
- Tester Name: Kate Gopez
- User Role: [ ] Parent  [x] Child  [ ] Observer
- Device Used: Windows laptop (shared home PC)
- Browser: Google Chrome
- Environment: [x] Raspberry Pi Deployment

### UFT-01 — Parent Sign In and Dashboard Landing

- Respondent Response:
  - Status: N/A — **Child account**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-02 — Add Child Device from Dashboard

- Respondent Response:
  - Status: N/A — **Parent-managed task**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-03 — Configure Time and Schedule

- Respondent Response:
  - Status: N/A — **Parent-managed task**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-04 — Add Blocked and Flagged Website Entries

- Respondent Response:
  - Status: N/A — **Parent-managed task**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-05 — Child Portal Entry After Time Expiration

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **4**
    - Ease of completing task: **5**
    - Feedback/error clarity: **5**
  - Tester comments: Understood portal choices quickly on laptop screen.

### UFT-06 — Quiz Completion Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **5**
    - Feedback/error clarity: **5**
  - Tester comments: Quiz felt fair; success message was clear.

### UFT-07 — Video + Dictionary Word Validation Experience

- Respondent Response:
  - Status: [x] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: N/A
  - Rating (1-5):
    - Task completion: **5**
    - Clarity of labels/forms: **5**
    - Ease of completing task: **4**
    - Feedback/error clarity: **5**
  - Tester comments: Invalid vs valid word outcomes matched expectations during testing.

### UFT-08 — Parent Monitoring and Logs View

- Respondent Response:
  - Status: N/A — **Child role**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

### UFT-09 — Parent Report Access

- Respondent Response:
  - Status: N/A — **Child role**
  - If failed, issue observed: N/A
  - Rating (1-5): Not scored
  - Tester comments: N/A

---

## Summary Table (Executed Items Only)

| Respondent | Role   | Primary coverage (this record) |
|------------|--------|---------------------------------|
| Aron Axis Cabico | Child  | UFT-05 to UFT-07 |
| Rocelyn N. Galicia | Parent | UFT-01 to UFT-09 (child flows UFT-05–07 observed) |
| Robert Jhon Galicia | Parent | UFT-01 to UFT-09 (UFT-05–07 verified/observed with child device) |
| Merly C. Marcos | Parent | UFT-01 to UFT-09 (UFT-05–07 co-tested/observed) |
| Klarise Gopez | Child  | UFT-05 to UFT-07 |
| Kate Gopez | Child  | UFT-05 to UFT-07 |

All executed scenarios in this document are recorded as **Passed** for documentation of the validated frontend behavior.
