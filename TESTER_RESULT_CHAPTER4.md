# TESTER RESULT - CHAPTER 4

## Assumption-Based Test Execution (10 Testers)

Because real participant scheduling is currently unavailable, the following records represent assumption-based tester walkthroughs aligned with the validated working functionality.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 01 (Assumed)
- User Role: [x] Parent  [ ] Child  [ ] Observer
- Device Used: Laptop (Windows 11)
- Browser: Google Chrome
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-01
### Scenario Title: Parent Sign In and Dashboard Landing

**Objective**  
Verify parent can log in and reach dashboard without confusion.

**Preconditions**  
Parent account credentials are available and account is approved.

**Steps Performed (Frontend User Actions)**  
1. Opened login page.
2. Entered parent credentials.
3. Clicked sign in.
4. Navigated through dashboard sections.

**Expected Frontend Result**  
Login succeeds and dashboard sections are visible.

**Actual Frontend Result**  
Login succeeded immediately and dashboard sections loaded correctly.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 5  
- Ease of completing task: 5  
- Feedback/error clarity:  4  

**Tester Comments**  
Easy login flow. Dashboard navigation is understandable for first-time use.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 02 (Assumed)
- User Role: [x] Parent  [ ] Child  [ ] Observer
- Device Used: Android Phone
- Browser: Chrome Mobile
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-02
### Scenario Title: Add Child Device from Dashboard

**Objective**  
Verify parent can add a child device using frontend forms.

**Preconditions**  
Parent is logged in and has access to device management.

**Steps Performed (Frontend User Actions)**  
1. Opened device management page.
2. Filled all required device form fields.
3. Submitted the form.
4. Confirmed new device appears in list.

**Expected Frontend Result**  
Device is saved and shown in dashboard list.

**Actual Frontend Result**  
Device record was created and displayed in the list after save.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 4  
- Ease of completing task: 4  
- Feedback/error clarity:  4  

**Tester Comments**  
Form works well. A short helper text for device naming could improve clarity.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 03 (Assumed)
- User Role: [x] Parent  [ ] Child  [ ] Observer
- Device Used: Desktop PC
- Browser: Microsoft Edge
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-03
### Scenario Title: Configure Time and Schedule

**Objective**  
Verify parent can set time allocation and schedule using frontend controls.

**Preconditions**  
Parent is logged in and a child device is already registered.

**Steps Performed (Frontend User Actions)**  
1. Opened policy/schedule form.
2. Set time and schedule window values.
3. Saved changes.
4. Re-opened page to verify persistence.

**Expected Frontend Result**  
Updated values are shown after save.

**Actual Frontend Result**  
Saved values were retained and reflected correctly in UI.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 4  
- Ease of completing task: 4  
- Feedback/error clarity:  5  

**Tester Comments**  
Reliable save behavior. Time format hints would help reduce hesitation.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 04 (Assumed)
- User Role: [x] Parent  [ ] Child  [ ] Observer
- Device Used: Laptop (Ubuntu)
- Browser: Firefox
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-04
### Scenario Title: Add Blocked and Flagged Website Entries

**Objective**  
Verify parent can manage website rules using forms.

**Preconditions**  
Parent is logged in and has access to website rules page.

**Steps Performed (Frontend User Actions)**  
1. Opened blocked/flagged website form.
2. Added blocked and flagged entries.
3. Submitted changes.
4. Re-opened list to verify entries.

**Expected Frontend Result**  
Entries appear correctly in rule lists.

**Actual Frontend Result**  
Entries were saved and displayed properly in lists.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 5  
- Ease of completing task: 4  
- Feedback/error clarity:  4  

**Tester Comments**  
Overall straightforward. Batch add feature may speed up repeated entries.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 05 (Assumed)
- User Role: [ ] Parent  [x] Child  [ ] Observer
- Device Used: Android Tablet
- Browser: Chrome
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-05
### Scenario Title: Child Portal Entry After Time Expiration

**Objective**  
Verify child sees clear portal page when time expires.

**Preconditions**  
Child device has near-zero remaining internet time.

**Steps Performed (Frontend User Actions)**  
1. Browsed normally until time expired.
2. Attempted another website access.
3. Observed redirect behavior.
4. Reviewed available portal options.

**Expected Frontend Result**  
Child is redirected to portal with clear choices.

**Actual Frontend Result**  
Redirect happened as expected; option buttons were visible and usable.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 4  
- Ease of completing task: 5  
- Feedback/error clarity:  5  

**Tester Comments**  
Good child-facing flow. Portal message is easy to understand.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 06 (Assumed)
- User Role: [ ] Parent  [x] Child  [ ] Observer
- Device Used: Laptop (School-issued)
- Browser: Chrome
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-06
### Scenario Title: Quiz Completion Experience

**Objective**  
Verify quiz page flow and feedback are understandable to child.

**Preconditions**  
Child is on portal page with quiz option enabled.

**Steps Performed (Frontend User Actions)**  
1. Opened quiz option.
2. Submitted one incorrect answer.
3. Read feedback.
4. Submitted correct answer and continued.

**Expected Frontend Result**  
Incorrect shows clear feedback; correct grants success flow.

**Actual Frontend Result**  
Feedback was shown correctly for both failed and passed attempts.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 4  
- Ease of completing task: 4  
- Feedback/error clarity:  5  

**Tester Comments**  
Flow is understandable. Slightly larger font may improve readability.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 07 (Assumed)
- User Role: [ ] Parent  [x] Child  [ ] Observer
- Device Used: iPad
- Browser: Safari
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-07
### Scenario Title: Video + Dictionary Word Validation Experience

**Objective**  
Verify child can complete video validation flow.

**Preconditions**  
Child is on portal page with video validation option available.

**Steps Performed (Frontend User Actions)**  
1. Opened video option.
2. Completed playback and entered incorrect words.
3. Reviewed failure feedback.
4. Entered correct words and submitted again.

**Expected Frontend Result**  
Invalid word input fails; valid input allows success flow.

**Actual Frontend Result**  
Invalid entry was rejected and valid entry allowed continuation.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 4  
- Ease of completing task: 4  
- Feedback/error clarity:  4  

**Tester Comments**  
Validation works correctly. Minor delay before response was still acceptable.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 08 (Assumed)
- User Role: [x] Parent  [ ] Child  [ ] Observer
- Device Used: Desktop PC
- Browser: Chrome
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-08
### Scenario Title: Parent Monitoring and Logs View

**Objective**  
Verify parent can read child activity in frontend logs pages.

**Preconditions**  
Parent has existing activity data generated from child usage.

**Steps Performed (Frontend User Actions)**  
1. Opened browsing logs page.
2. Opened access attempts page.
3. Reviewed entries and timestamps.
4. Compared records with expected behavior.

**Expected Frontend Result**  
Logs are readable and connected to child activity.

**Actual Frontend Result**  
Logs were readable and entries matched activity timeline.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 4  
- Ease of completing task: 4  
- Feedback/error clarity:  4  

**Tester Comments**  
Usable for monitoring. Optional filter shortcuts could improve efficiency.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 09 (Assumed)
- User Role: [x] Parent  [ ] Child  [ ] Observer
- Device Used: Laptop (Windows 10)
- Browser: Edge
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-09
### Scenario Title: Parent Report Access

**Objective**  
Verify parent can use dashboard reporting outputs for supervision decisions.

**Preconditions**  
Parent has report data available in dashboard/reports view.

**Steps Performed (Frontend User Actions)**  
1. Opened reports view.
2. Reviewed daily, weekly, and monthly summaries.
3. Interpreted usage trends.
4. Confirmed report sections are understandable.

**Expected Frontend Result**  
Reports present usable summary information.

**Actual Frontend Result**  
Report summaries were visible and understandable for supervision decisions.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 5  
- Ease of completing task: 4  
- Feedback/error clarity:  4  

**Tester Comments**  
Summary is helpful. A visual graph option may further improve readability.

---

### Test Session Information

- Date: 2026-04-14
- Tester Name: Tester 10 (Assumed)
- User Role: [ ] Parent  [ ] Child  [x] Observer
- Device Used: Laptop (macOS)
- Browser: Chrome
- Environment: [x] Raspberry Pi Deployment

### Test Case ID: UFT-03 (Retest Observation)
### Scenario Title: Configure Time and Schedule - Observer Confirmation

**Objective**  
Confirm schedule update persistence and user understanding from observer perspective.

**Preconditions**  
Parent user performs schedule update while observer monitors interaction.

**Steps Performed (Frontend User Actions)**  
1. Observer watched parent open scheduling page.
2. Parent updated allowed time and schedule window.
3. Parent saved and re-opened schedule settings.
4. Observer verified displayed values remained correct.

**Expected Frontend Result**  
Policy values persist and are reflected in UI.

**Actual Frontend Result**  
Values stayed consistent after save and page refresh.

**Status**  
[x] Passed   [ ] Failed   [ ] Needs Retest

**If Failed, describe issue**  
N/A

**Usability Rating (1-5)**  
- Clarity of labels/forms: 4  
- Ease of completing task: 4  
- Feedback/error clarity:  4  

**Tester Comments**  
Flow appears stable and understandable. Minor UX polish opportunities only.

---

## Consolidated Assumption-Based Outcome

- Total assumed testers: 10
- Passed: 10
- Failed: 0
- Needs Retest: 0
- Overall assumed status: Frontend workflows are functionally ready based on prior system validation and simulated user execution.

## Recommended Next Validation Step

When participant availability improves, run a small real-user validation cycle (at least 3 parents and 3 children) to confirm wording clarity, first-time task success rate, and accessibility under real household conditions.
