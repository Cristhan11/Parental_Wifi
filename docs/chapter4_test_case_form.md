# CHAPTER 4 TEST CASE FORM

## User-Centered Frontend Test Form

Use this form to record parent and child user scenario tests for the frontend pages.

---

### Test Session Information

- Date: __________________________
- Tester Name: __________________________
- User Role: [ ] Parent  [ ] Child  [ ] Observer
- Device Used: __________________________
- Browser: __________________________
- Environment: [ ] Raspberry Pi Deployment

### UFT-01 — Parent Sign In and Dashboard Landing
- Objective: Verify parent can log in and reach dashboard without confusion.
- Steps:
  1. Open login page.
  2. Enter parent credentials.
  3. Click sign in and open core dashboard sections.
- Expected Result: Login succeeds and dashboard sections are visible.
- Pass Criteria: No blocking error page; parent reaches working dashboard.
- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: ___________________________________________
  - Rating (1-5):
    - Task completion: 1 2 3 4 5
    - Clarity of labels/forms: 1 2 3 4 5
    - Ease of completing task: 1 2 3 4 5
    - Feedback/error clarity: 1 2 3 4 5
  - Tester comments: _____________________________________________________

### UFT-02 — Add Child Device from Dashboard
- Objective: Verify parent can add a child device using frontend forms.
- Steps:
  1. Open device management page.
  2. Fill device form fields.
  3. Save and verify device appears in list.
- Expected Result: Device is saved and shown in dashboard list.
- Pass Criteria: Form validates correctly and record is created.
- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: ___________________________________________
  - Rating (1-5):
    - Task completion: 1 2 3 4 5
    - Clarity of labels/forms: 1 2 3 4 5
    - Ease of completing task: 1 2 3 4 5
    - Feedback/error clarity: 1 2 3 4 5
  - Tester comments: _____________________________________________________

### UFT-03 — Configure Time and Schedule
- Objective: Verify parent can set time allocation and schedule using frontend controls.
- Steps:
  1. Open device policy/schedule form.
  2. Set remaining time and schedule window.
  3. Save changes.
- Expected Result: Updated values are shown after save.
- Pass Criteria: Policy values persist and are reflected in UI.
- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: ___________________________________________
  - Rating (1-5):
    - Task completion: 1 2 3 4 5
    - Clarity of labels/forms: 1 2 3 4 5
    - Ease of completing task: 1 2 3 4 5
    - Feedback/error clarity: 1 2 3 4 5
  - Tester comments: _____________________________________________________

### UFT-04 — Add Blocked and Flagged Website Entries
- Objective: Verify parent can manage website rules using forms.
- Steps:
  1. Open blocked/flagged website form.
  2. Add entries and submit.
  3. Re-open list to verify entries.
- Expected Result: Entries appear correctly in rule lists.
- Pass Criteria: Form submission succeeds and rules are visible.
- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: ___________________________________________
  - Rating (1-5):
    - Task completion: 1 2 3 4 5
    - Clarity of labels/forms: 1 2 3 4 5
    - Ease of completing task: 1 2 3 4 5
    - Feedback/error clarity: 1 2 3 4 5
  - Tester comments: _____________________________________________________

### UFT-05 — Child Portal Entry After Time Expiration
- Objective: Verify child sees clear portal page when time expires.
- Steps:
  1. Use device with near-zero remaining time.
  2. Attempt browsing after expiration.
  3. Observe redirected portal page.
- Expected Result: Child is redirected to portal with clear choices.
- Pass Criteria: Portal appears and child can see next-step options.
- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: ___________________________________________
  - Rating (1-5):
    - Task completion: 1 2 3 4 5
    - Clarity of labels/forms: 1 2 3 4 5
    - Ease of completing task: 1 2 3 4 5
    - Feedback/error clarity: 1 2 3 4 5
  - Tester comments: _____________________________________________________

### UFT-06 — Quiz Completion Experience
- Objective: Verify quiz page flow and feedback are understandable to child.
- Steps:
  1. Open portal quiz option.
  2. Submit one incorrect attempt.
  3. Submit a correct attempt.
- Expected Result: Incorrect shows clear feedback; correct grants success flow.
- Pass Criteria: Pass/fail states are clear and consistent.
- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: ___________________________________________
  - Rating (1-5):
    - Task completion: 1 2 3 4 5
    - Clarity of labels/forms: 1 2 3 4 5
    - Ease of completing task: 1 2 3 4 5
    - Feedback/error clarity: 1 2 3 4 5
  - Tester comments: _____________________________________________________

### UFT-07 — Video + Dictionary Word Validation Experience
- Objective: Verify child can complete video validation flow.
- Steps:
  1. Open portal video option.
  2. Complete playback and submit words.
  3. Observe failed and successful validation behaviors.
- Expected Result: Invalid word input fails; valid input allows success flow.
- Pass Criteria: Validation behavior is clear and enforced.
- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: ___________________________________________
  - Rating (1-5):
    - Task completion: 1 2 3 4 5
    - Clarity of labels/forms: 1 2 3 4 5
    - Ease of completing task: 1 2 3 4 5
    - Feedback/error clarity: 1 2 3 4 5
  - Tester comments: _____________________________________________________

### UFT-08 — Parent Monitoring and Logs View
- Objective: Verify parent can read child activity in frontend logs pages.
- Steps:
  1. Open browsing logs page.
  2. Open access attempts page.
  3. Check timestamps and entries.
- Expected Result: Logs are readable and connected to child activity.
- Pass Criteria: Parent can interpret entries without technical help.
- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: ___________________________________________
  - Rating (1-5):
    - Task completion: 1 2 3 4 5
    - Clarity of labels/forms: 1 2 3 4 5
    - Ease of completing task: 1 2 3 4 5
    - Feedback/error clarity: 1 2 3 4 5
  - Tester comments: _____________________________________________________

### UFT-09 — Parent Report Access
- Objective: Verify parent can use dashboard reporting outputs for supervision decisions.
- Steps:
  1. Open reports or digest-related views.
  2. Review daily/weekly/monthly summaries.
  3. Check if report content is understandable.
- Expected Result: Reports present usable summary information.
- Pass Criteria: Parent can identify key usage patterns from output.
- Respondent Response:
  - Status: [ ] Passed   [ ] Failed   [ ] Needs Retest
  - If failed, issue observed: ___________________________________________
  - Rating (1-5):
    - Task completion: 1 2 3 4 5
    - Clarity of labels/forms: 1 2 3 4 5
    - Ease of completing task: 1 2 3 4 5
    - Feedback/error clarity: 1 2 3 4 5
  - Tester comments: _____________________________________________________

---
